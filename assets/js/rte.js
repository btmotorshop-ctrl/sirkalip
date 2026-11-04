/* SIR KALIP – Yönetim paneli görsel metin editörü (WYSIWYG)
   Kullanım: <textarea data-rte> → otomatik editöre dönüşür, kayıtta HTML olarak gönderilir. */
(function () {
  var css = '.rte{border:1px solid #d9dde3;border-radius:8px;background:#fff;overflow:hidden}' +
    '.rte-bar{display:flex;flex-wrap:wrap;gap:4px;align-items:center;padding:6px;background:#f5f6f8;border-bottom:1px solid #d9dde3;position:sticky;top:0;z-index:5}' +
    '.rte-bar .g{display:flex;gap:2px;align-items:center;padding-right:6px;margin-right:2px;border-right:1px solid #d9dde3}' +
    '.rte-bar .g:last-child{border-right:0}' +
    '.rte-bar button{min-width:30px;height:30px;padding:0 7px;border:1px solid transparent;border-radius:5px;background:transparent;font-size:13px;font-weight:700;cursor:pointer;color:#222;line-height:1}' +
    '.rte-bar button:hover{background:#fff;border-color:#d9dde3}' +
    '.rte-bar button.on{background:#e30613;color:#fff}' +
    '.rte-bar select{height:30px;border:1px solid #d9dde3;border-radius:5px;background:#fff;font-size:12px;padding:0 4px;max-width:130px}' +
    '.rte-bar label.c{display:flex;align-items:center;gap:3px;font-size:12px;font-weight:700;cursor:pointer}' +
    '.rte-bar input[type=color]{width:26px;height:26px;padding:0;border:1px solid #d9dde3;border-radius:4px;background:#fff;cursor:pointer}' +
    '.rte-area{min-height:220px;max-height:640px;overflow:auto;padding:14px 16px;outline:none;font-size:15px;line-height:1.7;color:#222}' +
    '.rte-area p{margin:0 0 12px}.rte-area ul{list-style:disc;padding-left:24px;margin:0 0 12px}.rte-area ol{list-style:decimal;padding-left:24px;margin:0 0 12px}' +
    '.rte-area h2{font-size:22px;font-weight:800;margin:16px 0 8px}.rte-area h3{font-size:18px;font-weight:700;margin:14px 0 6px}.rte-area h4{font-size:16px;font-weight:700;margin:12px 0 6px}' +
    '.rte-area a{color:#e30613;text-decoration:underline}.rte-area img{max-width:100%;height:auto}' +
    '.rte-area table{border-collapse:collapse;width:100%;margin:0 0 12px}.rte-area td,.rte-area th{border:1px solid #d9dde3;padding:6px 10px;min-width:40px}' +
    '.rte-area img.sel{outline:3px solid #e30613}' +
    '.rte-src{display:none;width:100%;min-height:260px;padding:12px;border:0;outline:none;font:12px/1.5 ui-monospace,Consolas,monospace;resize:vertical}' +
    '.rte.is-src .rte-area{display:none}.rte.is-src .rte-src{display:block}';
  var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

  var FONTS = [['', 'Yazı tipi'], ['Arial, Helvetica, sans-serif', 'Arial'], ['Georgia, serif', 'Georgia'], ['"Times New Roman", Times, serif', 'Times New Roman'],
    ['Verdana, Geneva, sans-serif', 'Verdana'], ['Tahoma, Geneva, sans-serif', 'Tahoma'], ['"Trebuchet MS", sans-serif', 'Trebuchet'], ['"Courier New", monospace', 'Courier New'],
    ['Impact, Charcoal, sans-serif', 'Impact'], ['"Segoe UI", system-ui, sans-serif', 'Segoe UI']];
  var SIZES = ['', 12, 13, 14, 15, 16, 18, 20, 22, 24, 28, 32, 36, 42, 48];
  var LH = [['', 'Satır aralığı'], ['1', '1'], ['1.2', '1.2'], ['1.5', '1.5'], ['1.8', '1.8'], ['2', '2'], ['2.5', '2.5']];
  var BLOCKS = [['p', 'Normal'], ['h2', 'Başlık 1'], ['h3', 'Başlık 2'], ['h4', 'Başlık 3']];

  function h(tag, attrs, html) { var el = document.createElement(tag); for (var k in (attrs || {})) el.setAttribute(k, attrs[k]); if (html != null) el.innerHTML = html; return el; }

  function init(ta) {
    if (ta._rte) return; ta._rte = true;
    // <label> içindeki tıklamalar araç çubuğunu tetiklemesin diye label → div
    var lab = ta.closest('label');
    if (lab) { var d = document.createElement('div'); d.className = lab.className; while (lab.firstChild) d.appendChild(lab.firstChild); lab.parentNode.replaceChild(d, lab); }

    var wrap = h('div', { 'class': 'rte' }), bar = h('div', { 'class': 'rte-bar' });
    var area = h('div', { 'class': 'rte-area', contenteditable: 'true', spellcheck: 'false' }), src = h('textarea', { 'class': 'rte-src', spellcheck: 'false' });
    var saved = null;

    function load() {
      var v = ta.value || '';
      if (v && !/<[a-z][\s\S]*>/i.test(v)) v = v.split(/\n{2,}/).map(function (s) { return '<p>' + s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\n/g, '<br>') + '</p>'; }).join('');
      area.innerHTML = v;
    }
    function sync() { if (wrap.classList.contains('is-src')) return; var v = area.innerHTML; ta.value = (v === '<br>' || v === '<p><br></p>') ? '' : v; }
    function saveSel() { var s = getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) saved = s.getRangeAt(0).cloneRange(); }
    function restoreSel() { area.focus(); if (saved) { var s = getSelection(); s.removeAllRanges(); s.addRange(saved); } }
    function cmd(c, v) { restoreSel(); document.execCommand('styleWithCSS', false, true); document.execCommand(c, false, v == null ? null : v); sync(); saveSel(); state(); }

    function btn(label, title, fn, extra) { var b = h('button', { type: 'button', title: title }, label); if (extra) b.style.cssText = extra; b.addEventListener('mousedown', function (e) { e.preventDefault(); }); b.addEventListener('click', function () { fn(b); }); return b; }
    function group() { var g = h('span', { 'class': 'g' }); for (var i = 0; i < arguments.length; i++) g.appendChild(arguments[i]); bar.appendChild(g); return g; }
    function sel(opts, title, fn) { var s = h('select', { title: title }); opts.forEach(function (o) { var a = Array.isArray(o) ? o : [o, o === '' ? title : o]; s.appendChild(h('option', { value: a[0] }, a[1])); }); s.addEventListener('mousedown', saveSel); s.addEventListener('change', function () { fn(s.value); s.selectedIndex = 0; }); return s; }
    function color(title, label, fn, def) { var l = h('label', { 'class': 'c', title: title }, label), i = h('input', { type: 'color', value: def }); i.addEventListener('mousedown', saveSel); i.addEventListener('input', function () { fn(i.value); }); l.appendChild(i); return l; }

    // Boyut: execCommand fontSize → span style
    function setSize(px) {
      if (!px) return; restoreSel(); document.execCommand('styleWithCSS', false, false); document.execCommand('fontSize', false, '7');
      var made = [];
      area.querySelectorAll('font[size="7"]').forEach(function (f) { var s = h('span'); s.style.fontSize = px + 'px'; while (f.firstChild) s.appendChild(f.firstChild); f.parentNode.replaceChild(s, f); made.push(s); });
      area.querySelectorAll('span[style*="xxx-large"]').forEach(function (s) { s.style.fontSize = px + 'px'; made.push(s); });
      if (made.length) { var r = document.createRange(); r.setStartBefore(made[0].firstChild || made[0]); r.setEndAfter(made[made.length - 1].lastChild || made[made.length - 1]); var g = getSelection(); g.removeAllRanges(); g.addRange(r); }
      sync(); saveSel();
    }
    function setLH(v) { if (!v) return; restoreSel(); var s = getSelection(); if (!s.rangeCount) return; var n = s.anchorNode; while (n && n !== area && !/^(P|DIV|H[1-6]|LI|TD|TH|BLOCKQUOTE)$/.test(n.nodeName)) n = n.parentNode; if (n && n !== area) n.style.lineHeight = v; sync(); }
    function block(tag) { cmd('formatBlock', '<' + tag + '>'); }
    function link() { restoreSel(); var u = prompt('Bağlantı adresi (https://...):', 'https://'); if (u) cmd('createLink', u); }
    function img() { restoreSel(); var u = prompt('Görsel adresi (URL):', 'https://'); if (u) cmd('insertImage', u); }
    function table() { var r = parseInt(prompt('Kaç satır?', '3'), 10), c = parseInt(prompt('Kaç sütun?', '2'), 10); if (!r || !c) return; var t = '<table><tbody>'; for (var i = 0; i < r; i++) { t += '<tr>'; for (var j = 0; j < c; j++) t += '<td>&nbsp;</td>'; t += '</tr>'; } t += '</tbody></table><p><br></p>'; cmd('insertHTML', t); }
    function box() { cmd('insertHTML', '<div style="border-left:4px solid #e30613;background:#fff5f5;padding:12px 16px;margin:12px 0;border-radius:4px">Önemli not...</div><p><br></p>'); }
    function imgAlign(a) { var im = area.querySelector('img.sel'); if (!im) return false; im.style.float = a === 'center' ? '' : a; im.style.display = a === 'center' ? 'block' : ''; im.style.margin = a === 'center' ? '8px auto' : (a === 'left' ? '4px 14px 8px 0' : '4px 0 8px 14px'); sync(); return true; }
    function al(a) { return function () { if (!imgAlign(a)) cmd('justify' + { left: 'Left', center: 'Center', right: 'Right', justify: 'Full' }[a]); }; }
    function toggleSrc(b) {
      if (wrap.classList.contains('is-src')) { area.innerHTML = src.value; wrap.classList.remove('is-src'); b.classList.remove('on'); sync(); }
      else { sync(); src.value = ta.value; wrap.classList.add('is-src'); b.classList.add('on'); }
    }

    group(btn('↶', 'Geri al', function () { cmd('undo'); }), btn('↷', 'Yinele', function () { cmd('redo'); }));
    group(sel(BLOCKS, 'Biçim', block), sel(FONTS, 'Yazı tipi', function (v) { if (v) cmd('fontName', v); }), sel(SIZES, 'Boyut (px)', setSize), sel(LH, 'Satır aralığı', setLH));
    var bB = btn('B', 'Kalın', function () { cmd('bold'); }), bI = btn('I', 'İtalik', function () { cmd('italic'); }, 'font-style:italic'), bU = btn('U', 'Altı çizili', function () { cmd('underline'); }, 'text-decoration:underline'), bS = btn('S', 'Üstü çizili', function () { cmd('strikeThrough'); }, 'text-decoration:line-through');
    group(bB, bI, bU, bS, btn('x²', 'Üst simge', function () { cmd('superscript'); }), btn('x₂', 'Alt simge', function () { cmd('subscript'); }));
    group(color('Yazı rengi', 'A', function (v) { cmd('foreColor', v); }, '#e30613'), color('Arka plan (vurgu) rengi', '🖍', function (v) { cmd('hiliteColor', v); }, '#fff200'));
    group(btn('⯇', 'Sola hizala', al('left')), btn('☰', 'Ortala', al('center')), btn('⯈', 'Sağa hizala', al('right')), btn('≣', 'İki yana yasla', al('justify')));
    group(btn('• Liste', 'Madde işaretli liste', function () { cmd('insertUnorderedList'); }), btn('1. Liste', 'Numaralı liste', function () { cmd('insertOrderedList'); }), btn('⇥', 'Girinti artır', function () { cmd('indent'); }), btn('⇤', 'Girinti azalt', function () { cmd('outdent'); }));
    group(btn('🔗', 'Bağlantı ekle', link), btn('⛓︎✕', 'Bağlantıyı kaldır', function () { cmd('unlink'); }), btn('🖼', 'Görsel ekle (URL)', img), btn('▦', 'Tablo ekle', table), btn('▭', 'Vurgulu kutu', box), btn('―', 'Yatay çizgi', function () { cmd('insertHorizontalRule'); }));
    group(btn('Tx', 'Biçimi temizle', function () { cmd('removeFormat'); }), btn('</>', 'HTML kodunu göster/gizle', toggleSrc));

    ta.parentNode.insertBefore(wrap, ta); wrap.appendChild(bar); wrap.appendChild(area); wrap.appendChild(src); ta.style.display = 'none'; wrap.appendChild(ta);
    load();

    function state() { try { bB.classList.toggle('on', document.queryCommandState('bold')); bI.classList.toggle('on', document.queryCommandState('italic')); bU.classList.toggle('on', document.queryCommandState('underline')); bS.classList.toggle('on', document.queryCommandState('strikeThrough')); } catch (e) {} }
    area.addEventListener('input', sync); area.addEventListener('keyup', function () { saveSel(); state(); }); area.addEventListener('mouseup', function () { saveSel(); state(); });
    area.addEventListener('blur', saveSel);
    area.addEventListener('click', function (e) { area.querySelectorAll('img.sel').forEach(function (i) { i.classList.remove('sel'); }); if (e.target.tagName === 'IMG') e.target.classList.add('sel'); });
    area.addEventListener('paste', function (e) { // Word/web'den yapıştırılan düzensiz stilleri temizle: düz metin yapıştır
      var t = (e.clipboardData || window.clipboardData).getData('text/plain'); if (!t) return; e.preventDefault();
      document.execCommand('insertHTML', false, t.replace(/&/g, '&amp;').replace(/</g, '&lt;').split(/\n{2,}/).join('</p><p>').replace(/\n/g, '<br>')); sync();
    });
    var form = ta.form; if (form) form.addEventListener('submit', function () {
      if (wrap.classList.contains('is-src')) ta.value = src.value; else { area.querySelectorAll('img.sel').forEach(function (i) { i.classList.remove('sel'); }); sync(); }
    });
  }
  document.querySelectorAll('textarea[data-rte]').forEach(init);
})();
