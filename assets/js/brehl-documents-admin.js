(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    var select = event.target.closest('[data-brehl-document-select]');
    var remove = event.target.closest('[data-brehl-document-remove]');
    var input = document.getElementById('brehl-document-url');

    if (select && input && window.wp && wp.media) {
      event.preventDefault();
      var frame = wp.media({
        title: 'Dokument auswählen oder hochladen',
        button: { text: 'Dokument verwenden' },
        multiple: false
      });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        input.value = attachment.url || '';
        var removeButton = document.querySelector('[data-brehl-document-remove]');
        if (removeButton) removeButton.hidden = !input.value;
      });
      frame.open();
    }

    if (remove && input) {
      event.preventDefault();
      input.value = '';
      remove.hidden = true;
    }
  });
}());
