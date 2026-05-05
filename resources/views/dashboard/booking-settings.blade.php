@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')


{{-- ── Tab nav ─────────────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:2px;margin-bottom:24px;border-bottom:1px solid var(--border);overflow-x:auto;-webkit-overflow-scrolling:touch;">
    @foreach([['mode','🗓','Mode'],['window','🕐','Hours'],['negotiate','💬','Negotiate'],['categories','📦','Categories'],['appearance','🎨','Widget'],['domains','🌐','Domains'], ['sms','📱','SMS'],] as [$id,$icon,$label])
        <button onclick="switchTab('{{ $id }}')" id="tab-{{ $id }}" style="
            padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;
            border:none;background:none;margin-bottom:-1px;transition:all .15s;
            white-space:nowrap;flex-shrink:0;
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
            @php
                $modes = ['appointment'=>['🗓','Appointment','Date & time · Clinics, salons'],'ticket'=>['🎟','Ticket','Quantity · Events, concerts'],'reservation'=>['🏨','Reservation','Date range · Hotels, halls']];
                $cfg = $developer->modeConfig();
            @endphp
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px;">
                @foreach($modes as $v=>[$em,$title,$sub])
                    <label id="card-{{ $v }}" onclick="selectMode('{{ $v }}')" style="display:flex;flex-direction:column;gap:6px;padding:16px 10px;border-radius:10px;cursor:pointer;text-align:center;transition:all .15s;border:2px solid {{ $developer->booking_mode===$v?'var(--accent)':'var(--border)' }};background:{{ $developer->booking_mode===$v?'var(--accent-light)':'#fafafa' }};">
                        <input type="radio" name="booking_mode" value="{{ $v }}" id="mode-{{ $v }}" {{ $developer->booking_mode===$v?'checked':'' }} style="display:none;" />
                        <span style="font-size:22px;">{{ $em }}</span>
                        <span style="font-weight:700;font-size:13px;">{{ $title }}</span>
                        <span style="font-size:11px;color:var(--muted);line-height:1.4;">{{ $sub }}</span>
                    </label>
                @endforeach
            </div>
            <div id="reservation-unit-card" style="{{ $developer->booking_mode!=='reservation'?'display:none;':'' }}padding:14px;background:var(--soft);border-radius:8px;border:1px solid var(--border);margin-bottom:16px;">
                <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Pricing Unit</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    @foreach(['night'=>['🌙','Night','Hotels'],'day'=>['☀️','Day','Event centres']] as $u=>[$ic,$lb,$sb])
                        <label id="unit-{{ $u }}" onclick="selectUnit('{{ $u }}')" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;cursor:pointer;transition:all .15s;border:2px solid {{ ($developer->reservation_unit??'night')===$u?'var(--accent)':'var(--border)' }};background:{{ ($developer->reservation_unit??'night')===$u?'var(--accent-light)':'#fff' }};">
                            <input type="radio" name="reservation_unit" value="{{ $u }}" {{ ($developer->reservation_unit??'night')===$u?'checked':'' }} style="display:none;" />
                            <span style="font-size:16px;">{{ $ic }}</span>
                            <div><div style="font-weight:700;font-size:12px;">{{ $lb }}</div><div style="font-size:11px;color:var(--muted);">{{ $sb }}</div></div>
                        </label>
                    @endforeach
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;padding:10px 14px;background:var(--soft);border-radius:8px;border:1px solid var(--border);font-size:12px;color:var(--muted);">
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
            <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">Restrict when bookings are accepted. Requests outside these hours are automatically declined.</p>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:18px;">
                <input type="hidden" name="enable_booking_window" value="0" />
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
                    <div class="form-group" style="margin-bottom:0;"><label>Close at</label><input  type="time" name="close_time" class="form-control" value="{{ $developer->booking_window['close_time']??'17:00' }}" /></div>
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
            <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">When enabled, a <strong>Negotiate Price</strong> button appears on your widget. Customers open WhatsApp to discuss pricing, then you generate a payment link.</p>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px;">
                <input type="hidden" name="enable_negotiate" value="0" />
                <input type="checkbox" name="enable_negotiate" value="1"
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
                           placeholder="2348012345678 (country code, no +)" />
                    <div style="font-size:11px;color:var(--muted);margin-top:4px;">Nigeria example: <strong>2348012345678</strong></div>
                </div>
            </div>
        </div>
        <div style="padding:14px 16px;background:var(--soft);border-radius:8px;border:1px solid var(--border);margin-bottom:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">How it works</div>
            @foreach(['💬 Customer selects category → clicks Negotiate','📱 WhatsApp opens with pre-filled message','🤝 You agree on a price via chat','🔗 Generate a payment link from dashboard','💳 Customer pays → booking confirmed'] as $step)
                <div style="font-size:13px;color:var(--muted);margin-bottom:5px;">{{ $step }}</div>
            @endforeach
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save</button>
    </form>
</div>

{{-- ══ TAB 4 — CATEGORIES ═════════════════════════════════════════════════════ --}}
<div id="panel-categories" style="display:none;">
    @php
        $catLabel = match($developer->booking_mode){'ticket'=>'Ticket Types','reservation'=>'Room / Space Types',default=>'Services'};
        $mode     = $developer->booking_mode;
        $rateUnit = $developer->reservation_unit ?? 'night';
    @endphp
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <p style="font-size:13px;color:var(--muted);">{{ match($mode){'ticket'=>'e.g. VIP, Regular, Early Bird','reservation'=>'e.g. Standard Room, Conference Hall',default=>'e.g. Haircut, Consultation'} }}</p>
        <button type="button" class="btn btn-primary btn-sm" onclick="toggleAddForm()" id="add-cat-btn">+ Add {{ Str::singular($catLabel) }}</button>
    </div>
    <div id="cat-form-wrap" style="display:none;margin-bottom:16px;">
        <div class="card" style="background:#fafbff;border-color:var(--accent);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <strong id="cat-form-title" style="font-size:14px;">New {{ Str::singular($catLabel) }}</strong>
                <button type="button" onclick="closeForm()" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:20px;line-height:1;">✕</button>
            </div>
            <form method="POST" action="{{ route('categories.store') }}" id="cat-create-form">
                @csrf
                @include('dashboard.partials.category-form', ['category'=>null,'mode'=>$mode,'rateUnit'=>$rateUnit,'suffix'=>''])
                <div style="display:flex;gap:8px;margin-top:14px;">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    <button type="button" onclick="closeForm()" class="btn btn-outline btn-sm">Cancel</button>
                </div>
            </form>
            @foreach($categories as $cat)
                <form method="POST" action="{{ route('categories.update',$cat) }}" id="cat-edit-form-{{ $cat->id }}" style="display:none;">
                    @csrf @method('PUT')
                    @include('dashboard.partials.category-form', ['category'=>$cat,'mode'=>$mode,'rateUnit'=>$rateUnit,'suffix'=>'-'.$cat->id])
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
            <div style="font-size:13px;margin-top:4px;">Add your first above — it appears in your booking widget.</div>
        </div>
    @else
        <div class="card" style="padding:0;overflow:hidden;">
            @foreach($categories as $cat)
                <div style="display:flex;align-items:center;gap:10px;padding:13px 16px;{{ !$loop->last?'border-bottom:1px solid var(--border);':'' }}{{ $cat->status==='inactive'?'opacity:.5;':'' }}">
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-weight:600;font-size:14px;">{{ $cat->name }}</span>
                            @if(!($cat->fixed_price??true))<span style="font-size:10px;background:#fffbeb;color:#d97706;padding:2px 7px;border-radius:10px;font-weight:600;">Custom</span>@endif
                            @if($cat->status==='inactive')<span style="font-size:11px;background:#f3f4f6;color:var(--muted);padding:2px 8px;border-radius:10px;">Inactive</span>@endif
                        </div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;display:flex;gap:10px;flex-wrap:wrap;">
                            <span>{{ ($cat->fixed_price??true)?$cat->priceLabel($mode,$rateUnit):'Custom price' }}</span>
                            @if($cat->duration_minutes)<span>⏱ {{ $cat->duration_minutes }}min</span>@endif
                            @if($cat->total_slots!==null)<span>{{ $cat->slotsRemaining() }}/{{ $cat->total_slots }} slots</span>@endif
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                        <button type="button" onclick="openEditForm({{ $cat->id }})" class="btn btn-outline btn-sm" style="font-size:12px;padding:4px 10px;">Edit</button>
                        <form method="POST" action="{{ route('categories.toggle',$cat) }}" style="margin:0;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-outline btn-sm" style="font-size:12px;padding:4px 10px;{{ $cat->status==='active'?'color:var(--muted)':'color:var(--accent)' }}">{{ $cat->status==='active'?'Off':'On' }}</button>
                        </form>
                        <form method="POST" action="{{ route('categories.destroy',$cat) }}" style="margin:0;" onsubmit="return confirm('Remove?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="font-size:12px;padding:4px 10px;color:#ef4444;border-color:#fecaca;">✕</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ══ TAB 5 — WIDGET APPEARANCE ══════════════════════════════════════════════ --}}
<div id="panel-appearance" style="display:none;max-width:520px;">
    @php
        $wc         = $developer->widget_config ?? [];
        $wcBgType   = $wc['bg_type']       ?? 'none';
        $wcBgColor  = $wc['bg_color']      ?? '#f5f3ff';
        $wcBgImage  = $wc['bg_image_url']  ?? '';
        $wcAccent   = $wc['accent_color']  ?? '#4f46e5';
        $wcRadius   = $wc['border_radius'] ?? 14;
        $wcBranding = $wc['show_branding'] ?? true;
    @endphp
    <form method="POST" action="{{ route('dashboard.widget-appearance.save') }}" enctype="multipart/form-data">
        @csrf
        <div class="card" style="margin-bottom:12px;">
            <h3 style="margin-bottom:4px;">Widget Appearance</h3>
            <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">Customize how the booking widget looks on your website. Changes apply everywhere it's embedded.</p>

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

            <div id="wc-color-row" style="{{ $wcBgType==='color'?'':'display:none;' }}margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Background Color</label>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="color" id="wc-bg-color" name="bg_color" value="{{ $wcBgColor }}"
                           style="width:40px;height:36px;border:1px solid var(--border);border-radius:7px;padding:2px;cursor:pointer;" />
                    <input type="text" value="{{ $wcBgColor }}" class="form-control" style="max-width:120px;font-family:monospace;font-size:13px;"
                           oninput="document.getElementById('wc-bg-color').value=this.value" />
                </div>
            </div>

            <div id="wc-image-row" style="{{ $wcBgType==='image'?'':'display:none;' }}margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Background Image</label>
                @if(!empty($wcBgImage))
                    <div style="margin-bottom:10px;border-radius:8px;overflow:hidden;height:80px;background:url('{{ $wcBgImage }}') center/cover;">
                        <div style="background:rgba(0,0,0,.35);height:100%;display:flex;align-items:center;justify-content:center;">
                            <span style="color:#fff;font-size:11px;font-weight:600;">Current image</span>
                        </div>
                    </div>
                @endif
                <div style="border:2px dashed var(--border);border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:all .2s;"
                     id="wc-drop-zone"
                     ondragover="event.preventDefault();this.style.borderColor='var(--accent)'"
                     ondragleave="this.style.borderColor='var(--border)'"
                     ondrop="handleImageDrop(event)">
                    <div style="font-size:24px;margin-bottom:6px;">🖼</div>
                    <div style="font-size:13px;font-weight:600;color:var(--ink);">Drop image here or <label for="wc-bg-file" style="color:var(--accent);cursor:pointer;text-decoration:underline;">browse</label></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:4px;">JPG, PNG, WebP — max 2MB. Stored on your server.</div>
                    <input type="file" id="wc-bg-file" name="bg_image_file" accept="image/jpeg,image/png,image/webp"
                           style="display:none;" onchange="previewImage(this)" />
                </div>
                <div id="wc-img-preview" style="display:none;margin-top:10px;border-radius:8px;overflow:hidden;height:80px;position:relative;">
                    <img id="wc-img-preview-img" style="width:100%;height:100%;object-fit:cover;" />
                    <button type="button" onclick="clearImagePreview()"
                            style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.5);border:none;color:#fff;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:14px;line-height:1;">✕</button>
                </div>
                {{-- Keep existing URL as fallback --}}
                <input type="hidden" name="bg_image_url" id="wc-bg-image-url" value="{{ $wcBgImage }}" />
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Accent Color</label>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <input type="color" id="wc-accent" name="accent_color" value="{{ $wcAccent }}"
                               style="width:40px;height:36px;border:1px solid var(--border);border-radius:7px;padding:2px;cursor:pointer;" />
                        <input type="text" value="{{ $wcAccent }}" class="form-control" style="max-width:100px;font-family:monospace;font-size:13px;"
                               oninput="document.getElementById('wc-accent').value=this.value" />
                    </div>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Corner Radius</label>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="range" name="border_radius" min="0" max="28" value="{{ $wcRadius }}"
                               oninput="document.getElementById('wc-radius-val').textContent=this.value+'px'" style="flex:1;" />
                        <span id="wc-radius-val" style="font-size:13px;font-weight:600;min-width:36px;">{{ $wcRadius }}px</span>
                    </div>
                </div>
            </div>

            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;padding:12px 14px;background:var(--soft);border-radius:8px;border:1px solid var(--border);">
                <input type="hidden" name="show_branding" value="0" />
                <input type="checkbox" name="show_branding" value="1" {{ $wcBranding?'checked':'' }}
                       style="width:15px;height:15px;accent-color:var(--accent);margin-top:2px;cursor:pointer;flex-shrink:0;" />
                <div>
                    <div style="font-size:13px;font-weight:600;">Show "Powered by BookInStack"</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">Shown at the bottom of your widget</div>
                </div>
            </label>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save Appearance</button>
    </form>
</div>

{{-- ══ TAB 6 — ALLOWED DOMAINS ══════════════════════════════════════════════ --}}
<div id="panel-domains" style="display:none;max-width:520px;">

    <div class="card" style="margin-bottom:12px;">
        <h3 style="margin-bottom:4px;">Allowed Domains</h3>

        <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">
            Only these domains can load your booking widget.
            Add domains where your widget will be embedded.
        </p>

        {{-- Add Domain --}}
        <form method="POST" action="{{ route('dashboard.domains.store') }}" style="display:flex;gap:8px;margin-bottom:16px;">
            @csrf

            <input
                type="text"
                name="domain"
                class="form-control"
                placeholder="https://example.com"
                required
            />

            <button type="submit" class="btn btn-primary btn-sm">
                Add
            </button>
        </form>

        {{-- Domain List --}}
        @php
            $domains = $developer->allowed_domains ?? [];
        @endphp

        @if(empty($domains))
            <div style="padding:16px;background:var(--soft);border-radius:8px;font-size:13px;color:var(--muted);">
                No domains added yet.
            </div>
        @else
            <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden;">
                @foreach($domains as $domain)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--border);">
                        <span style="font-size:13px;font-weight:500;">
                            {{ $domain }}
                        </span>

                        <form method="POST" action="{{ route('dashboard.domains.delete') }}">
                            @csrf
                            @method('DELETE')

                            <input type="hidden" name="domain" value="{{ $domain }}">

                            <button
                                type="submit"
                                style="border:none;background:none;color:#ef4444;cursor:pointer;font-size:14px;"
                                onclick="return confirm('Remove domain?')"
                            >
                                ✕
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

