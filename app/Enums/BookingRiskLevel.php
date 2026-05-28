<?php


namespace App\Enums;

enum BookingRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
