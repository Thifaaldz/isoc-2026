<div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
    @foreach ($panels as $p)
        <a href="{{ $p['url'] }}"
           style="display:block;padding:20px;border-radius:12px;border:1px solid {{ $p['mine'] ? '#2563eb' : '#e5e7eb' }};background:{{ $p['mine'] ? '#eff6ff' : '#fff' }};text-decoration:none;color:#111827">
            <strong>Panel {{ $p['label'] }}</strong>
            <div style="font-size:13px;color:#6b7280;margin-top:4px">{{ $p['mine'] ? 'Akun Anda' : 'Masuk' }} &rarr; {{ $p['url'] }}</div>
        </a>
    @endforeach
</div>