{{-- ══ TAB 7 — SMS NOTIFICATION NUMBER ══════════════════════════════════════ --}}
<div id="panel-sms" style="display:none;max-width:520px;">
    <div class="card">
        <h3 style="margin-bottom:4px;">📱 SMS Credit Alerts</h3>
        <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.5;">
            Set the mobile number that receives credit alert SMS notifications
            when a booking payment is confirmed.
        </p>

        @if(! $developer->bvn_verified)
            {{-- ── BVN gate ─────────────────────────────────────────── --}}
            <div style="
                display:flex;flex-direction:column;align-items:center;
                gap:12px;padding:28px 20px;text-align:center;
                background:var(--soft);border-radius:10px;
                border:1px dashed var(--border);
            ">
                <span style="font-size:32px;">🔒</span>
                <p style="font-size:14px;font-weight:600;margin:0;">BVN Verification Required</p>
                <p style="font-size:13px;color:var(--muted);margin:0;line-height:1.5;">
                    You need to verify your BVN before you can set an SMS
                    notification number.
                </p>
                <a href="{{ route('dashboard.bvn') }}" class="btn btn-primary btn-sm">
                    Verify BVN →
                </a>
            </div>

        @else

            {{-- ── Current number badge ───────────────────────────── --}}
            @if($developer->sms_number)
                <div style="
                    display:flex;align-items:center;gap:10px;
                    padding:10px 14px;background:var(--soft);
                    border-radius:8px;margin-bottom:16px;
                    border:1px solid var(--border);
                ">
                    <span style="font-size:18px;">📲</span>
                    <div>
                        <div style="font-size:11px;color:var(--muted);">Current SMS number</div>
                        <div style="font-size:14px;font-weight:600;">{{ $developer->sms_number }}</div>
                    </div>
                </div>
            @endif

            {{-- ── Form ───────────────────────────────────────────── --}}
            <form method="POST" action="{{ route('dashboard.sms.update') }}">
                @csrf

                <div style="margin-bottom:16px;">
                    <label style="
                        display:block;font-size:13px;
                        font-weight:600;margin-bottom:6px;
                    ">
                        Mobile Number
                    </label>

                    <input
                        type="tel"
                        name="sms_number"
                        class="form-control @error('sms_number') is-invalid @enderror"
                        placeholder="e.g. 08012345678"
                        value="{{ old('sms_number', $developer->sms_number) }}"
                        maxlength="14"
                        style="max-width:100%;"
                    />

                    @error('sms_number')
                        <div style="font-size:12px;color:#ef4444;margin-top:4px;">
                            {{ $message }}
                        </div>
                    @enderror

                    <p style="font-size:12px;color:var(--muted);margin-top:6px;">
                        Accepts formats: <code>08012345678</code> or <code>2348012345678</code>
                    </p>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width:100%;"
                >
                    💾 Save SMS Number
                </button>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
