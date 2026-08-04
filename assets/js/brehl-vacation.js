(function () {
    'use strict';

    var months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    var weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

    function iso(date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    }

    function display(date) {
        return String(date.getDate()).padStart(2, '0') + '.' + String(date.getMonth() + 1).padStart(2, '0') + '.' + date.getFullYear();
    }

    function parse(value) {
        var parts = String(value || '').split('-').map(Number);
        return parts.length === 3 ? new Date(parts[0], parts[1] - 1, parts[2], 12) : null;
    }

    function closeOthers(current) {
        document.querySelectorAll('.mbs-calendar').forEach(function (calendar) {
            if (calendar !== current) calendar.hidden = true;
        });
    }

    function init(picker) {
        if (picker.dataset.ready) return;
        picker.dataset.ready = '1';
        var visible = picker.querySelector('.mbs-date-picker__display');
        var value = picker.querySelector('.mbs-date-picker__value');
        var button = picker.querySelector('.mbs-date-picker__button');
        var calendar = picker.querySelector('.mbs-calendar');
        var minimum = parse(picker.dataset.min) || new Date();
        var cursor = new Date(minimum.getFullYear(), minimum.getMonth(), 1, 12);

        function render() {
            var selected = parse(value.value);
            var first = new Date(cursor.getFullYear(), cursor.getMonth(), 1, 12);
            var offset = (first.getDay() + 6) % 7;
            var lastDay = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0, 12).getDate();
            var html = '<div class="mbs-calendar__head"><button type="button" data-move="-1" aria-label="Vorheriger Monat">‹</button><strong>' + months[cursor.getMonth()] + ' ' + cursor.getFullYear() + '</strong><button type="button" data-move="1" aria-label="Nächster Monat">›</button></div><div class="mbs-calendar__week">' + weekdays.map(function (day) { return '<span>' + day + '</span>'; }).join('') + '</div><div class="mbs-calendar__days">';
            for (var empty = 0; empty < offset; empty += 1) html += '<span></span>';
            for (var day = 1; day <= lastDay; day += 1) {
                var date = new Date(cursor.getFullYear(), cursor.getMonth(), day, 12);
                var disabled = iso(date) < iso(minimum);
                var chosen = selected && iso(date) === iso(selected);
                html += '<button type="button" data-date="' + iso(date) + '"' + (disabled ? ' disabled' : '') + (chosen ? ' class="is-selected"' : '') + '>' + day + '</button>';
            }
            calendar.innerHTML = html + '</div>';
        }

        function toggle() {
            closeOthers(calendar);
            calendar.hidden = !calendar.hidden;
            if (!calendar.hidden) render();
        }

        visible.addEventListener('click', toggle);
        button.addEventListener('click', toggle);
        calendar.addEventListener('click', function (event) {
            var move = event.target.closest('[data-move]');
            if (move) {
                cursor = new Date(cursor.getFullYear(), cursor.getMonth() + Number(move.dataset.move), 1, 12);
                render();
                return;
            }
            var day = event.target.closest('[data-date]');
            if (!day || day.disabled) return;
            var date = parse(day.dataset.date);
            value.value = day.dataset.date;
            visible.value = display(date);
            visible.setCustomValidity('');
            calendar.hidden = true;
        });
        visible.closest('form').addEventListener('submit', function (event) {
            if (!value.value) {
                event.preventDefault();
                visible.setCustomValidity('Bitte wählen Sie ein Datum aus.');
                visible.reportValidity();
            }
        });
    }

    function boot() {
        document.querySelectorAll('.mbs-date-picker').forEach(init);
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.mbs-date-picker')) closeOthers(null);
    });
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
    window.addEventListener('elementor/frontend/init', boot);
}());
