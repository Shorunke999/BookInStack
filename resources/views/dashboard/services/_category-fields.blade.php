{{--
    _category-fields.blade.php
    $service  = Service
    $category = BookingCategory|null
--}}

@php $mode = $service->booking_mode; @endphp

<div style="display:grid;gap:12px;">

    <div class="form-group">
        <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
        <input type="text" name="name" class="form-control"
               value="{{ old('name', $category?->name) }}" required>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group">
            <label class="form-label">Price (Naira)</label>
            <input type="number" name="price" class="form-control" min="100"
                   value="{{ old('price', $category?->price) }}" placeholder="e.g. 5000 = ₦5,000">
        </div>
        <div class="form-group">
            <label class="form-label">Total Slots</label>
            <input type="number" name="total_slots" class="form-control" min="1"
                   value="{{ old('total_slots', $category?->total_slots) }}">
        </div>
    </div>

    {{-- ── Ticket-specific ──────────────────────────────────────────────────── --}}
    @if($mode === 'ticket')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label class="form-label">Max Per Order</label>
                <input type="number" name="max_per_order" class="form-control" min="1" max="500"
                       value="{{ old('max_per_order', $category?->max_per_order) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Child Price (Naira)</label>
                <input type="number" name="child_price" class="form-control" min="0"
                       value="{{ old('child_price', $category?->child_price) }}" step="0.01">
            </div>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
            <input type="hidden"   name="enable_child_pricing" value="0">
            <input type="checkbox" name="enable_child_pricing" value="1"
                   {{ old('enable_child_pricing', $category?->enable_child_pricing) ? 'checked' : '' }}>
            Enable child pricing
        </label>
    @endif

    {{-- ── Reservation-specific ────────────────────────────────────────────── --}}
    @if($mode === 'reservation')
        <div class="form-group">
            <label class="form-label">Capacity (rooms/spaces)</label>
            <input type="number" name="capacity" class="form-control" min="1"
                   value="{{ old('capacity', $category?->capacity) }}">
        </div>
    @endif

    {{-- ── Appointment-specific ────────────────────────────────────────────── --}}
    @if($mode === 'appointment')
        <div class="form-group">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" min="5" max="480"
                   value="{{ old('duration_minutes', $category?->duration_minutes) }}">
        </div>
    @endif

    <div class="form-group">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control"
               value="{{ old('description', $category?->description) }}" placeholder="Optional">
    </div>

    {{-- ══ CHECK-IN / ATTENDANCE WINDOW ════════════════════════════════════════ --}}
    <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:2px;">

        <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;
                    letter-spacing:.07em;margin-bottom:12px;">
            @if($mode === 'ticket')    🎟 Door / Check-in Window
            @elseif($mode === 'reservation') 🏨 Check-in / Check-out Window
            @else                            🗓 Attendance Window
            @endif
        </div>

        {{-- Fixed date range — when the event/check-in period is open ──────── --}}
        @if($mode === 'ticket')
            {{-- Ticket: event date range (fixed calendar dates) --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Event Start Date</label>
                    <input type="date" name="checkin_start_date" class="form-control"
                           value="{{ old('checkin_start_date', $category?->checkin_start_date?->format('Y-m-d')) }}">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Event End Date</label>
                    <input type="date" name="checkin_end_date" class="form-control"
                           value="{{ old('checkin_end_date', $category?->checkin_end_date?->format('Y-m-d')) }}">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Door Opens (time)</label>
                    <input type="time" name="checkin_start_time" class="form-control"
                           value="{{ old('checkin_start_time', $category?->checkin_start_time) }}">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Door Closes (time)</label>
                    <input type="time" name="checkin_end_time" class="form-control"
                           value="{{ old('checkin_end_time', $category?->checkin_end_time) }}">
                </div>
            </div>
            <p style="font-size:11px;color:var(--muted);margin-top:6px;">
                Staff can only mark attendance within these dates and times.
            </p>

        @elseif($mode === 'reservation')
            {{-- Reservation: relative window around the booked check-in date --}}
            <p style="font-size:12px;color:var(--muted);margin-bottom:10px;line-height:1.5;">
                How many days before/after the booked check-in date can staff mark the guest as checked in?
            </p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Days Before Check-in</label>
                    <input type="number" name="checkin_days_before" class="form-control" min="0" max="30"
                           value="{{ old('checkin_days_before', $category?->checkin_days_before ?? 0) }}">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Days After Check-out</label>
                    <input type="number" name="checkin_days_after" class="form-control" min="0" max="30"
                           value="{{ old('checkin_days_after', $category?->checkin_days_after ?? 0) }}">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Check-in Opens (time)</label>
                    <input type="time" name="checkin_start_time" class="form-control"
                           value="{{ old('checkin_start_time', $category?->checkin_start_time) }}">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Check-in Closes (time)</label>
                    <input type="time" name="checkin_end_time" class="form-control"
                           value="{{ old('checkin_end_time', $category?->checkin_end_time) }}">
                </div>
            </div>
            <p style="font-size:11px;color:var(--muted);margin-top:6px;">
                e.g. 0 days before, 1 day after = check-in only on arrival day and the next morning.
            </p>

        @elseif($mode === 'appointment')
            {{-- Appointment: relative window around the preferred_date --}}
            <p style="font-size:12px;color:var(--muted);margin-bottom:10px;line-height:1.5;">
                How many days before/after the appointment date can attendance be marked?
                Use 0/0 to restrict to the exact appointment day only.
            </p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Days Before Appointment</label>
                    <input type="number" name="checkin_days_before" class="form-control" min="0" max="30"
                           value="{{ old('checkin_days_before', $category?->checkin_days_before ?? 0) }}">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Days After Appointment</label>
                    <input type="number" name="checkin_days_after" class="form-control" min="0" max="30"
                           value="{{ old('checkin_days_after', $category?->checkin_days_after ?? 0) }}">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Window Opens (time)</label>
                    <input type="time" name="checkin_start_time" class="form-control"
                           value="{{ old('checkin_start_time', $category?->checkin_start_time) }}">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:12px;">Window Closes (time)</label>
                    <input type="time" name="checkin_end_time" class="form-control"
                           value="{{ old('checkin_end_time', $category?->checkin_end_time) }}">
                </div>
            </div>
            <p style="font-size:11px;color:var(--muted);margin-top:6px;">
                Leave times blank to allow attendance marking any time on the valid days.
            </p>
        @endif

    </div>
    {{-- ══ END CHECK-IN WINDOW ════════════════════════════════════════════════ --}}

</div>
