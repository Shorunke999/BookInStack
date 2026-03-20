<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BookingCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingCategoryController extends Controller
{
    // ── Store (POST /settings/categories) ─────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $developer = auth()->user();
        $mode      = $developer->booking_mode;

        $data = $this->validated($request, $mode);

        $developer->bookingCategories()->create([
            ...$data,
            'booking_mode' => $mode,
            'sort_order'   => $developer->bookingCategories()
                                ->forMode($mode)->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Category added.');
    }

    // ── Update (PUT /settings/categories/{category}) ───────────────────────────

    public function update(Request $request, BookingCategory $category): RedirectResponse
    {
        $this->authorise($category);
        $mode = $category->booking_mode;
        
        $category->update($this->validated($request, $mode));

        return back()->with('success', 'Category updated.');
    }

    // ── Toggle status (PATCH /settings/categories/{category}/toggle) ──────────

    public function toggle(BookingCategory $category): RedirectResponse
    {
        $this->authorise($category);

        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Category ' . $category->status . '.');
    }

    // ── Destroy (DELETE /settings/categories/{category}) ──────────────────────

    public function destroy(BookingCategory $category): RedirectResponse
    {
        $this->authorise($category);
        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    // ── Reorder (POST /settings/categories/reorder) ────────────────────────────

    public function reorder(Request $request): RedirectResponse
    {
        $developer = auth()->user();
        $order     = $request->input('order', []);

        foreach ($order as $index => $id) {
            $developer->bookingCategories()
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

    private function authorise(BookingCategory $category): void
    {
        $developer = auth()->user();

        abort_if($category->developer_id !== $developer->id, 403);
    }
}