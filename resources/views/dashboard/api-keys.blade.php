@extends('layouts.app')
@section('title', 'API Keys')
@section('page-title', 'API Keys')

@section('content')
{{-- ── Per-Service Public Keys ──────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;">
    <div style="font-size:13px;font-weight:700;color:var(--ink);margin-bottom:4px;">Service Public Keys</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:20px;">
        Each service has its own public key. Use the key matching the service you're embedding.
    </div>

    @forelse($services as $service)
        <div style="padding:16px;background:var(--soft);border-radius:10px;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
                <span style="font-weight:600;font-size:14px;">{{ $service->name }}</span>
                <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;
                    background:{{ $service->modeBadgeColor() }}1a;color:{{ $service->modeBadgeColor() }};">
                    {{ $service->modeLabel() }}
                </span>
                @if($developer->active_service_id === $service->id)
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;
                        background:var(--accent-light);color:var(--accent);font-weight:600;">Active</span>
                @endif
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <code style="flex:1;font-size:12px;padding:9px 12px;background:#fff;
                             border:1px solid var(--border);border-radius:7px;
                             color:var(--accent);word-break:break-all;min-width:0;">
                    {{ $service->public_key ?? 'Not generated yet' }}
                </code>

                <button onclick="copyKey('{{ $service->public_key }}', this)"
                        style="padding:8px 12px;border:1px solid var(--border);border-radius:7px;
                               background:#fff;cursor:pointer;font-size:12px;white-space:nowrap;
                               font-family:inherit;color:var(--ink);">
                    Copy
                </button>


                    <form method="POST"
                          action="{{ route('api-keys.service.regenerate', $service) }}"
                          onsubmit="return confirm('Regenerate key for \'{{ addslashes($service->name) }}\'? Existing widgets using the old key will stop working.')">
                        @csrf
                        <button type="submit"
                                style="padding:8px 12px;border:1px solid #fecaca;border-radius:7px;
                                       background:#fff;cursor:pointer;font-size:12px;color:#ef4444;
                                       white-space:nowrap;font-family:inherit;">
                            Regenerate
                        </button>
                    </form>

            </div>
        </div>
    @empty
        <div style="text-align:center;padding:32px;color:var(--muted);font-size:14px;">
            No services yet.
            <a href="{{ route('services.create') }}" style="color:var(--accent);">Create a service →</a>
        </div>
    @endforelse
</div>

@push('scripts')
<script>
function copyKey(key, btn) {
    if (!key) return;
    navigator.clipboard.writeText(key).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.color = 'var(--green)';
        setTimeout(() => { btn.textContent = orig; btn.style.color = ''; }, 2000);
    });
}
</script>
@endpush

@endsection
