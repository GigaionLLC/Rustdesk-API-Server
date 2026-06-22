/*
 * rustdesk-api admin — app.js
 * jQuery helpers for the dark dashboard: AJAX (bearer/CSRF), live-save buttons,
 * toasts, sidebar toggle, theme persistence, and confirm-before-delete.
 * No build step: plain ES5-friendly jQuery so it runs straight from /public/assets.
 */
(function ($, window, document) {
  'use strict';

  var RD = window.RD || {};

  /* --------------------------------------------------------------- API client */
  RD.api = function (opts) {
    opts = opts || {};
    var headers = $.extend({ 'Accept': 'application/json' }, opts.headers || {});
    var token = RD.token();
    if (token) { headers['Authorization'] = 'Bearer ' + token; }
    var csrf = $('meta[name="csrf-token"]').attr('content');
    if (csrf) { headers['X-CSRF-TOKEN'] = csrf; }

    return $.ajax({
      url: opts.url,
      method: opts.method || 'GET',
      data: opts.json ? JSON.stringify(opts.data) : opts.data,
      contentType: opts.json ? 'application/json' : (opts.contentType || 'application/x-www-form-urlencoded'),
      headers: headers,
      dataType: 'json'
    });
  };

  RD.token = function (value) {
    if (typeof value !== 'undefined') {
      if (value === null) { try { localStorage.removeItem('rd_token'); } catch (e) {} }
      else { try { localStorage.setItem('rd_token', value); } catch (e) {} }
      return value;
    }
    try { return localStorage.getItem('rd_token'); } catch (e) { return null; }
  };

  /* ------------------------------------------------------------------- Toasts */
  RD.toast = function (message, type) {
    type = type || 'info';
    var $wrap = $('.rd-toasts');
    if (!$wrap.length) { $wrap = $('<div class="rd-toasts"></div>').appendTo('body'); }
    var icons = { success: 'ri-checkbox-circle-line', error: 'ri-error-warning-line', info: 'ri-information-line' };
    var $t = $('<div class="rd-toast rd-toast--' + type + '">' +
      '<i class="' + (icons[type] || icons.info) + '"></i>' +
      '<span></span></div>');
    $t.find('span').text(message);
    $wrap.append($t);
    window.setTimeout(function () {
      $t.css({ transition: 'opacity .25s', opacity: 0 });
      window.setTimeout(function () { $t.remove(); }, 260);
    }, type === 'error' ? 6000 : 3200);
  };

  /* ---------------------------------------------------- Live-save form binding
   * Markup:
   *   <form class="rd-liveform" data-url="/admin/users/5" data-method="PUT">
   *     <input name="email" ...>
   *     <button type="submit" class="rd-btn rd-btn--save" data-state="idle">Save</button>
   *   </form>
   * Button state machine: idle -> dirty -> saving -> saved|error -> idle
   */
  function setState($btn, state) {
    $btn.attr('data-state', state);
    var labels = { idle: 'Save', dirty: 'Save changes', saving: 'Saving…', saved: 'Saved', error: 'Retry' };
    var html = labels[state] || 'Save';
    if (state === 'saving') { html = '<span class="rd-spin"></span> Saving…'; }
    else if (state === 'saved') { html = '<i class="ri-check-line"></i> Saved'; }
    $btn.html(html).prop('disabled', state === 'saving');
  }

  RD.bindLiveForms = function (root) {
    $(root || document).find('form.rd-liveform').each(function () {
      var $form = $(this);
      var $btn = $form.find('.rd-btn--save').first();
      if (!$btn.length) { return; }
      setState($btn, 'idle');

      $form.on('input change', ':input', function () {
        if ($btn.attr('data-state') !== 'saving') { setState($btn, 'dirty'); }
      });

      $form.on('submit', function (e) {
        e.preventDefault();
        setState($btn, 'saving');
        var data = {};
        $.each($form.serializeArray(), function (_, f) { data[f.name] = f.value; });
        RD.api({
          url: $form.data('url'),
          method: ($form.data('method') || 'POST').toUpperCase(),
          json: true,
          data: data
        }).done(function (resp) {
          if (resp && resp.error) { setState($btn, 'error'); RD.toast(resp.error, 'error'); return; }
          setState($btn, 'saved');
          RD.toast('Saved successfully', 'success');
          window.setTimeout(function () { setState($btn, 'idle'); }, 1600);
        }).fail(function (xhr) {
          setState($btn, 'error');
          var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Save failed';
          RD.toast(msg, 'error');
        });
      });
    });
  };

  /* ----------------------------------------------------- Confirm-before-delete */
  RD.bindConfirms = function (root) {
    $(root || document).on('click', '[data-confirm]', function (e) {
      var msg = $(this).data('confirm') || 'Are you sure? This action cannot be undone.';
      if (!window.confirm(msg)) { e.preventDefault(); e.stopImmediatePropagation(); }
    });
  };

  /* ---------------------------------------------------------- Sidebar + theme */
  RD.bindShell = function () {
    $(document).on('click', '.rd-sidebar__toggle', function () {
      $('.rd-sidebar').toggleClass('is-open');
    });
    // Theme is dark-first; persist an override if a toggle exists.
    var saved;
    try { saved = localStorage.getItem('rd_theme'); } catch (e) {}
    if (saved) { document.documentElement.setAttribute('data-theme', saved); }
    $(document).on('click', '[data-theme-toggle]', function () {
      var cur = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', cur);
      try { localStorage.setItem('rd_theme', cur); } catch (e) {}
    });
  };

  /* ------------------------------------------------------------------- Charts */
  // Thin wrapper so pages can render an ApexCharts area chart with theme colors.
  RD.areaChart = function (el, series, categories, color) {
    if (!window.ApexCharts) { return null; }
    var chart = new window.ApexCharts(typeof el === 'string' ? document.querySelector(el) : el, {
      chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
      theme: { mode: 'dark' },
      series: series,
      colors: [color || '#6571ff'],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
      grid: { borderColor: '#1b2942', strokeDashArray: 4 },
      xaxis: { categories: categories || [], labels: { style: { colors: '#7987a1' } } },
      yaxis: { labels: { style: { colors: '#7987a1' } } },
      tooltip: { theme: 'dark' }
    });
    chart.render();
    return chart;
  };

  /* --------------------------------------------------------------------- Init */
  $(function () {
    RD.bindShell();
    RD.bindLiveForms(document);
    RD.bindConfirms(document);
  });

  window.RD = RD;
})(jQuery, window, document);
