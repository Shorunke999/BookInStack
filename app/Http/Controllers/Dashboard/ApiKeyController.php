<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    public function index(Request $request): View
    {
        $developer = $request->user();

        // All services with their public keys
        $services = $developer->services()->orderBy('sort_order')->get();

        return view('dashboard.api-keys', compact('developer', 'services'));
    }

    /**
     * POST /dashboard/api-keys/services/{service}/regenerate
     * Regenerates the public key for a specific service.
     */
    public function regenerateServiceKey(Request $request, Service $service): RedirectResponse
    {
        $developer = $request->user();

        abort_if($service->developer_id !== $developer->id, 403);
        $newKey = $service->regeneratePublicKey();

        return back()->with('success', "Public key for \"{$service->name}\" regenerated. Update your widget embed.");
    }

    /**
     * POST /dashboard/api-keys/regenerate  (legacy — regenerates the developer-level key)
     */
    public function regenerateDeveloperKey(Request $request): RedirectResponse
    {
        $developer = $request->user();


        $newKey = 'pk_live_' . \Illuminate\Support\Str::random(40);
        $developer->update(['public_key' => $newKey]);

        return back()->with('success', 'Master API key regenerated. Update your integrations immediately.');
    }
}
