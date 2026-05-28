<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\StaffCredentials;
use App\Models\Developer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $admin = $request->user();

        $staff = Developer::where('owner_id', $admin->id)
            ->with('assignedServices')
            ->latest()
            ->paginate(20);

        return view('dashboard.staff.index', compact('staff'));
    }

    public function create(Request $request): View
    {
        $admin    = $request->user();
        $services = $admin->services()->active()->orderBy('sort_order')->get();

        return view('dashboard.staff.create', compact('services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $request->user();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:developers,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'service_ids'   => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
        ]);

        $staff = Developer::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'role'              => 'staff',
            'owner_id'          => $admin->id,
            'business_name'     => $admin->business_name,
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        // Assign services — validate they belong to this admin
        if (! empty($data['service_ids'])) {
            $validIds = $admin->services()->whereIn('id', $data['service_ids'])->pluck('id');
            $staff->assignedServices()->sync($validIds);
        }

        try {
            defer(fn() => Mail::to($staff->email)->send(new StaffCredentials($staff, $data['password'], $admin)));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Staff credentials email failed', [
                'staff_id' => $staff->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return redirect()->route('staff.index')
            ->with('success', "Staff account created. Share the password with {$data['name']} securely.");
    }

    public function edit(Request $request, int $id): View
    {
        $admin    = $request->user();
        $staff    = $this->findStaff($request, $id);
        $services = $admin->services()->active()->orderBy('sort_order')->get();

        return view('dashboard.staff.edit', compact('staff', 'services'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $admin = $request->user();
        $staff = $this->findStaff($request, $id);

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:developers,email,' . $staff->id,
            'password'      => ['nullable', 'confirmed', Password::min(8)],
            'service_ids'   => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
        ]);

        $staff->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            ...(filled($data['password']) ? ['password' => Hash::make($data['password'])] : []),
        ]);

        // Re-sync service assignments
        $validIds = $admin->services()->whereIn('id', $data['service_ids'] ?? [])->pluck('id');
        $staff->assignedServices()->sync($validIds);

        return redirect()->route('staff.index')->with('success', 'Staff account updated.');
    }

    public function suspend(Request $request, int $id): RedirectResponse
    {
        $staff     = $this->findStaff($request, $id);
        $newStatus = $staff->status === 'active' ? 'suspended' : 'active';
        $staff->update(['status' => $newStatus]);
        $label = $newStatus === 'active' ? 'reactivated' : 'suspended';

        return back()->with('success', "{$staff->name} has been {$label}.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $staff = $this->findStaff($request, $id);
        $name  = $staff->name;
        $staff->delete();

        return redirect()->route('staff.index')->with('success', "{$name}'s account has been deleted.");
    }

    public function resendCredentials(int $id): RedirectResponse
    {
        $admin         = auth()->user();
        $staff         = $this->findStaff(request(), $id);
        $plainPassword = Str::password(12, true, true, false);

        $staff->update(['password' => Hash::make($plainPassword)]);

        try {
            defer(fn() => Mail::to($staff->email)->send(new StaffCredentials($staff, $plainPassword, $admin)));
            return redirect()->route('staff.index')->with('success', "New credentials sent to {$staff->email}.");
        } catch (\Exception $e) {
            return redirect()->route('staff.index')->with('error', 'Could not send email. Please try again.');
        }
    }

    private function findStaff(Request $request, int $id): Developer
    {
        return Developer::where('id', $id)
            ->where('owner_id', $request->user()->id)
            ->where('role', 'staff')
            ->firstOrFail();
    }
}
