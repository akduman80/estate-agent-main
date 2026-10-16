@props(['card'])

@if($card)
    <section class="listing-eids-card" aria-label="EİDS ilan yetkilendirmesi">
        <img class="listing-eids-card__logo" src="{{ asset('fe-assets/assets/img/eids-logo.jpg') }}" alt="EİDS — Elektronik İlan Doğrulama Sistemi" width="800" height="363" loading="lazy">
        <div class="listing-eids-card__text">
            <h2>{{ $card['title'] }}</h2>
            @if($card['description'])
                <p>{{ $card['description'] }}</p>
            @endif
        </div>
    </section>
@endif
