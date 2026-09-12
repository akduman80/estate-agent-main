<style>
    .listing-rich-editor-formatting-dropdown {
        display: inline-block;
        position: relative;
        z-index: 80;
    }

    .listing-rich-editor-formatting-dropdown__summary {
        align-items: center;
        border: 1px solid rgb(203 213 225);
        border-radius: .45rem;
        color: rgb(15 23 42);
        display: inline-flex;
        font-size: .78rem;
        font-weight: 700;
        gap: .45rem;
        height: 2rem;
        list-style: none;
        padding-inline: .65rem;
        user-select: none;
        white-space: nowrap;
    }

    .listing-rich-editor-formatting-dropdown__summary {
        cursor: pointer;
    }

    .listing-rich-editor-formatting-dropdown__summary::-webkit-details-marker {
        display: none;
    }

    .listing-rich-editor-formatting-dropdown__summary::after {
        border-left: .28rem solid transparent;
        border-right: .28rem solid transparent;
        border-top: .34rem solid currentColor;
        content: "";
        display: block;
        opacity: .65;
    }

    .listing-rich-editor-formatting {
        align-items: stretch;
        background: white;
        border: 1px solid rgb(226 232 240);
        border-radius: .7rem;
        box-shadow: 0 18px 45px rgb(15 23 42 / .16);
        display: none;
        flex-wrap: wrap;
        gap: .5rem;
        max-width: min(22rem, calc(100vw - 2rem));
        min-width: min(22rem, calc(100vw - 2rem));
        padding: .75rem;
        position: fixed;
        width: min(22rem, calc(100vw - 2rem));
        z-index: 9999;
    }

    .listing-rich-editor-formatting-dropdown[open] .listing-rich-editor-formatting {
        display: flex;
    }

    .listing-rich-editor-formatting__field {
        display: grid;
        gap: .2rem;
        margin: 0;
    }

    .listing-rich-editor-formatting__label {
        color: rgb(100 116 139);
        font-size: .68rem;
        font-weight: 700;
        line-height: 1;
    }

    .listing-rich-editor-formatting__input {
        background: white;
        border: 1px solid rgb(203 213 225);
        border-radius: .375rem;
        color: rgb(15 23 42);
        font-size: .75rem;
        height: 1.9rem;
        max-width: none;
        padding-inline: .45rem;
        width: 100%;
    }

    .listing-rich-editor-formatting__select {
        appearance: none;
        background:
            linear-gradient(45deg, transparent 50%, rgb(71 85 105) 50%) calc(100% - .85rem) 50% / .35rem .35rem no-repeat,
            linear-gradient(135deg, rgb(71 85 105) 50%, transparent 50%) calc(100% - .62rem) 50% / .35rem .35rem no-repeat,
            white;
        cursor: pointer;
        padding-right: 1.9rem;
    }

    .listing-rich-editor-formatting__input--size {
        max-width: 5.4rem;
        width: 5.4rem;
    }

    .listing-rich-editor-formatting__color {
        align-items: center;
        display: flex;
        gap: .25rem;
    }

    .listing-rich-editor-formatting__color input[type="color"] {
        border: 1px solid rgb(203 213 225);
        border-radius: .375rem;
        height: 1.9rem;
        padding: .1rem;
        width: 2.1rem;
    }

    .listing-rich-editor-formatting__color input[type="text"] {
        flex: 1;
        max-width: none;
        width: auto;
    }

    .listing-rich-editor-formatting__align {
        align-items: center;
        border: 1px solid rgb(203 213 225);
        border-radius: .45rem;
        display: inline-flex;
        height: 1.9rem;
        overflow: hidden;
        width: 100%;
    }

    .listing-rich-editor-formatting__align-button {
        align-items: center;
        color: rgb(51 65 85);
        display: inline-flex;
        font-size: .72rem;
        font-weight: 700;
        height: 100%;
        justify-content: center;
        min-width: 2.55rem;
        padding-inline: .45rem;
        width: 100%;
    }

    .listing-rich-editor-formatting__align-button + .listing-rich-editor-formatting__align-button {
        border-left: 1px solid rgb(203 213 225);
    }

    .listing-rich-editor-formatting__align-button[aria-pressed="true"] {
        background: rgb(245 158 11 / .14);
        color: rgb(180 83 9);
    }

    trix-editor span[style*="text-align"] {
        display: block;
    }

    .listing-description span[style*="text-align"] {
        display: block;
    }

    .listing-rich-editor-formatting-host {
        overflow: visible !important;
    }

    trix-toolbar[data-listing-formatting-toolbar="true"] {
        overflow: visible !important;
        position: relative;
        z-index: 90;
    }

    trix-toolbar .trix-button-row {
        align-items: flex-start;
        flex-wrap: wrap;
        gap: .5rem;
        overflow: visible !important;
    }

    trix-toolbar .trix-button-group {
        margin-bottom: 0;
        margin-left: 0;
        margin-right: .35rem;
    }

    .listing-rich-editor-formatting__field {
        width: 100%;
    }
