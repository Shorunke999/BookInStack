@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Booking Settings')

@section('content')
<div style="max-width:720px; display:flex; flex-direction:column; gap:0;">

{{-- ── Flash messages ─────────────────────────────────────────────────────── --}}
@if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534;
                border-radius:8px; padding:12px 16px; font-size:14px; margin-bottom:20px;
                display:flex; align-items:center; gap:8px;">
        ✓ {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b;
                border-radius:8px; padding:12px 16px; font-size:14px; margin-bottom:20px;">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════
     SECTION 1 — BOOKING MODE + WINDOW (single form)
══════════════════════════════════════════════════════════════════════════ --}}
<form method="POST" action="{{ route('dashboard.booking-settings.save') }}">
    @csrf

    {{-- ── Mode picker ─────────────────────────────────────────────────────── --}}
    <div class="card" style="border-radius:12px 12px 0 0; border-bottom:none;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
            <h3 style="margin:0;">Booking Mode</h3>
            <span style="font-size:12px; color:var(--muted);">Controls widget & table layout</span>
        </div>
        <p style="font-size:13px; color:var(--muted); margin-bottom:18px; line-height:1.5;">
            Choose how customers book with you. Switch modes to update the widget, form fields and dashboard columns instantly.
        </p>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px;">
            @php
                $modes = [
                    'appointment' => ['emoji'=>'🗓','title'=>'Appointment','desc'=>'Date & time slots'],
                    'ticket'      => ['emoji'=>'🎟','title'=>'Ticket',     'desc'=>'Events with qty'],
                    'reservation' => ['emoji'=>'🏨','title'=>'Reservation','desc'=>'Date range stays'],
                ];
            @endphp
            @foreach($modes as $value => $m)
                <label id="card-{{ $value }}" style="
                    display:flex; flex-direction:column; gap:6px; padding:14px 12px;
                    border-radius:10px; cursor:pointer; transition:all .15s; text-align:center;
                    border:2px solid {{ $developer->booking_mode === $value ? 'var(--accent)' : 'var(--border)' }};
                    background:{{ $developer->booking_mode === $value ? 'var(--accent-light)' : '#fafafa' }};
                " onclick="selectMode('{{ $value }}')">
                    <input type="radio" name="booking_mode" value="{{ $value }}"
                           id="mode-{{ $value }}"
                           {{ $developer->booking_mode === $value ? 'checked' : '' }}
                           style="display:none;" />
                    <span style="font-size:22px; line-height:1;">{{ $m['emoji'] }}</span>
                    <span style="font-weight:700; font-size:13px; color:var(--ink);">{{ $m['title'] }}</span>
                    <span style="font-size:11px; color:var(--muted); line-height:1.4;">{{ $m['desc'] }}</span>
                </label>
            @endforeach
        </div>

        {{-- Live preview strip --}}
        @php $cfg = $developer->modeConfig(); @endphp
        <div style="
            background:var(--soft); border:1px solid var(--border); border-radius:8px;
            padding:10px 14px; font-size:12px;
            display:flex; flex-wrap:wrap; gap:12px; color:var(--muted);
        ">
            <span>Button: <strong id="sum-cta" style="color:var(--ink);">{{ $cfg['cta'] }}</strong></span>
            <span>Price label: <strong id="sum-amount" style="color:var(--ink);">{{ $cfg['amount_label'] }}</strong></span>
            <span>Category: <strong id="sum-desc" style="color:var(--ink);">{{ $cfg['desc_label'] }}</strong></span>
            <span>Attendance: <strong id="sum-attend" style="color:var(--ink);">{{ $cfg['attendance_label'] }}</strong></span>
        </div>
    </div>

    {{-- ── Booking Window ───────────────────────────────────────────────────── --}}
    <div class="card" style="border-radius:0; border-top:none; border-bottom:none;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
            <h3 style="margin:0;">Booking Window</h3>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px;">
                <input type="hidden" name="enable_booking_window" value="0" />
                <input type="checkbox" name="enable_booking_window" value="1"
                       id="window-toggle"
                       {{ $developer->enable_booking_window ? 'checked' : '' }}
                       onchange="document.getElementById('window-fields').style.display = this.checked ? 'block' : 'none'"
                       style="width:15px; height:15px; accent-color:var(--accent); cursor:pointer;" />
                <span>Restrict hours</span>
            </label>
        </div>
        <p style="font-size:13px; color:var(--muted); margin-bottom:14px; line-height:1.5;">
            When enabled, the API rejects booking requests outside the defined window.
        </p>

        <div id="window-fields" style="{{ $developer->enable_booking_window ? '' : 'display:none;' }}">
            @php
                $windowDays = $developer->booking_window['days'] ?? [];
                $allDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
            @endphp
            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px;">
                @foreach($allDays as $day)
                    @php $active = in_array($day, $windowDays); @endphp
                    <label id="day-{{ $day }}" style="
                        padding:5px 12px; border-radius:20px; cursor:pointer; font-size:12px; font-weight:600;
                        transition:all .15s;
                        border:1px solid {{ $active ? 'var(--accent)' : 'var(--border)' }};
                        background:{{ $active ? 'var(--accent)' : '#fff' }};
                        color:{{ $active ? '#fff' : 'var(--muted)' }};
                    ">
                        <input type="checkbox" name="window_days[]" value="{{ $day }}"
                               {{ $active ? 'checked' : '' }} style="display:none;"
                               onchange="toggleDay('{{ $day }}', this.checked)" />
                        {{ ucfirst(substr($day,0,3)) }}
                    </label>
                @endforeach
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Open from</label>
                    <input type="time" name="open_time" class="form-control"
                           value="{{ $developer->booking_window['open_time'] ?? '09:00' }}" />
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Close at</label>
                    <input type="time" name="close_time" class="form-control"
                           value="{{ $developer->booking_window['close_time'] ?? '17:00' }}" />
                </div>
            </div>
        </div>
    </div>

    {{-- Save settings --}}
    <div class="card" style="border-radius:0 0 12px 12px; border-top:none; padding-top:14px; padding-bottom:14px;">
        <button type="submit" class="btn btn-primary btn-sm">Save Mode & Window Settings</button>
    </div>

