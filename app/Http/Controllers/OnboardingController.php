<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;
use App\Enums\KycStatus;
use App\Enums\OnboardingStatus;
use App\Models\Developer;
use App\Models\Onboarding;
use App\Services\AnchorService;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private $onboardingProvider;
    public function __construct(private AnchorService $anchor) {
        $this->onboardingProvider = config('services.onboarding_provider');
    }
    private function effectiveDeveloper(Request $request): Developer
    {
        $developer = $request->user();
        // Staff see their admin's data
        return $developer->isStaff() ? $developer->owner : $developer;
    }
    // ─── Step 0: Landing — choose type ───────────────────────────────────────

    public function index(Request $request): View|RedirectResponse
    {
        $developer = $this->effectiveDeveloper($request);
        // Already fully onboarded — go to dashboard
        if ($developer->onboarding_status === OnboardingStatus::Complete) {
            return redirect()->route('dashboard');
        }

        // Has an onboarding record in progress — resume
        if ($developer->onboarding) {
            return $this->resume($developer);
        }

        return view('onboarding.start');
    }

    // ─── Step 1: Choose individual or business ────────────────────────────────

    public function chooseType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_type' => ['required', \Illuminate\Validation\Rule::enum(CustomerType::class)],
        ]);

        $developer = $this->effectiveDeveloper($request);

        // Create or update onboarding record
        $developer->onboarding()->updateOrCreate(
            ['developer_id' => $developer->id],
            ['customer_type' => $data['customer_type']]
        );
        return $data['customer_type'] === CustomerType::Individual->value
            ? redirect()->route('onboarding.individual')
            : redirect()->route('onboarding.business');
    }

    // ─── Step 2a: Individual KYC form ────────────────────────────────────────

    public function individualForm(Request $request): View|RedirectResponse
    {
        $developer  = $this->effectiveDeveloper($request);
        $onboarding = $developer->onboarding;

        if (! $onboarding || $onboarding->customer_type !== CustomerType::Individual) {
            return redirect()->route('onboarding.index');
        }

        // Already submitted — show pending screen
        // if ($onboarding && ! in_array($onboarding->kyc_status, [KycStatus::Approved, KycStatus::Submitted])) {
        //     if($this->onboardingProvider === 'paystack'){
        //        $verified = $this->handlePaystackKycVerification(
        //             $developer,
        //             $onboarding
        //         );
        //         if($verified === true){
        //               return redirect()
        //                      ->route('dashboard')
        //                      ->with('success', 'Verification completed successfully.');
        //         }
        //     }
        //     return redirect()->route('onboarding.pending');
        // }
         try{
            $provider = $this->onboardingProvider;
            if($provider  == 'paystack'){
               $banks =  app(PaystackService::class)->listBanks();
            }else{
                $banks = $this->anchor->listBanks();
            }
        }catch(\Exception $e){
            Log::info('Error while fetching banks from  services');
        }
        return view('onboarding.individual', compact('developer', 'onboarding','banks','provider'));
    }

    public function submitIndividual(Request $request)
    {
        $developer  = $this->effectiveDeveloper($request);
        $onboarding = $developer->onboarding;
        $data = $request->validate([
            'first_name'   => 'required|string|max:80',
            'last_name'    => 'required|string|max:80',
            'bvn'          => ['required', 'digits:11'],
            'date_of_birth'=> 'required|date|before:-18 years',
            'gender'       => 'required|in:Male,Female',
            'phone_number' => ['required', 'regex:/^(0[7-9][01]\d{8})$/'],
            'address_line1'=> 'required|string|max:200',
            'city'         => 'required|string|max:100',
            'state'        => 'required|string|max:100',
            'bank_code'      => 'nullable|string',
            // Settlement bank (where we NIP-transfer payouts)
            'settlement_bank_nip_code'    => 'nullable|string',
            'settlement_account_number'   => 'required|digits:10',
        ]);
        if($this->onboardingProvider == 'paystack'){
           $verified = $this->handlePaystackKycVerification(
                $developer,
                $onboarding,
                $data
            );
            if($verified === true){
                return redirect()
                        ->route('dashboard')
                        ->with('success', 'Verification completed successfully.');
            }else{
                return redirect()
                    ->route('onboarding.pending')
                    ->with('error', 'Verification is still processing. Please try again later.');
            }
        }else{
                try {
                    // ── 1. Create Anchor individual customer ──────────────────────
                    $customer = $this->anchor->createIndividualCustomer([
                        'first_name'   => $data['first_name'],
                        'last_name'    => $data['last_name'],
                        'email'        => $developer->email,
                        'phone_number' => '234' . substr($data['phone_number'], 1),
                        'address_line1'=> $data['address_line1'],
                        'city'         => $data['city'],
                        'state'        => $data['state'],
                        'developer_id' => $developer->id,
                    ]);

                    $anchorCustomerId = $customer['id'];

                // ── 2. Verify settlement account ──────────────────────────────
                $verified = $this->anchor->verifyAccount(
                    $data['settlement_bank_nip_code'],
                    $data['settlement_account_number']
                );

                // ── 3. Update onboarding record ───────────────────────────────
                $onboarding->update([
                    'anchor_customer_id'         => $anchorCustomerId,
                    'kyc_status'                 => KycStatus::Submitted,
                    'bvn'                        => $data['bvn'],
                    'date_of_birth'              => $data['date_of_birth'],
                    'gender'                     => $data['gender'],
                    'phone_number'               => $data['phone_number'],
                    'address_line1'              => $data['address_line1'],
                    'city'                       => $data['city'],
                    'state'                      => $data['state'],
                    'settlement_bank_nip_code'   => $data['settlement_bank_nip_code'] ?? null,
                    'settlement_account_number'  => $data['settlement_account_number'] ?? null,
                    'settlement_account_name'    => $verified['attributes']['accountName'] ?? null,
                    'meta'                       => ['anchor_customer' => $customer],
                ]);

                // ── 4. Trigger TIER_1 KYC ─────────────────────────────────────
                $this->anchor->triggerIndividualKyc($anchorCustomerId, [
                    'bvn'           => $data['bvn'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender'        => $data['gender'],
                ]);

                $developer->update(['onboarding_status' => OnboardingStatus::KycSubmitted]);

                return redirect()->route('onboarding.pending')
                    ->with('success', 'KYC submitted! We\'re verifying your identity.');

            } catch (\Exception $e) {

                Log::error('Individual KYC submission failed', [
                    'developer_id' => $developer->id,
                    'error'        => $e->getMessage(),
                ]);

                return back()->withInput()
                    ->with('error', 'Verification failed. Please check your details and try again.');
            }
        }

    }

    // ─── Step 2b: Business KYB form ──────────────────────────────────────────

    public function businessForm(Request $request): View|RedirectResponse
    {
        $developer  = $this->effectiveDeveloper($request);
        $onboarding = $developer->onboarding;

        if (! $onboarding || $onboarding->customer_type !== CustomerType::Business) {
            return redirect()->route('onboarding.index');
        }

        if ($onboarding->kyc_status !== KycStatus::Pending) {
            return redirect()->route('onboarding.pending');
        }
        try{
            $banks = $this->anchor->listBanks();
        }catch(Exception $e){
            Log::info('Error while fetching banks from Ancor services');
        }

        return view('onboarding.business', compact('developer', 'onboarding','banks'));
    }

    public function submitBusiness(Request $request): RedirectResponse
    {
        $developer  = $this->effectiveDeveloper($request);
        $onboarding = $developer->onboarding;

        $data = $request->validate([
            // Business details
            'business_name'        => 'required|string|max:200',
            'rc_number'            => 'required|string|max:20',
            'registration_type'    => 'required|in:Private_Incorporated,Public_Incorporated,Business_Name,Incorporated_Trustee',
            'date_of_registration' => 'required|date|before:today',
            'industry' => [
                'required',
                \Illuminate\Validation\Rule::in([
                    // Agriculture
                    'Agriculture-AgriculturalCooperatives',
                    'Agriculture-AgriculturalServices',
                    // Commerce
                    'Commerce-Automobiles',
                    'Commerce-DigitalGoods',
                    'Commerce-PhysicalGoods',
                    'Commerce-RealEstate',
                    'Commerce-DigitalServices',
                    'Commerce-LegalServices',
                    'Commerce-PhysicalServices',
                    'Commerce-ProfessionalServices',
                    'Commerce-OtherProfessionalServices',
                    // Education
                    'Education-NurserySchools',
                    'Education-PrimarySchools',
                    'Education-SecondarySchools',
                    'Education-TertiaryInstitutions',
                    'Education-VocationalTraining',
                    'Education-VirtualLearning',
                    'Education-OtherEducationalServices',
                    // Gaming
                    'Gaming-Betting',
                    'Gaming-Lotteries',
                    'Gaming-PredictionServices',
                    // Financial Services
                    'FinancialServices-FinancialCooperatives',
                    'FinancialServices-CorporateServices',
                    'FinancialServices-PaymentSolutionServiceProviders',
                    'FinancialServices-Insurance',
                    'FinancialServices-Investments',
                    'FinancialServices-AgriculturalInvestments',
                    'FinancialServices-Lending',
                    'FinancialServices-BillPayments',
                    'FinancialServices-Payroll',
                    'FinancialServices-Remittances',
                    'FinancialServices-Savings',
                    'FinancialServices-MobileWallets',
                    // Health
                    'Health-Gyms',
                    'Health-Hospitals',
                    'Health-Pharmacies',
                    'Health-HerbalMedicine',
                    'Health-Telemedicine',
                    'Health-MedicalLaboratories',
                    // Hospitality
                    'Hospitality-Hotels',
                    'Hospitality-Restaurants',
                    // Nonprofits
                    'Nonprofits-ProfessionalAssociations',
                    'Nonprofits-GovernmentAgencies',
                    'Nonprofits-NGOs',
                    'Nonprofits-PoliticalParties',
                    'Nonprofits-ReligiousOrganizations',
                    'Nonprofits-Leisure_And_Entertainment',
                    'Nonprofits-Cinemas',
                    'Nonprofits-Nightclubs',
                    'Nonprofits-Events',
                    'Nonprofits-Press_And_Media',
                    'Nonprofits-RecreationCentres',
                    'Nonprofits-StreamingServices',
                    // Logistics
                    'Logistics-CourierServices',
                    'Logistics-FreightServices',
                    // Travel
                    'Travel-Airlines',
                    'Travel-Ridesharing',
                    'Travel-TourServices',
                    'Travel-Transportation',
                    'Travel-TravelAgencies',
                    // Utilities
                    'Utilities-CableTelevision',
                    'Utilities-Electricity',
                    'Utilities-GarbageDisposal',
                    'Utilities-Internet',
                    'Utilities-Telecoms',
                    'Utilities-Water',
                    // Other
                    'Retail',
                    'Wholesale',
                    'Restaurants',
                    'Construction',
                    'Unions',
                    'RealEstate',
                    'FreelanceProfessional',
                    'OtherProfessionalServices',
                    'OnlineRetailer',
                    'OtherEducationServices',
                ]),
            ],
            'website'              => 'nullable|url',
            'phone_number'         => ['required', 'regex:/^(0[7-9][01]\d{8})$/'],
            'address_line1'        => 'required|string|max:200',
            'city'                 => 'required|string|max:100',
            'state'                => 'required|string|max:100',
            // Director / officer
            'director_first_name'  => 'required|string|max:80',
            'director_last_name'   => 'required|string|max:80',
            'director_bvn'         => 'required|digits:11',
            'director_dob'         => 'required|date|before:-18 years',
            'director_id_type'     => 'required|in:DRIVERS_LICENSE,NIN_SLIP,INTL_PASSPORT',
            'director_id_number'   => 'required|string|max:50',
            // Settlement
            'settlement_bank_nip_code'   => 'required|string',
            'settlement_account_number'  => 'required|digits:10',
            // Documents (we email these)
            'cac_document'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'memart_document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'utility_bill'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            Log::info('creating business customer');

            // ── 1. Create Anchor business customer ────────────────────────
            $customer = $this->anchor->createBusinessCustomer([
                'business_name'        => $data['business_name'],
                'registration_type'    => $data['registration_type'],
                'date_of_registration' => $data['date_of_registration'],
                'industry'             => $data['industry'],           // now passes Anchor enum directly
                'description'          => $data['business_name'],
                'website'              => $data['website'] ?? null,
                'email'                => $developer->email,
                'phone_number'         => $data['phone_number'],       // keep local format 07012345678
                'address_line1'        => $data['address_line1'],
                'city'                 => $data['city'],
                'state'                => $data['state'],              // will be uppercased inside service
                'director_first_name'  => $data['director_first_name'],
                'director_last_name'   => $data['director_last_name'],
                'director_bvn'         => $data['director_bvn'],
                'director_dob'         => $data['director_dob'],
                'developer_id'         => $developer->id,
            ]);
            $anchorCustomerId = $customer['id'];
            // ── 3. Trigger KYB ────────────────────────────────────────────
            $this->anchor->triggerBusinessKyb($anchorCustomerId);
            // ── 4. Verify settlement account ──────────────────────────────
            $verified = $this->anchor->verifyAccount(
                $data['settlement_bank_nip_code'],
                $data['settlement_account_number']
            );
            Log::info('acct verified successfully');
            // ── 5. Save onboarding record ─────────────────────────────────
            $onboarding->update([
                'anchor_customer_id'         => $anchorCustomerId,
                'kyc_status'                 => KycStatus::Submitted,
                'rc_number'                  => $data['rc_number'],
                'registration_type'          => $data['registration_type'],
                'date_of_registration'       => $data['date_of_registration'],
                'industry'                   => $data['industry'],
                'website'                    => $data['website'] ?? null,
                'phone_number'               => $data['phone_number'],
                'address_line1'              => $data['address_line1'],
                'city'                       => $data['city'],
                'state'                      => $data['state'],
                'director_first_name'        => $data['director_first_name'],
                'director_last_name'         => $data['director_last_name'],
                'director_bvn'               => $data['director_bvn'],
                'director_dob'               => $data['director_dob'],
                'director_id_type'           => $data['director_id_type'],
                'director_id_number'         => $data['director_id_number'],
                'settlement_bank_nip_code'   => $data['settlement_bank_nip_code'],
                'settlement_account_number'  => $data['settlement_account_number'],
                'settlement_account_name'    => $verified['attributes']['accountName'] ?? null,
                'meta'                       => ['anchor_customer' => $customer],
            ]);

            $developer->update(['onboarding_status' => OnboardingStatus::KycSubmitted]);

            // ── 6. Email documents to your team ───────────────────────────
            $this->emailKybDocuments($developer, $onboarding, $request);

            return redirect()->route('onboarding.pending')
                ->with('success', 'Business submitted! Your documents are under review.');

        } catch (\Exception $e) {
            Log::error('Business KYB submission failed', [
                'developer_id' => $developer->id,
                'error'        => $e->getMessage(),
            ]);

            return back()->withInput()
                ->with('error', 'Submission failed. Please check your details and try again.');
        }
    }

    // ─── Step 3: Pending screen ───────────────────────────────────────────────

    public function pending(Request $request): View
    {
        $developer  = $this->effectiveDeveloper($request);
        $onboarding = $developer->onboarding;

        return view('onboarding.pending', compact('developer', 'onboarding'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resume to the correct onboarding step based on current status.
     */
    private function resume(Developer $developer)
    {
        $onboarding = $developer->onboarding;
        return match ($developer->onboarding_status) {
            OnboardingStatus::Incomplete->value => match ($onboarding?->customer_type) {
                CustomerType::Individual => redirect()->route('onboarding.individual'),
                CustomerType::Business   => redirect()->route('onboarding.business'),
                default                  => view('onboarding.start'),
            },
            OnboardingStatus::KycSubmitted->value,
            OnboardingStatus::KycApproved->value => redirect()->route('onboarding.pending'),
            OnboardingStatus::AccountCreated->value,
            OnboardingStatus::Complete->value    => redirect()->route('dashboard'),
        };
    }

    /**
     * Email KYB documents to internal team for manual Anchor dashboard review.
     */
    private function emailKybDocuments(
        Developer $developer,
        Onboarding $onboarding,
        Request $request
    ): void {
        try {
            $attachments = [];

            foreach (['cac_document', 'memart_document', 'utility_bill'] as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $attachments[] = [
                        'path' => $file->getRealPath(),
                        'name' => $field . '.' . $file->getClientOriginalExtension(),
                        'mime' => $file->getMimeType(),
                    ];
                }
            }

            Mail::send([], [], function ($message) use ($developer, $onboarding, $attachments) {
                $message->to('shorunke998@gmail.com')
                    ->subject("KYB Documents — {$developer->business_name} (ID: {$developer->id})")
                    ->html(
                        "<h2>New Business KYB Submission</h2>
                        <p><strong>Business:</strong> {$developer->business_name}</p>
                        <p><strong>Developer ID:</strong> {$developer->id}</p>
                        <p><strong>Email:</strong> {$developer->email}</p>
                        <p><strong>Anchor Customer ID:</strong> {$onboarding->anchor_customer_id}</p>
                        <p><strong>RC Number:</strong> {$onboarding->rc_number}</p>
                        <p><strong>Director:</strong> {$onboarding->director_first_name} {$onboarding->director_last_name}</p>
                        <hr>
                        <p>Please verify this business on the Anchor dashboard and approve KYB.</p>"
                    );

                foreach ($attachments as $att) {
                    $message->attach($att['path'], [
                        'as'   => $att['name'],
                        'mime' => $att['mime'],
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('Failed to email KYB documents', [
                'developer_id' => $developer->id,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
 * GET /onboarding/resolve-account
 * Used by JS in the forms to show account name before submission.
 */
public function resolveAccount(Request $request): \Illuminate\Http\JsonResponse
{
    $data = $request->validate([
        'bank'    => 'required|string',
        'account' => 'required|digits:10',
    ]);

    try {
        $result = $this->anchor->verifyAccount($data['bank'], $data['account']);

        return response()->json([
            'account_name'   => $result['attributes']['accountName'] ?? null,
            'account_number' => $result['attributes']['accountNumber'] ?? null,
        ]);

    } catch (\Exception $e) {
        return response()->json(['account_name' => null], 200);
    }
}

    private function handlePaystackKycVerification(
        Developer $developer,
        Onboarding $onboarding,
        $data = []
    ){

        try {
            $paystackService = app(\App\Services\PaystackService::class);

            /*
            |--------------------------------------------------------------------------
            | Verify BVN + Settlement Account
            |--------------------------------------------------------------------------
            */

            $verification = $paystackService->verifyBvn(
                $onboarding->bvn ?? $data['bvn'] ?? null,
                $developer->bank_code ?? $data['bank_code'] ?? null,
                $developer->settlement_account_number ?? $data['settlement_account_number'] ?? null
            );

            if (!($verification['verified'] ?? false)) {

                Log::warning('Paystack BVN verification failed', [
                    'developer_id' => $developer->id,
                    'onboarding_id' => $onboarding->id ?? null,
                ]);

                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | Create Paystack Subaccount
            |--------------------------------------------------------------------------
            */

            $subaccount = $paystackService->createSubAccount([
                'email' => $developer->email,
                'settlement_bank' => $developer->bank_code ?? $data['bank_code'] ?? null,
                'business_name' => $developer->business_name ?? $developer->name,
                'bank_code'     => $developer->bank_code ?? $data['bank_code'] ?? null,
                'account_number'=> $developer->settlement_account_number ?? $data['settlement_account_number'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Developer
            |--------------------------------------------------------------------------
            */

            $developer->update([
                'account_number'           => $developer->settlement_account_number ?? $data['settlement_account_number'] ?? null,
                'paystack_subaccount_code' => $subaccount['subaccount_code'] ?? null,
                'paystack_subaccount_id'   => $subaccount['id'] ?? null,
                'onboarding_status'        => OnboardingStatus::Complete,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Onboarding
            |--------------------------------------------------------------------------
            */

            $onboarding->update([
                'bvn'                        => $data['bvn'] ?? $onboarding->bvn,
                'date_of_birth'              => $data['date_of_birth'] ?? $onboarding->date_of_birth,
                'gender'                     => $data['gender'] ?? $onboarding->gender,
                'phone_number'               => $data['phone_number'] ?? $onboarding->phone_number,
                'address_line1'              => $data['address_line1'] ?? $onboarding->address_line1,
                'city'                       => $data['city'] ?? $onboarding->city,
                'state'                      => $data['state'] ?? $onboarding->state,
                'kyc_status' => KycStatus::Approved,
                'settlement_account_number'  => $data['settlement_account_number'] ?? null,
            ]);

            return true;

        } catch (\Throwable $e) {

            Log::error('Existing KYC verification failed', [
                'developer_id' => $developer->id,
                'onboarding_id' => $onboarding->id,
                'provider' => $this->onboardingProvider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
