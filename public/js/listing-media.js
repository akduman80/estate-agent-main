(() => {
    window.zydListingMedia = {
        setup(pond, component, videoLimits = null) {
            const server = pond.server;
            pond.setOptions({
                server: {
                    ...server,
                    load(source, load, error, progress, abort) {
                        const request = new XMLHttpRequest();
                        request.open('GET', source);
                        request.responseType = 'blob';
                        request.timeout = 30000;
                        request.onprogress = event => progress(event.lengthComputable, event.loaded, event.total);
                        request.onload = () => {
                            if (request.status >= 200 && request.status < 300 && request.response.size > 0
                                && ! request.response.type.includes('text/html')) {
                                load(request.response);
                            } else {
                                error('Önizleme yüklenemedi. Dosyayı kaldırabilir veya tekrar deneyebilirsiniz.');
                            }
                        };
                        request.onerror = request.ontimeout = () => error('Önizleme yüklenemedi. Bağlantınızı kontrol edip tekrar deneyin.');
                        request.send();
                        return { abort() { request.abort(); abort(); } };
                    },
                    process(...args) {
                        const onError = args[4];
                        args[4] = (...errors) => {
                            component.shouldUpdateState = true;
                            onError(...errors);
                        };
                        return server.process(...args);
                    },
                },
            });

            if (! videoLimits) return;

            const message = document.createElement('p');
            message.className = 'text-sm text-danger-600';
            message.setAttribute('role', 'alert');
            message.hidden = true;
            pond.element.after(message);

            pond.setOptions({
                beforeAddFile: async item => {
                    if (item.origin === window.FilePond.FileOrigin.LOCAL) return true;
                    message.hidden = true;
                    if (item.fileSize > videoLimits.maxBytes) {
                        message.textContent = `Video en fazla ${videoLimits.maxBytes / 1024 / 1024} MB olabilir.`;
                        message.hidden = false;
                        return false;
                    }

                    const duration = await new Promise(resolve => {
                        const video = document.createElement('video');
                        const objectUrl = URL.createObjectURL(item.file);
                        let finished = false;
                        const finish = value => {
                            if (finished) return;
                            finished = true;
                            clearTimeout(timer);
                            URL.revokeObjectURL(objectUrl);
                            video.removeAttribute('src');
                            video.load();
                            resolve(value);
                        };
                        const timer = setTimeout(() => finish(null), 10000);
                        video.preload = 'metadata';
                        video.onloadedmetadata = () => finish(video.duration);
                        video.onerror = () => finish(null);
                        video.src = objectUrl;
                    });

                    if (duration === null || ! Number.isFinite(duration) || duration <= 0) {
                        message.textContent = 'Video süresi okunamadı. MP4 (H.264) veya WebM (VP8/VP9) dosyası seçin.';
                    } else if (duration > videoLimits.maxSeconds) {
                        message.textContent = 'Video en fazla 60 saniye olabilir. Videoyu kısaltıp tekrar seçin.';
                    } else {
                        return true;
                    }
                    message.hidden = false;
                    return false;
                },
            });
        },
    };
})();
