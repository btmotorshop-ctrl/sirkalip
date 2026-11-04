/* Ön Muhasebe – yeşil tarih seçici (gg.aa.yyyy), input[type=date] alanlarını otomatik dönüştürür */
(function () {
  var MONTHS = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
  var DAYS = ['Pzt', 'Sa', 'Çr', 'Pr', 'Cu', 'Ct', 'Pz'];
  var pop = null, cur = null, view = null, mode = 'days';
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
  var show = function (s) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? m[3] + '.' + m[2] + '.' + m[1] : ''; };
  var parseIso = function (s) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; };
  var parseTr = function (s) {
    s = (s || '').trim(); if (!s) return '';
    var m = /^(\d{1,2})[.\/\-](\d{1,2})[.\/\-](\d{2}|\d{4})$/.exec(s); if (!m) return null;
    var y = +m[3]; if (y < 100) y += 2000;
    var d = new Date(y, +m[2] - 1, +m[1]);
    if (d.getFullYear() !== y || d.getMonth() !== +m[2] - 1 || d.getDate() !== +m[1]) return null;
    return iso(d);
  };

  function build() {
    pop = document.createElement('div'); pop.className = 'dp-pop'; pop.hidden = true;
    pop.addEventListener('mousedown', function (e) { e.preventDefault(); });
    document.body.appendChild(pop);
    document.addEventListener('mousedown', function (e) { if (pop && !pop.hidden && !pop.contains(e.target) && (!cur || e.target !== cur.vis)) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    window.addEventListener('resize', close);
  }
  function close() { if (pop) pop.hidden = true; cur = null; }

  function open(c) {
    if (!pop) build();
    cur = c; mode = 'days';
    var d = parseIso(c.hid.value) || new Date();
    view = new Date(d.getFullYear(), d.getMonth(), 1);
    draw(); pop.hidden = false; place();
  }
  function place() {
    var r = cur.vis.getBoundingClientRect(), w = pop.offsetWidth, h = pop.offsetHeight;
    var left = Math.min(Math.max(8, r.left + window.scrollX), window.scrollX + document.documentElement.clientWidth - w - 8);
    var top = r.bottom + window.scrollY + 6;
    if (r.bottom + h + 10 > window.innerHeight && r.top > h + 10) top = r.top + window.scrollY - h - 6;
    pop.style.left = left + 'px'; pop.style.top = top + 'px';
  }
  function choose(val) {
    cur.hid.value = val; cur.vis.value = show(val); cur.vis.classList.remove('dp-bad');
    ['input', 'change'].forEach(function (t) { cur.hid.dispatchEvent(new Event(t, { bubbles: true })); });
    close();
  }
  function draw() {
    var sel = parseIso(cur.hid.value), today = new Date(), h = '';
    if (mode === 'months') {
      h += '<div class="dp-h"><button type="button" data-a="py">«</button><b data-a="days">' + view.getFullYear() + '</b><button type="button" data-a="ny">»</button></div><div class="dp-m">';
      for (var i = 0; i < 12; i++) h += '<button type="button" data-m="' + i + '" class="' + (sel && sel.getFullYear() === view.getFullYear() && sel.getMonth() === i ? 'on' : '') + '">' + MONTHS[i].slice(0, 3) + '</button>';
      h += '</div>';
    } else {
      h += '<div class="dp-h"><button type="button" data-a="pm">«</button><b data-a="months">' + MONTHS[view.getMonth()] + ' ' + view.getFullYear() + '</b><button type="button" data-a="nm">»</button></div>';
      h += '<div class="dp-w">' + DAYS.map(function (x) { return '<span>' + x + '</span>'; }).join('') + '</div><div class="dp-d">';
      var first = new Date(view.getFullYear(), view.getMonth(), 1), off = (first.getDay() + 6) % 7;
      var start = new Date(view.getFullYear(), view.getMonth(), 1 - off);
      for (var k = 0; k < 42; k++) {
        var d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + k), cls = [];
        if (d.getMonth() !== view.getMonth()) cls.push('out');
        if (iso(d) === iso(today)) cls.push('today');
        if (sel && iso(d) === iso(sel)) cls.push('on');
        h += '<button type="button" data-d="' + iso(d) + '" class="' + cls.join(' ') + '">' + d.getDate() + '</button>';
      }
      h += '</div><div class="dp-f"><button type="button" data-a="today">Bugün</button><button type="button" data-a="clear">Temizle</button></div>';
    }
    pop.innerHTML = h;
    [].forEach.call(pop.querySelectorAll('button,b'), function (b) {
      b.onclick = function () {
        var a = b.getAttribute('data-a'), dd = b.getAttribute('data-d'), mm = b.getAttribute('data-m');
        if (dd) return choose(dd);
        if (mm !== null) { view = new Date(view.getFullYear(), +mm, 1); mode = 'days'; return draw(); }
        if (a === 'pm') view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
        else if (a === 'nm') view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
        else if (a === 'py') view = new Date(view.getFullYear() - 1, view.getMonth(), 1);
        else if (a === 'ny') view = new Date(view.getFullYear() + 1, view.getMonth(), 1);
        else if (a === 'months') mode = 'months';
        else if (a === 'days') mode = 'days';
        else if (a === 'today') return choose(iso(new Date()));
        else if (a === 'clear') return choose('');
        draw(); place();
      };
    });
  }

  function enhance(inp) {
    if (inp.getAttribute('data-dp') || !/acc-body/.test(document.body.className)) return;
    inp.setAttribute('data-dp', '1');
    var hid = document.createElement('input');
    hid.type = 'hidden'; hid.name = inp.name; hid.value = inp.value || '';
    if (inp.id) { hid.id = inp.id + '_iso'; }
    inp.removeAttribute('name'); inp.type = 'text'; inp.value = show(inp.value); inp.placeholder = 'gg.aa.yyyy';
    inp.autocomplete = 'off'; inp.classList.add('dp-in'); inp.maxLength = 10;
    inp.parentNode.insertBefore(hid, inp.nextSibling);
    var c = { vis: inp, hid: hid };
    inp.addEventListener('focus', function () { open(c); });
    inp.addEventListener('click', function () { if (!cur || cur.vis !== inp) open(c); });
    inp.addEventListener('input', function () {
      var v = inp.value.replace(/[^\d.]/g, '');
      if (/^\d{2}$/.test(v) || /^\d{2}\.\d{2}$/.test(v)) { if (inp.value.length > (inp._l || 0)) v += '.'; }
      inp.value = v; inp._l = v.length;
    });
    var commit = function () {
      var p = parseTr(inp.value);
      if (p === null) { inp.classList.add('dp-bad'); return; }
      inp.classList.remove('dp-bad'); hid.value = p;
      ['input', 'change'].forEach(function (t) { hid.dispatchEvent(new Event(t, { bubbles: true })); });
    };
    inp.addEventListener('change', commit);
    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { commit(); if (pop && !pop.hidden) { e.preventDefault(); close(); } } });
    inp.addEventListener('blur', function () { setTimeout(function () { if (cur && cur.vis === inp && document.activeElement !== inp) { commit(); close(); } }, 120); });
  }
  function init() { [].forEach.call(document.querySelectorAll('input[type=date]'), enhance); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
