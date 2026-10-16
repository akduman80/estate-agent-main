@php($prayers = app(\App\Services\OsmaniyePrayerTimesService::class)->today())
<section class="home-welcome home-welcome--prayers" aria-label="Hoş geldiniz">
    <img class="home-welcome__architecture home-welcome__architecture--left" src="{{ asset('images/welcome/house-left.svg') }}" width="420" height="150" alt="" aria-hidden="true">
    <div class="home-welcome__copy">
        <p class="home-welcome__title">HAYIRLA GELDİNİZ</p>
        <p class="home-welcome__subtitle">Yeni başlangıçlara, hayırlı yuvalara…</p>
    </div>
    <img class="home-welcome__architecture home-welcome__architecture--right" src="{{ asset('images/welcome/house-right.svg') }}" width="420" height="150" alt="" aria-hidden="true">
    <aside class="home-prayers" aria-label="Osmaniye Merkez namaz vakitleri" data-prayer-date="{{ $prayers['date'] }}">
        <div class="home-prayers__head">
            <strong><span aria-hidden="true">☾</span> OSMANİYE · Namaz Vakitleri</strong>
            <time datetime="{{ $prayers['date'] }}">{{ $prayers['label'] }}</time>
        </div>
        @if($prayers['times'])
            <div class="home-prayers__times">
                @foreach($prayers['times'] as $name => $value)
                    <div class="home-prayers__time"><span>{{ $name }}</span><b>{{ $value }}</b></div>
                @endforeach
            </div>
        @else
            <p class="home-prayers__unavailable">Güncel vakitler şu anda alınamıyor.</p>
        @endif
        <a class="home-prayers__source" href="{{ \App\Services\OsmaniyePrayerTimesService::SOURCE_URL }}" target="_blank" rel="noopener noreferrer" aria-label="Diyanet Osmaniye Merkez namaz vakitlerini aç">Diyanet ↗</a>
    </aside>
</section>
<script>
    // Reload an open page at the Istanbul date change; hide old values first.
    (() => {
        const panel = document.querySelector('[data-prayer-date]');
        const checkDate = () => {
            const parts = new Intl.DateTimeFormat('en-US', { timeZone: 'Europe/Istanbul', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
            const value = type => parts.find(part => part.type === type).value;
            if (panel && `${value('year')}-${value('month')}-${value('day')}` !== panel.dataset.prayerDate) {
                panel.querySelector('.home-prayers__times')?.remove();
                window.location.reload();
            }
        };
        window.setInterval(checkDate, 60000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) checkDate(); });
    })();
</script>
