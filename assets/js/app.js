// SIR KALIP – mağaza etkileşimleri (kütüphanesiz)
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const SK = window.SK || { base: '' };
  const en = SK.lang !== 'tr';
  const post = (url, data) => fetch(url, { method: 'POST', body: data, headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' } }).then(r => r.json());

  /* Toast */
  const toast = (msg, ok = true, actions = true) => {
    const t = $('[data-toast]'); if (!t) return;
    $('[data-toast-msg]', t).textContent = msg;
    $('[data-toast-actions]', t).style.display = actions && ok ? '' : 'none';
    $('[data-toast-icon]', t).style.display = ok ? '' : 'none';
    t.classList.remove('hidden'); clearTimeout(t._h); t._h = setTimeout(() => t.classList.add('hidden'), actions ? 4000 : 2500);
  };

  /* Mobil çekmece */
  $$('[data-open="drawer"]').forEach(b => b.onclick = () => $('[data-drawer]').classList.remove('hidden'));
  $$('[data-close="drawer"]').forEach(b => b.onclick = () => $('[data-drawer]').classList.add('hidden'));

  /* Mega menü */
  const nav = $('[data-mega-nav]'), mega = $('[data-mega]');
  if (nav && mega) {
    let h;
    $$('[data-mega-trigger]', nav).forEach(t => t.addEventListener('mouseenter', () => { clearTimeout(h); mega.classList.remove('hidden'); }));
    $$('[data-mega-close]', nav).forEach(t => t.addEventListener('mouseenter', () => mega.classList.add('hidden')));
    nav.addEventListener('mouseleave', () => { h = setTimeout(() => mega.classList.add('hidden'), 150); });
    mega.addEventListener('mouseenter', () => clearTimeout(h));
  }

  /* Parça bulucu: markaya göre modeller */
  $$('[data-finder-brand]').forEach(brand => brand.addEventListener('change', () => {
    const model = brand.form.querySelector('[data-finder-model]');
    const models = JSON.parse(brand.selectedOptions[0].dataset.models || '[]');
    model.length = 1; models.forEach(m => model.add(new Option(m, m)));
  }));

  /* Yatay kaydırmalı listeler + sekmeler */
  $$('[data-tabs]').forEach(sec => {
    const row = $('[data-row]', sec); if (!row) return;
    $$('[data-tab]', sec).forEach(b => b.onclick = () => {
      $$('[data-tab]', sec).forEach(x => x.classList.toggle('is-active', x === b));
      $$('[data-cat]', row).forEach(it => it.style.display = !b.dataset.tab || it.dataset.cat === b.dataset.tab ? '' : 'none');
      row.scrollLeft = 0;
    });
    $$('[data-scroll]', sec).forEach(b => b.onclick = () => row.scrollBy({ left: +b.dataset.scroll * row.clientWidth * 0.8, behavior: 'smooth' }));
  });

  /* Favori */
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-fav]'); if (!b) return;
    e.preventDefault();
    const fd = new FormData(); fd.append('id', b.dataset.fav);
    try {
      const j = await post(SK.base + (SK.lang !== 'tr' ? '/' + SK.lang : '') + '/favori', fd);
      $$(`[data-fav="${b.dataset.fav}"] svg`).forEach(s => s.classList.toggle('is-fav', j.on));
      $$('[data-fav-count]').forEach(c => { c.textContent = j.count; c.classList.toggle('hidden', !j.count); });
      toast(j.on ? (en ? 'Added to favorites' : 'Favorilere eklendi') : (en ? 'Removed from favorites' : 'Favorilerden çıkarıldı'), true, false);
    } catch (_) {}
  });

  /* Adet +/- (ürün detay) */
  $$('[data-add-form]').forEach(f => {
    const q = f.querySelector('[name=qty]'), max = +q.dataset.max || 999;
    $$('[data-qty]', f).forEach(b => b.onclick = () => { q.value = Math.min(max, Math.max(1, (+q.value || 1) + +b.dataset.qty)); });
    q.addEventListener('change', () => { q.value = Math.min(max, Math.max(1, +q.value || 1)); });
  });

  /* Yandan açılan sepet */
  const drawer = $('[data-cart-drawer]'), panel = $('[data-cart-panel]');
  const loadMini = async () => { panel.innerHTML = await fetch(SK.base + (SK.lang !== 'tr' ? '/' + SK.lang : '') + '/sepet/ozet', { headers: { 'X-Requested-With': 'fetch' } }).then(r => r.text()); };
  const openCart = async (reload = true) => {
    if (!drawer) return;
    if (reload || !panel.innerHTML) await loadMini();
    drawer.classList.remove('hidden'); document.documentElement.style.overflow = 'hidden';
    requestAnimationFrame(() => { panel.classList.remove('translate-x-full'); drawer.firstElementChild.classList.remove('opacity-0'); });
  };
  const closeCart = () => {
    if (!drawer) return;
    panel.classList.add('translate-x-full'); drawer.firstElementChild.classList.add('opacity-0');
    setTimeout(() => { drawer.classList.add('hidden'); document.documentElement.style.overflow = ''; }, 300);
  };
  document.addEventListener('click', async e => {
    const o = e.target.closest('[data-open-cart]');
    if (o && drawer && !location.pathname.endsWith('/sepet') && !location.pathname.endsWith('/odeme')) { e.preventDefault(); openCart(); return; }
    if (e.target.closest('[data-close-cart]')) { closeCart(); return; }
    const item = e.target.closest('[data-mc-item]'); if (!item) return;
    const code = item.dataset.mcItem, q = e.target.closest('[data-mc-qty]'), rm = e.target.closest('[data-mc-remove]');
    if (!q && !rm) return;
    const cur = +item.querySelector('[data-mc-input]').value || 1;
    const fd = new FormData(); fd.append(`qty[${code}]`, rm ? 0 : cur + +q.dataset.mcQty);
    item.style.opacity = .5;
    const j = await post(SK.base + '/sepet/guncelle', fd);
    $$('[data-cart-count]').forEach(c => c.textContent = j.count);
    await loadMini();
  });
  /* Sepette adedi yazarak değiştirme */
  const mcCommit = async inp => {
    const item = inp.closest('[data-mc-item]'); if (!item || inp.dataset.busy) return;
    const max = +inp.dataset.max || 999;
    let v = parseInt(String(inp.value).replace(/\D/g, ''), 10);
    if (!v || v < 1) v = 1; if (v > max) v = max;
    inp.value = v; inp.dataset.busy = 1; item.style.opacity = .5;
    const fd = new FormData(); fd.append(`qty[${item.dataset.mcItem}]`, v);
    const j = await post(SK.base + '/sepet/guncelle', fd);
    $$('[data-cart-count]').forEach(c => c.textContent = j.count);
    await loadMini();
  };
  document.addEventListener('change', e => { if (e.target.matches('[data-mc-input]')) mcCommit(e.target); });
  document.addEventListener('input', e => { if (e.target.matches('[data-mc-input]')) e.target.value = e.target.value.replace(/\D/g, ''); });
  document.addEventListener('focusin', e => { if (e.target.matches('[data-mc-input]')) e.target.select(); });
  document.addEventListener('keydown', e => {
    if (e.target.matches && e.target.matches('[data-mc-input]') && e.key === 'Enter') { e.preventDefault(); e.target.blur(); }
    if (e.key === 'Escape') closeCart();
  });

  /* Butondan geçen tarım makinesi animasyonu */
  const TRACTOR = '<svg viewBox="0 0 64 40" width="46" height="29" aria-hidden="true"><rect x="22" y="8" width="18" height="14" rx="2" fill="#fff"/><rect x="25" y="10" width="11" height="8" rx="1" fill="#1f2530" opacity=".55"/><path d="M8 18h40a4 4 0 0 1 4 4v6H8z" fill="#fff"/><rect x="48" y="12" width="3" height="8" rx="1" fill="#fff"/><circle cx="17" cy="30" r="8" fill="#1f2530" stroke="#fff" stroke-width="2.5"/><circle cx="17" cy="30" r="2.5" fill="#fff"/><circle cx="46" cy="32" r="5.5" fill="#1f2530" stroke="#fff" stroke-width="2.5"/><circle cx="46" cy="32" r="1.8" fill="#fff"/><path d="M2 34h4M0 29h5" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".7"/></svg>';
  const driveTractor = btn => {
    if (!btn || !btn.classList) return;
    btn.classList.add('tractor-host');
    const t = document.createElement('span'); t.className = 'tractor-run'; t.innerHTML = TRACTOR;
    btn.appendChild(t); btn.classList.add('is-driving');
    setTimeout(() => { t.remove(); btn.classList.remove('is-driving'); }, 1100);
  };

  /* Sepete ekle */
  const addToCart = async (code, qty, btn) => {
    const fd = new FormData(); fd.append('code', code); fd.append('qty', qty);
    driveTractor(btn);
    btn && (btn.disabled = true);
    try {
      const j = await post(SK.base + '/sepet/ekle', fd);
      $$('[data-cart-count]').forEach(c => { c.textContent = j.count; c.classList.remove('bump'); void c.offsetWidth; c.classList.add('bump'); });
      if (!j.ok) { toast(j.msg, false); return false; }
      return true;
    } catch (_) { toast(en ? 'An error occurred' : 'Bir hata oluştu', false); return false; }
    finally { btn && (btn.disabled = false); }
  };
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-add-cart]'); if (!b) return;
    const qEl = b.closest('form')?.querySelector('[name=qty]') || (b.dataset.qtyFrom && $(b.dataset.qtyFrom));
    if (await addToCart(b.dataset.addCart, qEl ? qEl.value : 1, b)) setTimeout(() => openCart(), 650);
  });
  $$('[data-buy-now]').forEach(b => b.onclick = async () => {
    const q = b.form?.querySelector('[name=qty]');
    if (await addToCart(b.dataset.buyNow, q ? q.value : 1, b)) location.href = SK.checkoutUrl;
  });

  /* Tıklanan yerde SIR KALIP logosu */
  const logoSrc = SK.base + '/assets/img/cursor-logo.png';
  const pre = new Image(); pre.src = logoSrc;
  document.addEventListener('pointerdown', e => {
    if (e.button !== 0 || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const i = document.createElement('img'); i.src = logoSrc; i.alt = ''; i.className = 'click-logo';
    i.style.left = e.clientX + 'px'; i.style.top = e.clientY + 'px';
    document.body.appendChild(i); setTimeout(() => i.remove(), 750);
  }, { passive: true });

  /* Sepet sayfası: adet değişince güncelle */
  $$('[data-cart-form]').forEach(f => {
    let h;
    const submit = () => { clearTimeout(h); h = setTimeout(() => f.submit(), 400); };
    $$('[data-cart-qty]', f).forEach(b => b.onclick = () => {
      const q = b.parentElement.querySelector('input'); const max = +q.dataset.max || 999;
      q.value = Math.min(max, Math.max(0, (+q.value || 0) + +b.dataset.cartQty)); submit();
    });
    $$('input[name^=qty]', f).forEach(q => q.addEventListener('change', submit));
  });

  /* Ürün galerisi */
  $$('[data-gallery]').forEach(g => {
    const show = i => {
      $$('[data-slide]', g).forEach(s => s.classList.toggle('hidden', s.dataset.slide != i));
      $$('[data-thumb]', g).forEach(t => { const on = t.dataset.thumb == i; t.classList.toggle('border-brand', on); t.classList.toggle('border-line', !on); t.classList.toggle('bg-brand', on && t.classList.contains('rounded-full')); });
    };
    $$('[data-thumb]', g).forEach(t => t.onclick = () => show(t.dataset.thumb));
    let x0 = null; const slides = $$('[data-slide]', g); let cur = 0;
    g.addEventListener('touchstart', e => x0 = e.touches[0].clientX, { passive: true });
    g.addEventListener('touchend', e => { if (x0 === null || slides.length < 2) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) { cur = (cur + (dx < 0 ? 1 : -1) + slides.length) % slides.length; show(cur); } x0 = null; });
    $$('[data-zoom]', g).forEach(z => {
      const img = z.querySelector('img');
      z.addEventListener('mousemove', e => { const r = z.getBoundingClientRect(); img.style.transformOrigin = `${(e.clientX - r.left) / r.width * 100}% ${(e.clientY - r.top) / r.height * 100}%`; img.style.transform = 'scale(2)'; });
      z.addEventListener('mouseleave', () => img.style.transform = '');
    });
  });

  /* Tam ekran görsel görüntüleyici (lightbox) */
  (function () {
    const galleries = $$('[data-gallery]');
    if (!galleries.length) return;
    const T = en
      ? { close: 'Close', prev: 'Previous', next: 'Next', zoom: 'Zoom', full: 'View full screen', hint: 'Double-click to zoom · Esc to close' }
      : { close: 'Kapat', prev: 'Önceki', next: 'Sonraki', zoom: 'Yakınlaştır', full: 'Tam ekran görüntüle', hint: 'Yakınlaştırmak için çift tıklayın · Kapatmak için Esc' };
    const ico = {
      x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
      l: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>',
      r: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>',
      zi: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5M8 11h6M11 8v6"/></svg>',
      zo: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5M8 11h6"/></svg>',
      ex: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>'
    };

    const lb = document.createElement('div');
    lb.className = 'sk-lb';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.innerHTML =
      '<div class="sk-lb__bg"></div>' +
      '<div class="sk-lb__top"><div class="sk-lb__title"></div><div class="sk-lb__tools">' +
        '<span class="sk-lb__count"></span>' +
        '<button type="button" class="sk-lb__btn" data-lb-zoom aria-label="' + T.zoom + '">' + ico.zi + '</button>' +
        '<button type="button" class="sk-lb__btn sk-lb__btn--close" data-lb-close aria-label="' + T.close + '">' + ico.x + '</button>' +
      '</div></div>' +
      '<div class="sk-lb__stage"><div class="sk-lb__frame"><img class="sk-lb__img" alt="" draggable="false"></div></div>' +
      '<button type="button" class="sk-lb__nav sk-lb__nav--prev" data-lb-prev aria-label="' + T.prev + '">' + ico.l + '</button>' +
      '<button type="button" class="sk-lb__nav sk-lb__nav--next" data-lb-next aria-label="' + T.next + '">' + ico.r + '</button>' +
      '<div class="sk-lb__bottom"><div class="sk-lb__thumbs"></div><div class="sk-lb__hint">' + T.hint + '</div></div>';
    document.body.appendChild(lb);

    const img = $('.sk-lb__img', lb), frame = $('.sk-lb__frame', lb), stage = $('.sk-lb__stage', lb);
    const thumbs = $('.sk-lb__thumbs', lb), count = $('.sk-lb__count', lb), title = $('.sk-lb__title', lb);
    const zoomBtn = $('[data-lb-zoom]', lb);
    let items = [], idx = 0, scale = 1, tx = 0, ty = 0, lastFocus = null, onChange = null;

    const apply = (anim) => {
      img.style.transition = anim ? 'transform .35s cubic-bezier(.2,.8,.2,1)' : 'none';
      img.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
      lb.classList.toggle('is-zoomed', scale > 1);
      zoomBtn.innerHTML = scale > 1 ? ico.zo : ico.zi;
    };
    const clamp = () => {
      const r = frame.getBoundingClientRect();
      const mx = Math.max(0, (r.width * scale - r.width) / 2), my = Math.max(0, (r.height * scale - r.height) / 2);
      tx = Math.min(mx, Math.max(-mx, tx)); ty = Math.min(my, Math.max(-my, ty));
    };
    const zoomTo = (s, cx, cy) => {
      const r = frame.getBoundingClientRect();
      if (s > 1 && cx != null) { tx = (r.left + r.width / 2 - cx) * (s - 1); ty = (r.top + r.height / 2 - cy) * (s - 1); }
      else { tx = 0; ty = 0; }
      scale = s; clamp(); apply(true);
    };

    const show = (i, dir) => {
      idx = (i + items.length) % items.length;
      scale = 1; tx = 0; ty = 0; apply(false);
      frame.classList.remove('is-in', 'from-l', 'from-r');
      void frame.offsetWidth;
      frame.classList.add('is-in', dir < 0 ? 'from-l' : dir > 0 ? 'from-r' : 'from-c');
      img.src = items[idx];
      count.textContent = items.length > 1 ? (idx + 1) + ' / ' + items.length : '';
      $$('.sk-lb__thumb', thumbs).forEach((t, k) => t.classList.toggle('is-active', k === idx));
      const at = $$('.sk-lb__thumb', thumbs)[idx]; if (at) at.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
      if (onChange) onChange(idx);
    };

    const open = (list, start, name, cb) => {
      items = list; onChange = cb; lastFocus = document.activeElement;
      title.textContent = name || '';
      lb.classList.toggle('is-single', items.length < 2);
      thumbs.innerHTML = items.length > 1 ? items.map((u, k) => '<button type="button" class="sk-lb__thumb" data-k="' + k + '"><img src="' + u + '" alt=""></button>').join('') : '';
      $$('.sk-lb__thumb', thumbs).forEach(b => b.onclick = () => show(+b.dataset.k, +b.dataset.k > idx ? 1 : -1));
      document.documentElement.classList.add('sk-lb-lock');
      lb.classList.add('is-open');
      show(start, 0);
      setTimeout(() => $('[data-lb-close]', lb).focus({ preventScroll: true }), 50);
    };
    const close = () => {
      lb.classList.remove('is-open');
      document.documentElement.classList.remove('sk-lb-lock');
      if (lastFocus) lastFocus.focus({ preventScroll: true });
    };

    $('[data-lb-close]', lb).onclick = close;
    $('.sk-lb__bg', lb).onclick = close;
    stage.addEventListener('click', e => { if (e.target === stage) close(); });
    $('[data-lb-prev]', lb).onclick = () => show(idx - 1, -1);
    $('[data-lb-next]', lb).onclick = () => show(idx + 1, 1);
    zoomBtn.onclick = () => zoomTo(scale > 1 ? 1 : 2.5);
    img.addEventListener('dblclick', e => zoomTo(scale > 1 ? 1 : 2.5, e.clientX, e.clientY));

    document.addEventListener('keydown', e => {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft' && items.length > 1) show(idx - 1, -1);
      else if (e.key === 'ArrowRight' && items.length > 1) show(idx + 1, 1);
      else if (e.key === '+' || e.key === '=') zoomTo(Math.min(4, scale + 0.75));
      else if (e.key === '-') zoomTo(Math.max(1, scale - 0.75));
    });

    lb.addEventListener('wheel', e => {
      if (!lb.classList.contains('is-open')) return;
      e.preventDefault();
      const s = Math.min(4, Math.max(1, scale * (e.deltaY < 0 ? 1.15 : 1 / 1.15)));
      if (s === 1) { scale = 1; tx = 0; ty = 0; apply(true); return; }
      const r = frame.getBoundingClientRect(), k = s / scale;
      const ox = e.clientX - (r.left + r.width / 2), oy = e.clientY - (r.top + r.height / 2);
      tx = ox - (ox - tx) * k; ty = oy - (oy - ty) * k; scale = s; clamp(); apply(false);
    }, { passive: false });

    // Sürükleme, kaydırma ve iki parmakla yakınlaştırma
    let drag = null, pinch = null, lastTap = 0;
    const dist = t => Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY);
    img.addEventListener('pointerdown', e => {
      if (e.pointerType === 'touch') return;
      e.preventDefault();
      drag = { x: e.clientX, y: e.clientY, tx, ty, moved: false };
      img.setPointerCapture(e.pointerId);
    });
    img.addEventListener('pointermove', e => {
      if (!drag) return;
      const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
      if (Math.abs(dx) + Math.abs(dy) > 4) drag.moved = true;
      if (scale > 1) { tx = drag.tx + dx; ty = drag.ty + dy; clamp(); apply(false); }
    });
    img.addEventListener('pointerup', e => {
      if (!drag) return;
      const dx = e.clientX - drag.x;
      if (scale === 1 && items.length > 1 && Math.abs(dx) > 60) show(idx + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
      drag = null;
    });

    stage.addEventListener('touchstart', e => {
      if (e.touches.length === 2) { pinch = { d: dist(e.touches), s: scale }; drag = null; return; }
      const t = e.touches[0];
      const now = Date.now();
      if (now - lastTap < 280) { zoomTo(scale > 1 ? 1 : 2.5, t.clientX, t.clientY); lastTap = 0; return; }
      lastTap = now;
      drag = { x: t.clientX, y: t.clientY, tx, ty };
    }, { passive: true });
    stage.addEventListener('touchmove', e => {
      if (pinch && e.touches.length === 2) {
        e.preventDefault();
        scale = Math.min(4, Math.max(1, pinch.s * dist(e.touches) / pinch.d)); if (scale === 1) { tx = 0; ty = 0; }
        clamp(); apply(false); return;
      }
      if (!drag) return;
      const t = e.touches[0], dx = t.clientX - drag.x, dy = t.clientY - drag.y;
      if (scale > 1) { e.preventDefault(); tx = drag.tx + dx; ty = drag.ty + dy; clamp(); apply(false); }
      else { img.style.transition = 'none'; img.style.transform = `translate(${dx}px, ${Math.max(0, dy)}px)`; lb.style.setProperty('--lb-fade', Math.max(0.35, 1 - Math.max(0, dy) / 400)); }
    }, { passive: false });
    stage.addEventListener('touchend', e => {
      if (pinch) { if (e.touches.length < 2) pinch = null; return; }
      if (!drag) return;
      const t = e.changedTouches[0], dx = t.clientX - drag.x, dy = t.clientY - drag.y;
      lb.style.removeProperty('--lb-fade');
      if (scale === 1) {
        if (dy > 120 && Math.abs(dy) > Math.abs(dx)) close();
        else if (items.length > 1 && Math.abs(dx) > 60) show(idx + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
        else apply(true);
      }
      drag = null;
    });

    // Ürün sayfasındaki galerilere bağla
    galleries.forEach(g => {
      const zooms = $$('[data-zoom]', g);
      if (!zooms.length) return;
      const list = zooms.map(z => z.querySelector('img').currentSrc || z.querySelector('img').src);
      const name = zooms[0].querySelector('img').alt;
      const sync = k => {
        const slide = zooms[k].closest('[data-slide]');
        const th = slide && $(`[data-thumb="${slide.dataset.slide}"]`, g);
        if (th) th.click();
      };
      zooms.forEach((z, k) => {
        z.addEventListener('click', () => open(list, k, name, sync));
        z.setAttribute('role', 'button');
        z.setAttribute('aria-label', T.full);
        z.tabIndex = 0;
        z.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(list, k, name, sync); } });
        const host = z.parentElement;
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'sk-lb-open'; b.innerHTML = ico.ex + '<span>' + T.full + '</span>';
        b.onclick = e => { e.stopPropagation(); open(list, k, name, sync); };
        host.style.position = 'relative';
        host.appendChild(b);
      });
    });
  })();

  /* Ürün detay sekmeleri */
  const openTab = (box, k) => {
    $$('[data-ptab]', box).forEach(b => b.classList.toggle('is-active', b.dataset.ptab === k));
    $$('[data-ppane]', box).forEach(p => p.classList.toggle('hidden', p.dataset.ppane !== k));
  };
  $$('[data-ptabs]').forEach(box => {
    $$('[data-ptab]', box).forEach(b => b.onclick = () => openTab(box, b.dataset.ptab));
    const h = location.hash.replace('#tab-', ''); if (h && $(`[data-ptab="${h}"]`, box)) { openTab(box, h); }
  });
  $$('[data-goto-tab]').forEach(a => a.onclick = e => { const box = $('[data-ptabs]'); if (!box) return; e.preventDefault(); openTab(box, a.dataset.gotoTab); box.scrollIntoView({ behavior: 'smooth' }); });
  $$('[data-ctabs]').forEach(box => $$('[data-ctab]', box).forEach(b => b.onclick = () => {
    $$('[data-ctab]', box).forEach(x => { const on = x === b; x.classList.toggle('border-brand', on); x.classList.toggle('bg-brand-50', on); x.classList.toggle('text-brand', on); x.classList.toggle('border-line', !on); });
    $$('[data-cpane]', box).forEach(p => p.classList.toggle('hidden', p.dataset.cpane !== b.dataset.ctab));
  }));

  /* Listeleme filtreleri */
  $$('[data-open-filters]').forEach(b => b.onclick = () => $('[data-filters]').classList.remove('hidden'));
  $$('[data-close-filters]').forEach(b => b.onclick = () => $('[data-filters]').classList.add('hidden'));
  $$('[data-autosubmit]').forEach(i => i.addEventListener('change', () => { if (window.innerWidth >= 1024) i.form.submit(); }));

  /* Ödeme sayfası */
  $$('[data-recalc]').forEach(r => r.addEventListener('change', () => { const f = r.form; f.querySelector('[name=step]').value = 'recalc'; f.submit(); }));
  $$('[data-toggle]').forEach(c => c.addEventListener('change', () => $(c.dataset.toggle).classList.toggle('hidden', !c.checked)));
  $$('[data-fill-address]').forEach(b => b.onclick = () => {
    const d = JSON.parse(b.dataset.fillAddress), f = b.closest('form');
    Object.entries(d).forEach(([k, v]) => { const el = f.querySelector(`[name=${k}]`); if (el) el.value = v || ''; });
  });
  $$('[data-copy]').forEach(b => b.onclick = () => { navigator.clipboard?.writeText(b.parentElement.querySelector('[data-copy-src]').textContent.trim()); b.textContent = en ? 'Copied' : 'Kopyalandı'; });

  /* Canlı arama */
  $$('[data-search]').forEach(f => {
    const inp = $('[data-search-input]', f), box = $('[data-search-results]', f); let h, ctl;
    const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    inp.addEventListener('input', () => {
      clearTimeout(h); const q = inp.value.trim();
      if (q.length < 2) { box.classList.add('hidden'); return; }
      h = setTimeout(async () => {
        ctl?.abort(); ctl = new AbortController();
        try {
          const j = await fetch(SK.searchUrl + '?ajax=1&q=' + encodeURIComponent(q), { signal: ctl.signal }).then(r => r.json());
          box.innerHTML = j.items.length ? j.items.map(i => `<a href="${i.url}" class="flex items-center gap-3 border-b border-line px-4 py-2.5 last:border-0 hover:bg-soft">${i.img ? `<img src="${esc(i.img)}" class="h-10 w-10 rounded object-contain">` : '<span class="flex h-10 w-10 items-center justify-center rounded bg-soft text-xs font-bold text-muted">' + esc(i.code.slice(0, 2)) + '</span>'}<span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">${esc(i.name)}</span><span class="text-xs text-muted">${esc(i.code)}</span></span><b class="text-sm text-brand">${esc(i.price)}</b></a>`).join('')
            + `<a href="${SK.searchUrl}?q=${encodeURIComponent(q)}" class="block bg-soft px-4 py-2.5 text-center text-sm font-bold text-brand">${en ? 'See all results' : 'Tüm sonuçları gör'} (${j.total})</a>`
            : `<div class="px-4 py-4 text-sm text-muted">${en ? 'No results for' : 'Sonuç bulunamadı:'} "<b>${esc(q)}</b>"</div>`;
          box.classList.remove('hidden');
        } catch (_) {}
      }, 250);
    });
    document.addEventListener('click', e => { if (!f.contains(e.target)) box.classList.add('hidden'); });
    inp.addEventListener('keydown', e => { if (e.key === 'Escape') box.classList.add('hidden'); });
  });

  /* Çerez bildirimi */
  const ck = $('[data-cookie]');
  try { if (ck && !localStorage.getItem('sk_cookie')) ck.classList.remove('hidden'); } catch (_) { ck && ck.classList.remove('hidden'); }
  $$('[data-cookie-ok]').forEach(b => b.onclick = () => { try { localStorage.setItem('sk_cookie', b.dataset.cookieOk); } catch (_) {} ck.classList.add('hidden'); });

  /* Ana sayfa banner slider */
  $$('[data-hero-slider]').forEach(sl => {
    const slides = $$('[data-hero-slide]', sl), dots = $$('[data-hero-dot]', sl); if (slides.length < 2) return;
    let cur = 0, timer;
    const show = i => {
      cur = (i + slides.length) % slides.length;
      slides.forEach((s, k) => { s.classList.toggle('opacity-0', k !== cur); s.style.zIndex = k === cur ? 1 : 0; });
      dots.forEach((d, k) => { d.classList.toggle('w-7', k === cur); d.classList.toggle('bg-white', k === cur); d.classList.toggle('w-2.5', k !== cur); });
    };
    const auto = () => { clearInterval(timer); timer = setInterval(() => show(cur + 1), 6000); };
    dots.forEach((d, k) => d.onclick = () => { show(k); auto(); });
    $('[data-hero-prev]', sl) && ($('[data-hero-prev]', sl).onclick = () => { show(cur - 1); auto(); });
    $('[data-hero-next]', sl) && ($('[data-hero-next]', sl).onclick = () => { show(cur + 1); auto(); });
    sl.addEventListener('mouseenter', () => clearInterval(timer)); sl.addEventListener('mouseleave', auto);
    auto();
  });

  /* Flash mesajı birkaç saniye sonra kaybolsun */
  $$('[data-flash]').forEach(f => setTimeout(() => f.style.display = 'none', 6000));
})();
