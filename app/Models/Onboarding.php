<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Onboarding extends Model
{
    protected $fillable = [
        'developer_id',
        'customer_type',
        'anchor_customer_id',
        'anchor_account_id',
        'anchor_reserved_account_id',
        'anchor_nuban',
        'anchor_bank_name',
        'anchor_counterparty_id',
        'kyc_status',
        'kyc_tier',
        'kyc_documents_required',
        'bvn',
        'date_of_birth',
        'gender',
        'phone_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'rc_number',
        'registration_type',
        'date_of_registration',
        'industry',
        'website',
        'director_first_name',
        'director_last_name',
        'director_bvn',
        'director_dob',
        'director_id_type',
        'director_id_number',
        'settlement_bank_code',
        'settlement_bank_nip_code',
        'settlement_account_number',
        'settlement_account_name',
        'meta',
    ];

    protected $casts = [
        'customer_type'           => CustomerType::class,
        'kyc_status'              => KycStatus::class,
        'kyc_documents_required'  => 'array',
        'date_of_birth'           => 'date',
        'date_of_registration'    => 'date',
        'director_dob'            => 'date',
        'meta'                    => 'array',
    ];

    protected $hidden = ['bvn', 'director_bvn'];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->kyc_status === KycStatus::Approved;
    }

    public function hasNuban(): bool
    {
        return ! empty($this->anchor_nuban);
    }

    public function isIndividual(): bool
    {
        return $this->customer_type === CustomerType::Individual;
    }

    public function isBusiness(): bool
    {
        return $this->customer_type === CustomerType::Business;
    }
}
