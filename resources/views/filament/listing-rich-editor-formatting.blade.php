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
        position: relative;
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

    .listing-rich-editor-formatting__font-picker {
        position: relative;
        width: 100%;
    }

    .listing-rich-editor-formatting__font-button {
        align-items: center;
        background: white;
        border: 1px solid rgb(203 213 225);
        border-radius: .45rem;
        color: rgb(15 23 42);
        cursor: pointer;
        display: flex;
        font-size: .82rem;
        gap: .5rem;
        height: 2.25rem;
        justify-content: space-between;
        line-height: 1.2;
        padding-inline: .65rem;
        text-align: left;
        width: 100%;
    }

    .listing-rich-editor-formatting__font-button::after {
        border-left: .28rem solid transparent;
        border-right: .28rem solid transparent;
        border-top: .34rem solid rgb(71 85 105);
        content: "";
        flex: 0 0 auto;
    }

    .listing-rich-editor-formatting__font-name {
        display: block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .listing-rich-editor-formatting__font-options {
        background: white;
        border: 1px solid rgb(203 213 225);
        border-radius: .55rem;
        box-shadow: 0 16px 34px rgb(15 23 42 / .18);
        display: none;
        left: 0;
        max-height: 15rem;
        overflow-y: auto;
        padding: .25rem;
        position: absolute;
        right: 0;
        top: calc(100% + .25rem);
        z-index: 10000;
    }

    .listing-rich-editor-formatting__font-picker[data-open="true"] .listing-rich-editor-formatting__font-options {
        display: grid;
        gap: .1rem;
    }

    .listing-rich-editor-formatting__font-option {
        border-radius: .35rem;
        color: rgb(15 23 42);
        cursor: pointer;
        display: block;
        font-size: .86rem;
        line-height: 1.25;
        padding: .45rem .55rem;
        text-align: left;
        width: 100%;
    }

    .listing-rich-editor-formatting__font-option:hover,
    .listing-rich-editor-formatting__font-option[aria-selected="true"] {
        background: rgb(245 158 11 / .14);
        color: rgb(180 83 9);
    }

    .listing-rich-editor-formatting__input--size {
        max-width: 5.4rem;
        width: 5.4rem;
    }

    .listing-rich-editor-formatting__color {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
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

    .listing-rich-editor-formatting__palette {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: .2rem;
        width: 100%;
        margin-top: .35rem;
    }

    .listing-rich-editor-formatting__swatch {
        aspect-ratio: 1;
        border: 1px solid rgb(148 163 184 / .45);
        border-radius: .2rem;
        cursor: pointer;
        min-width: 0;
    }

    .listing-rich-editor-formatting__swatch:hover,
    .listing-rich-editor-formatting__swatch:focus-visible,
    .listing-rich-editor-formatting__swatch[aria-pressed="true"] {
        outline: 2px solid rgb(245 158 11);
        outline-offset: 1px;
        position: relative;
        z-index: 1;
    }

    .dark .listing-rich-editor-formatting-dropdown__summary {
        background: rgb(31 41 55);
        border-color: rgb(75 85 99);
        color: rgb(243 244 246);
    }

    .listing-rich-editor-formatting {
        max-height: calc(100dvh - 2rem);
        overflow-y: auto;
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

    .listing-owner-select .choices__list--dropdown,
    .listing-owner-select .choices__list[aria-expanded] {
        z-index: 120;
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

        // Columns are color families; rows run from dark to light.
        const colorFamilies = [
            ['Kırmızı', '7f1d1d b91c1c dc2626 ef4444 f87171 fca5a5 fee2e2'],
            ['Turuncu', '7c2d12 c2410c ea580c f97316 fb923c fdba74 ffedd5'],
            ['Kehribar', '78350f b45309 d97706 f59e0b fbbf24 fcd34d fef3c7'],
            ['Sarı', '713f12 a16207 ca8a04 eab308 facc15 fde047 fef9c3'],
            ['Limon', '365314 4d7c0f 65a30d 84cc16 a3e635 bef264 ecfccb'],
            ['Yeşil', '14532d 15803d 16a34a 22c55e 4ade80 86efac dcfce7'],
            ['Zümrüt', '064e3b 047857 059669 10b981 34d399 6ee7b7 d1fae5'],
            ['Turkuaz', '134e4a 0f766e 0d9488 14b8a6 2dd4bf 5eead4 ccfbf1'],
            ['Camgöbeği', '164e63 0e7490 0891b2 06b6d4 22d3ee 67e8f9 cffafe'],
            ['Mavi', '1e3a8a 1d4ed8 2563eb 3b82f6 60a5fa 93c5fd dbeafe'],
            ['Mor', '4c1d95 6d28d9 7c3aed 8b5cf6 a78bfa c4b5fd ede9fe'],
            ['Gri', '000000 1f2937 4b5563 6b7280 9ca3af d1d5db ffffff'],
        ].map(([name, colors]) => [name, colors.split(' ').map((color) => '#' + color)]);

        const updateColorSwatches = (wrapper, color) => {
            wrapper.querySelectorAll('[data-listing-color-value]').forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.listingColorValue === color ? 'true' : 'false');
            });
        };

        const configureTrix = () => {
            if (! window.Trix || window.__listingRichEditorFormattingConfigured) return;

            window.__listingRichEditorFormattingConfigured = true;

            // Trix combines text styles on one span. Keep alignment on an outer
            // block so the inline highlight only covers the text itself.
            const pieceView = window.Trix.views.PieceView.prototype;
            const createElement = pieceView.createElement;
            pieceView.createElement = function () {
                const element = createElement.call(this);
                const alignment = this.attributes.listingTextAlign;

                if (! element || ! alignment) return element;

                element.style.removeProperty('text-align');
                const wrapper = document.createElement('span');
                wrapper.style.textAlign = alignment;
                wrapper.append(element);

                return wrapper;
            };
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

        const addFontOption = (picker, font) => {
            const list = picker.querySelector('[data-listing-font-options]');

            if (! list || list.querySelector(`[data-listing-font-value="${CSS.escape(font)}"]`)) return;

            const option = document.createElement('button');
            option.className = 'listing-rich-editor-formatting__font-option';
            option.type = 'button';
            option.dataset.listingFontValue = font;
            option.setAttribute('aria-selected', 'false');
            option.style.fontFamily = font;
            option.textContent = font;
            option.title = font;
            list.append(option);
        };

        const setFontPickerValue = (picker, font) => {
            addFontOption(picker, font);

            const label = picker.querySelector('[data-listing-font-label]');

            if (label) {
                label.textContent = font;
                label.style.fontFamily = font;
                label.title = font;
            }

            picker.dataset.listingFontCurrent = font;
            picker.querySelectorAll('[data-listing-font-value]').forEach((option) => {
                option.setAttribute('aria-selected', option.dataset.listingFontValue === font ? 'true' : 'false');
            });
        };

        const createFontControl = (editor) => {
            const picker = document.createElement('span');
            picker.className = 'listing-rich-editor-formatting__font-picker';
            picker.dataset.listingFontFamily = 'true';

            const button = document.createElement('button');
            button.className = 'listing-rich-editor-formatting__font-button';
            button.type = 'button';
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-haspopup', 'listbox');

            const selectedLabel = document.createElement('span');
            selectedLabel.className = 'listing-rich-editor-formatting__font-name';
            selectedLabel.dataset.listingFontLabel = 'true';

            const list = document.createElement('span');
            list.className = 'listing-rich-editor-formatting__font-options';
            list.dataset.listingFontOptions = 'true';
            list.setAttribute('role', 'listbox');

            button.append(selectedLabel);
            picker.append(button, list);
            commonFonts.forEach((font) => addFontOption(picker, font));
            setFontPickerValue(picker, commonFonts[0]);

            button.addEventListener('click', (event) => {
                event.stopPropagation();

                const isOpen = picker.dataset.open === 'true';
                picker.dataset.open = isOpen ? 'false' : 'true';
                button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            });

            list.addEventListener('click', (event) => {
                const option = event.target.closest('[data-listing-font-value]');

                if (! option) return;

                const font = option.dataset.listingFontValue;
                setFontPickerValue(picker, font);
                picker.dataset.open = 'false';
                button.setAttribute('aria-expanded', 'false');
                applyAttribute(editor, 'listingFontFamily', normalizeFontFamily(font));
            });

            return createField('Yazı tipi', picker);
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
                updateColorSwatches(wrapper, color);
                applyAttribute(editor, attribute, color);
            };

            picker.addEventListener('input', () => apply(picker.value));
            text.addEventListener('change', () => apply(text.value));

            wrapper.dataset[marker] = 'true';
            const palette = document.createElement('span');
            palette.className = 'listing-rich-editor-formatting__palette';
            palette.setAttribute('role', 'group');
            palette.setAttribute('aria-label', labelText + ' paleti');

            for (let shade = 0; shade < 7; shade++) {
                colorFamilies.forEach(([name, colors]) => {
                    const color = colors[shade];
                    const swatch = document.createElement('button');
                    swatch.type = 'button';
                    swatch.className = 'listing-rich-editor-formatting__swatch';
                    swatch.dataset.listingColorValue = color;
                    swatch.style.backgroundColor = color;
                    swatch.title = name + ' ' + color;
                    swatch.setAttribute('aria-label', labelText + ': ' + swatch.title);
                    // Keep the editor selection while choosing a color.
                    swatch.addEventListener('mousedown', (event) => event.preventDefault());
                    swatch.addEventListener('click', () => apply(color));
                    palette.append(swatch);
                });
            }

            wrapper.append(picker, text, palette);
            updateColorSwatches(wrapper, defaultColor);

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

                setFontPickerValue(fontInput, fontFamily);
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
                updateColorSwatches(controls.querySelector(selector), value);
            });
        };

        const addToolbarControls = (editor) => {
            if (! editor.matches('[data-listing-description-editor="true"]')) return;

            const toolbar = document.getElementById(editor.getAttribute('toolbar'));

            if (! toolbar || toolbar.querySelector('[data-listing-formatting-controls]')) return;

            // Livewire must preserve the controls added by JavaScript on form refresh.
            toolbar.setAttribute('wire:ignore', '');
            toolbar.dataset.listingFormattingToolbar = 'true';
            toolbar.closest('.fi-fo-rich-editor')?.classList.add('listing-rich-editor-formatting-host');

            const dropdown = document.createElement('details');
            dropdown.className = 'listing-rich-editor-formatting-dropdown';
            dropdown.dataset.listingFormattingDropdown = 'true';

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
                const top = Math.max(16, Math.min(summaryRect.bottom + 8, window.innerHeight - panel.offsetHeight - 16));
                panel.style.top = `${top}px`;
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

            group.addEventListener('click', (event) => {
                if (event.target.closest('[data-listing-font-family]')) return;

                group.querySelectorAll('[data-listing-font-family][data-open="true"]').forEach((picker) => {
                    picker.dataset.open = 'false';
                    picker.querySelector('[aria-expanded="true"]')?.setAttribute('aria-expanded', 'false');
                });
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
