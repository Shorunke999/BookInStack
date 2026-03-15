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

    {{-- ── Reservation Unit (only shown when mode = reservation) ───────────────── --}}
    <div class="card" id="reservation-unit-card" style="
        border-radius:0; border-top:none; border-bottom:none;
        {{ $developer->booking_mode !== 'reservation' ? 'display:none;' : '' }}
    ">
        <h3 style="margin-bottom:6px; font-size:14px;">Reservation Unit</h3>
        <p style="font-size:13px; color:var(--muted); margin-bottom:14px; line-height:1.5;">
            Controls the pricing label and date labels in your widget and dashboard.
        </p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <label id="unit-night" onclick="selectUnit('night')" style="
                display:flex; align-items:center; gap:10px; padding:12px 14px;
                border-radius:9px; cursor:pointer; transition:all .15s;
                border:2px solid {{ ($developer->reservation_unit ?? 'night') === 'night' ? 'var(--accent)' : 'var(--border)' }};
                background:{{ ($developer->reservation_unit ?? 'night') === 'night' ? 'var(--accent-light)' : '#fafafa' }};
            ">
                <input type="radio" name="reservation_unit" value="night"
                       {{ ($developer->reservation_unit ?? 'night') === 'night' ? 'checked' : '' }}
                       style="display:none;" />
                <span style="font-size:20px;">🌙</span>
                <div>
                    <div style="font-weight:700; font-size:13px;">Night</div>
                    <div style="font-size:11px; color:var(--muted);">Hotels, lodges, short stays</div>
                </div>
            </label>
            <label id="unit-day" onclick="selectUnit('day')" style="
                display:flex; align-items:center; gap:10px; padding:12px 14px;
                border-radius:9px; cursor:pointer; transition:all .15s;
                border:2px solid {{ ($developer->reservation_unit ?? 'night') === 'day' ? 'var(--accent)' : 'var(--border)' }};
                background:{{ ($developer->reservation_unit ?? 'night') === 'day' ? 'var(--accent-light)' : '#fafafa' }};
            ">
                <input type="radio" name="reservation_unit" value="day"
                       {{ ($developer->reservation_unit ?? 'night') === 'day' ? 'checked' : '' }}
                       style="display:none;" />
                <span style="font-size:20px;">☀️</span>
                <div>
                    <div style="font-weight:700; font-size:13px;">Day</div>
                    <div style="font-size:11px; color:var(--muted);">Event centres, halls, venues</div>
                </div>
            </label>
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

        // Show/hide reservation unit card
        const unitCard = document.getElementById('reservation-unit-card');
        if (unitCard) unitCard.style.display = value === 'reservation' ? 'block' : 'none';

        // Update amount label based on current unit if reservation
        if (value === 'reservation') updateUnitSummary();
    }

    function selectUnit(value) {
        ['night','day'].forEach(u => {
            const label = document.getElementById('unit-' + u);
            const sel   = u === value;
            label.style.borderColor = sel ? 'var(--accent)' : 'var(--border)';
            label.style.background  = sel ? 'var(--accent-light)' : '#fafafa';
            label.querySelector('input').checked = sel;
        });
        updateUnitSummary();
    }

    function updateUnitSummary() {
        const isDay = document.querySelector('input[name="reservation_unit"]:checked')?.value === 'day';
        document.getElementById('sum-amount').textContent = isDay ? 'Rate per Day' : 'Rate per Night';
        document.getElementById('sum-cta').textContent    = isDay ? 'Book Now'     : 'Reserve Now';
        document.getElementById('sum-attend').textContent = isDay ? 'Event Done'   : 'Checked Out';
        document.getElementById('sum-desc').textContent   = isDay ? 'Hall / Space' : 'Room / Space';
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

    // ── Widget CMS ────────────────────────────────────────────────────────────
    function setBgType(type) {
        document.querySelectorAll('[data-bg-type]').forEach(el => {
            const active = el.dataset.bgType === type;
            el.style.borderColor = active ? 'var(--accent)' : 'var(--border)';
            el.style.background  = active ? 'var(--accent-light)' : '#fafafa';
        });
        document.getElementById('wc-bg-type').value = type;
        document.getElementById('wc-color-row').style.display  = type === 'color' ? 'block' : 'none';
        document.getElementById('wc-image-row').style.display  = type === 'image' ? 'block' : 'none';
        updatePreview();
    }

    function updatePreview() {
        const type    = document.getElementById('wc-bg-type').value;
        const color   = document.getElementById('wc-bg-color').value;
        const imgUrl  = document.getElementById('wc-bg-image').value.trim();
        const accent  = document.getElementById('wc-accent').value;
        const radius  = document.getElementById('wc-radius').value;
        const preview = document.getElementById('widget-preview-box');

        preview.style.borderRadius = radius + 'px';
        preview.style.background   = type === 'color' ? color
                                   : type === 'image' && imgUrl ? `url(${imgUrl}) center/cover no-repeat` : '#fff';

        document.getElementById('preview-btn').style.background    = accent;
        document.getElementById('preview-accent').style.background = accent;
        document.getElementById('preview-accent').style.color      = accent;
    }
</script>
@endpush

{{-- ══════════════════════════════════════════════════════════════════════════
     SECTION 3 — WIDGET APPEARANCE
══════════════════════════════════════════════════════════════════════════ --}}
@php
    $wc = $developer->widget_config ?? [];
    $wcBgType  = $wc['bg_type']      ?? 'none';
    $wcBgColor = $wc['bg_color']     ?? '#f5f3ff';
    $wcBgImage = $wc['bg_image_url'] ?? '';
    $wcAccent  = $wc['accent_color'] ?? '#4f46e5';
    $wcRadius  = $wc['border_radius'] ?? 14;
    $wcBranding = $wc['show_branding'] ?? true;
@endphp

<div style="margin-top:24px;">
    <div style="margin-bottom:14px;">
        <h3 style="margin:0; font-size:15px;">Widget Appearance</h3>
        <p style="font-size:13px; color:var(--muted); margin-top:3px;">
            Customize how your booking widget looks on your website.
        </p>
    </div>

    <div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;">

        {{-- Controls --}}
        <form method="POST" action="{{ route('dashboard.widget-appearance.save') }}">
            @csrf

            {{-- Background type --}}
            <div class="card" style="margin-bottom:16px;">
                <h4 style="font-size:13px; font-weight:700; margin-bottom:12px;">Background</h4>

                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:14px;">
                    @foreach([
                        'none'  => ['icon'=>'⬜', 'label'=>'White'],
                        'color' => ['icon'=>'🎨', 'label'=>'Color'],
                        'image' => ['icon'=>'🖼',  'label'=>'Image'],
                    ] as $type => $opt)
                        <div data-bg-type="{{ $type }}"
                             onclick="setBgType('{{ $type }}')"
                             style="
                                 padding:12px 8px; border-radius:9px; cursor:pointer;
                                 text-align:center; transition:all .15s;
                                 border:2px solid {{ $wcBgType === $type ? 'var(--accent)' : 'var(--border)' }};
                                 background:{{ $wcBgType === $type ? 'var(--accent-light)' : '#fafafa' }};
                             ">
                            <div style="font-size:20px; margin-bottom:4px;">{{ $opt['icon'] }}</div>
                            <div style="font-size:12px; font-weight:600;">{{ $opt['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                <input type="hidden" id="wc-bg-type" name="bg_type" value="{{ $wcBgType }}" />

                <div id="wc-color-row" style="{{ $wcBgType === 'color' ? '' : 'display:none;' }}">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;">Background Color</label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="color" id="wc-bg-color" name="bg_color"
                                   value="{{ $wcBgColor }}"
                                   oninput="updatePreview()"
                                   style="width:44px; height:36px; border:1px solid var(--border); border-radius:7px; cursor:pointer; padding:2px;" />
                            <input type="text" value="{{ $wcBgColor }}"
                                   style="width:90px; font-family:monospace; font-size:13px;"
                                   class="form-control"
                                   oninput="document.getElementById('wc-bg-color').value=this.value; updatePreview()" />
                        </div>
                    </div>
                </div>

                <div id="wc-image-row" style="{{ $wcBgImage ? '' : 'display:none;' }} {{ $wcBgType === 'image' ? '' : 'display:none;' }}">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;">Image URL</label>
                        <input type="url" id="wc-bg-image" name="bg_image_url"
                               value="{{ $wcBgImage }}"
                               class="form-control" style="font-size:13px;"
                               placeholder="https://yoursite.com/bg.jpg"
                               oninput="updatePreview()" />
                        <div style="font-size:11px; color:var(--muted); margin-top:4px;">
                            Use a publicly accessible URL. Recommended: 800×600px or larger.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Accent color + radius --}}
            <div class="card" style="margin-bottom:16px;">
                <h4 style="font-size:13px; font-weight:700; margin-bottom:14px;">Colors & Shape</h4>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;">Accent Color <span style="color:var(--muted); font-weight:400;">(button, highlights)</span></label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" id="wc-accent" name="accent_color"
                                   value="{{ $wcAccent }}"
                                   oninput="updatePreview()"
                                   style="width:44px; height:36px; border:1px solid var(--border); border-radius:7px; cursor:pointer; padding:2px;" />
                            <input type="text" value="{{ $wcAccent }}"
                                   style="width:90px; font-family:monospace; font-size:13px;"
                                   class="form-control"
                                   oninput="document.getElementById('wc-accent').value=this.value; updatePreview()" />
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:12px;">Corner Radius <span style="color:var(--muted); font-weight:400;">(px)</span></label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="range" id="wc-radius" name="border_radius"
                                   min="0" max="28" value="{{ $wcRadius }}"
                                   oninput="document.getElementById('radius-val').textContent=this.value+'px'; updatePreview()"
                                   style="flex:1;" />
                            <span id="radius-val" style="font-size:13px; font-weight:600; min-width:32px;">{{ $wcRadius }}px</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Branding --}}
            <div class="card" style="margin-bottom:16px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="hidden" name="show_branding" value="0" />
                    <input type="checkbox" name="show_branding" value="1"
                           {{ $wcBranding ? 'checked' : '' }}
                           style="width:15px; height:15px; accent-color:var(--accent); cursor:pointer;" />
                    <div>
                        <div style="font-size:13px; font-weight:600;">Show "Powered by BookStack"</div>
                        <div style="font-size:12px; color:var(--muted);">Displayed at the bottom of your widget.</div>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Save Appearance</button>
        </form>

        {{-- Live Preview --}}
        <div>
            <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; margin-bottom:10px;">
                Live Preview
            </div>
            <div id="widget-preview-box" style="
                border-radius:{{ $wcRadius }}px;
                background:{{ $wcBgType === 'color' ? $wcBgColor : ($wcBgType === 'image' && $wcBgImage ? 'url('.$wcBgImage.') center/cover no-repeat' : '#fff') }};
                border:1px solid var(--border);
                padding:20px;
                box-shadow:0 2px 12px rgba(0,0,0,.06);
            ">
                {{-- Mini widget mockup --}}
                <div style="font-size:15px; font-weight:700; margin-bottom:14px; color:#111;">
                    {{ $modeConfig['cta'] }}
                    <span id="preview-accent" style="
                        font-size:11px; font-weight:700; margin-left:6px;
                        background:transparent; color:{{ $wcAccent }};
                        border:1px solid {{ $wcAccent }}; padding:2px 8px; border-radius:20px;
                    ">{{ $modeConfig['label'] }}</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
                    @foreach($categories->take(3) as $cat)
                        <div style="
                            padding:10px 12px; border:2px solid var(--border); border-radius:8px;
                            background:rgba(255,255,255,.8); font-size:13px;
                        ">
                            <div style="font-weight:600;">{{ $cat->name }}</div>
                            <div style="font-size:12px; color:{{ $wcAccent }}; font-weight:600;">{{ $cat->formattedPrice() }}</div>
                        </div>
                    @endforeach
                    @if($categories->isEmpty())
                        <div style="padding:10px 12px; border:2px solid var(--border); border-radius:8px; background:rgba(255,255,255,.8); font-size:13px;">
                            <div style="font-weight:600;">Sample Category</div>
                            <div style="font-size:12px; color:{{ $wcAccent }}; font-weight:600;">₦5,000</div>
                        </div>
                    @endif
                </div>

                <div style="height:36px; background:#f3f4f6; border-radius:7px; margin-bottom:8px;"></div>
                <div style="height:36px; background:#f3f4f6; border-radius:7px; margin-bottom:14px;"></div>

                <button id="preview-btn" style="
                    width:100%; padding:11px; background:{{ $wcAccent }};
                    color:#fff; border:none; border-radius:8px;
                    font-size:14px; font-weight:700; cursor:default;
                ">{{ $modeConfig['cta'] }}</button>

                @if($wcBranding)
                    <div style="text-align:center; margin-top:10px; font-size:11px; color:#9ca3af;">
                        Powered by BookStack
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection