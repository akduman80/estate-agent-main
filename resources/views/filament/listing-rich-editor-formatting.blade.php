<style>
    .listing-rich-editor-formatting {
        align-items: center;
        display: inline-flex;
        flex: 0 0 auto;
        flex-wrap: nowrap;
        gap: .25rem;
        white-space: nowrap;
    }

    .listing-rich-editor-formatting__field {
        align-items: center;
        display: inline-flex;
        margin: 0;
    }

    .listing-rich-editor-formatting__label {
        height: 1px;
        overflow: hidden;
        position: absolute;
        white-space: nowrap;
        width: 1px;
        clip: rect(1px, 1px, 1px, 1px);
    }

    .listing-rich-editor-formatting__input {
        border: 1px solid rgb(203 213 225);
        border-radius: .375rem;
        color: rgb(15 23 42);
        font-size: .75rem;
        height: 2rem;
        max-width: none;
        padding-inline: .45rem;
        width: 7.25rem;
    }

    .listing-rich-editor-formatting__input--size {
        text-align: center;
        width: 4.4rem;
    }

    .listing-rich-editor-formatting__color {
        align-items: center;
        display: flex;
    }

    .listing-rich-editor-formatting__color input[type="color"] {
        border: 1px solid rgb(203 213 225);
        border-radius: .375rem;
        cursor: pointer;
        height: 2rem;
        padding: .1rem;
        width: 2.1rem;
    }

    .listing-rich-editor-formatting__input--color-text {
        display: none;
    }

    .listing-rich-editor-formatting__align {
        align-items: center;
        border: 1px solid rgb(203 213 225);
        border-radius: .45rem;
        display: inline-flex;
        height: 2rem;
        overflow: hidden;
    }

    .listing-rich-editor-formatting__align-button {
        align-items: center;
        color: rgb(51 65 85);
        display: inline-flex;
        font-size: .7rem;
        font-weight: 700;
        height: 100%;
        justify-content: center;
        min-width: 2.25rem;
        padding-inline: .38rem;
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
        align-items: center;
        flex-wrap: nowrap;
        gap: .35rem;
        overflow-x: auto !important;
        overflow-y: visible !important;
        padding-bottom: .25rem;
    }

    trix-toolbar .trix-button-group {
        margin-bottom: 0;
        margin-left: 0;
        margin-right: .35rem;
    }

    trix-toolbar .trix-button-group {
        flex: 0 0 auto;
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

            field.append(label, control);

            return field;
        };

        const createFontControl = (editor, datalistId) => {
            const input = document.createElement('input');
            input.className = 'listing-rich-editor-formatting__input';
            input.dataset.listingFontFamily = 'true';
            input.setAttribute('list', datalistId);
            input.placeholder = 'Font ara veya yaz';
            input.type = 'text';

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
            text.className = 'listing-rich-editor-formatting__input listing-rich-editor-formatting__input--color-text';
            text.placeholder = '#000000 veya rgb(0,0,0)';
            text.type = 'text';
            text.value = defaultColor;
            picker.title = labelText;

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

        const createFontDatalist = (toolbar) => {
            const datalist = document.createElement('datalist');
            datalist.id = `listing-fonts-${Math.random().toString(36).slice(2)}`;

            commonFonts.forEach((font) => {
                const option = document.createElement('option');
                option.value = font;
                datalist.appendChild(option);
            });

            toolbar.appendChild(datalist);

            return datalist.id;
        };

        const updateToolbarState = (editor) => {
            const toolbar = document.getElementById(editor.getAttribute('toolbar'));
            const controls = toolbar?.querySelector('[data-listing-formatting-controls]');
            const attributes = editor.editor?.composition?.currentAttributes?.toObject?.() || {};

            if (! controls) return;

            const fontInput = controls.querySelector('[data-listing-font-family]');
            const sizeInput = controls.querySelector('[data-listing-font-size]');

            if (fontInput && attributes.listingFontFamily) {
                fontInput.value = attributes.listingFontFamily.replace(/^"|"$/g, '');
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

            const group = document.createElement('div');
            group.className = 'listing-rich-editor-formatting';
            group.dataset.listingFormattingControls = 'true';
            group.dataset.trixButtonGroup = 'listing-formatting-tools';

            const datalistId = createFontDatalist(toolbar);

            group.append(
                createFontControl(editor, datalistId),
                createSizeControl(editor),
                createAlignmentControl(editor),
                createColorControl(editor, 'Yazı rengi', 'listingTextColor', '#111827', 'listingTextColor'),
                createColorControl(editor, 'Vurgu rengi', 'listingHighlightColor', '#fef08a', 'listingHighlightColor'),
            );

            const textTools = toolbar.querySelector('[data-trix-button-group="text-tools"]');
            const toolbarRow = toolbar.querySelector('.trix-button-row') || toolbar.querySelector('.flex.gap-x-3');

            if (textTools?.parentNode) {
                textTools.after(group);
            } else {
                toolbarRow?.prepend(group);
            }

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