</form>

{{-- ══════════════════════════════════════════════════════════════════════════
     SECTION 2 — CATEGORIES
     Label adapts per mode: Services / Ticket Types / Room Types
══════════════════════════════════════════════════════════════════════════ --}}
@php
    $catLabel = match($developer->booking_mode) {
        'ticket'      => 'Ticket Types',
        'reservation' => 'Room / Space Types',
        default       => 'Services',
    };
    $catHint = match($developer->booking_mode) {
        'ticket'      => 'e.g. VIP, Regular, Early Bird',
        'reservation' => 'e.g. Standard Room, Deluxe Suite, Conference Hall',
        default       => 'e.g. Haircut, Full Treatment, Consultation',
    };
    $mode = $developer->booking_mode;
@endphp

<div style="margin-top:24px;">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
        <div>
            <h3 style="margin:0; font-size:15px;">{{ $catLabel }}</h3>
            <p style="font-size:13px; color:var(--muted); margin-top:3px;">{{ $catHint }}</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm"
                onclick="toggleAddForm()"
                id="add-cat-btn">
            + Add {{ Str::singular($catLabel) }}
        </button>
    </div>

    {{-- ── Add / Edit form (hidden by default) ────────────────────────────── --}}
    <div id="cat-form-wrap" style="display:none; margin-bottom:16px;">
        <div class="card" style="padding:20px; background:#fafbff; border-color:var(--accent);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <strong id="cat-form-title" style="font-size:14px;">New {{ Str::singular($catLabel) }}</strong>
                <button type="button" onclick="closeForm()" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:18px;">✕</button>
            </div>

            {{-- CREATE form --}}
            <form method="POST" action="{{ route('categories.store') }}" id="cat-create-form">
                @csrf
                @include('dashboard.partials.category-form', ['category' => null, 'mode' => $mode])
                <div style="display:flex; gap:8px; margin-top:14px;">
                    <button type="submit" class="btn btn-primary btn-sm">Add {{ Str::singular($catLabel) }}</button>
                    <button type="button" onclick="closeForm()" class="btn btn-outline btn-sm">Cancel</button>
                </div>
            </form>

            {{-- EDIT forms (one per category, toggled by JS) --}}
            @foreach($categories as $cat)
                <form method="POST" action="{{ route('categories.update', $cat) }}"
                      id="cat-edit-form-{{ $cat->id }}" style="display:none;">
                    @csrf
                    @method('PUT')
                    @include('dashboard.partials.category-form', ['category' => $cat, 'mode' => $mode])
                    <div style="display:flex; gap:8px; margin-top:14px;">
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                        <button type="button" onclick="closeForm()" class="btn btn-outline btn-sm">Cancel</button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>

    {{-- ── Category list ────────────────────────────────────────────────────── --}}
    @if($categories->isEmpty())
        <div class="card" style="text-align:center; padding:40px; color:var(--muted);">
            <div style="font-size:32px; margin-bottom:10px;">
                {{ match($mode) { 'ticket'=>'🎟', 'reservation'=>'🏨', default=>'🗓' } }}
            </div>
            <div style="font-weight:600; margin-bottom:4px;">No {{ strtolower($catLabel) }} yet</div>
            <div style="font-size:13px;">Add your first one above — it will appear in your booking widget.</div>
        </div>
    @else
        <div class="card" style="padding:0; overflow:hidden;">
            @foreach($categories as $i => $cat)
                <div style="
                    display:flex; align-items:center; gap:12px;
                    padding:14px 16px;
                    {{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}
                    {{ $cat->status === 'inactive' ? 'opacity:.55;' : '' }}
                ">
                    {{-- Drag handle --}}
                    <span style="color:var(--border); cursor:grab; font-size:16px; flex-shrink:0;">⠿</span>

                    {{-- Info --}}
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-weight:600; font-size:14px;">{{ $cat->name }}</span>
                            @if($cat->status === 'inactive')
                                <span style="font-size:11px; background:#f3f4f6; color:var(--muted);
                                             padding:2px 8px; border-radius:10px;">Inactive</span>
                            @endif
                        </div>
                        <div style="font-size:12px; color:var(--muted); margin-top:2px; display:flex; gap:12px; flex-wrap:wrap;">
                            {{-- Price --}}
                            <span>
                                @if($mode === 'ticket')
                                    Adult: {{ $cat->formattedPrice() }}
                                    @if($cat->enable_child_pricing && $cat->child_price)
                                        · Child: {{ $cat->formattedChildPrice() }}
                                    @endif
                                @elseif($mode === 'reservation')
                                    {{ $cat->priceLabel($mode) }}
                                    @if($cat->capacity) · {{ $cat->capacity }} guests max @endif
                                @else
                                    {{ $cat->formattedPrice() }}
                                    @if($cat->duration_minutes) · {{ $cat->duration_minutes }}min @endif
                                @endif
                            </span>
                            @if($cat->description)
                                <span>{{ Str::limit($cat->description, 60) }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="display:flex; gap:6px; flex-shrink:0;">
                        <button type="button"
                                onclick="openEditForm({{ $cat->id }})"
                                class="btn btn-outline btn-sm"
                                style="font-size:12px; padding:4px 10px;">
                            Edit
                        </button>

                        <form method="POST" action="{{ route('categories.toggle', $cat) }}" style="margin:0;">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="btn btn-outline btn-sm"
                                    style="font-size:12px; padding:4px 10px;
                                           {{ $cat->status === 'active' ? 'color:var(--muted)' : 'color:var(--accent)' }}">
                                {{ $cat->status === 'active' ? 'Disable' : 'Enable' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('categories.destroy', $cat) }}" style="margin:0;"
                              onsubmit="return confirm('Remove {{ $cat->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="btn btn-outline btn-sm"
                                    style="font-size:12px; padding:4px 10px; color:#ef4444; border-color:#fecaca;">
                                ✕
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
<script>
    // ── Mode selector ────────────────────────────────────────────────────────
    const MODE_CONFIGS = {
        appointment: { cta:'Book Appointment', amount:'Service Fee',    desc:'Service',      attend:'Attended'    },
        ticket:      { cta:'Buy Ticket',        amount:'Ticket Price',   desc:'Event Name',   attend:'Checked In'  },
        reservation: { cta:'Reserve Now',       amount:'Rate per Night', desc:'Room / Space', attend:'Checked Out' },
    };

    function selectMode(value) {
        ['appointment','ticket','reservation'].forEach(m => {
            const card = document.getElementById('card-' + m);
            const sel  = m === value;
            card.style.borderColor = sel ? 'var(--accent)' : 'var(--border)';
            card.style.background  = sel ? 'var(--accent-light)' : '#fafafa';
            document.getElementById('mode-' + m).checked = sel;
        });
        const c = MODE_CONFIGS[value];
        document.getElementById('sum-cta').textContent    = c.cta;
        document.getElementById('sum-amount').textContent = c.amount;
        document.getElementById('sum-desc').textContent   = c.desc;
        document.getElementById('sum-attend').textContent = c.attend;
    }

    function toggleDay(day, checked) {
        const label = document.getElementById('day-' + day);
        label.style.background  = checked ? 'var(--accent)' : '#fff';
        label.style.color       = checked ? '#fff' : 'var(--muted)';
        label.style.borderColor = checked ? 'var(--accent)' : 'var(--border)';
    }

    // ── Category form ────────────────────────────────────────────────────────
    let activeEditId = null;

    function toggleAddForm() {
        const wrap = document.getElementById('cat-form-wrap');
        if (wrap.style.display === 'none') {
            openAddForm();
        } else {
            closeForm();
        }
    }

    function openAddForm() {
        closeAllEditForms();
        document.getElementById('cat-create-form').style.display = 'block';
        document.getElementById('cat-form-title').textContent = 'New Category';
        document.getElementById('cat-form-wrap').style.display = 'block';
        document.getElementById('add-cat-btn').textContent = '✕ Cancel';
        document.getElementById('cat-form-wrap').scrollIntoView({ behavior:'smooth', block:'nearest' });
    }

    function openEditForm(id) {
        closeAllEditForms();
        document.getElementById('cat-create-form').style.display = 'none';
        document.getElementById('cat-form-title').textContent = 'Edit Category';
        document.getElementById('cat-edit-form-' + id).style.display = 'block';
        document.getElementById('cat-form-wrap').style.display = 'block';
        activeEditId = id;
        document.getElementById('cat-form-wrap').scrollIntoView({ behavior:'smooth', block:'nearest' });
    }

    function closeForm() {
        document.getElementById('cat-form-wrap').style.display = 'none';
        document.getElementById('add-cat-btn').textContent = '+ Add Category';
        closeAllEditForms();
        activeEditId = null;
    }

    function closeAllEditForms() {
        document.querySelectorAll('[id^="cat-edit-form-"]').forEach(f => f.style.display = 'none');
    }

    // ── Toggle child price fields ─────────────────────────────────────────────
    function toggleChildPrice(checkbox, suffix) {
        const wrap = document.getElementById('child-price-wrap' + suffix);
        if (wrap) wrap.style.display = checkbox.checked ? 'block' : 'none';
    }
</script>
@endpush

@endsection
