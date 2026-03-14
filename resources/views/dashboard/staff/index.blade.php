@extends('layouts.app')
@section('title', 'Staff')
@section('page-title', 'Staff Management')

@section('content')

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <div>
        <p style="color:var(--muted); font-size:14px; margin:0;">
            Staff can view bookings and payments and mark attendance.
            They cannot access API keys or settings.
        </p>
    </div>
    <a href="{{ route('staff.create') }}" class="btn btn-primary">+ Add Staff</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    <tr>
                        <td>
                            <div style="font-weight:500;">{{ $member->name }}</div>
                        </td>
                        <td>{{ $member->email }}</td>
                        <td>
                            @include('components.status-badge', ['status' => $member->status])
                        </td>
                        <td>{{ $member->created_at->format('d M Y') }}</td>
                        <td>
                            <div style="display:flex; gap:8px; justify-content:flex-end;">

                                <a href="{{ route('staff.edit', $member->id) }}"
                                   class="btn btn-sm btn-outline">Edit</a>

                                <form method="POST"
                                      action="{{ route('staff.suspend', $member->id) }}"
                                      style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline"
                                            style="{{ $member->status === 'active' ? 'color:#f59e0b;' : 'color:var(--green);' }}">
                                        {{ $member->status === 'active' ? 'Suspend' : 'Reactivate' }}
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('staff.destroy', $member->id) }}"
                                      style="margin:0;"
                                      onsubmit="return confirm('Delete {{ $member->name }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"
                            style="text-align:center; color:var(--muted); padding:40px;">
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
        <div style="padding:16px; border-top:1px solid var(--border);">
            {{ $staff->links('components.pagination') }}
        </div>
    @endif
</div>

@endsection