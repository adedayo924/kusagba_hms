(function () {
  'use strict';

  /* ------------------------------------------------------------ Mobile drawer
   * The drawer used to open and close on a single class toggle with no backdrop,
   * no Escape handler and no state on the toggle button, so keyboard and screen
   * reader users had no way to open it reliably or to find out whether it was
   * open. Focus is now moved into the drawer and returned on close.
   */
  var toggle = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('sidebar');

  if (toggle && sidebar) {
    var backdrop = document.getElementById('sidebarBackdrop');

    function isOpen() {
      return sidebar.classList.contains('show');
    }

    function setOpen(open) {
      sidebar.classList.toggle('show', open);
      document.body.classList.toggle('sidebar-open', open);
      if (backdrop) backdrop.classList.toggle('show', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      var isMobile = window.matchMedia('(max-width: 991px)').matches;
      sidebar.setAttribute('aria-hidden', (isMobile && !open) ? 'true' : 'false');
    }

    // Initialise the exposed state rather than relying on the markup default.
    setOpen(false);

    toggle.addEventListener('click', function () {
      var next = !isOpen();
      setOpen(next);
      if (next) {
        var firstLink = sidebar.querySelector('a, button');
        if (firstLink) firstLink.focus();
      } else {
        toggle.focus();
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', function () {
        setOpen(false);
        toggle.focus();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen()) {
        setOpen(false);
        toggle.focus();
      }
    });

    // Following a link should not leave the drawer covering the new page.
    sidebar.addEventListener('click', function (e) {
      if (e.target.closest('a') && window.matchMedia('(max-width: 991px)').matches) {
        setOpen(false);
      }
    });

    // A resize past the breakpoint reveals the permanent sidebar; drop the
    // mobile-open state so the body does not stay scroll-locked.
    window.addEventListener('resize', function () {
      if (!window.matchMedia('(max-width: 991px)').matches && isOpen()) {
        setOpen(false);
      }
    });
  }

  // Confirm dialogs
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el) {
      e.preventDefault();
      var msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (window.confirm(msg)) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = el.href || el.getAttribute('href') || '';
        var token = document.querySelector('meta[name="csrf"]');
        if (token) {
          var inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = '_token';
          inp.value = token.getAttribute('content');
          form.appendChild(inp);
        }
        document.body.appendChild(form);
        form.submit();
      }
    }
  });

  // Auto-dismiss alerts
  document.querySelectorAll('.alert-dismissible').forEach(function (a) {
    setTimeout(function () {
      var b = window.bootstrap && bootstrap.Alert;
      if (b) {
        var al = new b.Alert(a);
        if (al.close) al.close();
      }
    }, 5000);
  });

  // Appointment status quick actions
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-appt-status');
    if (!btn) return;
    e.preventDefault();
    var meta = document.querySelector('meta[name="csrf"]');
    var fd = new URLSearchParams();
    fd.append('_token', meta ? meta.getAttribute('content') : '');
    fd.append('status', btn.getAttribute('data-action'));
    fetch(btn.getAttribute('data-href') || ('appointments/status/' + btn.getAttribute('data-id')), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString()
    }).then(function (r) { return r.json(); }).then(function (out) {
      if (out.ok) {
        var row = document.getElementById('appt-' + btn.getAttribute('data-id'));
        if (row) window.location.reload();
      } else {
        window.alert('Could not update appointment.');
      }
    }).catch(function () { window.alert('Request failed.'); });
  });

  // Two-column helper counter for inline item rows
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-row]');
    if (!add) return;
    e.preventDefault();
    var tbody = add.closest('table').querySelector('tbody') ||
                document.querySelector(add.getAttribute('data-add-row'));
    var row = add.getAttribute('data-row-template');
    if (tbody && row) {
      var html = row;
      var idx = tbody.children.length;
      html = html.replace(/{i}/g, idx);
      var tr = document.createElement('tr');
      tr.innerHTML = html;
      tbody.appendChild(tr);
    }
  });

  document.addEventListener('click', function (e) {
    var rm = e.target.closest('[data-remove-row]');
    if (!rm) return;
    e.preventDefault();
    var tr = rm.closest('tr');
    if (tr) tr.remove();
  });

  // Compute line amounts in any table with rows carrying data-unit and data-qty inputs
  document.addEventListener('input', function (e) {
    if (e.target.matches('[data-line-amount]')) {
      var tr = e.target.closest('tr');
      if (!tr) return;
      var qty = tr.querySelector('[data-line-qty]');
      var price = tr.querySelector('[data-line-price]');
      var out = tr.querySelector('[data-line-total]');
      var q = parseFloat(qty ? qty.value : 1) || 0;
      var p = parseFloat(price ? price.value : 0) || 0;
      if (out) out.value = (q * p).toFixed(2);
      recalcTotals();
    }
  });

  window.recalcTotals = function recalcTotals() {
    var rows = document.querySelectorAll('tr[data-line-row]');
    var subtotal = 0;
    rows.forEach(function (r) {
      var total = r.querySelector('[data-line-total]');
      if (total) subtotal += parseFloat(total.value) || 0;
    });
    var st = document.getElementById('lineSubtotal');
    var tax = document.getElementById('lineTax');
    var grand = document.getElementById('lineGrand');
    if (st && grand) {
      var t = tax ? parseFloat(tax.value) || 0 : 0;
      st.textContent = subtotal.toLocaleString(undefined, { minimumFractionDigits: 2 });
      grand.textContent = (subtotal + t).toLocaleString(undefined, { minimumFractionDigits: 2 });
    }
  };

})();