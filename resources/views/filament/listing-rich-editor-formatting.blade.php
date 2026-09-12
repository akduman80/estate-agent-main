<style>
    .listing-rich-editor-formatting-dropdown {
        display: inline-block;
        position: relative;
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
        left: 0;
        max-width: min(22rem, calc(100vw - 2rem));
        min-width: min(22rem, calc(100vw - 2rem));
        padding: .75rem;
        position: absolute;
        top: calc(100% + .45rem);
        width: min(22rem, calc(100vw - 2rem));
        z-index: 40;
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
        border: 1px solid rgb(203 213 225);
        border-radius: .375rem;
        color: rgb(15 23 42);
        font-size: .75rem;
        height: 1.9rem;
        max-width: none;
        padding-inline: .45rem;
        width: 100%;
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

    trix-toolbar .trix-button-row {
        align-items: flex-start;
        flex-wrap: wrap;
        gap: .5rem;
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
            text.className = 'listing-rich-editor-formatting__input';
            text.placeholder = '#000000 veya rgb(0,0,0)';
            text.type = 'text';
            text.value = defaultColor;

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

            const dropdown = document.createElement('details');
            dropdown.className = 'listing-rich-editor-formatting-dropdown';
            dropdown.dataset.trixButtonGroup = 'listing-formatting-tools';

            const summary = document.createElement('summary');
            summary.className = 'listing-rich-editor-formatting-dropdown__summary';
            summary.textContent = 'Biçim';

            const group = document.createElement('div');
            group.className = 'listing-rich-editor-formatting';
            group.dataset.listingFormattingControls = 'true';

            const datalistId = createFontDatalist(toolbar);

            group.append(
                createFontControl(editor, datalistId),
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
