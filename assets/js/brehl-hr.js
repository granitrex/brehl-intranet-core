(function () {
  'use strict';

  function setMode(form, employee) {
    var fields = ['first_name', 'last_name', 'email', 'personnel_number', 'department', 'position', 'phone', 'location', 'vehicle_license_plate', 'vacation_entitlement', 'vacation_carryover'];
    form.elements.employee_id.value = employee ? employee.id : 0;
    form.elements._wpnonce.value = employee ? employee.nonce : form.getAttribute('data-create-nonce');
    fields.forEach(function (name) {
      form.elements[name].value = employee ? (employee[name] || '') : '';
    });
    if (!employee) {
      form.elements.vacation_entitlement.value = '30';
      form.elements.vacation_carryover.value = '0';
    }
    form.elements.directory_visible.checked = employee ? !!employee.directory_visible : true;
    if (form.elements.account_active) form.elements.account_active.checked = employee ? !!employee.account_active : true;
    form.elements.password.value = '';
    form.elements.password_confirm.value = '';
    form.elements.password.required = !employee;
    form.elements.password_confirm.required = !employee;

    var root = form.closest('.brehl-hr');
    root.querySelector('[data-brehl-employee-form-title]').textContent = employee ? 'Mitarbeiter bearbeiten' : 'Mitarbeiter anlegen';
    root.querySelector('[data-brehl-password-label]').textContent = employee ? 'Neues Passwort (optional)' : 'Anfangspasswort';
    root.querySelector('[data-brehl-employee-submit]').textContent = employee ? 'Änderungen speichern' : 'Mitarbeiter anlegen';
    root.querySelector('[data-brehl-employee-cancel]').hidden = !employee;
    root.querySelector('[data-brehl-account-active]').hidden = !employee;
    root.querySelector('[data-brehl-password-reset]').hidden = !employee;
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  document.addEventListener('click', function (event) {
    var edit = event.target.closest('[data-brehl-edit-employee]');
    var cancel = event.target.closest('[data-brehl-employee-cancel]');
    if (!edit && !cancel) return;
    event.preventDefault();
    var root = event.target.closest('.brehl-hr');
    var form = root.querySelector('[data-brehl-employee-form]');
    if (edit) {
      try { setMode(form, JSON.parse(edit.getAttribute('data-brehl-edit-employee'))); } catch (_) {}
    } else {
      setMode(form, null);
    }
  });
}());
