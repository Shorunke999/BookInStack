<?php

namespace App\Http\Middleware;

use App\Models\Developer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates SDK public key requests (used by Booking.js).
 *
 * Developers include their public key in the Authorization header:
 *   Authorization: Bearer pk_live_xxxxx
 *
 * or as a query parameter:
 *   ?key=pk_live_xxxxx
 */
class VerifyPublicKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // Extract public key from Authorization header or query param
        $publicKey = $this->extractPublicKey($request);

        if (! $publicKey) {
            return response()->json([
                'error' => 'missing_key',
                'message' => 'Public API key is required. Pass it in the Authorization header.',
            ], 401);
        }

        // Validate format
        if (! str_starts_with($publicKey, 'pk_live_') && ! str_starts_with($publicKey, 'pk_test_')) {
            return response()->json([
                'error' => 'invalid_key_format',
                'message' => 'Invalid public key format.',
            ], 401);
        }

        // Find developer
        $developer = Developer::where('public_key', $publicKey)
            ->where('status', 'active')
            ->where('bvn_verified', true)
            ->first();

        if (! $developer) {
            return response()->json([
                'error' => 'invalid_key',
                'message' => 'Invalid or inactive API key.',
            ], 401);
        }

        $origin = request()->header('Origin');
        $allowedDomains = $developer->allowed_domains ?? [];
        // if (!in_array($origin, $allowedDomains)) {
        //     return response()->json([
        //         'error' => 'unauthorized_domain',
        //         'message' => 'Unauthorized domain'
        //     ], 403);
        // }
        // Attach developer to request for use in controllers
        $request->merge(['developer' => $developer]);

        return $next($request);
    }

    private function extractPublicKey(Request $request): ?string
    {
        // Try Authorization: Bearer pk_live_xxxxx
        $authorization = $request->header('Authorization');
        if ($authorization && str_starts_with($authorization, 'Bearer pk_')) {
            return substr($authorization, 7);
        }

        // Try X-Public-Key header
        $headerKey = $request->header('X-Public-Key');
        if ($headerKey) {
            return $headerKey;
        }

        // Try query param
        return $request->query('key');
    }
}
