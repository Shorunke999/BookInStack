@extends('layouts.app')
@section('title', 'Staff')
@section('page-title', 'Staff Management')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <p style="color:var(--muted);font-size:14px;margin:0;">
        Staff can view bookings and mark attendance for their assigned services.
        They cannot access API keys or settings.
    </p>
    <a href="{{ route('staff.create') }}" class="btn btn-primary">+ Add Staff</a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Services</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    <tr>
                        <td style="font-weight:500;">{{ $member->name }}</td>
                        <td style="color:var(--muted);font-size:13px;">{{ $member->email }}</td>
                        <td>
                            @if($member->assignedServices->isEmpty())
                                <span style="font-size:12px;color:var(--muted);">No services</span>
                            @else
                                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                    @foreach($member->assignedServices as $svc)
                                        <span style="font-size:11px;font-weight:600;padding:2px 7px;
                                            border-radius:20px;
                                            background:{{ $svc->modeBadgeColor() }}1a;
                                            color:{{ $svc->modeBadgeColor() }};">
                                            {{ $svc->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>@include('components.status-badge', ['status' => $member->status])</td>
                        <td style="font-size:13px;color:var(--muted);">
                            {{ $member->created_at->format('d M Y') }}
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">

                                <a href="{{ route('staff.edit', $member->id) }}"
                                   class="btn btn-sm btn-outline">Edit</a>

                                <form method="POST" action="{{ route('staff.suspend', $member->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline"
                                        style="{{ $member->status === 'active' ? 'color:#f59e0b;' : 'color:var(--green);' }}">
                                        {{ $member->status === 'active' ? 'Suspend' : 'Reactivate' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('staff.resend-credentials', $member->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline"
                                            style="color:var(--accent);"
                                            title="Send new login credentials to this staff member">
                                        Resend Credentials
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('staff.destroy', $member->id) }}"
                                      onsubmit="return confirm('Delete {{ addslashes($member->name) }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--muted);padding:40px;">
                            No staff yet.
                            <a href="{{ route('staff.create') }}" style="color:var(--accent);">
                                Add your first staff member
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($staff->hasPages())
        <div style="padding:16px;border-top:1px solid var(--border);">
            {{ $staff->links('components.pagination') }}
        </div>
    @endif
</div>

@endsection
