{{-- resources/views/components/pagination.blade.php --}}
@if ($paginator->hasPages())
    <nav style="display:flex; align-items:center; justify-content:space-between; font-size:13px;">

        <div style="color:var(--muted);">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of {{ $paginator->total() }} results
        </div>

        <div style="display:flex; gap:4px;">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span style="padding:6px 12px; border:1px solid var(--border); border-radius:6px; color:#c4c9d4; cursor:not-allowed;">← Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   style="padding:6px 12px; border:1px solid var(--border); border-radius:6px; color:var(--ink); text-decoration:none; transition:background .15s;"
                   onmouseover="this.style.background='var(--soft)'" onmouseout="this.style.background=''">
                    ← Prev
                </a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span style="padding:6px 8px; color:var(--muted);">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span style="padding:6px 12px; background:var(--accent); color:#fff; border-radius:6px; font-weight:600;">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                               style="padding:6px 12px; border:1px solid var(--border); border-radius:6px; color:var(--ink); text-decoration:none; transition:background .15s;"
                               onmouseover="this.style.background='var(--soft)'" onmouseout="this.style.background=''">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   style="padding:6px 12px; border:1px solid var(--border); border-radius:6px; color:var(--ink); text-decoration:none; transition:background .15s;"
                   onmouseover="this.style.background='var(--soft)'" onmouseout="this.style.background=''">
                    Next →
                </a>
            @else
                <span style="padding:6px 12px; border:1px solid var(--border); border-radius:6px; color:#c4c9d4; cursor:not-allowed;">Next →</span>
            @endif

        </div>
    </nav>
@endif