</style>

<script>
    (() => {
        const commonFonts = [
            'Arial',
            'Arial Black',
            'Verdana',
            'Times New Roman',
            'Georgia',
            'Tahoma',
            'Trebuchet MS',
            'Courier New',
            'Impact',
            'Lucida Console',
            'Palatino Linotype',
            'Segoe UI',
            'Inter',
            'Roboto',
            'Montserrat',
            'Open Sans',
        ];

        const configureTrix = () => {
            if (! window.Trix || window.__listingRichEditorFormattingConfigured) return;

            window.__listingRichEditorFormattingConfigured = true;
            window.Trix.config.textAttributes.listingFontFamily = {
                inheritable: true,
                styleProperty: 'fontFamily',
            };
            window.Trix.config.textAttributes.listingFontSize = {
                inheritable: true,
                styleProperty: 'fontSize',
            };
            window.Trix.config.textAttributes.listingTextColor = {
                inheritable: true,
                styleProperty: 'color',
            };
            window.Trix.config.textAttributes.listingHighlightColor = {
                inheritable: true,
                styleProperty: 'backgroundColor',
            };
            window.Trix.config.textAttributes.listingTextAlign = {
                inheritable: true,
                styleProperty: 'textAlign',
            };
        };

        const normalizeColor = (value) => {
            if (! value) return null;

            const normalized = value.trim().toLowerCase();

            if (/^#[0-9a-f]{6}$/.test(normalized)) return normalized;

            if (/^#[0-9a-f]{3}$/.test(normalized)) {
                return '#' + normalized.slice(1).split('').map((char) => char + char).join('');
            }

            const rgb = normalized.match(/^rgba?\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})/);

            if (! rgb) return null;

            const parts = rgb.slice(1, 4).map((part) => Math.max(0, Math.min(255, Number(part))));

            return '#' + parts.map((part) => part.toString(16).padStart(2, '0')).join('');
        };

        const normalizeFontFamily = (value) => {
            const font = (value || '').trim();

            if (! font || font.length > 80 || ! /^[\p{L}\p{N}\s"',.-]+$/u.test(font)) return null;

            return font.includes(' ') && ! /^["'].*["']$/.test(font) ? `"${font.replaceAll('"', '')}"` : font;
        };

        const normalizeFontSize = (value) => {
            const normalized = (value || '').trim().replace(',', '.');

            if (! /^\d{1,3}(\.\d{1,2})?$/.test(normalized)) return null;

            const size = Number(normalized);

            if (size <= 0 || size > 200) return null;

            return `${normalized}px`;
        };

        const normalizeTextAlign = (value) => ['left', 'center', 'right'].includes(value) ? value : null;

        const applyAttribute = (editor, attribute, value) => {
            if (! editor?.editor) return;

            if (value) {
                editor.editor.activateAttribute(attribute, value);
            } else {
                editor.editor.deactivateAttribute(attribute);
            }

            editor.editorController?.render();
            editor.focus();
        };

        const createField = (labelText, control) => {
            const field = document.createElement('label');
            field.className = 'listing-rich-editor-formatting__field';

            const label = document.createElement('span');
            label.className = 'listing-rich-editor-formatting__label';
            label.textContent = labelText;

            control.setAttribute('aria-label', labelText);
            control.setAttribute('title', labelText);

            field.append(label, control);

            return field;
        };

        const createFontControl = (editor) => {
            const input = document.createElement('select');
            input.className = 'listing-rich-editor-formatting__input listing-rich-editor-formatting__select';
            input.dataset.listingFontFamily = 'true';

            commonFonts.forEach((font) => {
                const option = document.createElement('option');
                option.value = font;
                option.textContent = font;
                option.style.fontFamily = font;
                input.append(option);
            });

            input.addEventListener('change', () => {
                applyAttribute(editor, 'listingFontFamily', normalizeFontFamily(input.value));
            });

            return createField('Yazı tipi', input);
        };

        const createSizeControl = (editor) => {
            const input = document.createElement('input');
            input.className = 'listing-rich-editor-formatting__input listing-rich-editor-formatting__input--size';
            input.dataset.listingFontSize = 'true';
            input.inputMode = 'decimal';
            input.placeholder = '14.5';
            input.step = '0.1';
            input.type = 'number';

            input.addEventListener('change', () => {
                applyAttribute(editor, 'listingFontSize', normalizeFontSize(input.value));
            });

            return createField('Yazı boyutu', input);
        };

        const createColorControl = (editor, labelText, attribute, defaultColor, marker) => {
            const wrapper = document.createElement('span');
            wrapper.className = 'listing-rich-editor-formatting__color';

            const picker = document.createElement('input');
            picker.type = 'color';
            picker.value = defaultColor;

            const text = document.createElement('input');
            text.className = 'listing-rich-editor-formatting__input';
            text.placeholder = '#000000 veya rgb(0,0,0)';
            text.type = 'text';
            text.value = defaultColor;
            picker.title = labelText;
            picker.setAttribute('aria-label', labelText);
            text.setAttribute('aria-label', `${labelText} HEX veya RGB değeri`);
            text.setAttribute('title', `${labelText} HEX veya RGB değeri`);

            const apply = (value) => {
                const color = normalizeColor(value);

                if (! color) return;

                picker.value = color;
                text.value = color;
                applyAttribute(editor, attribute, color);
            };

            picker.addEventListener('input', () => apply(picker.value));
            text.addEventListener('change', () => apply(text.value));

            wrapper.dataset[marker] = 'true';
            wrapper.append(picker, text);

            return createField(labelText, wrapper);
        };

        const createAlignmentControl = (editor) => {
            const wrapper = document.createElement('span');
            wrapper.className = 'listing-rich-editor-formatting__align';
            wrapper.dataset.listingTextAlign = 'true';

            [
                ['left', 'Sol'],
                ['center', 'Orta'],
                ['right', 'Sağ'],
            ].forEach(([value, label]) => {
                const button = document.createElement('button');
                button.className = 'listing-rich-editor-formatting__align-button';
                button.type = 'button';
                button.dataset.listingTextAlignValue = value;
                button.setAttribute('aria-pressed', 'false');
                button.textContent = label;
                button.title = `${label} hizala`;

                button.addEventListener('click', () => {
                    applyAttribute(editor, 'listingTextAlign', normalizeTextAlign(value));
                });

                wrapper.append(button);
            });

            return createField('Hizalama', wrapper);
        };

        const annotateToolbarButtons = (toolbar) => {
            const actionLabels = {
                bold: 'Kalın',
                italic: 'İtalik',
                strike: 'Üstü çizili',
                link: 'Bağlantı ekle',
                heading1: 'Başlık 1',
                heading2: 'Başlık 2',
                quote: 'Alıntı',
                code: 'Kod',
                bulletList: 'Madde işaretli liste',
                numberList: 'Numaralı liste',
                attachFiles: 'Görsel ekle',
                undo: 'Geri al',
                redo: 'İleri al',
                decreaseNestingLevel: 'Girintiyi azalt',
                increaseNestingLevel: 'Girintiyi artır',
            };
            const attributeLabels = {
                bold: 'Kalın',
                italic: 'İtalik',
                strike: 'Üstü çizili',
                href: 'Bağlantı ekle',
                heading1: 'Başlık 1',
                quote: 'Alıntı',
                code: 'Kod',
                bullet: 'Madde işaretli liste',
                number: 'Numaralı liste',
            };

            toolbar.querySelectorAll('[data-trix-action], [data-trix-attribute]').forEach((button) => {
                const action = button.dataset.trixAction;
                const attribute = button.dataset.trixAttribute;
                const label = actionLabels[action] || attributeLabels[attribute];

                if (! label) return;

                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
            });
        };

        const updateToolbarState = (editor) => {
            const toolbar = document.getElementById(editor.getAttribute('toolbar'));
            const controls = toolbar?.querySelector('[data-listing-formatting-controls]');
            const attributes = editor.editor?.composition?.currentAttributes?.toObject?.() || {};

            if (! controls) return;

            const fontInput = controls.querySelector('[data-listing-font-family]');
            const sizeInput = controls.querySelector('[data-listing-font-size]');

            if (fontInput && attributes.listingFontFamily) {
                const fontFamily = attributes.listingFontFamily.replace(/^"|"$/g, '');

                if (! [...fontInput.options].some((option) => option.value === fontFamily)) {
                    const option = document.createElement('option');
                    option.value = fontFamily;
                    option.textContent = fontFamily;
                    option.style.fontFamily = fontFamily;
                    fontInput.append(option);
                }

                fontInput.value = fontFamily;
            }

            if (sizeInput && attributes.listingFontSize) {
                sizeInput.value = attributes.listingFontSize.replace('px', '');
            }

            const activeAlign = normalizeTextAlign(attributes.listingTextAlign) || 'left';

            controls.querySelectorAll('[data-listing-text-align-value]').forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.listingTextAlignValue === activeAlign ? 'true' : 'false');
            });

            [
                ['listingTextColor', '[data-listing-text-color]'],
                ['listingHighlightColor', '[data-listing-highlight-color]'],
            ].forEach(([attribute, selector]) => {
                const value = normalizeColor(attributes[attribute]);

                if (! value) return;

                controls.querySelector(`${selector} input[type="color"]`).value = value;
                controls.querySelector(`${selector} input[type="text"]`).value = value;
            });
        };

        const addToolbarControls = (editor) => {
            if (! editor.matches('[data-listing-description-editor="true"]')) return;

            const toolbar = document.getElementById(editor.getAttribute('toolbar'));

            if (! toolbar || toolbar.querySelector('[data-listing-formatting-controls]')) return;

            toolbar.dataset.listingFormattingToolbar = 'true';
            toolbar.closest('.fi-fo-rich-editor')?.classList.add('listing-rich-editor-formatting-host');

            const dropdown = document.createElement('details');
            dropdown.className = 'listing-rich-editor-formatting-dropdown';
            dropdown.dataset.trixButtonGroup = 'listing-formatting-tools';

            const summary = document.createElement('summary');
            summary.className = 'listing-rich-editor-formatting-dropdown__summary';
            summary.textContent = 'Biçim';
            summary.setAttribute('aria-label', 'Biçimlendirme seçeneklerini aç veya kapat');
            summary.setAttribute('title', 'Biçimlendirme seçeneklerini aç veya kapat');

            const positionDropdown = () => {
                const panel = dropdown.querySelector('[data-listing-formatting-controls]');

                if (! panel || ! dropdown.open) return;

                const summaryRect = summary.getBoundingClientRect();
                const panelWidth = Math.min(352, window.innerWidth - 32);
                const left = Math.min(Math.max(16, summaryRect.left), window.innerWidth - panelWidth - 16);

                panel.style.left = `${left}px`;
                panel.style.top = `${summaryRect.bottom + 8}px`;
                panel.style.width = `${panelWidth}px`;
                panel.style.minWidth = `${panelWidth}px`;
                panel.style.maxWidth = `${panelWidth}px`;
            };

            summary.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                dropdown.toggleAttribute('open');
                positionDropdown();
            });

            const group = document.createElement('div');
            group.className = 'listing-rich-editor-formatting';
            group.dataset.listingFormattingControls = 'true';
            group.addEventListener('click', (event) => event.stopPropagation());

            group.append(
                createFontControl(editor),
                createSizeControl(editor),
                createAlignmentControl(editor),
                createColorControl(editor, 'Yazı rengi', 'listingTextColor', '#111827', 'listingTextColor'),
                createColorControl(editor, 'Vurgu rengi', 'listingHighlightColor', '#fef08a', 'listingHighlightColor'),
            );

            dropdown.append(summary, group);

            const textTools = toolbar.querySelector('[data-trix-button-group="text-tools"]');
            const toolbarRow = toolbar.querySelector('.trix-button-row') || toolbar.querySelector('.flex.gap-x-3');

            if (textTools?.parentNode) {
                textTools.after(dropdown);
            } else {
                toolbarRow?.prepend(dropdown);
            }

            annotateToolbarButtons(toolbar);

            document.addEventListener('click', (event) => {
                if (! dropdown.open || dropdown.contains(event.target)) return;

                dropdown.removeAttribute('open');
            });

            window.addEventListener('resize', positionDropdown);
            window.addEventListener('scroll', positionDropdown, true);

            editor.addEventListener('trix-selection-change', () => updateToolbarState(editor));
            editor.addEventListener('trix-change', () => updateToolbarState(editor));
            updateToolbarState(editor);
        };

        const addControlsToExistingEditors = () => {
            document.querySelectorAll('trix-editor[data-listing-description-editor="true"]').forEach(addToolbarControls);
        };

        document.addEventListener('trix-before-initialize', configureTrix);
        document.addEventListener('trix-initialize', (event) => addToolbarControls(event.target));

        configureTrix();
        addControlsToExistingEditors();
        requestAnimationFrame(addControlsToExistingEditors);
        setTimeout(addControlsToExistingEditors, 250);
    })();
</script>
