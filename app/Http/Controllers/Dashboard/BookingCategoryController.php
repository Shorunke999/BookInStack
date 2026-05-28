<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BookingCategory;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;


class BookingCategoryController extends Controller
{
    // ── Store (POST /settings/categories) ─────────────────────────────────────

    public function store(Request $request, Service $service): RedirectResponse
    {
       $this->authoriseService($service);

        $mode = $service->booking_mode;
        $data = $this->validated($request, $mode);

        $service->bookingCategories()->create([
            ...$data,
            'developer_id' => $service->developer_id,
            'booking_mode' => $mode,
            'sort_order'   => $service->bookingCategories()->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Category added.');
    }

    // ── Update (PUT /settings/categories/{category}) ───────────────────────────

    public function update(Request $request,Service $service, BookingCategory $category): RedirectResponse
    {
        $this->authoriseService($service);
        $this->authoriseCategory($category, $service);

        $category->update($this->validated($request, $service->booking_mode));

        return back()->with('success', 'Category updated.');
    }

    // ── Toggle status (PATCH /settings/categories/{category}/toggle) ──────────

    public function toggle(Service $service, BookingCategory $category): RedirectResponse
    {
        $this->authoriseService($service);
        $this->authoriseCategory($category, $service);

        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
        ]);
        return back()->with('success', 'Category ' . $category->status . '.');
    }

    // ── Destroy (DELETE /settings/categories/{category}) ──────────────────────

    public function destroy(Service $service, BookingCategory $category): RedirectResponse
    {
        $this->authoriseService($service);
        $this->authoriseCategory($category, $service);

        $category->delete();
        return back()->with('success', 'Category removed.');
    }

    // ── Reorder (POST /settings/categories/reorder) ────────────────────────────

    public function reorder(Request $request,Service $service): RedirectResponse
    {
        $this->authoriseService($service);

        $order = $request->input('order', []);

        foreach ($order as $index => $id) {
            $service->bookingCategories()
                    ->where('id', $id)
                    ->update(['sort_order' => $index]);
        }


        return back();
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function validated(Request $request, string $mode): array
    {
        $rules = [
            'name'        => 'required|string|max:80',
            'description' => 'nullable|string|max:255',
            'price'       => 'nullable|integer|min:100', // kobo
            'total_slots' => 'nullable|integer|min:1',
            'status'      => 'in:active,inactive',

            'checkin_start_date'  => 'nullable|date',
            'checkin_end_date'    => 'nullable|date|after_or_equal:checkin_start_date',
            'checkin_start_time'  => 'nullable|date_format:H:i',
            'checkin_end_time'    => 'nullable|date_format:H:i',
            'checkin_days_before' => 'nullable|integer|min:0|max:30',
            'checkin_days_after'  => 'nullable|integer|min:0|max:30',
        ];

        if ($mode === 'ticket') {
            $rules['enable_child_pricing'] = 'boolean';
            $rules['child_price']          = 'nullable|integer|min:0';
            $rules['max_per_order']        = 'nullable|integer|min:1|max:500';
        }

        if ($mode === 'reservation') {
            $rules['capacity'] = 'nullable|integer|min:1';
        }

        if ($mode === 'appointment') {
            $rules['duration_minutes'] = 'nullable|integer|min:5|max:480';
        }

        return $request->validate($rules);
    }

     private function authoriseService(Service $service): void
    {
        abort_if($service->developer_id !== auth()->id(), 403);
    }

    private function authoriseCategory(BookingCategory $category, Service $service): void
    {
        abort_if($category->service_id !== $service->id, 403);
    }
    private function authorise(BookingCategory $category): void
    {
        $developer = auth()->user();

        abort_if($category->developer_id !== $developer->id, 403);
    }
}
