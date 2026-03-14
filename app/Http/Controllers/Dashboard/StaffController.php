<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * GET /staff
     */
    public function index(Request $request): View
    {
        $admin = $request->user();

        $staff = Developer::where('owner_id', $admin->id)
            ->latest()
            ->paginate(20);

        return view('dashboard.staff.index', compact('staff'));
    }

    /**
     * GET /staff/create
     */
    public function create(): View
    {
        return view('dashboard.staff.create');
    }

    /**
     * POST /staff
     */
    public function store(Request $request): RedirectResponse
    {
        $admin = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:developers,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'can_mark_attendance' => 'boolean',
        ]);

        Developer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'staff',
            'owner_id' => $admin->id,
            'business_name' => $admin->business_name,
            'status' => 'active',
             'email_verified_at'  => now(),
        ]);
        // Send login credentials to staff member
        try {
            Mail::to($staff->email)->send(new StaffCredentials($staff, $plainPassword, $admin));
        } catch (\Exception $e) {
            // Non-fatal — staff was created, just log the failure
            \Illuminate\Support\Facades\Log::error('Staff credentials email failed', [
                'staff_id' => $staff->id,
                'error'    => $e->getMessage(),
            ]);
        }
        return redirect()->route('staff.index')
            ->with('success', "Staff account created. Share the password with {$data['name']} securely.");
    }

    /**
     * GET /staff/{id}/edit
     */
    public function edit(Request $request, int $id): View
    {
        $staff = $this->findStaff($request, $id);

        return view('dashboard.staff.edit', compact('staff'));
    }

    /**
     * PUT /staff/{id}
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $staff = $this->findStaff($request, $id);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:developers,email,'.$staff->id,
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $staff->update([
            'name' => $data['name'],
            'email' => $data['email'],
            // Only update password if a new one was provided
            ...(filled($data['password'])
                ? ['password' => Hash::make($data['password'])]
                : []
            ),
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff account updated.');
    }

    /**
     * POST /staff/{id}/suspend
     * Toggles between active and suspended.
     */
    public function suspend(Request $request, int $id): RedirectResponse
    {
        $staff = $this->findStaff($request, $id);

        $newStatus = $staff->status === 'active' ? 'suspended' : 'active';
        $staff->update(['status' => $newStatus]);

        $label = $newStatus === 'active' ? 'reactivated' : 'suspended';

        return back()->with('success', "{$staff->name} has been {$label}.");
    }

    /**
     * DELETE /staff/{id}
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $staff = $this->findStaff($request, $id);
        $name = $staff->name;
        $staff->delete();

        return redirect()->route('staff.index')
            ->with('success', "{$name}'s account has been deleted.");
    }

     
    /**
     * Resend credentials with a fresh password.
     */
    public function resendCredentials(int $id): RedirectResponse
    {
        $admin         = auth()->user()->effectiveDeveloper();
        $staff         = $this->findStaff($id);
        $plainPassword = Str::password(12, true, true, false);
 
        $staff->update(['password' => Hash::make($plainPassword)]);
 
        try {
            Mail::to($staff->email)->send(new StaffCredentials($staff, $plainPassword, $admin));
            return redirect()->route('staff.index')
                ->with('success', "New credentials sent to {$staff->email}.");
        } catch (\Exception $e) {
            return redirect()->route('staff.index')
                ->with('error', 'Could not send email. Please try again.');
        }
    }
 
    // ── Private helper ────────────────────────────────────────────────────────

    private function findStaff(Request $request, int $id): Developer
    {
        // Scope to current admin's staff only — prevents accessing other admins' staff
        return Developer::where('id', $id)
            ->where('owner_id', $request->user()->id)
            ->where('role', 'staff')
            ->firstOrFail();
    }
}
