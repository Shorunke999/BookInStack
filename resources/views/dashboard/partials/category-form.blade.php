{{--
    Partial: category-form
    Variables: $category (null for create, model for edit), $mode (string)
--}}
@php
    $val = fn($field, $default='') => old($field, $category?->{$field} ?? $default);
    $suffix = $category ? '-' . $category->id : '-new';
@endphp

<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">

    {{-- Name --}}
    <div class="form-group" style="margin-bottom:0;">
        <label>
            @if($mode === 'ticket') Ticket Type Name
            @elseif($mode === 'reservation') Room / Space Name
            @else Service Name
            @endif
        </label>
        <input type="text" name="name" class="form-control"
               value="{{ $val('name') }}"
               placeholder="{{ match($mode) {
                   'ticket'      => 'e.g. VIP, Regular, Early Bird',
                   'reservation' => 'e.g. Standard Room, Deluxe Suite',
                   default       => 'e.g. Haircut, Consultation',
               } }}"
               required />
    </div>

    {{-- Price --}}
    <div class="form-group" style="margin-bottom:0;">
        <label>
            @if($mode === 'ticket') Adult Price (₦)
            @elseif($mode === 'reservation') Price per Night (₦)
            @else Price (₦)
            @endif
        </label>
        <input type="number" name="price" class="form-control"
               value="{{ $category ? $category->price : old('price', '') }}"
               placeholder="e.g. 5000"
               min="1" step="0.01" required />
        <span style="font-size:11px; color:var(--muted);">Enter in Naira </span>
    </div>

</div>

{{-- Description --}}
<div class="form-group" style="margin-top:12px; margin-bottom:0;">
    <label>Description <span style="font-weight:400; color:var(--muted);">(optional)</span></label>
    <input type="text" name="description" class="form-control"
           value="{{ $val('description') }}"
           placeholder="{{ match($mode) {
               'ticket'      => 'e.g. Front row access, free drinks',
               'reservation' => 'e.g. Sea view, king bed, en-suite',
               default       => 'e.g. 30 minute session including wash',
           } }}" />
</div>

{{-- Total slots / capacity limit --}}
<div class="form-group" style="margin-top:12px; margin-bottom:0;">
    <label>
        @if($mode === 'ticket') Total Tickets Available
        @elseif($mode === 'reservation') Total Rooms / Units Available
        @else Total Appointment Slots
        @endif
        <span style="font-weight:400; color:var(--muted);">(leave blank for unlimited)</span>
    </label>
    <input type="number" name="total_slots" class="form-control"
           value="{{ $val('total_slots') }}"
           placeholder="e.g. 200" min="1" style="max-width:200px;" />
    @if($category && $category->total_slots !== null)
        @php $booked = $category->slotsBooked(); $rem = $category->slotsRemaining(); @endphp
        <div style="margin-top:6px; font-size:12px; color:var(--muted);">
            {{ $booked }} booked · {{ $rem }} remaining
            <div style="margin-top:4px; height:5px; background:var(--border); border-radius:10px; max-width:200px; overflow:hidden;">
                <div style="height:100%; border-radius:10px;
                    width:{{ $category->total_slots > 0 ? round(($booked/$category->total_slots)*100) : 0 }}%;
                    background:{{ $rem === 0 ? '#ef4444' : ($rem <= ($category->total_slots * 0.2) ? '#f59e0b' : '#10b981') }};"></div>
            </div>
        </div>
    @endif
</div>

{{-- ── Mode-specific fields ────────────────────────────────────────────────── --}}

@if($mode === 'appointment')
    <div class="form-group" style="margin-top:12px; margin-bottom:0;">
        <label>Duration <span style="font-weight:400; color:var(--muted);">(minutes, optional)</span></label>
        <input type="number" name="duration_minutes" class="form-control"
               value="{{ $val('duration_minutes') }}"
               placeholder="e.g. 30" min="5" max="480" style="max-width:160px;" />
    </div>
@endif

@if($mode === 'reservation')
    <div class="form-group" style="margin-top:12px; margin-bottom:0;">
        <label>Max Guests <span style="font-weight:400; color:var(--muted);">(capacity, optional)</span></label>
        <input type="number" name="capacity" class="form-control"
               value="{{ $val('capacity') }}"
               placeholder="e.g. 2" min="1" style="max-width:160px;" />
    </div>
@endif

@if($mode === 'ticket')
    <div style="margin-top:12px; display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group" style="margin-bottom:0;">
            <label>Max per Order <span style="font-weight:400; color:var(--muted);">(optional)</span></label>
            <input type="number" name="max_per_order" class="form-control"
                   value="{{ $val('max_per_order') }}"
                   placeholder="e.g. 10" min="1" max="500" />
        </div>
    </div>

    {{-- Child pricing toggle --}}
    <div style="margin-top:14px; padding:12px 14px; background:#f8fafc; border:1px solid var(--border); border-radius:8px;">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:500;">
            <input type="hidden" name="enable_child_pricing" value="0" />
            <input type="checkbox" name="enable_child_pricing" value="1"
                   id="enable-child{{ $suffix }}"
                   {{ $val('enable_child_pricing', false) ? 'checked' : '' }}
                   onchange="toggleChildPrice(this, '{{ $suffix }}')"
                   style="width:15px; height:15px; accent-color:var(--accent);" />
            Enable separate child pricing
        </label>

        <div id="child-price-wrap{{ $suffix }}"
             style="{{ $val('enable_child_pricing', false) ? '' : 'display:none;' }} margin-top:10px;">
            <div class="form-group" style="margin-bottom:0;">
                <label>Child Price (₦)</label>
                <input type="number" name="child_price" class="form-control"
                       value="{{ $category ? ($category->child_price ? $category->child_price / 100 : '') : old('child_price', '') }}"
                       placeholder="e.g. 2500" min="0" step="0.01" style="max-width:200px;" />
            </div>
        </div>
    </div>
@endif
