{{--
    dashboard/services/_form.blade.php
    Used by services/create.blade.php and services/edit.blade.php
    $service = Service|null
--}}

@php $isEdit = $service !== null; @endphp

{{-- ── Basic Info ───────────────────────────────────────────────────────────── --}}
<div class="form-group" style="margin-bottom:18px;">
    <label class="form-label">Service Name <span style="color:#ef4444;">*</span></label>
    <input type="text" name="name" class="form-control"
           value="{{ old('name', $service?->name) }}"
           placeholder="e.g. Hotel Rooms, Weekend Events, Haircut Appointments" required>
    @error('name') <div class="field-error">{{ $message }}</div> @enderror
</div>

<div class="form-group" style="margin-bottom:20px;">
    <label class="form-label">Description</label>
    <input type="text" name="description" class="form-control"
           value="{{ old('description', $service?->description) }}"
           placeholder="Short description (optional)">
</div>

{{-- ── Booking Mode ─────────────────────────────────────────────────────────── --}}
<div class="form-group" style="margin-bottom:24px;">
    <label class="form-label">Booking Mode <span style="color:#ef4444;">*</span></label>

    @if($isEdit)
        <input type="hidden" name="booking_mode" value="{{ $service->booking_mode }}">
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @foreach(['ticket'=>['🎟','Ticket','#6366f1'],'reservation'=>['🏨','Reservation','#10b981'],'appointment'=>['🗓','Appointment','#f59e0b']] as $m=>[$em,$lb,$color])
                <div style="padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;
                    border:1.5px solid {{ $service->booking_mode===$m ? $color : 'var(--border)' }};
                    background:{{ $service->booking_mode===$m ? $color.'14' : 'transparent' }};
                    color:{{ $service->booking_mode===$m ? $color : 'var(--muted)' }};
                    opacity:{{ $service->booking_mode===$m ? '1' : '0.4' }};">
                    {{ $em }} {{ $lb }}{{ $service->booking_mode===$m ? ' ✓' : '' }}
                </div>
            @endforeach
        </div>
        <p style="font-size:12px;color:var(--muted);margin-top:6px;">Mode cannot be changed after creation.</p>
    @else
        @php $selMode = old('booking_mode', 'ticket'); @endphp
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
            @foreach(['ticket'=>['🎟','Ticket','Events & concerts','#6366f1'],'reservation'=>['🏨','Reservation','Hotels & halls','#10b981'],'appointment'=>['🗓','Appointment','Clinics & salons','#f59e0b']] as $m=>[$em,$lb,$sub,$color])
                <label id="mode-card-{{ $m }}" style="display:flex;flex-direction:column;gap:6px;
                    padding:16px 10px;border-radius:10px;cursor:pointer;text-align:center;
                    transition:all .15s;
                    border:2px solid {{ $selMode===$m ? $color : 'var(--border)' }};
                    background:{{ $selMode===$m ? $color.'14' : '#fafafa' }};">
                    <input type="radio" name="booking_mode" value="{{ $m }}"
                           {{ $selMode===$m ? 'checked' : '' }} style="display:none;"
                           onchange="onModeChange('{{ $m }}')">
                    <span style="font-size:22px;">{{ $em }}</span>
                    <span style="font-weight:700;font-size:13px;">{{ $lb }}</span>
                    <span style="font-size:11px;color:var(--muted);line-height:1.4;">{{ $sub }}</span>
                </label>
            @endforeach
        </div>
    @endif
    @error('booking_mode') <div class="field-error">{{ $message }}</div> @enderror
</div>

