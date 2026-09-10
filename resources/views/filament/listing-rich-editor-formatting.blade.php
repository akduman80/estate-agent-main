<style>
    .listing-rich-editor-formatting {
        align-items: center;
        display: flex;
        gap: .25rem;
    }

    .listing-rich-editor-formatting__label {
        color: rgb(100 116 139);
        font-size: .75rem;
        font-weight: 700;
        padding-inline: .25rem;
    }

    .listing-rich-editor-formatting__button {
        border: 1px solid rgb(203 213 225);
        border-radius: .375rem;
        height: 1.75rem;
        min-width: 1.75rem;
    }

    .listing-rich-editor-formatting__button:hover {
        border-color: rgb(249 115 22);
    }

    .listing-rich-editor-formatting__button.trix-active {
        border-color: rgb(249 115 22);
        box-shadow: 0 0 0 2px rgb(249 115 22 / .25);
    }
</style>

<script>
    (() => {
        const textColors = [
            ['Siyah', '#111827', 'listingTextBlack'],
            ['Gri', '#475569', 'listingTextSlate'],
            ['Kırmızı', '#dc2626', 'listingTextRed'],
            ['Turuncu', '#ea580c', 'listingTextOrange'],
            ['Yeşil', '#16a34a', 'listingTextGreen'],
            ['Mavi', '#2563eb', 'listingTextBlue'],
        ];

        const highlightColors = [
            ['Sarı vurgu', '#fef08a', 'listingHighlightYellow'],
            ['Turuncu vurgu', '#fed7aa', 'listingHighlightOrange'],
            ['Yeşil vurgu', '#bbf7d0', 'listingHighlightGreen'],
            ['Mavi vurgu', '#bfdbfe', 'listingHighlightBlue'],
        ];

        const textColorAttributes = textColors.map(([, , attribute]) => attribute);
        const highlightColorAttributes = highlightColors.map(([, , attribute]) => attribute);

        const toHex = (value) => {
            if (! value) return null;

            const normalized = value.trim().toLowerCase();

            if (/^#[0-9a-f]{6}$/.test(normalized)) return normalized;

            if (/^#[0-9a-f]{3}$/.test(normalized)) {
                return '#' + normalized.slice(1).split('').map((char) => char + char).join('');
            }

            const rgb = normalized.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);

            if (! rgb) return null;

            return '#' + rgb.slice(1, 4).map((part) => Number(part).toString(16).padStart(2, '0')).join('');
        };

        const configureTrix = () => {
            if (! window.Trix || window.__listingRichEditorFormattingConfigured) return;

            window.__listingRichEditorFormattingConfigured = true;

            textColors.forEach(([label, color, attribute]) => {
                window.Trix.config.textAttributes[attribute] = {
                    inheritable: true,
                    parser: (element) => toHex(window.getComputedStyle(element).color) === color,
                    style: { color },
                };
            });

            highlightColors.forEach(([label, color, attribute]) => {
                window.Trix.config.textAttributes[attribute] = {
                    inheritable: true,
                    parser: (element) => toHex(window.getComputedStyle(element).backgroundColor) === color,
                    style: { backgroundColor: color },
                };
            });
        };

        const applyExclusiveAttribute = (editor, attribute, attributes) => {
            if (! editor?.editor) return;

            const wasActive = editor.editor.attributeIsActive(attribute);

            attributes.forEach((candidate) => editor.editor.deactivateAttribute(candidate));

            if (! wasActive) {
                editor.editor.activateAttribute(attribute);
            }

            editor.editorController?.render();
            editor.focus();
            updateToolbarState(editor);
        };

        const createButton = ({ title, attribute, color, isHighlight = false }) => {
            const button = document.createElement('button');

            button.className = 'listing-rich-editor-formatting__button';
            button.dataset.listingFormattingAttribute = attribute;
            button.style.backgroundColor = color;
            button.tabIndex = -1;
            button.title = title;
            button.type = 'button';

            if (! isHighlight) {
                button.style.boxShadow = `inset 0 -4px 0 ${color}`;
                button.style.backgroundColor = '#ffffff';
            }

            return button;
        };

        const updateToolbarState = (editor) => {
            if (! editor?.editor) return;

            const toolbar = document.getElementById(editor.getAttribute('toolbar'));

            toolbar?.querySelectorAll('[data-listing-formatting-attribute]').forEach((button) => {
                button.classList.toggle(
                    'trix-active',
                    editor.editor.attributeIsActive(button.dataset.listingFormattingAttribute),
                );
            });
        };

        const addToolbarControls = (editor) => {
            if (! editor.matches('[data-listing-description-editor="true"]')) return;

            const toolbar = document.getElementById(editor.getAttribute('toolbar'));

            if (! toolbar || toolbar.querySelector('[data-listing-formatting-controls]')) return;

            const group = document.createElement('div');
            group.className = 'listing-rich-editor-formatting';
            group.dataset.listingFormattingControls = 'true';
            group.dataset.trixButtonGroup = 'listing-formatting-tools';

            const textLabel = document.createElement('span');
            textLabel.className = 'listing-rich-editor-formatting__label';
            textLabel.textContent = 'Renk';
            group.appendChild(textLabel);

            textColors.forEach(([title, color, attribute]) => {
                const button = createButton({ title, color, attribute });
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    applyExclusiveAttribute(editor, attribute, textColorAttributes);
                });
                group.appendChild(button);
            });

            const highlightLabel = document.createElement('span');
            highlightLabel.className = 'listing-rich-editor-formatting__label';
            highlightLabel.textContent = 'Vurgu';
            group.appendChild(highlightLabel);

            highlightColors.forEach(([title, color, attribute]) => {
                const button = createButton({ title, color, attribute, isHighlight: true });
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    applyExclusiveAttribute(editor, attribute, highlightColorAttributes);
                });
                group.appendChild(button);
            });

            const textTools = toolbar.querySelector('[data-trix-button-group="text-tools"]');
            const toolbarRow = toolbar.querySelector('.flex.gap-x-3');

            if (textTools?.parentNode) {
                textTools.after(group);
            } else {
                toolbarRow?.prepend(group);
            }

            editor.addEventListener('trix-selection-change', () => updateToolbarState(editor));
            editor.addEventListener('trix-change', () => updateToolbarState(editor));
            updateToolbarState(editor);
        };

        document.addEventListener('trix-before-initialize', configureTrix);
        document.addEventListener('trix-initialize', (event) => addToolbarControls(event.target));

        configureTrix();
    })();
</script>
