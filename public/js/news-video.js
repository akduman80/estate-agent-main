(() => {
    'use strict';
    let library;
    const loadHls = () => library ??= new Promise((resolve, reject) => {
        if (window.Hls) return resolve(window.Hls);
        const script = document.createElement('script');
        script.src = '/js/vendor/hls.min.js';
        script.onload = () => window.Hls ? resolve(window.Hls) : reject(new Error('HLS yüklenemedi.'));
        script.onerror = () => { library = null; reject(new Error('Video oynatıcı yüklenemedi. Tekrar deneyin.')); };
        document.head.append(script);
    });
    function init() {
        document.querySelectorAll('[data-news-video]:not([data-initialized])').forEach(root => {
            root.dataset.initialized = 'true';
            const video = root.querySelector('video'), quality = root.querySelector('[data-quality]');
            const message = root.querySelector('[data-video-message]');
            const variants = JSON.parse(root.dataset.variants || '[]');
            let hls, started = false, starting = false, native = false;
            const fail = error => { message.textContent = error.message || 'Video oynatılamadı. Lütfen tekrar deneyin.'; };
            async function start() {
                if (starting) return;
                if (started) { try { await video.play(); } catch (e) { fail(e); } return; }
                starting = true;
                try {
                    // Prefer MSE for manual switching, including supported recent Safari versions.
                    let Hls;
                    try { Hls = await loadHls(); } catch (e) { if (!video.canPlayType('application/vnd.apple.mpegurl')) throw e; }
                    if (Hls?.isSupported()) {
                        hls = new Hls({autoStartLoad:false, startLevel:-1, capLevelToPlayerSize:true, maxBufferLength:20, maxMaxBufferLength:40});
                        hls.on(Hls.Events.MANIFEST_PARSED, () => {
                            quality.replaceChildren(new Option('Otomatik', 'auto'));
                            hls.levels.forEach((level, i) => quality.add(new Option(`${Math.min(level.width,level.height)}p`, String(i))));
                            hls.startLoad();
                            video.play().catch(fail);
                        });
                        hls.on(Hls.Events.ERROR, (_, data) => {
                            if (data.fatal) { message.textContent='Video bağlantısı kesildi. Yeniden oynatmayı deneyin.'; hls.destroy(); hls=null; started=false; }
                        });
                        hls.loadSource(root.dataset.manifest); hls.attachMedia(video);
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        native = true;
                        variants.forEach(v => quality.add(new Option(`${v.quality}p`, String(v.quality))));
                        video.src = root.dataset.manifest;
                        await video.play();
                    } else throw new Error('Tarayıcınız bu video yayınını desteklemiyor.');
                    started = true;
                    message.textContent = '';
                    root.querySelector('[data-start]').textContent = '▶ Oynat';
                } catch (e) { fail(e); } finally { starting = false; }
            }
            root.querySelector('[data-start]').addEventListener('click', start);
            video.addEventListener('play', () => { if (!started) start(); });
            video.addEventListener('error', () => { if (started) fail(new Error('Video yüklenemedi.')); });
            quality.addEventListener('change', () => {
                if (hls) { hls.capLevelToPlayerSize=quality.value==='auto'; hls.nextLevel=quality.value==='auto'?-1:Number(quality.value); }
                else if (native && started) {
                    const time=video.currentTime, paused=video.paused, rate=video.playbackRate;
                    video.src=quality.value==='auto'?root.dataset.manifest:variants.find(v=>String(v.quality)===quality.value).url;
                    video.addEventListener('loadedmetadata', () => { video.currentTime=time; video.playbackRate=rate; if(!paused)video.play().catch(fail); },{once:true});
                    message.textContent='Bu tarayıcıda kalite değişirken kısa süreli yükleme olabilir.';
                }
            });
            root.querySelector('[data-speed]').addEventListener('change', event => video.playbackRate=Number(event.target.value));
            root.querySelectorAll('[data-seek]').forEach(button => button.addEventListener('click', () => { if(Number.isFinite(video.duration))video.currentTime=Math.min(video.duration,Math.max(0,video.currentTime+Number(button.dataset.seek))); }));
            root.querySelector('[data-captions]').addEventListener('click', () => {
                const track=video.textTracks[0]; if(track)track.mode=track.mode==='showing'?'hidden':'showing';
            });
            // Livewire can replace an admin preview. Release MSE/network resources on removal.
            const observer = new MutationObserver(() => { if(!root.isConnected){hls?.destroy();video.removeAttribute('src');video.load();observer.disconnect();} });
            observer.observe(document.documentElement,{childList:true,subtree:true});
        });
        document.querySelectorAll('[data-youtube]:not([data-initialized])').forEach(root => {
            root.dataset.initialized='true';
            root.querySelector('[data-youtube-play]').addEventListener('click', () => {
                if(!/^[\w-]{11}$/.test(root.dataset.youtube))return;
                const frame=document.createElement('iframe');
                frame.src=`https://www.youtube-nocookie.com/embed/${root.dataset.youtube}?autoplay=1`;
                frame.title='Emlak haberi YouTube videosu';frame.allow='accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen';frame.allowFullscreen=true;
                root.replaceChildren(frame);
            },{once:true});
        });
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
    document.addEventListener('livewire:navigated',init);
    new MutationObserver(init).observe(document.documentElement,{childList:true,subtree:true});
})();
