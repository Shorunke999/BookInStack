<?php

// app/Services/Fraud/RiskEngine.php

namespace App\Services\Fraud;

use App\Enums\BookingRiskLevel;
use App\Models\Booking;
use App\Models\BookingCategory;
use App\Models\Developer;
use App\Models\Service;

class RiskEngine
{
    public function score(
        array $data,
        Developer $developer,
        Service $service,
        ?BookingCategory $category = null
    ): array {

        $score = 0;

        $reasons = [];

        /*
        |--------------------------------------------------------------------------
        | Velocity Detection
        |--------------------------------------------------------------------------
        */

        $velocityRules = $service->fraudConfig()['velocity'] ?? [];

        foreach ($velocityRules as $minutes => $rule) {

            $count = Booking::query()
                ->where('developer_id', $developer->id)
                ->where('service_id', $service->id)
                ->where('created_at', '>=', now()->subMinutes((int)$minutes))
                ->count();

            if ($count >= $rule['count']) {

                $score += $rule['score'];

                $reasons[] = "velocity_{$minutes}min";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Amount Anomaly
        |--------------------------------------------------------------------------
        */

        if ($category) {

            $amount = (float) $data['amount'];

            if ($category->fixed_price) {

                if ($amount != $category->price) {

                    $score += $service->fraudConfig()['amount']['score'] ?? 15;

                    $reasons[] = 'fixed_price_mismatch';
                }

            } else {

                if (
                    $amount < $category->min_price ||
                    $amount > $category->max_price
                ) {

                    $score += $service->fraudConfig()['amount']['score'] ?? 15;

                    $reasons[] = 'amount_outside_range';
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Risk Level
        |--------------------------------------------------------------------------
        */

        $level = match (true) {
            $score >= 60 => BookingRiskLevel::HIGH,
            $score >= 30 => BookingRiskLevel::MEDIUM,
            default => BookingRiskLevel::LOW,
        };

        return [
            'score' => $score,
            'level' => $level,
            'reasons' => $reasons,
        ];
    }
}
