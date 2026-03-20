@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')


{{-- ── Tab nav ─────────────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:2px;margin-bottom:28px;border-bottom:1px solid var(--border);">
    @foreach([['mode','🗓','Mode'],['window','🕐','Hours'],['negotiate','💬','Negotiate'],['categories','📦','Categories'],['appearance', '🎨', 'Widget'],] as [$id,$icon,$label])
        <button onclick="switchTab('{{ $id }}')" id="tab-{{ $id }}" style="
            padding:10px 18px;font-size:13px;font-weight:600;cursor:pointer;
            border:none;background:none;margin-bottom:-1px;transition:all .15s;
            border-bottom:2px solid {{ $id==='mode'?'var(--accent)':'transparent' }};
            color:{{ $id==='mode'?'var(--accent)':'var(--muted)' }};
        ">{{ $icon }} {{ $label }}</button>
    @endforeach
</div>

{{-- ══ TAB 1 — MODE ══════════════════════════════════════════════════════════ --}}
<div id="panel-mode" style="max-width:640px;">
    <form method="POST" action="{{ route('dashboard.booking-settings.save') }}">
        @csrf
        <div class="card">
            <h3 style="margin-bottom:4px;">Booking Mode</h3>
            <p style="font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.5;">Controls widget layout, form fields and dashboard columns.</p>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px;">
                @php $modes=['appointment'=>['🗓','Appointment','Date & time · Clinics, salons'],'ticket'=>['🎟','Ticket','Quantity · Events, concerts'],'reservation'=>['🏨','Reservation','Date range · Hotels, halls']]; @endphp
                @foreach($modes as $v=>[$em,$title,$sub])
                    <label id="card-{{ $v }}" onclick="selectMode('{{ $v }}')" style="
                        display:flex;flex-direction:column;gap:6px;padding:16px 10px;
                        border-radius:10px;cursor:pointer;text-align:center;transition:all .15s;
                        border:2px solid {{ $developer->booking_mode===$v?'var(--accent)':'var(--border)' }};
                        background:{{ $developer->booking_mode===$v?'var(--accent-light)':'#fafafa' }};
                    ">
                        <input type="radio" name="booking_mode" value="{{ $v }}" id="mode-{{ $v }}"
                               {{ $developer->booking_mode===$v?'checked':'' }} style="display:none;" />
                        <span style="font-size:24px;">{{ $em }}</span>
                        <span style="font-weight:700;font-size:13px;">{{ $title }}</span>
                        <span style="font-size:11px;color:var(--muted);line-height:1.4;">{{ $sub }}</span>
                    </label>
                @endforeach
            </div>

            {{-- Reservation unit --}}
            @php $cfg=$developer->modeConfig(); @endphp
            <div id="reservation-unit-card" style="{{ $developer->booking_mode!=='reservation'?'display:none;':'' }}padding:14px;background:var(--soft);border-radius:8px;border:1px solid var(--border);margin-bottom:16px;">
                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Pricing Unit</div>
                <div style="display:flex;gap:8px;">
                    @foreach(['night'=>['🌙','Night','Hotels'],'day'=>['☀️','Day','Event centres']] as $u=>[$ic,$lb,$sb])
                        <label id="unit-{{ $u }}" onclick="selectUnit('{{ $u }}')" style="
                            flex:1;display:flex;align-items:center;gap:8px;padding:10px 12px;
                            border-radius:8px;cursor:pointer;transition:all .15s;
                            border:2px solid {{ ($developer->reservation_unit??'night')===$u?'var(--accent)':'var(--border)' }};
                            background:{{ ($developer->reservation_unit??'night')===$u?'var(--accent-light)':'#fff' }};
                        ">
                            <input type="radio" name="reservation_unit" value="{{ $u }}" {{ ($developer->reservation_unit??'night')===$u?'checked':'' }} style="display:none;" />
                            <span style="font-size:16px;">{{ $ic }}</span>
                            <div><div style="font-weight:700;font-size:12px;">{{ $lb }}</div><div style="font-size:11px;color:var(--muted);">{{ $sb }}</div></div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:14px;padding:10px 14px;background:var(--soft);border-radius:8px;border:1px solid var(--border);font-size:12px;color:var(--muted);">
                <span>Button: <strong id="sum-cta" style="color:var(--ink);">{{ $cfg['cta'] }}</strong></span>
                <span>Price: <strong id="sum-amount" style="color:var(--ink);">{{ $cfg['amount_label'] }}</strong></span>
                <span>Attendance: <strong id="sum-attend" style="color:var(--ink);">{{ $cfg['attendance_label'] }}</strong></span>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:12px;">Save Mode</button>
    </form>
</div>

{{-- ══ TAB 2 — HOURS ══════════════════════════════════════════════════════════ --}}
<div id="panel-window" style="display:none;max-width:520px;">
    <form method="POST" action="{{ route('dashboard.booking-settings.save') }}">
        @csrf
        <input type="hidden" name="booking_mode"     value="{{ $developer->booking_mode }}" />
        <input type="hidden" name="reservation_unit" value="{{ $developer->reservation_unit??'night' }}" />

        <div class="card">
            <h3 style="margin-bottom:4px;">Booking Hours</h3>
            <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">Restrict when bookings are accepted. Requests outside these hours are automatically rejected.</p>

            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:18px;">
                <input type="hidden"   name="enable_booking_window" value="0" />
                <input type="checkbox" name="enable_booking_window" value="1" id="window-toggle"
                       {{ $developer->enable_booking_window?'checked':'' }}
                       onchange="document.getElementById('window-fields').style.display=this.checked?'block':'none'"
                       style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;" />
                <span style="font-size:14px;font-weight:600;">Restrict booking hours</span>
            </label>

            <div id="window-fields" style="{{ $developer->enable_booking_window?'':'display:none;' }}">
                @php $windowDays=$developer->booking_window['days']??[]; @endphp
                <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:8px;">Open on</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
                    @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                        @php $active=in_array($day,$windowDays); @endphp
                        <label id="day-{{ $day }}" style="padding:5px 13px;border-radius:20px;cursor:pointer;font-size:12px;font-weight:600;transition:all .15s;border:1px solid {{ $active?'var(--accent)':'var(--border)' }};background:{{ $active?'var(--accent)':'#fff' }};color:{{ $active?'#fff':'var(--muted)' }};">
                            <input type="checkbox" name="window_days[]" value="{{ $day }}" {{ $active?'checked':'' }} style="display:none;" onchange="toggleDay('{{ $day }}',this.checked)" />
                            {{ ucfirst(substr($day,0,3)) }}
                        </label>
                    @endforeach
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="form-group" style="margin-bottom:0;"><label>Open from</label><input type="time" name="open_time"  class="form-control" value="{{ $developer->booking_window['open_time']??'09:00' }}" /></div>
                    <div class="form-group" style="margin-bottom:0;"><label>Close at</label> <input type="time" name="close_time" class="form-control" value="{{ $developer->booking_window['close_time']??'17:00' }}" /></div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:12px;">Save Hours</button>
    </form>
</div>

{{-- ══ TAB 3 — NEGOTIATE ══════════════════════════════════════════════════════ --}}
<div id="panel-negotiate" style="display:none;max-width:520px;">
    <form method="POST" action="{{ route('dashboard.booking-settings.save') }}">
        @csrf
        <input type="hidden" name="booking_mode"          value="{{ $developer->booking_mode }}" />
        <input type="hidden" name="reservation_unit"      value="{{ $developer->reservation_unit??'night' }}" />
        <input type="hidden" name="enable_booking_window" value="{{ $developer->enable_booking_window?'1':'0' }}" />

        <div class="card" style="margin-bottom:12px;">
            <h3 style="margin-bottom:4px;">Price Negotiation</h3>
            <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">
                When enabled, a <strong>Negotiate Price</strong> button appears on your widget. Customers tap it to start a WhatsApp conversation with you, agree on a price, then you generate a payment link.
            </p>

            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px;">
                <input type="hidden"   name="enable_negotiate" value="0" />
                <input type="checkbox" name="enable_negotiate" value="1" id="negotiate-toggle"
                       {{ $developer->enable_negotiate?'checked':'' }}
                       onchange="document.getElementById('negotiate-fields').style.display=this.checked?'block':'none'"
                       style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;" />
                <span style="font-size:14px;font-weight:600;">Enable price negotiation</span>
            </label>

            <div id="negotiate-fields" style="{{ $developer->enable_negotiate?'':'display:none;' }}">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Your WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="form-control"
                           value="{{ $developer->whatsapp_number??'' }}"
                           placeholder="2348012345678 (include country code, no +)" style="max-width:280px;" />
                    <div style="font-size:11px;color:var(--muted);margin-top:4px;">Nigeria example: <strong>2348012345678</strong></div>
                </div>
            </div>
        </div>

        <div style="padding:16px 20px;background:var(--soft);border-radius:8px;border:1px solid var(--border);margin-bottom:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">How it works</div>
            @foreach(['💬 Customer clicks Negotiate on widget','📱 WhatsApp opens with pre-filled message','🤝 You agree on a price via chat','🔗 Generate payment link from dashboard','💳 Customer pays via the link'] as $step)
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:6px;">{{ $step }}</div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary btn-sm">Save</button>
    </form>
</div>

{{-- ══ TAB 4 — CATEGORIES ═════════════════════════════════════════════════════ --}}
<div id="panel-categories" style="display:none;">
    @php
        $catLabel = match($developer->booking_mode){'ticket'=>'Ticket Types','reservation'=>'Room / Space Types','appointment'=> 'services',default=>'Services'};
        $catHint  = match($developer->booking_mode){'ticket'=>'e.g. VIP, Regular, Early Bird','reservation'=>'e.g. Standard Room, Deluxe Suite','appointment'=> 'e.g. Haircut, Consultation','default'=>'e.g. Haircut, Consultation'};
        $mode     = $developer->booking_mode;
        $rateUnit = $developer->reservation_unit ?? 'night';
    @endphp

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <p style="font-size:13px;color:var(--muted);">{{ $catHint }}</p>
        <button type="button" class="btn btn-primary btn-sm" onclick="toggleAddForm()" id="add-cat-btn">+ Add {{ Str::singular($catLabel) }}</button>
    </div>

    <div id="cat-form-wrap" style="display:none;margin-bottom:16px;max-width:680px;">
        <div class="card" style="background:#fafbff;border-color:var(--accent);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <strong id="cat-form-title" style="font-size:14px;">New {{ Str::singular($catLabel) }}</strong>
                <button type="button" onclick="closeForm()" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:18px;">✕</button>
            </div>
            <form method="POST" action="{{ route('categories.store') }}" id="cat-create-form">
                @csrf
                @include('dashboard.partials.category-form',['category'=>null,'mode'=>$mode,'rateUnit'=>$rateUnit])
                <div style="display:flex;gap:8px;margin-top:14px;">
                    <button type="submit" class="btn btn-primary btn-sm">Add {{ Str::singular($catLabel) }}</button>
                    <button type="button" onclick="closeForm()" class="btn btn-outline btn-sm">Cancel</button>
                </div>
            </form>
            @foreach($categories as $cat)
                <form method="POST" action="{{ route('categories.update',$cat) }}" id="cat-edit-form-{{ $cat->id }}" style="display:none;">
                    @csrf @method('PUT')
                    @include('dashboard.partials.category-form',['category'=>$cat,'mode'=>$mode,'rateUnit'=>$rateUnit])
                    <div style="display:flex;gap:8px;margin-top:14px;">
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                        <button type="button" onclick="closeForm()" class="btn btn-outline btn-sm">Cancel</button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>

    @if($categories->isEmpty())
        <div class="card" style="text-align:center;padding:40px;color:var(--muted);">
            <div style="font-size:32px;margin-bottom:10px;">{{ match($mode){'ticket'=>'🎟','reservation'=>'🏨',default=>'🗓'} }}</div>
            <div style="font-weight:600;">No {{ strtolower($catLabel) }} yet</div>
            <div style="font-size:13px;margin-top:4px;">Add your first above — it appears in your widget.</div>
        </div>
    @else
        <div class="card" style="padding:0;overflow:hidden;max-width:720px;">
            @foreach($categories as $cat)
                <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;{{ !$loop->last?'border-bottom:1px solid var(--border);':'' }}{{ $cat->status==='inactive'?'opacity:.5;':'' }}">
                    <span style="color:var(--border);font-size:16px;flex-shrink:0;">⠿</span>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-weight:600;font-size:14px;">{{ $cat->name }}</span>
                            @if(!$cat->fixed_price)<span style="font-size:10px;background:#fffbeb;color:#d97706;padding:2px 7px;border-radius:10px;font-weight:600;">Custom</span>@endif
                            @if($cat->status==='inactive')<span style="font-size:11px;background:#f3f4f6;color:var(--muted);padding:2px 8px;border-radius:10px;">Inactive</span>@endif
                        </div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;display:flex;gap:10px;flex-wrap:wrap;">
                            <span>
                                @if($cat->fixed_price) {{ $cat->priceLabel($mode, $rateUnit) }}
                                @elseif($cat->min_price || $cat->max_price) ₦{{ number_format($cat->min_price/100,0) }} – ₦{{ number_format($cat->max_price/100,0) }}
                                @else Custom price
                                @endif
                            </span>
                            @if($cat->duration_minutes)<span>⏱ {{ $cat->duration_minutes }}min</span>@endif
                            @if($cat->capacity)<span>👥 {{ $cat->capacity }}</span>@endif
                            @if($cat->total_slots!==null)<span>{{ $cat->slotsRemaining() }}/{{ $cat->total_slots }} slots</span>@endif
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                        <button type="button" onclick="openEditForm({{ $cat->id }})" class="btn btn-outline btn-sm" style="font-size:12px;padding:4px 10px;">Edit</button>
                        <form method="POST" action="{{ route('categories.toggle',$cat) }}" style="margin:0;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-outline btn-sm" style="font-size:12px;padding:4px 10px;{{ $cat->status==='active'?'color:var(--muted)':'color:var(--accent)' }}">
                                {{ $cat->status==='active'?'Disable':'Enable' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('categories.destroy',$cat) }}" style="margin:0;" onsubmit="return confirm('Remove {{ $cat->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="font-size:12px;padding:4px 10px;color:#ef4444;border-color:#fecaca;">✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ══ TAB 5 — WIDGET APPEARANCE ═════════════════════════════════════════════ --}}
<div id="panel-appearance" style="display:none;max-width:640px;">
    @php
        $wc = $developer->widget_config ?? [];
        $wcBgType  = $wc['bg_type']      ?? 'none';
        $wcBgColor = $wc['bg_color']     ?? '#f5f3ff';
        $wcBgImage = $wc['bg_image_url'] ?? '';
        $wcAccent  = $wc['accent_color'] ?? '#4f46e5';
        $wcRadius  = $wc['border_radius'] ?? 14;
        $wcBranding = $wc['show_branding'] ?? true;
    @endphp
    <form method="POST" action="{{ route('dashboard.widget-appearance.save') }}">
        @csrf
        <div class="card" style="margin-bottom:12px;">
            <h3 style="margin-bottom:4px;">Widget Appearance</h3>
            <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">Customize how your booking widget looks on your website.</p>

            {{-- Background --}}
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Background</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px;">
                @foreach(['none'=>['⬜','White'],'color'=>['🎨','Color'],'image'=>['🖼','Image']] as $type=>[$ic,$lb])
                    <div data-bg-type="{{ $type }}" onclick="setBgType('{{ $type }}')" style="padding:12px 8px;border-radius:9px;cursor:pointer;text-align:center;transition:all .15s;border:2px solid {{ $wcBgType===$type?'var(--accent)':'var(--border)' }};background:{{ $wcBgType===$type?'var(--accent-light)':'#fafafa' }};">
                        <div style="font-size:20px;margin-bottom:4px;">{{ $ic }}</div>
                        <div style="font-size:12px;font-weight:600;">{{ $lb }}</div>
                    </div>
                @endforeach
            </div>
            <input type="hidden" id="wc-bg-type" name="bg_type" value="{{ $wcBgType }}" />
            <div id="wc-color-row" style="{{ $wcBgType==='color'?'':'display:none;' }}margin-bottom:12px;">
                <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Color</label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="color" id="wc-bg-color" name="bg_color" value="{{ $wcBgColor }}" oninput="updatePreview()" style="width:40px;height:36px;border:1px solid var(--border);border-radius:7px;padding:2px;cursor:pointer;" />
                    <input type="text" value="{{ $wcBgColor }}" class="form-control" style="width:100px;font-family:monospace;font-size:13px;" oninput="document.getElementById('wc-bg-color').value=this.value;updatePreview()" />
                </div>
            </div>
            <div id="wc-image-row" style="{{ $wcBgType==='image'?'':'display:none;' }}margin-bottom:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Image URL</label>
                    <input type="url" id="wc-bg-image" name="bg_image_url" value="{{ $wcBgImage }}" class="form-control" style="font-size:13px;" placeholder="https://yoursite.com/bg.jpg" oninput="updatePreview()" />
                </div>
            </div>

            {{-- Accent + radius --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Accent Color</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="color" id="wc-accent" name="accent_color" value="{{ $wcAccent }}" oninput="updatePreview()" style="width:40px;height:36px;border:1px solid var(--border);border-radius:7px;padding:2px;cursor:pointer;" />
                        <input type="text" value="{{ $wcAccent }}" class="form-control" style="width:100px;font-family:monospace;font-size:13px;" oninput="document.getElementById('wc-accent').value=this.value;updatePreview()" />
                    </div>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Corner Radius</label>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="range" id="wc-radius" name="border_radius" min="0" max="28" value="{{ $wcRadius }}" oninput="document.getElementById('radius-val').textContent=this.value+'px';updatePreview()" style="flex:1;" />
                        <span id="radius-val" style="font-size:13px;font-weight:600;min-width:36px;">{{ $wcRadius }}px</span>
                    </div>
                </div>
            </div>

        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save Appearance</button>
    </form>
</div>
@push('scripts')
<script>
const TABS = ['mode','window','negotiate','categories','appearance'];
function switchTab(id) {
    TABS.forEach(t => {
        document.getElementById('panel-' + t).style.display = t === id ? 'block' : 'none';
        const b = document.getElementById('tab-' + t);
        b.style.borderBottomColor = t === id ? 'var(--accent)' : 'transparent';
        b.style.color = t === id ? 'var(--accent)' : 'var(--muted)';
    });
}
const MODE_CFGS = {
    appointment:{cta:'Book Appointment',amount:'Service Fee',    attend:'Attended'},
    ticket:     {cta:'Buy Ticket',       amount:'Ticket Price',  attend:'Checked In'},
    reservation:{cta:'Reserve Now',      amount:'Rate per Night',attend:'Checked Out'},
};
function selectMode(v) {
    ['appointment','ticket','reservation'].forEach(m => {
        const c = document.getElementById('card-' + m), sel = m===v;
        c.style.borderColor = sel?'var(--accent)':'var(--border)';
        c.style.background  = sel?'var(--accent-light)':'#fafafa';
        document.getElementById('mode-' + m).checked = sel;
    });
    const cfg = MODE_CFGS[v];
    document.getElementById('sum-cta').textContent    = cfg.cta;
    document.getElementById('sum-amount').textContent = cfg.amount;
    document.getElementById('sum-attend').textContent = cfg.attend;
    document.getElementById('reservation-unit-card').style.display = v==='reservation'?'block':'none';
    if (v==='reservation') updateUnitSummary();
}
function selectUnit(u) {
    ['night','day'].forEach(x => {
        const l = document.getElementById('unit-' + x), sel = x===u;
        l.style.borderColor = sel?'var(--accent)':'var(--border)';
        l.style.background  = sel?'var(--accent-light)':'#fff';
        l.querySelector('input').checked = sel;
    });
    updateUnitSummary();
}
function updateUnitSummary() {
    const d = document.querySelector('input[name="reservation_unit"]:checked')?.value === 'day';
    document.getElementById('sum-amount').textContent = d?'Rate per Day':'Rate per Night';
    document.getElementById('sum-cta').textContent    = d?'Book Now':'Reserve Now';
    document.getElementById('sum-attend').textContent = d?'Event Done':'Checked Out';
}
function toggleDay(day, checked) {
    const l = document.getElementById('day-' + day);
    l.style.background  = checked?'var(--accent)':'#fff';
    l.style.color       = checked?'#fff':'var(--muted)';
    l.style.borderColor = checked?'var(--accent)':'var(--border)';
}
function setBgType(type) {
    document.querySelectorAll('[data-bg-type]').forEach(el => {
        const sel = el.dataset.bgType === type;
        el.style.borderColor = sel ? 'var(--accent)' : 'var(--border)';
        el.style.background  = sel ? 'var(--accent-light)' : '#fafafa';
    });
    document.getElementById('wc-bg-type').value = type;
    document.getElementById('wc-color-row').style.display = type === 'color' ? 'block' : 'none';
    document.getElementById('wc-image-row').style.display = type === 'image' ? 'block' : 'none';
}
function updatePreview() {} 
function toggleAddForm() { const w=document.getElementById('cat-form-wrap'); w.style.display==='none'?openAddForm():closeForm(); }
function openAddForm() {
    closeAllEditForms();
    document.getElementById('cat-create-form').style.display='block';
    document.getElementById('cat-form-title').textContent='New Category';
    document.getElementById('cat-form-wrap').style.display='block';
    document.getElementById('add-cat-btn').textContent='✕ Cancel';
    document.getElementById('cat-form-wrap').scrollIntoView({behavior:'smooth',block:'nearest'});
}
function openEditForm(id) {
    closeAllEditForms();
    document.getElementById('cat-create-form').style.display='none';
    document.getElementById('cat-form-title').textContent='Edit Category';
    document.getElementById('cat-edit-form-'+id).style.display='block';
    document.getElementById('cat-form-wrap').style.display='block';
    document.getElementById('cat-form-wrap').scrollIntoView({behavior:'smooth',block:'nearest'});
}
function closeForm() {
    document.getElementById('cat-form-wrap').style.display='none';
    document.getElementById('add-cat-btn').textContent='+ Add Category';
    closeAllEditForms();
}
function closeAllEditForms() { document.querySelectorAll('[id^="cat-edit-form-"]').forEach(f=>f.style.display='none'); }
function toggleChildPrice(cb,s) { const w=document.getElementById('child-price-wrap'+s); if(w)w.style.display=cb.checked?'block':'none'; }
function toggleFixedPrice(cb,s) { const r=document.getElementById('price-range-wrap'+s),p=document.getElementById('price-input'+s); if(r)r.style.display=cb.checked?'none':'block'; if(p)p.required=cb.checked; }

// Open tab from URL hash
const hash=location.hash.replace('#','');
if(TABS.includes(hash)) switchTab(hash);
</script>
@endpush

@endsection