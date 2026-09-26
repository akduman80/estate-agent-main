<style>
    .zyd-speech-button {
        align-items: center;
        align-self: center;
        background: transparent;
        border: 0;
        border-radius: .5rem;
        color: rgb(234 88 12);
        cursor: pointer;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 1.2rem;
        justify-content: center;
        min-height: 2.5rem;
        min-width: 2.5rem;
    }
    .zyd-speech-button:hover { background: rgb(255 247 237); }
    .zyd-speech-button[data-listening="true"] {
        animation: zyd-speech-pulse 1s infinite;
        background: rgb(255 237 213);
        color: rgb(194 65 12);
    }
    @keyframes zyd-speech-pulse { 50% { opacity: .55; } }

    .zyd-camera-button {
        align-items: center;
        background: rgb(17 24 39);
        border: 0;
        border-radius: .9rem;
        color: white;
        cursor: pointer;
        display: inline-flex;
        font-size: .95rem;
        font-weight: 800;
        justify-content: center;
        margin-top: .75rem;
        min-height: 2.85rem;
        padding: .7rem 1rem;
        width: 100%;
    }

    .zyd-camera-button:hover {
        background: rgb(31 41 55);
    }

    @media (max-width: 768px) {
        .fi-main {
            padding-left: .75rem !important;
            padding-right: .75rem !important;
        }

        .fi-page,
        .fi-fo-component-ctn,
        .fi-fo-field-wrp {
            gap: .75rem !important;
        }

        .fi-section,
        .fi-fo-section {
            border-radius: 1rem !important;
            margin-left: -.25rem;
            margin-right: -.25rem;
        }

        .fi-section-content,
        .fi-fo-section-content {
            padding: .85rem !important;
        }

        .fi-input-wrp,
        .fi-select-input,
        .fi-textarea {
            border-radius: .9rem !important;
        }

        .fi-input,
        .fi-select-input,
        .fi-textarea {
            font-size: 16px !important;
            min-height: 2.85rem;
        }

        .fi-btn {
            min-height: 2.75rem;
        }

        .fi-ta-content {
            overflow-x: hidden !important;
        }

        .fi-ta table {
            min-width: 100% !important;
            width: 100% !important;
        }

        .fi-ta td {
            white-space: normal !important;
        }

        .fi-ta-actions,
        .fi-ta-actions .fi-ac {
            width: 100%;
        }

        .fi-ta-actions .fi-btn,
        .fi-ta-actions a {
            justify-content: center;
            width: 100%;
        }

        [data-project-location-picker] {
            gap: .75rem !important;
        }

        [data-project-location-picker][data-expanded="false"] [x-ref="map"] {
            height: 16rem !important;
            min-height: 16rem !important;
        }
    }
</style>
<script>
(() => {
    if (window.zydProjectMobileToolsLoaded) return;
    window.zydProjectMobileToolsLoaded = true;

    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    let activeRecognition = null;
    let activeButton = null;

    const stopActiveRecognition = () => {
        activeRecognition?.stop();
        activeRecognition = null;
        if (activeButton) activeButton.dataset.listening = 'false';
        activeButton = null;
    };

    const startDictation = (field, button) => {
        if (activeRecognition) {
            stopActiveRecognition();
            return;
        }

        const recognition = new Recognition();
        activeRecognition = recognition;
        activeButton = button;
        button.dataset.listening = 'true';
        recognition.lang = 'tr-TR';
        recognition.interimResults = false;
        recognition.continuous = false;

        recognition.onresult = (event) => {
            const transcript = Array.from(event.results)
                .map((result) => result[0].transcript)
                .join(' ')
                .trim();
            if (! transcript) return;

            const separator = field.value?.trim() ? ' ' : '';
            field.value = `${field.value || ''}${separator}${transcript}`;
            field.dispatchEvent(new InputEvent('input', { bubbles: true, inputType: 'insertText', data: transcript }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
            field.focus();
        };
        recognition.onerror = () => stopActiveRecognition();
        recognition.onend = () => stopActiveRecognition();
        recognition.start();
    };

    const enhanceFields = () => {
        if (Recognition) document.querySelectorAll('input[type="text"], input[type="url"], textarea').forEach((field) => {
            if (field.dataset.zydSpeechReady || field.disabled || field.readOnly) return;
            if (field.closest('[role="dialog"]') && ! field.closest('.fi-fo-field-wrp')) return;
            field.dataset.zydSpeechReady = 'true';

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'zyd-speech-button';
            button.dataset.listening = 'false';
            button.title = 'Konuşarak yaz';
            button.setAttribute('aria-label', 'Bu alana konuşarak yaz');
            button.textContent = '🎙️';
            button.addEventListener('click', () => startDictation(field, button));
            field.insertAdjacentElement('afterend', button);
        });

        document.querySelectorAll('.filepond--root').forEach((filePond) => {
            if (filePond.dataset.zydCameraReady) return;

            const field = filePond.querySelector('input[type="file"]');
            if (! field || field.disabled) return;

            filePond.dataset.zydCameraReady = 'true';
            const accept = field.getAttribute('accept') || '';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'zyd-camera-button';
            button.textContent = accept.includes('video') ? 'Video çek' : 'Kamerayı aç';
            button.addEventListener('click', () => {
                const cameraInput = document.createElement('input');
                cameraInput.type = 'file';
                cameraInput.accept = accept || (button.textContent === 'Video çek' ? 'video/*' : 'image/*');
                cameraInput.capture = 'environment';
                cameraInput.style.display = 'none';

                cameraInput.addEventListener('change', () => {
                    const files = Array.from(cameraInput.files || []);
                    if (! files.length) {
                        cameraInput.remove();
                        return;
                    }

                    const transfer = new DataTransfer();
                    files.forEach((file) => transfer.items.add(file));
                    field.files = transfer.files;
                    field.dispatchEvent(new Event('change', { bubbles: true }));

                    cameraInput.remove();
                }, { once: true });

                document.body.appendChild(cameraInput);
                cameraInput.click();
            });

            filePond.insertAdjacentElement('afterend', button);
        });
    };

    enhanceFields();
    new MutationObserver(enhanceFields).observe(document.body, { childList: true, subtree: true });
    document.addEventListener('livewire:navigated', enhanceFields);
})();
</script>