const TABS = ['mode','window','negotiate','categories','appearance','domains','sms'];

function switchTab(id) {
    TABS.forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const tab   = document.getElementById('tab-'   + t);
        if (panel) panel.style.display       = t === id ? 'block' : 'none';
        if (tab)   tab.style.borderBottomColor = t === id ? 'var(--accent)' : 'transparent';
        if (tab)   tab.style.color             = t === id ? 'var(--accent)' : 'var(--muted)';
    });
}

const MODE_CFGS = {
    appointment: { cta:'Book Appointment', amount:'Service Fee',    attend:'Attended'    },
    ticket:      { cta:'Buy Ticket',       amount:'Ticket Price',   attend:'Checked In'  },
    reservation: { cta:'Reserve Now',      amount:'Rate per Night', attend:'Checked Out' },
};

function selectMode(v) {
    ['appointment','ticket','reservation'].forEach(m => {
        const c = document.getElementById('card-' + m), sel = m === v;
        if (c) { c.style.borderColor = sel?'var(--accent)':'var(--border)'; c.style.background = sel?'var(--accent-light)':'#fafafa'; }
        const r = document.getElementById('mode-' + m); if (r) r.checked = sel;
    });
    const cfg = MODE_CFGS[v]; if (!cfg) return;
    document.getElementById('sum-cta').textContent    = cfg.cta;
    document.getElementById('sum-amount').textContent = cfg.amount;
    document.getElementById('sum-attend').textContent = cfg.attend;
    const uc = document.getElementById('reservation-unit-card');
    if (uc) uc.style.display = v === 'reservation' ? 'block' : 'none';
    if (v === 'reservation') updateUnitSummary();
}