{{-- ── Reservation Unit (reservation only) ─────────────────────────────────── --}}
@php $curMode = old('booking_mode', $service?->booking_mode ?? 'ticket'); @endphp
<div id="reservation-unit-section"
     style="margin-bottom:20px;{{ $curMode !== 'reservation' ? 'display:none;' : '' }}">
    <label class="form-label">Pricing Unit</label>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        @foreach(['night'=>['🌙','Night','Hotels'],'day'=>['☀️','Day','Event centres']] as $u=>[$ic,$lb,$sb])
            @php $selUnit = old('reservation_unit', $service?->reservation_unit ?? 'night'); @endphp
            <label id="unit-card-{{ $u }}" style="display:flex;align-items:center;gap:8px;
                padding:10px 12px;border-radius:8px;cursor:pointer;transition:all .15s;
                border:2px solid {{ $selUnit===$u ? 'var(--accent)' : 'var(--border)' }};
                background:{{ $selUnit===$u ? 'var(--accent-light)' : '#fff' }};">
                <input type="radio" name="reservation_unit" value="{{ $u }}"
                       {{ $selUnit===$u ? 'checked' : '' }} style="display:none;"
                       onchange="selectUnit('{{ $u }}')">
                <span style="font-size:16px;">{{ $ic }}</span>
                <div><div style="font-weight:700;font-size:12px;">{{ $lb }}</div>
                     <div style="font-size:11px;color:var(--muted);">{{ $sb }}</div></div>
            </label>
        @endforeach
    </div>
</div>

{{-- ══ SMS Payment Notification ═════════════════════════════════════════════════════ --}}
<div class="card" style="margin-bottom:20px;padding:18px;">
    <h4 style="margin-bottom:4px;font-size:14px;">📲 SMS Payment Notification</h4>

    <p style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.5;">
        Receive instant SMS notifications when a customer payment enters the system.
        This should preferably be a staff phone number or a number that can immediately
        confirm incoming payments and attend to customers quickly.
    </p>

    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:14px;">
        <input type="hidden" name="enable_sms_notification" value="0">

        <input type="checkbox"
               name="enable_sms_notification"
               value="1"
               {{ old('enable_sms_notification', $service?->enable_sms_notification) ? 'checked' : '' }}
               onchange="document.getElementById('sms-fields').style.display=this.checked?'block':'none'"
               style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">

        <span style="font-size:14px;font-weight:600;">
            Enable payment SMS notifications
        </span>
    </label>

    <div id="sms-fields"
         style="{{ old('enable_sms_notification', $service?->enable_sms_notification) ? '' : 'display:none;' }}">

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">SMS Notification Number</label>

            <input type="text"
                   name="sms_number"
                   class="form-control"
                   value="{{ old('sms_number', $service?->sms_number) }}"
                   placeholder="2348012345678 (country code, no +)">

            <div style="font-size:11px;color:var(--muted);margin-top:4px;line-height:1.5;">
                Payment alerts will be sent to this number immediately a customer payment is confirmed.
                <br>
                Nigeria example: <strong>2348012345678</strong>
            </div>
        </div>

    </div>
</div>

{{-- ══ BOOKING HOURS ═══════════════════════════════════════════════════════════ --}}
<div class="card" style="margin-bottom:20px;padding:18px;">
    <h4 style="margin-bottom:4px;font-size:14px;">🕐 Booking Hours</h4>
    <p style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.5;">
        Restrict when bookings are accepted for this service.
    </p>

    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px;">
        <input type="hidden"   name="enable_booking_window" value="0">
        <input type="checkbox" name="enable_booking_window" value="1"
               id="window-toggle"
               {{ old('enable_booking_window', $service?->enable_booking_window) ? 'checked' : '' }}
               onchange="document.getElementById('window-fields').style.display=this.checked?'block':'none'"
               style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">
        <span style="font-size:14px;font-weight:600;">Restrict booking hours</span>
    </label>

    <div id="window-fields"
         style="{{ old('enable_booking_window', $service?->enable_booking_window) ? '' : 'display:none;' }}">

        @php
            $winDays  = old('window_days',  $service?->booking_window['days']       ?? []);
            $openTime = old('open_time',    $service?->booking_window['open_time']   ?? '09:00');
            $closeTime= old('close_time',   $service?->booking_window['close_time']  ?? '17:00');
        @endphp

        <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:8px;">Open on</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
            @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                @php $active = in_array($day, $winDays); @endphp
                <label id="day-{{ $day }}"
                       style="padding:5px 13px;border-radius:20px;cursor:pointer;
                           font-size:12px;font-weight:600;transition:all .15s;
                           border:1px solid {{ $active ? 'var(--accent)' : 'var(--border)' }};
                           background:{{ $active ? 'var(--accent)' : '#fff' }};
                           color:{{ $active ? '#fff' : 'var(--muted)' }};">
                    <input type="checkbox" name="window_days[]" value="{{ $day }}"
                           {{ $active ? 'checked' : '' }} style="display:none;"
                           onchange="toggleDay('{{ $day }}', this.checked)">
                    {{ ucfirst(substr($day, 0, 3)) }}
                </label>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:12px;">Open from</label>
                <input type="time" name="open_time"  class="form-control" value="{{ $openTime }}">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:12px;">Close at</label>
                <input type="time" name="close_time" class="form-control" value="{{ $closeTime }}">
            </div>
        </div>
    </div>
