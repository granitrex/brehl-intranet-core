(function () {
  'use strict';
  function update(select) {
    var form = select.closest('form');
    var fields = form ? form.querySelector('[data-brehl-third-party-fields]') : null;
    if (!fields) return;
    var visible = select.value === 'collision';
    fields.hidden = !visible;
    fields.querySelectorAll('[data-brehl-third-party-required]').forEach(function (input) { input.required = visible; });
  }
  document.querySelectorAll('[data-brehl-incident-type]').forEach(update);
  document.addEventListener('change', function (event) {
    if (event.target.matches('[data-brehl-incident-type]')) update(event.target);
  });
}());
