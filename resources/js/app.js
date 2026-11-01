import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-mobile-menu-button]');
    const menu = document.querySelector('[data-mobile-menu]');

    button?.addEventListener('click', () => {
        menu?.classList.toggle('hidden');
        button.setAttribute('aria-expanded', String(! menu?.classList.contains('hidden')));
    });

    document.querySelectorAll('[data-home-print-ad]').forEach((ad) => {
        const slides = [...ad.querySelectorAll('img')];
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        if (slides.length === 0) {
            console.error('Reklam görseli bulunamadı.');
            return;
        }

        let activeIndex = 0;
        let timer;
        let transitioning = false;

        const updateLink = () => {
            const activeSlide = slides[activeIndex];
            const destination = new URL(activeSlide.dataset.destination || 'https://wa.me/905303607769');
            ad.href = destination.href;
            ad.setAttribute('aria-label', activeSlide.dataset.label);
        };

        const stopRotation = () => {
            window.clearTimeout(timer);
            timer = undefined;
        };

        const startRotation = () => {
            stopRotation();

            if (slides.length < 2 || transitioning || reducedMotion.matches || document.hidden || ad.matches(':hover') || ad.contains(document.activeElement)) {
                return;
            }

            timer = window.setTimeout(() => {
                const nextIndex = (activeIndex + 1) % slides.length;
                const nextSlide = slides[nextIndex];

                if (! nextSlide.complete || ! nextSlide.naturalWidth) {
                    console.error(`Ana sayfa reklamı yüklenemedi: ${nextSlide.currentSrc}`);
                    startRotation();
                    return;
                }

                const previousSlide = slides[activeIndex];
                // Keep an opaque image underneath the incoming slide throughout the fade.
                previousSlide.classList.add('is-previous');
                previousSlide.setAttribute('aria-hidden', 'true');
                activeIndex = nextIndex;
                transitioning = true;
                slides[activeIndex].classList.add('is-visible');
                slides[activeIndex].removeAttribute('aria-hidden');
                updateLink();
                window.setTimeout(() => {
                    previousSlide.classList.remove('is-visible', 'is-previous');
                    transitioning = false;
                    startRotation();
                }, reducedMotion.matches ? 0 : 640);
            }, 5000);
        };

        updateLink();
        startRotation();
        ad.addEventListener('pointerenter', stopRotation);
        ad.addEventListener('pointerleave', startRotation);
        ad.addEventListener('focusin', stopRotation);
        ad.addEventListener('focusout', (event) => {
            if (! ad.contains(event.relatedTarget)) startRotation();
        });
        document.addEventListener('visibilitychange', startRotation);
        reducedMotion.addEventListener('change', startRotation);
    });

    document.querySelectorAll('[data-project-card-gallery]').forEach((gallery) => {
        const track = gallery.querySelector('[data-project-card-track]');
        const slides = track ? [...track.children] : [];
        const counter = gallery.querySelector('[data-project-card-counter]');

        if (! track || slides.length < 2) return;

        let currentIndex = 0;
        let scrollFrame = null;

        const syncVideo = () => {
            slides.forEach((slide, index) => {
                const video = slide.querySelector('[data-project-card-video]');
                if (! video) return;

                const active = index === currentIndex;
                if (video.tagName === 'VIDEO' && ! active) video.pause();
                if (video.tagName === 'IFRAME') {
                    if (active && ! video.src) video.src = video.dataset.videoSrc;
                    if (! active && video.src) video.removeAttribute('src');
                }
            });
        };

        const updateCounter = () => {
            currentIndex = Math.max(0, Math.min(slides.length - 1, Math.round(track.scrollLeft / track.clientWidth)));
            if (counter) counter.textContent = `${currentIndex + 1} / ${slides.length}`;
            syncVideo();
        };

        const showSlide = (index) => {
            currentIndex = (index + slides.length) % slides.length;
            track.scrollTo({ left: track.clientWidth * currentIndex, behavior: 'smooth' });
            if (counter) counter.textContent = `${currentIndex + 1} / ${slides.length}`;
            syncVideo();
        };

        gallery.querySelector('[data-project-card-previous]')?.addEventListener('click', () => showSlide(currentIndex - 1));
        gallery.querySelector('[data-project-card-next]')?.addEventListener('click', () => showSlide(currentIndex + 1));
        track.addEventListener('scroll', () => {
            window.cancelAnimationFrame(scrollFrame);
            scrollFrame = window.requestAnimationFrame(updateCounter);
        }, { passive: true });
        syncVideo();
    });

    document.querySelectorAll('[data-location-selects]').forEach((container) => {
        const citySelect = container.querySelector('[data-city-select]');
        const districtSelect = container.querySelector('[data-district-select]');
        const neighborhoodSelect = container.querySelector('[data-neighborhood-select]');

        if (! citySelect || ! districtSelect || ! neighborhoodSelect) return;

        const setPlaceholder = (select, text) => {
            select.disabled = true;
            select.innerHTML = `<option value="">${text}</option>`;
        };

        let districtRequest = 0;
        let neighborhoodRequest = 0;

        const loadNeighborhoods = async () => {
            const request = ++neighborhoodRequest;
            const city = citySelect.value;
            const district = districtSelect.value;
            const selected = neighborhoodSelect.dataset.selected || '';

            setPlaceholder(neighborhoodSelect, district ? 'Mahalleler yükleniyor...' : 'Önce ilçe seçin');
            if (! city || ! district) return;

            try {
                const url = new URL(citySelect.dataset.neighborhoodsUrl, window.location.origin);
                url.searchParams.set('city', city);
                url.searchParams.set('district', district);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (! response.ok) throw new Error('Mahalleler alınamadı.');

                const payload = await response.json();
                if (request !== neighborhoodRequest || city !== citySelect.value || district !== districtSelect.value) return;
                neighborhoodSelect.innerHTML = '<option value="">Mahalle / köy seçin</option>';
                Object.entries(payload.data).forEach(([value, label]) => {
                    neighborhoodSelect.add(new Option(label, value, false, value === selected));
                });
                neighborhoodSelect.disabled = false;
                neighborhoodSelect.dataset.selected = '';
            } catch (error) {
                if (request === neighborhoodRequest) setPlaceholder(neighborhoodSelect, 'Mahalleler yüklenemedi');
            }
        };

        const loadDistricts = async () => {
            const request = ++districtRequest;
            ++neighborhoodRequest;
            const city = citySelect.value;
            const selected = districtSelect.dataset.selected || '';

            setPlaceholder(districtSelect, city ? 'İlçeler yükleniyor...' : 'Önce il seçin');
            setPlaceholder(neighborhoodSelect, 'Önce ilçe seçin');
            if (! city) return;

            try {
                const url = new URL(citySelect.dataset.districtsUrl, window.location.origin);
                url.searchParams.set('city', city);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (! response.ok) throw new Error('İlçeler alınamadı.');

                const payload = await response.json();
                if (request !== districtRequest || city !== citySelect.value) return;
                districtSelect.innerHTML = '<option value="">İlçe seçin</option>';
                Object.entries(payload.data).forEach(([value, label]) => {
                    districtSelect.add(new Option(label, value, false, value === selected));
                });
                districtSelect.disabled = false;
                districtSelect.dataset.selected = '';
                await loadNeighborhoods();
            } catch (error) {
                if (request === districtRequest) setPlaceholder(districtSelect, 'İlçeler yüklenemedi');
            }
        };

        citySelect.addEventListener('change', () => {
            districtSelect.dataset.selected = '';
            neighborhoodSelect.dataset.selected = '';
            loadDistricts();
        });

        districtSelect.addEventListener('change', () => {
            neighborhoodSelect.dataset.selected = '';
            loadNeighborhoods();
        });

        loadDistricts();
    });

    document.querySelectorAll('[data-featured-listings]').forEach((container) => {
        const authenticated = container.dataset.authenticated === '1';
        const accountCount = Number(container.dataset.defaultCount) === 6 ? 6 : 3;
        const storedCount = Number(window.localStorage.getItem('featured_listing_count'));
        let currentCount = authenticated ? accountCount : (storedCount === 6 ? 6 : 3);
        const buttons = document.querySelectorAll('[data-listing-count]');

        const render = (count) => {
            currentCount = count;
            container.querySelectorAll('[data-listing-index]').forEach((item) => {
                item.classList.toggle('hidden', Number(item.dataset.listingIndex) >= count);
            });
            buttons.forEach((button) => {
                const active = Number(button.dataset.listingCount) === count;
                button.classList.toggle('border-orange-500', active);
                button.classList.toggle('bg-orange-50', active);
                button.classList.toggle('text-orange-700', active);
            });
        };

        buttons.forEach((button) => button.addEventListener('click', async () => {
            const count = Number(button.dataset.listingCount) === 6 ? 6 : 3;
            render(count);

            if (! authenticated) {
                window.localStorage.setItem('featured_listing_count', String(count));
                return;
            }

            try {
                await window.axios.patch(container.dataset.saveUrl, { featured_listing_count: count });
            } catch (error) {
                render(accountCount);
            }
        }));

        render(currentCount);
    });
});

// Deterrence only; keyboard shortcuts and controls remain available.
document.addEventListener('dragstart', (event) => {
    if (event.target instanceof Element && event.target.closest('img[data-listing-photo]')) {
        event.preventDefault();
    }
});

// Requested context-menu deterrent applies only to listing photographs.
document.addEventListener('contextmenu', (event) => {
    if (event.target instanceof Element && event.target.closest('img[data-listing-photo]')) {
        event.preventDefault();
    }
});
