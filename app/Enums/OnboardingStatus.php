<?php

namespace App\Enums;

enum OnboardingStatus: string
{
    case Incomplete      = 'incomplete';       // just registered
    case KycSubmitted    = 'kyc_submitted';    // sent to Anchor, awaiting webhook
    case KycApproved     = 'kyc_approved';     // Anchor approved KYC/KYB
    case AccountCreated  = 'account_created';  // deposit account + NUBAN issued
    case Complete        = 'complete';         // fully onboarded, can use platform
}
