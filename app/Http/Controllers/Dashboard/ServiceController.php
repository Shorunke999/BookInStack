<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    // ── Index: list all services ───────────────────────────────────────────────

    public function index(Request $request): View
    {
        $developer = $request->user();
        $services  = $developer->services()->withCount('bookingCategories', 'bookings')->get();

        return view('dashboard.services.index', compact('developer', 'services'));
    }

    // ── Create form ────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('dashboard.services.create');
    }

    // ── Store ──────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $developer = $request->user();

        $data = $this->validated($request);

        $service = $developer->services()->create([
            ...$data,
            'sort_order' => $developer->services()->max('sort_order') + 1,
        ]);

        // Auto-switch to the newly created service
        $developer->update(['active_service_id' => $service->id]);

        return redirect()->route('services.index')
                         ->with('success', "Service \"{$service->name}\" created.");
    }

    // ── Edit form ──────────────────────────────────────────────────────────────

    public function edit(Service $service): View
    {
        $this->authorise($service);

        return view('dashboard.services.edit', compact('service'));
    }

    // ── Update ─────────────────────────────────────────────────────────────────

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorise($service);

        $service->update($this->validated($request));

        return redirect()->route('services.index')
                         ->with('success', 'Service updated.');
    }

    // ── Toggle status ──────────────────────────────────────────────────────────

    public function toggle(Service $service): RedirectResponse
    {
        $this->authorise($service);
        $service->update([
            'status' => $service->status === 'active' ? 'inactive' : 'active',
        ]);
        return back()->with('success', 'Service ' . $service->status . '.');
    }

    // ── Destroy ────────────────────────────────────────────────────────────────

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorise($service);

        // If this was the active service, clear it
        $developer = auth()->user();
        if ($developer->active_service_id === $service->id) {
            $fallback = $developer->services()
                                  ->where('id', '!=', $service->id)
                                  ->where('status', 'active')
                                  ->first();

            $developer->update(['active_service_id' => $fallback?->id]);
        }

        $service->delete();

        return redirect()->route('services.index')
                         ->with('success', 'Service removed.');
    }

    // ── Activate (switch active context) ──────────────────────────────────────

    public function activate(Service $service): RedirectResponse
    {
        $developer = auth()->user();
        $developer->switchService($service);   // authorisation happens inside
        return back()->with('success', "Switched to \"{$service->name}\".");
    }

    // ── Reorder ────────────────────────────────────────────────────────────────

    public function reorder(Request $request): RedirectResponse
    {
        $developer = auth()->user();
        $order     = $request->input('order', []);

        foreach ($order as $index => $id) {
            $developer->services()
                      ->where('id', $id)
                      ->update(['sort_order' => $index]);
        }

        return back();
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:80',
            'description'           => 'nullable|string|max:255',
            'booking_mode'          => 'required|in:appointment,ticket,reservation',
            'status'                => 'in:active,inactive',
            'reservation_unit'      => 'nullable|in:night,day',
            'enable_negotiate'      => 'boolean',
            'sms_number'            => 'nullable|string|max:20',
            'whatsapp_number'       => 'nullable|string|max:20',
            'enable_booking_window' => 'boolean',
            'window_days'           => 'nullable|array',
            'window_days.*'         => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'open_time'             => 'nullable|date_format:H:i',
            'close_time'            => 'nullable|date_format:H:i|after:open_time',
        ]);

        // Assemble booking_window JSON — same shape as old developer-level settings
        $data['booking_window'] = [
            'days'       => $data['window_days']  ?? [],
            'open_time'  => $data['open_time']    ?? '09:00',
            'close_time' => $data['close_time']   ?? '17:00',
        ];

        // Remove the flat keys — they live inside booking_window now
        unset($data['window_days'], $data['open_time'], $data['close_time']);

        return $data;
    }


    private function authorise(Service $service): void
    {
        abort_if($service->developer_id !== auth()->id(), 403);
    }
}
