<dialog id="visitor-poll" aria-labelledby="visitor-poll-question" data-endpoint="{{ route('polls.active') }}" data-csrf="{{ csrf_token() }}">
    <button type="button" class="poll-close" aria-label="Anketi kapat">×</button>
    <p class="poll-eyebrow">GÖRÜŞÜNÜZ BİZİM İÇİN DEĞERLİ</p>
    <h2 id="visitor-poll-question"></h2>
    <form>
        <fieldset aria-labelledby="visitor-poll-question"></fieldset>
        <p class="poll-message" role="status" aria-live="polite"></p>
        <div class="poll-actions">
            <button type="button" class="poll-later">Daha Sonra</button>
            <button type="submit" class="poll-submit" disabled>Oy Ver</button>
        </div>
    </form>
</dialog>
<style>
#visitor-poll *{box-sizing:border-box}
#visitor-poll{box-sizing:border-box;width:400px;max-width:calc(100vw - 32px);max-height:calc(100dvh - 32px);margin:auto;padding:28px 22px 22px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;color:#172554;overflow:auto;box-shadow:0 20px 60px #0f172a40;font-family:inherit}
#visitor-poll::backdrop{background:#0f172a55}
#visitor-poll .poll-close{display:block;margin:-16px -10px 4px auto;width:44px;height:44px;font-size:28px;border:0;background:transparent;cursor:pointer;color:#475569}
#visitor-poll .poll-eyebrow{font-size:11px;font-weight:800;letter-spacing:.08em;color:#c2410c;margin:0 0 10px}
#visitor-poll h2{font-size:20px;font-weight:800;line-height:1.4;overflow-wrap:anywhere;margin:0 0 20px}
#visitor-poll fieldset{padding:0;margin:0;border:0;min-width:0;display:grid;gap:10px}
#visitor-poll label{display:flex;align-items:center;gap:12px;min-height:48px;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:15px;line-height:1.5;cursor:pointer;overflow-wrap:anywhere}
#visitor-poll label:has(input:checked){background:#fff7ed;border-color:#f97316}
#visitor-poll input{flex-shrink:0;accent-color:#ea580c;width:18px;height:18px}
#visitor-poll .poll-actions{display:flex;gap:10px;margin-top:16px}
#visitor-poll .poll-actions button{flex:1;min-height:46px;padding:10px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:1px solid #cbd5e1;background:#f8fafc;color:#172554}
#visitor-poll .poll-actions .poll-submit{background:#ea580c;color:#fff;border-color:#ea580c}
#visitor-poll button:disabled{opacity:.5;cursor:wait}
#visitor-poll :focus-visible{outline:3px solid #2563eb;outline-offset:3px}
#visitor-poll .poll-message{font-size:14px;line-height:1.5;color:#9a3412;margin:12px 0 0}
</style>
<script src="{{ asset('js/visitor-poll.js') }}?v={{ filemtime(public_path('js/visitor-poll.js')) }}" defer></script>
