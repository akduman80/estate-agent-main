@php
    $prayers = app(\App\Services\OsmaniyePrayerTimesService::class)->today();
    $weather = app(\App\Services\OsmaniyeWeatherService::class)->current();
    $tomorrow = app(\App\Services\OsmaniyePrayerTimesService::class)->today(1);
    $schedule = [];
    foreach ([$prayers, $tomorrow] as $day) {
        foreach ($day['times'] as $name => $value) {
            if ($name !== 'Güneş') $schedule[] = ['name' => $name, 'time' => $value, 'at' => $day['date'].'T'.$value.':00+03:00', 'date' => $day['date']];
        }
    }
    $next = collect($schedule)->first(fn ($item) => strtotime($item['at']) > time());
@endphp
<section class="home-welcome home-welcome--prayers" aria-label="Hoş geldiniz">
    <img class="home-welcome__architecture home-welcome__architecture--left" src="{{ asset('images/welcome/house-left.svg') }}" width="420" height="150" alt="" aria-hidden="true">
    <div class="home-welcome__copy">
        <p class="home-welcome__title">HAYIRLA GELDİNİZ</p>
        <p class="home-welcome__subtitle">Yeni başlangıçlara, hayırlı yuvalara…</p>
    </div>
    <img class="home-welcome__architecture home-welcome__architecture--right" src="{{ asset('images/welcome/house-right.svg') }}" width="420" height="150" alt="" aria-hidden="true">
    <div class="home-local-info">
    <div class="home-local-info__heading"><strong>OSMANİYE NAMAZ VE HAVA DURUMU</strong><time datetime="{{ $prayers['date'] }}">{{ $prayers['label'] }} · Merkez</time></div>
    <aside class="home-prayers" aria-label="Osmaniye Merkez namaz vakitleri" data-prayer-date="{{ $prayers['date'] }}">
        <div class="home-prayers__head">
            <strong><span aria-hidden="true">☾</span> OSMANİYE · Namaz Vakitleri</strong>
            <time datetime="{{ $prayers['date'] }}">{{ $prayers['label'] }}</time>
        </div>
        @if($prayers['times'])
            <details class="home-next-prayer" open>
                <summary><span>Sıradaki namaz</span><strong data-next-name>{{ $next['name'] ?? 'Vakit bekleniyor' }}</strong><b data-next-time>{{ $next['time'] ?? '—' }}</b><small data-next-day>{{ $next && $next['date'] !== $prayers['date'] ? 'Yarın · ' : '' }}Tüm vakitler ⌄</small></summary>
            <div class="home-prayers__times">
                @foreach($prayers['times'] as $name => $value)
                    <div class="home-prayers__time"><span>{{ $name }}</span><b>{{ $value }}</b></div>
                @endforeach
            </div>
            </details>
        @else
            <p class="home-prayers__unavailable">Güncel vakitler şu anda alınamıyor.</p>
        @endif
        <a class="home-prayers__source" href="{{ \App\Services\OsmaniyePrayerTimesService::SOURCE_URL }}" target="_blank" rel="noopener noreferrer" aria-label="Diyanet Osmaniye Merkez namaz vakitlerini aç">Diyanet ↗</a>
    </aside>
    <aside class="home-weather" aria-label="Osmaniye Merkez hava durumu">
        <div class="home-weather__head"><strong>OSMANİYE · Hava Durumu</strong><small>Merkez</small></div>
        @if($weather)
            <div class="home-weather__main"><i class="fa-solid {{ match ($weather['label']) { 'Yağmurlu' => 'fa-cloud-rain', 'Kar yağışlı' => 'fa-snowflake', 'Gök gürültülü' => 'fa-cloud-bolt', 'Açık' => 'fa-sun', 'Sisli' => 'fa-smog', default => 'fa-cloud-sun' } }} home-weather__icon" aria-hidden="true"></i><div><div class="home-weather__temp">{{ $weather['temperature'] }}°<span>C</span></div><div class="home-weather__condition">{{ $weather['label'] }}</div></div></div>
            <div class="home-weather__details"><span>Nem <b>%{{ $weather['humidity'] }}</b></span><span>Rüzgâr <b>{{ $weather['wind'] }} km/sa</b></span></div>
            <p class="home-weather__note">Tahmin · {{ \Carbon\Carbon::parse($weather['time'])->timezone('Europe/Istanbul')->format('H:i') }}</p>
        @else
            <p class="home-prayers__unavailable">Güncel hava durumu alınamıyor.</p>
        @endif
        <div class="home-weather__credit"><a href="https://www.met.no/" target="_blank" rel="noopener noreferrer">MET Norway</a> · <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer">CC BY 4.0</a></div>
    </aside>
    </div>
</section>
<script>
    // Reload an open page at the Istanbul date change; hide old values first.
    (() => {
        const panel = document.querySelector('[data-prayer-date]');
        const schedule = {{ \Illuminate\Support\Js::from($schedule) }};
        const details = panel?.querySelector('.home-next-prayer');
        const desktop = matchMedia('(min-width: 1280px)');
        if (details) {
            details.open = desktop.matches;
            desktop.addEventListener('change', event => { details.open = event.matches; });
        }
        if (details && matchMedia('(hover: hover)').matches) {
            details.addEventListener('mouseenter', () => { if (innerWidth < 1280) details.open = true; });
            details.addEventListener('mouseleave', () => { if (innerWidth < 1280) details.open = false; });
        }
        const updateNext = () => {
            const next = schedule.find(item => Date.parse(item.at) > Date.now());
            if (!details) return;
            details.querySelector('[data-next-name]').textContent = next?.name || 'Vakit bekleniyor';
            details.querySelector('[data-next-time]').textContent = next?.time || '—';
            details.querySelector('[data-next-day]').textContent = (next && next.date !== panel.dataset.prayerDate ? 'Yarın · ' : '') + 'Tüm vakitler ⌄';
        };
        updateNext();
        const checkDate = () => {
            const parts = new Intl.DateTimeFormat('en-US', { timeZone: 'Europe/Istanbul', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
            const value = type => parts.find(part => part.type === type).value;
            if (panel && `${value('year')}-${value('month')}-${value('day')}` !== panel.dataset.prayerDate) {
                panel.querySelector('.home-prayers__times')?.remove();
                window.location.reload();
            }
        };
        window.setInterval(() => { checkDate(); updateNext(); }, 30000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) { checkDate(); updateNext(); } });
    })();
</script>
