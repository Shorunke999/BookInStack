<?php

namespace App\Http\Middleware;

use App\Models\BookingCategory;
use App\Services\Fraud\RiskEngine;
use Closure;
use Illuminate\Http\Request;

class FraudDetectionMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $developer = $user->isStaff()
            ? $user->owner
            : $user;

        $service = $developer->activeService();

        if (!$service) {
            return $next($request);
        }

        $category = null;

        if ($request->filled('category_id')) {
            $category = BookingCategory::find($request->category_id);
        }

        $risk = app(RiskEngine::class)->score(
            data: $request->all(),
            developer: $developer,
            service: $service,
            category: $category
        );

        $request->merge([
            'risk_score' => $risk['score'],
            'risk_level' => $risk['level']->value,
            'risk_reasons' => $risk['reasons'],
        ]);

        return $next($request);
    }
}
