{{--
    components/service-switcher.blade.php
    Drop this inside the topbar-right div in layouts/app.blade.php.
    Requires $allServices and $service to be passed from the controller,
    OR use a View Composer to inject them globally (recommended — see note below).

    Usage in layout:
        @include('components.service-switcher')
--}}

@php
    $currentService = $service ?? null;
    $services       = $allServices ?? collect();
@endphp

@if($services->count() > 1 && $currentService)
<div style="position:relative;" id="svc-switcher-wrap">

    <button onclick="toggleSvcMenu()" id="svc-switcher-btn"
            style="display:flex;align-items:center;gap:7px;padding:6px 12px;
                border-radius:8px;border:1px solid var(--border);background:#fff;
                cursor:pointer;font-size:13px;font-weight:500;color:var(--ink);
                font-family:'DM Sans',sans-serif;max-width:200px;">

        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;flex-shrink:0;
            background:{{ $currentService->modeBadgeColor() }};"></span>

        <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;text-align:left;">
            {{ $currentService->name }}
        </span>

        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" style="flex-shrink:0;opacity:.5;">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>

    <div id="svc-switcher-menu"
         style="display:none;position:absolute;top:calc(100% + 6px);right:0;
             background:#fff;border:1px solid var(--border);border-radius:10px;
             box-shadow:0 8px 24px rgba(0,0,0,.1);min-width:220px;z-index:100;
             padding:6px;overflow:hidden;">

        <div style="font-size:10px;font-weight:700;text-transform:uppercase;
                    letter-spacing:.08em;color:var(--muted);padding:6px 10px 4px;">
            Switch Service
        </div>

        @foreach($services as $svc)
            @php $isSelected = $svc->id === $currentService->id; @endphp
            <form method="POST" action="{{ route('services.activate', $svc) }}">
                @csrf
                <button type="submit"
                        style="width:100%;display:flex;align-items:center;gap:10px;
                            padding:9px 10px;border-radius:7px;border:none;
                            background:{{ $isSelected ? 'var(--accent-light)' : 'transparent' }};
                            cursor:pointer;font-family:'DM Sans',sans-serif;font-size:13px;
                            color:{{ $isSelected ? 'var(--accent)' : 'var(--ink)' }};
                            font-weight:{{ $isSelected ? '600' : '400' }};text-align:left;">

                    <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;
                        background:{{ $svc->modeBadgeColor() }};display:inline-block;"></span>

                    <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $svc->name }}
                    </span>

                    <span style="font-size:10px;opacity:.6;flex-shrink:0;">{{ $svc->modeLabel() }}</span>

                    @if($isSelected)
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="3" style="flex-shrink:0;">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    @endif
                </button>
            </form>
        @endforeach

        <div style="border-top:1px solid var(--border);margin-top:4px;padding-top:4px;">
            <a href="{{ route('services.index') }}"
               style="display:flex;align-items:center;gap:8px;padding:8px 10px;
                   border-radius:7px;font-size:13px;color:var(--muted);
                   text-decoration:none;font-weight:500;"
               onmouseover="this.style.background='var(--soft)'"
               onmouseout="this.style.background='transparent'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Manage Services
            </a>
        </div>
    </div>
</div>

<script>
function toggleSvcMenu() {
    const menu = document.getElementById('svc-switcher-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('svc-switcher-wrap');
    if (wrap && !wrap.contains(e.target)) {
        const menu = document.getElementById('svc-switcher-menu');
        if (menu) menu.style.display = 'none';
    }
});
</script>

@elseif($currentService)
    {{-- Only one service — just show the name as a plain badge --}}
    <span style="font-size:12px;padding:4px 10px;border-radius:20px;
        background:{{ $currentService->modeBadgeColor() }}1a;
        color:{{ $currentService->modeBadgeColor() }};font-weight:600;">
        {{ $currentService->name }}
    </span>
@endif