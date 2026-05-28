<?php

namespace App\Http\Middleware;

use App\Enums\OnboardingStatus;
use App\Models\Developer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnboardingComplete
{
    /**
     * Routes the developer can access before completing onboarding.
     * Everything else redirects to the onboarding flow.
     */
    private array $except = [
        'onboarding*',
        'logout',
        'verification*',
        'email*',
        'webhooks*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Developer $user */
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Resolve the effective developer (staff → their owner)
        $developer = $user->isStaff() ? $user->owner : $user;

        // If onboarding is complete, let them through
        if ($developer->onboarding_status === OnboardingStatus::Complete->value) {
            return $next($request);
        }

        // If already on an allowed route, let them through
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        // Redirect to the correct onboarding step
        return match ($developer->onboarding_status) {
            OnboardingStatus::Incomplete     => redirect()->route('onboarding.index'),
            OnboardingStatus::KycSubmitted,
            OnboardingStatus::KycApproved,
            OnboardingStatus::AccountCreated => redirect()->route('onboarding.pending'),
            default                          => redirect()->route('onboarding.index'),
        };
    }

    private function isExcluded(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->routeIs($pattern) || $request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
