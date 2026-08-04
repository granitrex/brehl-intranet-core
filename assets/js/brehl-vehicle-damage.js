(function () {
  'use strict';
  function update(toggle) {
    var form = toggle.closest('form');
    var fields = form ? form.querySelector('[data-brehl-third-party-fields]') : null;
    if (!fields) return;
    fields.hidden = !toggle.checked;
    fields.querySelectorAll('[data-brehl-third-party-required]').forEach(function (input) { input.required = toggle.checked; });
  }
  document.querySelectorAll('[data-brehl-third-party-toggle]').forEach(update);
  document.addEventListener('change', function (event) {
    if (event.target.matches('[data-brehl-third-party-toggle]')) update(event.target);
  });
}());