function selectUnit(u) {
    ['night','day'].forEach(x => {
        const l = document.getElementById('unit-' + x), sel = x === u;
        if (l) { l.style.borderColor = sel?'var(--accent)':'var(--border)'; l.style.background = sel?'var(--accent-light)':'#fff'; }
        const r = l?.querySelector('input'); if (r) r.checked = sel;
    });
    updateUnitSummary();
}

function updateUnitSummary() {
    const d = document.querySelector('input[name="reservation_unit"]:checked')?.value === 'day';
    document.getElementById('sum-amount').textContent = d ? 'Rate per Day'  : 'Rate per Night';
    document.getElementById('sum-cta').textContent    = d ? 'Book Now'      : 'Reserve Now';
    document.getElementById('sum-attend').textContent = d ? 'Event Done'    : 'Checked Out';
}

function toggleDay(day, checked) {
    const l = document.getElementById('day-' + day); if (!l) return;
    l.style.background  = checked ? 'var(--accent)' : '#fff';
    l.style.color       = checked ? '#fff' : 'var(--muted)';
    l.style.borderColor = checked ? 'var(--accent)' : 'var(--border)';
}

function toggleAddForm() {
    const w = document.getElementById('cat-form-wrap');
    w.style.display === 'none' ? openAddForm() : closeForm();
}
function openAddForm() {
    closeAllEditForms();
    document.getElementById('cat-create-form').style.display = 'block';
    document.getElementById('cat-form-title').textContent    = 'New Category';
    document.getElementById('cat-form-wrap').style.display   = 'block';
    document.getElementById('add-cat-btn').textContent       = '✕ Cancel';
    document.getElementById('cat-form-wrap').scrollIntoView({ behavior:'smooth', block:'nearest' });
}
function openEditForm(id) {
    closeAllEditForms();
    document.getElementById('cat-create-form').style.display = 'none';
    document.getElementById('cat-form-title').textContent    = 'Edit Category';
    const ef = document.getElementById('cat-edit-form-' + id);
    if (ef) ef.style.display = 'block';
    document.getElementById('cat-form-wrap').style.display   = 'block';
    document.getElementById('cat-form-wrap').scrollIntoView({ behavior:'smooth', block:'nearest' });
}
function closeForm() {
    document.getElementById('cat-form-wrap').style.display = 'none';
    document.getElementById('add-cat-btn').textContent     = '+ Add Category';
    closeAllEditForms();
}
function closeAllEditForms() { document.querySelectorAll('[id^="cat-edit-form-"]').forEach(f => f.style.display='none'); }
function toggleChildPrice(cb, s) { const w=document.getElementById('child-price-wrap'+s); if(w) w.style.display=cb.checked?'block':'none'; }
function toggleFixedPrice(cb, s) { const r=document.getElementById('price-range-wrap'+s),p=document.getElementById('price-input'+s); if(r) r.style.display=cb.checked?'none':'block'; if(p) p.required=cb.checked; }

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

function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 2 * 1024 * 1024) {
        alert('Image must be under 2MB.');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('wc-img-preview-img').src = e.target.result;
        document.getElementById('wc-img-preview').style.display = 'block';
        document.getElementById('wc-drop-zone').style.display   = 'none';
    };
    reader.readAsDataURL(file);
}

function handleImageDrop(event) {
    event.preventDefault();
    document.getElementById('wc-drop-zone').style.borderColor = 'var(--border)';
    const file = event.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    const input = document.getElementById('wc-bg-file');
    input.files = dt.files;
    previewImage(input);
}

function clearImagePreview() {
    document.getElementById('wc-bg-file').value    = '';
    document.getElementById('wc-img-preview').style.display  = 'none';
    document.getElementById('wc-drop-zone').style.display    = 'block';
    document.getElementById('wc-bg-image-url').value         = '';
}

const hash = location.hash.replace('#','');
if (TABS.includes(hash)) switchTab(hash);
</script>
@endpush

@endsection