</div>

{{-- ══ NEGOTIATE ════════════════════════════════════════════════════════════════ --}}
<div class="card" style="margin-bottom:20px;padding:18px;">
    <h4 style="margin-bottom:4px;font-size:14px;">💬 Price Negotiation</h4>
    <p style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.5;">
        When enabled, a <strong>Negotiate Price</strong> button appears in your widget.
        Customers open WhatsApp to discuss pricing, then you generate a payment link.
    </p>

    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:14px;">
        <input type="hidden"   name="enable_negotiate" value="0">
        <input type="checkbox" name="enable_negotiate" value="1"
               {{ old('enable_negotiate', $service?->enable_negotiate) ? 'checked' : '' }}
               onchange="document.getElementById('negotiate-fields').style.display=this.checked?'block':'none'"
               style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">
        <span style="font-size:14px;font-weight:600;">Enable price negotiation</span>
    </label>

    <div id="negotiate-fields"
         style="{{ old('enable_negotiate', $service?->enable_negotiate) ? '' : 'display:none;' }}">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">WhatsApp Number</label>
            <input type="text" name="whatsapp_number" class="form-control"
                   value="{{ old('whatsapp_number', $service?->whatsapp_number) }}"
                   placeholder="2348012345678 (country code, no +)">
            <div style="font-size:11px;color:var(--muted);margin-top:4px;">
                Nigeria example: <strong>2348012345678</strong>
            </div>
        </div>
    </div>
</div>

{{-- ── Status (edit only) ───────────────────────────────────────────────────── --}}
@if($isEdit)
<div class="form-group" style="margin-bottom:18px;">
    <label class="form-label">Status</label>
    <select name="status" class="form-control" style="max-width:180px;">
        <option value="active"   {{ old('status', $service->status) === 'active'   ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $service->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
</div>
@endif

@push('scripts')
<script>
const MODE_COLORS = { ticket:'#6366f1', reservation:'#10b981', appointment:'#f59e0b' };

function onModeChange(selected) {
    Object.keys(MODE_COLORS).forEach(m => {
        const card = document.getElementById('mode-card-' + m);
        const sel  = m === selected;
        if (!card) return;
        card.style.borderColor = sel ? MODE_COLORS[m] : 'var(--border)';
        card.style.background  = sel ? MODE_COLORS[m] + '14' : '#fafafa';
        card.querySelector('input').checked = sel;
    });
    const resSection = document.getElementById('reservation-unit-section');
    if (resSection) resSection.style.display = selected === 'reservation' ? 'block' : 'none';
}

function selectUnit(u) {
    ['night','day'].forEach(x => {
        const card = document.getElementById('unit-card-' + x);
        const sel  = x === u;
        if (!card) return;
        card.style.borderColor = sel ? 'var(--accent)' : 'var(--border)';
        card.style.background  = sel ? 'var(--accent-light)' : '#fff';
        card.querySelector('input').checked = sel;
    });
}

function toggleDay(day, checked) {
    const l = document.getElementById('day-' + day);
    if (!l) return;
    l.style.background  = checked ? 'var(--accent)' : '#fff';
    l.style.color       = checked ? '#fff' : 'var(--muted)';
    l.style.borderColor = checked ? 'var(--accent)' : 'var(--border)';
}
</script>
@endpush
