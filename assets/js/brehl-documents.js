(function () {
  'use strict';

  function update(library) {
    var query = (library.querySelector('[data-document-search]') || {}).value || '';
    var active = library.querySelector('[data-document-filter].is-active');
    var category = active ? active.getAttribute('data-document-filter') : 'all';
    var visible = 0;

    library.querySelectorAll('[data-document-card]').forEach(function (card) {
      var matchesText = !query || (card.getAttribute('data-search') || '').indexOf(query.toLocaleLowerCase()) !== -1;
      var matchesCategory = category === 'all' || card.getAttribute('data-category') === category;
      card.hidden = !(matchesText && matchesCategory);
      if (!card.hidden) visible += 1;
    });

    var empty = library.querySelector('.brehl-documents__no-results');
    if (empty) empty.hidden = visible !== 0;
  }

  document.addEventListener('input', function (event) {
    if (!event.target.matches('[data-document-search]')) return;
    update(event.target.closest('[data-brehl-documents]'));
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-document-filter]');
    if (!button) return;
    var library = button.closest('[data-brehl-documents]');
    library.querySelectorAll('[data-document-filter]').forEach(function (item) {
      item.classList.toggle('is-active', item === button);
    });
    update(library);
  });
}());
