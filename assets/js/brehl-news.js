(function () {
  'use strict';

  var config = window.MyBrehlNews || {};

  function request(action, data) {
    var body = new URLSearchParams();
    body.append('action', action);
    body.append('nonce', config.nonce || '');
    Object.keys(data || {}).forEach(function (key) { body.append(key, data[key]); });
    return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() }).then(function (response) { return response.json(); });
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('brehl-modal-open');
  }

  function applyFilters(feed) {
    var search = feed.querySelector('[data-brehl-news-search]');
    var active = feed.querySelector('[data-brehl-news-filter].is-active');
    var term = search ? search.value.trim().toLowerCase() : '';
    var category = active ? active.getAttribute('data-brehl-news-filter') : 'all';
    var visible = 0;
    feed.querySelectorAll('[data-news-card]').forEach(function (card) {
      var matchesText = !term || (card.getAttribute('data-search') || '').indexOf(term) !== -1;
      var matchesCategory = category === 'all' || card.getAttribute('data-category') === category;
      card.hidden = !(matchesText && matchesCategory);
      if (!card.hidden) visible += 1;
    });
    var empty = feed.querySelector('.brehl-news-no-results');
    if (empty) empty.hidden = visible !== 0;
  }

  document.addEventListener('input', function (event) {
    if (event.target.matches('[data-brehl-news-search]')) applyFilters(event.target.closest('.brehl-news-feed'));
  });

  document.addEventListener('click', function (event) {
    var notificationToggle = event.target.closest('[data-my-brehl-notifications-toggle]');
    if (notificationToggle) {
      var wrap = notificationToggle.closest('[data-my-brehl-notifications]');
      var panel = wrap ? wrap.querySelector('[data-my-brehl-notifications-panel]') : null;
      if (panel) {
        var open = panel.hasAttribute('hidden');
        document.querySelectorAll('[data-my-brehl-notifications-panel]').forEach(function (item) { item.setAttribute('hidden', ''); });
        if (open) panel.removeAttribute('hidden');
        notificationToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
      return;
    }

    var notificationNews = event.target.closest('[data-my-brehl-notification-news]');
    if (notificationNews) {
      var newsId = notificationNews.getAttribute('data-my-brehl-notification-news');
      var newsTrigger = document.querySelector('[data-brehl-news-open="' + newsId + '"]');
      var panelWrap = notificationNews.closest('[data-my-brehl-notifications-panel]');
      if (panelWrap) panelWrap.setAttribute('hidden', '');
      if (newsTrigger) { newsTrigger.click(); }
      else { request('my_brehl_news_read', { post_id: newsId }); }
      notificationNews.remove();
      var count = document.querySelector('[data-notification-count]');
      if (count) {
        var next = Math.max(0, parseInt(count.textContent || '1', 10) - 1);
        if (next) count.textContent = String(next); else count.remove();
      }
      return;
    }

    var filter = event.target.closest('[data-brehl-news-filter]');
    if (filter) {
      var feed = filter.closest('.brehl-news-feed');
      feed.querySelectorAll('[data-brehl-news-filter]').forEach(function (button) { button.classList.remove('is-active'); });
      filter.classList.add('is-active'); applyFilters(feed); return;
    }

    var trigger = event.target.closest('[data-brehl-news-open]');
    if (trigger) {
      event.preventDefault();
      var id = trigger.getAttribute('data-brehl-news-open');
      var modal = document.getElementById('brehl-news-modal-' + id);
      if (modal) {
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('brehl-modal-open');
        var close = modal.querySelector('[data-brehl-news-close]'); if (close) close.focus();
        request('my_brehl_news_read', { post_id: id }).then(function () {
          var card = trigger.closest('[data-news-card]');
          if (card) { card.classList.remove('is-unread'); card.classList.add('is-read'); var badge = card.querySelector('.brehl-news-unread'); if (badge) badge.remove(); }
        });
      }
      return;
    }

    var closeTrigger = event.target.closest('[data-brehl-news-close]');
    if (closeTrigger) { closeModal(closeTrigger.closest('.brehl-news-modal')); return; }
    if (event.target.classList.contains('brehl-news-modal')) { closeModal(event.target); return; }

    var reaction = event.target.closest('[data-brehl-reaction]');
    if (reaction) {
      reaction.disabled = true;
      request('my_brehl_news_react', { post_id: reaction.getAttribute('data-news-id'), reaction: reaction.getAttribute('data-brehl-reaction') }).then(function (result) {
        if (result.success) { reaction.classList.toggle('is-active', !!result.data.active); var count = reaction.querySelector('[data-reaction-count]'); if (count) count.textContent = result.data.count; }
      }).finally(function () { reaction.disabled = false; });
    }
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-brehl-comment-form]');
    if (!form) return;
    event.preventDefault();
    var textarea = form.querySelector('textarea');
    var status = form.querySelector('[data-comment-status]');
    var button = form.querySelector('button[type="submit"]');
    var text = textarea.value.trim();
    if (!text) { if (status) status.textContent = (config.messages && config.messages.commentEmpty) || 'Bitte geben Sie einen Kommentar ein.'; return; }
    button.disabled = true;
    request('my_brehl_news_comment', { post_id: form.getAttribute('data-news-id'), comment: text }).then(function (result) {
      if (!result.success) throw new Error('comment');
      var list = form.closest('.brehl-news-comments').querySelector('[data-comment-list]');
      list.insertAdjacentHTML('beforeend', result.data.html);
      var total = form.closest('.brehl-news-comments').querySelector('[data-comment-total]');
      if (total) total.textContent = result.data.total;
      textarea.value = ''; if (status) status.textContent = (config.messages && config.messages.commentSaved) || 'Kommentar wurde veröffentlicht.';
    }).catch(function () { if (status) status.textContent = (config.messages && config.messages.error) || 'Es ist ein Fehler aufgetreten.'; }).finally(function () { button.disabled = false; });
  });

  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { closeModal(document.querySelector('.brehl-news-modal.is-open')); document.querySelectorAll('[data-my-brehl-notifications-panel]').forEach(function (item) { item.setAttribute('hidden', ''); }); } });
}());
