<?php

namespace App\Enums;

enum FraudSignalType: string
{
    case VELOCITY = 'velocity';
    case AMOUNT_ANOMALY = 'amount_anomaly';
    case FAILED_PAYMENT = 'failed_payment';
}
