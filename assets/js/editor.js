(function () {
    'use strict';

    var textarea = document.getElementById('content');
    var toolbar = document.querySelector('.editor-toolbar');
    var imageSelect = document.getElementById('editor_image_url');
    var imageWidth = document.getElementById('editor_image_width');
    var imageAlign = document.getElementById('editor_image_align');
    var imageCaption = document.getElementById('editor_image_caption');
    var imageInsertButton = document.querySelector('.editor-image-insert');
    var savedSelection = { start: 0, end: 0 };

    if (!textarea || !toolbar) {
        return;
    }

    function rememberSelection() {
        savedSelection = {
            start: textarea.selectionStart,
            end: textarea.selectionEnd
        };
    }

    function restoreSelection() {
        textarea.focus();
        textarea.setSelectionRange(savedSelection.start, savedSelection.end);
    }

    function currentSelection() {
        return {
            start: textarea.selectionStart,
            end: textarea.selectionEnd,
            text: textarea.value.slice(textarea.selectionStart, textarea.selectionEnd)
        };
    }

    function replaceSelection(text, selectStartOffset, selectEndOffset) {
        var selection = currentSelection();
        var before = textarea.value.slice(0, selection.start);
        var after = textarea.value.slice(selection.end);
        var selectStart = typeof selectStartOffset === 'number' ? selection.start + selectStartOffset : selection.start + text.length;
        var selectEnd = typeof selectEndOffset === 'number' ? selection.start + selectEndOffset : selectStart;

        textarea.value = before + text + after;
        textarea.focus();
        textarea.setSelectionRange(selectStart, selectEnd);
        rememberSelection();
    }

    function wrapSelection(template) {
        var selection = currentSelection();
        var marker = template.indexOf('|');
        var selected = selection.text || 'Text';
        var text = template.replace('|', selected);
        var selectedStart = marker >= 0 ? marker : text.length;
        var selectedEnd = selectedStart + selected.length;

        replaceSelection(text, selectedStart, selectedEnd);
    }

    function listSelection(tagName) {
        var selection = currentSelection();
        var lines = (selection.text || 'List item').split(/\r?\n/);
        var items = lines
            .filter(function (line) {
                return line.trim() !== '';
            })
            .map(function (line) {
                return '    <li>' + line.trim() + '</li>';
            })
            .join('\n');
        var text = '<' + tagName + '>\n' + items + '\n</' + tagName + '>';

        replaceSelection(text);
    }

    function clearSelection() {
        var selection = currentSelection();
        var cleaned = selection.text.replace(/<\/?[^>]+>/g, '');

        if (!selection.text) {
            return;
        }

        replaceSelection(cleaned, 0, cleaned.length);
    }

    function escapeAttribute(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function imageMarkup(url, altText, width, align, caption) {
        var className = 'image-' + (align || 'center');
        var image = '<img src="' + escapeAttribute(url) + '" alt="' + escapeAttribute(altText) + '">';
        var figcaption = caption ? '\n    <figcaption>' + escapeHtml(caption) + '</figcaption>' : '';

        return '<figure class="content-image ' + className + '" style="max-width: ' + escapeAttribute(width || '100%') + ';">\n    ' + image + figcaption + '\n</figure>';
    }

    textarea.addEventListener('keyup', rememberSelection);
    textarea.addEventListener('mouseup', rememberSelection);
    textarea.addEventListener('select', rememberSelection);
    textarea.addEventListener('focus', rememberSelection);

    function preserveSelectionOnButton(event) {
        if (event.target.closest('button')) {
            event.preventDefault();
            restoreSelection();
        }
    }

    function handleButtonClick(event) {
        var button = event.target.closest('button');

        if (!button) {
            return;
        }

        restoreSelection();

        var wrap = button.getAttribute('data-editor-wrap');
        var action = button.getAttribute('data-editor-action');

        if (wrap) {
            wrapSelection(wrap);
            return;
        }

        if (action === 'link') {
            var selection = currentSelection();
            var label = selection.text || 'Link text';
            var url = window.prompt('Enter link URL', 'https://');
            if (url) {
                replaceSelection('<a href="' + url.replace(/"/g, '&quot;') + '">' + label + '</a>');
            }
        }

        if (action === 'image') {
            var imageUrl = imageSelect ? imageSelect.value : '';
            var altText = window.prompt('Image alt text', currentSelection().text || '');
            var width = imageWidth ? imageWidth.value : '100%';
            var align = imageAlign ? imageAlign.value : 'center';
            var caption = imageCaption ? imageCaption.value : '';

            if (!imageUrl) {
                imageUrl = window.prompt('Image URL', 'https://');
            }

            if (imageUrl) {
                replaceSelection(imageMarkup(imageUrl, altText, width, align, caption));
            }
        }

        if (action === 'ul') {
            listSelection('ul');
        }

        if (action === 'ol') {
            listSelection('ol');
        }

        if (action === 'hr') {
            replaceSelection('<hr>');
        }

        if (action === 'clear') {
            clearSelection();
        }
    }

    toolbar.addEventListener('mousedown', preserveSelectionOnButton);
    toolbar.addEventListener('click', handleButtonClick);

    if (imageInsertButton) {
        imageInsertButton.addEventListener('mousedown', preserveSelectionOnButton);
        imageInsertButton.addEventListener('click', handleButtonClick);
    }

    rememberSelection();
})();
