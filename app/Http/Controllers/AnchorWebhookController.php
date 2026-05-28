<?php

namespace App\Http\Controllers;

use App\Enums\KycStatus;
use App\Enums\OnboardingStatus;
use App\Models\Booking;
use App\Models\Onboarding;
use App\Services\AnchorService;
use App\Services\Sms\EBulkSmsAlertService;
use App\Mail\BookingConfirmed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AnchorWebhookController extends Controller
{
    public function __construct(private AnchorService $anchor) {}

    /**
     * POST /webhooks/anchor
     */
    public function handle(Request $request): Response
    {
        Log::info('Anchor webhook headers', ['headers' => $request->headers->all()]);
        Log::info('Anchor webhook body', ['body' => $request->getContent()]);

        // ── 1. Verify signature ───────────────────────────────────────────
        $signature = $request->header('x-anchor-signature');
        $rawBody   = $request->getContent();
         // ── 1. Verify signature (skip in local/sandbox) ───────────────────────
        if (config('app.env') === 'production') {
            $signature = $request->header('x-anchor-signature');
            $rawBody   = $request->getContent();

            if (! $signature || ! $this->anchor->verifyWebhookSignature($rawBody, $signature)) {
                Log::warning('Invalid Anchor webhook signature', ['ip' => $request->ip()]);
                return response('Unauthorized', 401);
            }
        } else {
            Log::info('Anchor webhook signature check skipped in ' . config('app.env'));
        }

        // ── 2. Parse event ────────────────────────────────────────────────
        $payload  = $request->json()->all();

        // Anchor sends either top-level `type` or nested under `data.type`
        $type     = $payload['data']['type'] ?? $payload['type'] ?? null;
        $included = $payload['included'] ?? [];

        Log::info("Anchor webhook received: {$type}");

        // ── 3. Route ──────────────────────────────────────────────────────
        match ($type) {
            'payin.received'                            => $this->handlePayin($payload, $included),
            'customer.identification.approved'          => $this->handleKycApproved($payload),
            'customer.identification.rejected'          => $this->handleKycRejected($payload),
            'customer.identification.awaitingDocument'  => $this->handleAwaitingDocument($payload),
            'customer.identification.error'             => $this->handleKycError($payload),
            'transfer.successful'                       => $this->handleTransferSuccessful($payload),
            'transfer.failed'                           => $this->handleTransferFailed($payload),
            default => Log::info("Unhandled Anchor event: {$type}"),
        };

        return response('OK', 200);
    }

    // ─── KYC / KYB Events ────────────────────────────────────────────────────

    private function handleKycApproved(array $payload): void
    {
        $anchorCustomerId = $this->extractCustomerId($payload);

        if (! $anchorCustomerId) {
            Log::error('KYC approved: missing customer ID', $payload);
            return;
        }

        $onboarding = Onboarding::where('anchor_customer_id', $anchorCustomerId)->first();

        if (! $onboarding) {
            Log::warning("KYC approved: no onboarding found for {$anchorCustomerId}");
            return;
        }

        $developer = $onboarding->developer;

        try {
           DB::transaction(function () use ($onboarding, $developer, $anchorCustomerId) {

                $onboarding->update([
                    'kyc_status' => KycStatus::Approved,
                    'kyc_tier'   => 'TIER_2',
                ]);

                $developer->update(['onboarding_status' => OnboardingStatus::KycApproved]);

                // ── Step 1: Create deposit account ───────────────────────────────
                $depositAccount   = $this->anchor->createDepositAccount($anchorCustomerId);
                $depositAccountId = $depositAccount['id'];

                Log::info('Deposit account created', [
                    'developer_id'      => $developer->id,
                    'deposit_account_id'=> $depositAccountId,
                ]);

                // ── Step 2: Fetch the NUBAN ───────────────────────────────────────
                // Give Anchor a moment to generate the account number
                sleep(2);
                $accountNumber = $this->anchor->getAccountNumber($depositAccountId);

                $nuban    = $accountNumber['attributes']['accountNumber']  ?? null;
                $bankName = $accountNumber['attributes']['bank']['name']   ?? null;

                Log::info('NUBAN fetched', ['nuban' => $nuban, 'bank' => $bankName]);

                // ── Step 3: Create counterparty for NIP payout ───────────────────
                $counterparty = $this->anchor->createCounterparty([
                    'account_name'   => $onboarding->settlement_account_name,
                    'account_number' => $onboarding->settlement_account_number,
                    'nip_code'       => $onboarding->settlement_bank_nip_code,
                ]);

                // ── Step 4: Save everything ───────────────────────────────────────
                $onboarding->update([
                    'anchor_account_id'          => $depositAccountId,
                    'anchor_reserved_account_id' => $depositAccountId, // same thing now
                    'anchor_nuban'               => $nuban,
                    'anchor_bank_name'           => $bankName,
                    'anchor_counterparty_id'     => $counterparty['id'],
                ]);

                $developer->update(['onboarding_status' => OnboardingStatus::Complete]);
            });
            Log::info("Onboarding complete for developer {$developer->id}, NUBAN: {$onboarding->fresh()->anchor_nuban}");

        } catch (\Exception $e) {
            Log::error("Failed to complete onboarding after KYC approval", [
                'developer_id'       => $developer->id,
                'anchor_customer_id' => $anchorCustomerId,
                'error'              => $e->getMessage(),
            ]);
        }
    }

    private function handleKycRejected(array $payload): void
    {
        $anchorCustomerId = $this->extractCustomerId($payload);
        $onboarding       = Onboarding::where('anchor_customer_id', $anchorCustomerId)->first();

        if (! $onboarding) return;

        $onboarding->update(['kyc_status' => KycStatus::Rejected]);
        $onboarding->developer->update(['onboarding_status' => OnboardingStatus::Incomplete]);

        Log::warning("KYC rejected for developer {$onboarding->developer_id}");

        // Notify developer by email
        try {
            Mail::raw(
                "Your identity verification was rejected. Please log in and resubmit your details.",
                fn ($m) => $m->to($onboarding->developer->email)
                             ->subject('Action Required — Verification Rejected')
            );
        } catch (\Exception $e) {
            Log::error('Failed to send KYC rejection email', ['error' => $e->getMessage()]);
        }
    }

    private function handleAwaitingDocument(array $payload): void
    {
        $anchorCustomerId = $this->extractCustomerId($payload);
        $onboarding       = Onboarding::where('anchor_customer_id', $anchorCustomerId)->first();

        if (! $onboarding) return;

        $requiredDocs = $payload['data']['attributes']['requiredDocuments'] ?? [];

        $onboarding->update([
            'kyc_status'              => KycStatus::AwaitingDocument,
            'kyc_documents_required'  => $requiredDocs,
        ]);

        Log::info("KYB awaiting document for developer {$onboarding->developer_id}", [
            'required_docs' => $requiredDocs,
        ]);
    }

    private function handleKycError(array $payload): void
    {
        $anchorCustomerId = $this->extractCustomerId($payload);
        $onboarding       = Onboarding::where('anchor_customer_id', $anchorCustomerId)->first();

        if ($onboarding) {
            $onboarding->update(['kyc_status' => KycStatus::Error]);
        }

        Log::error("KYC error from Anchor", ['payload' => $payload]);
    }

    // ─── Payment Events ───────────────────────────────────────────────────────

    /**
     * payin.received — payment landed on a developer's NUBAN.
     * 1. Match to a booking via amount + developer
     * 2. Mark booking as paid
     * 3. Auto NIP-transfer payout to developer's settlement account
     * 4. Send confirmation email + SMS
     */
    private function handlePayin(array $payload, array $included): void
    {
        $payIn = collect($included)->firstWhere('type', 'PayIn');
    
        if (! $payIn) {
            Log::error('payin.received: no PayIn in included', $payload);
            return;
        }
    
        $amountKobo      = $payIn['attributes']['amount'];
        $sessionId       = $payIn['attributes']['sessionId'] ?? null;
        $payinReference  = $payIn['attributes']['reference'];  // this is YOUR booking reference from metadata
        $anchorCustomerId = $payIn['relationships']['customer']['data']['id'] ?? null;
    
        // ── Match booking by virtual account reference (exact match) ──────────
        $booking = Booking::where('anchor_va_reference', $payinReference)
            ->orWhere('reference', $payinReference)       // fallback: direct reference match
            ->first();
    
        if (! $booking) {
            Log::warning('payin.received: no booking matched', [
                'payin_reference' => $payinReference,
                'amount_kobo'     => $amountKobo,
            ]);
            return;
        }
    
        if ($booking->status === 'paid') {
            Log::info("Booking {$booking->reference} already paid — skipping.");
            return;
        }
    
        // Sanity check: amount must match exactly
        $expectedKobo = (int) $booking->amount * 100;
        if ($amountKobo !== $expectedKobo) {
            Log::warning("Amount mismatch for booking {$booking->reference}", [
                'expected' => $expectedKobo,
                'received' => $amountKobo,
            ]);
            // Still mark paid but log the discrepancy — partial payment handling is up to you
        }
    
        $onboarding = $booking->developer->onboarding;
        $developer =$booking->developer;
    
        try {
            DB::transaction(function () use ($booking,$dveloper, $onboarding, $amountKobo, $payinReference, $sessionId) {
                $booking->update([
                    'status'            => 'paid',
                    'anchor_payin_ref'  => $payinReference,
                    'anchor_session_id' => $sessionId,
                    'paid_at'           => now(),
                ]);
                $cost = ($developer->platform_fee_percent / 100) * $booking->amount;
                // Create payment record
                Payment::create([
                    'booking_id' => $booking->id,
                    'developer_id' => $booking->developer_id,
                    'amount' => $booking->amount,
                    'platform_fee' =>  $cost,
                    'developer_amount' => $booking->amount - $cost,
                    'currency' =>'NGN',
                    'channel' => 'Transfer-Anchor',
                    'status' => 'success',
                    'paid_at' => now(),
                ]);
                Log::info("Booking {$booking->reference} marked paid via Anchor Pay with Transfer");
    
                $this->initiateAutoPayout($onboarding, $booking->developer, $amountKobo, $payinReference);
            });
    
            $this->sendConfirmation($booking->fresh(['developer']));
    
        } catch (\Exception $e) {
            Log::error("Failed to process payin for {$booking->reference}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
 

    /**
     * Auto NIP-transfer developer payout after deducting platform + NIP fees.
     */
    private function initiateAutoPayout(
        Onboarding $onboarding,
        $developer,
        int $amountKobo,
        string $payinReference
    ): void {
        if (! $onboarding->anchor_counterparty_id || ! $onboarding->anchor_reserved_account_id) {
            Log::error("Cannot payout: missing counterparty or account ID", [
                'developer_id' => $developer->id,
            ]);
            return;
        }

        $split = $this->anchor->calculatePayout($amountKobo, $developer->platform_fee_percent);

        if ($split['developer_payout_kobo'] <= 0) {
            Log::warning("Payout amount is zero or negative — skipping", [
                'developer_id' => $developer->id,
                'split'        => $split,
            ]);
            return;
        }

        try {
            $transfer = $this->anchor->initiateNipTransfer(
                sourceAccountId: $onboarding->anchor_reserved_account_id,
                counterpartyId:  $onboarding->anchor_counterparty_id,
                amountKobo:      $split['developer_payout_kobo'],
                reference:       'PAYOUT-' . $payinReference . '-' . time(),
                narration:       "BookInStack payout — {$developer->business_name}"
            );

            Log::info("Auto NIP payout initiated for developer {$developer->id}", [
                'payout_kobo'    => $split['developer_payout_kobo'],
                'platform_fee'   => $split['platform_fee_kobo'],
                'nip_fee'        => $split['nip_fee_kobo'],
                'transfer_id'    => $transfer['id'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error("Auto NIP payout failed for developer {$developer->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleTransferSuccessful(array $payload): void
    {
        $reference = $payload['data']['attributes']['reference'] ?? null;
        Log::info("Anchor transfer successful: {$reference}");
    }

    private function handleTransferFailed(array $payload): void
    {
        $reference = $payload['data']['attributes']['reference'] ?? null;
        Log::warning("Anchor transfer failed: {$reference}", $payload);
        // TODO: retry logic or alert your team
    }

    // ─── Notifications ────────────────────────────────────────────────────────

    private function sendConfirmation(Booking $booking): void
    {
        // Email
        try {
            Mail::to($booking->customer_email)
                ->send(new BookingConfirmed($booking, $booking->developer));
        } catch (\Exception $e) {
            Log::error("Confirmation email failed for {$booking->reference}", [
                'error' => $e->getMessage(),
            ]);
        }

        // SMS to developer's registered number
        $phone = $booking->developer->sms_number ?? null;

        if ($phone) {
            try {
                $developer = $booking->developer;

                app(EBulkSmsAlertService::class)->sendCreditAlert($phone, [
                    'business_name'  => $developer->business_name ?? $developer->name,
                    'account_number' => $onboarding->anchor_nuban ?? '0000000000',
                    'amount'         => $booking->amount,
                    'description'    => "Booking payment — {$booking->reference}",
                    'balance'        => null,
                    'charge'         => $booking->platform_fee ?? 0,
                    'reference'      => $booking->reference,
                ]);

            } catch (\Exception $e) {
                Log::error("SMS alert failed for {$booking->reference}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function extractCustomerId(array $payload): ?string
    {
        return $payload['data']['relationships']['customer']['data']['id']
            ?? $payload['relationships']['customer']['data']['id']
            ?? null;
    }
}
