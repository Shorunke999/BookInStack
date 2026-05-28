<?php

namespace App\Enums;

enum KycStatus: string
{
    case Pending           = 'pending';
    case Submitted         = 'submitted';
    case AwaitingDocument  = 'awaiting_document';
    case Approved          = 'approved';
    case Rejected          = 'rejected';
    case Error             = 'error';
}
