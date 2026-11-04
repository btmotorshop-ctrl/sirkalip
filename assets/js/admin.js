// Panel etkileşimleri
document.querySelectorAll('[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
document.querySelectorAll('[data-tabs]').forEach(box => {
  const btns = box.querySelectorAll('[data-tab]'), panes = box.querySelectorAll('[data-pane]');
  btns.forEach(b => b.onclick = () => {
    btns.forEach(x => x.classList.toggle('is-active', x === b));
    panes.forEach(p => p.classList.toggle('hidden', p.dataset.pane !== b.dataset.tab));
  });
});
// Toplu seçim
const all = document.querySelector('[data-check-all]');
if (all) all.onchange = () => document.querySelectorAll('[data-check]').forEach(c => c.checked = all.checked);
// Tüm modeller seç/kaldır
document.querySelectorAll('[data-toggle-group]').forEach(b => b.onclick = () => {
  const boxes = document.querySelectorAll('[data-group="' + b.dataset.toggleGroup + '"]');
  const on = [...boxes].some(x => !x.checked);
  boxes.forEach(x => x.checked = on);
});
// Satır ekle (teknik detay / referans)
document.querySelectorAll('[data-add-row]').forEach(b => b.onclick = () => {
  const tpl = document.getElementById(b.dataset.addRow);
  tpl.parentElement.insertBefore(tpl.content.cloneNode(true), tpl);
});
document.addEventListener('click', e => { const r = e.target.closest('[data-remove-row]'); if (r) r.closest('[data-row]').remove(); });
// Görsel yükleme önizlemesi
document.querySelectorAll('[data-img-input]').forEach(inp => inp.addEventListener('change', () => {
  const f = inp.files[0]; if (!f) return;
  const box = inp.closest('.flex').querySelector('[data-img-preview]');
  box.innerHTML = ''; const img = document.createElement('img'); img.className = 'max-h-full max-w-full object-contain'; img.src = URL.createObjectURL(f); box.appendChild(img);
}));

// Dashboard satış grafiği (SVG alan grafiği + crosshair tooltip)
document.querySelectorAll('[data-chart]').forEach(box => {
  const data = JSON.parse(box.dataset.chart); const svg = box.querySelector('[data-chart-svg]'); const area = box.querySelector('[data-chart-area]');
  const tip = box.querySelector('[data-chart-tip]'); const totalEl = box.querySelector('[data-chart-total]'); const subEl = box.querySelector('[data-chart-sub]');
  const tl = n => new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + ' TL';
  const int = n => new Intl.NumberFormat('tr-TR').format(n);
  const cfg = { ciro: { color: '#e30613', fmt: tl, sub: 'toplam ciro', short: n => n >= 1000 ? int(Math.round(n / 1000)) + 'B' : int(Math.round(n)) },
                siparis: { color: '#2a78d6', fmt: n => int(n) + ' sipariş', sub: 'toplam sipariş', short: int },
                ziyaretci: { color: '#1baf7a', fmt: n => int(n) + ' ziyaretçi', sub: 'toplam ziyaretçi', short: int } };
  let metric = 'ciro';
  const nice = m => { if (m <= 0) return 4; const p = Math.pow(10, Math.floor(Math.log10(m))); const f = m / p; return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * p; };
  const draw = () => {
    const vals = data[metric], labels = data.labels, c = cfg[metric];
    const W = area.clientWidth, H = area.clientHeight, L = 44, R = 8, T = 10, B = 26, iw = W - L - R, ih = H - T - B;
    const max = nice(Math.max(...vals, 0) * 1.1);
    const x = i => L + (vals.length > 1 ? i * iw / (vals.length - 1) : iw / 2), y = v => T + ih - v / max * ih;
    const pts = vals.map((v, i) => [x(i), y(v)]);
    const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    let s = `<defs><linearGradient id="g-${metric}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${c.color}" stop-opacity=".28"/><stop offset="1" stop-color="${c.color}" stop-opacity="0"/></linearGradient></defs>`;
    for (let k = 0; k <= 4; k++) { const v = max / 4 * k, yy = y(v); s += `<line x1="${L}" x2="${W - R}" y1="${yy}" y2="${yy}" stroke="#e8ebf0" stroke-width="1" ${k ? 'stroke-dasharray="3 4"' : ''}/><text x="${L - 8}" y="${yy + 4}" text-anchor="end" font-size="11" fill="#8a8f99">${c.short(v)}</text>`; }
    const step = Math.ceil(labels.length / Math.max(2, Math.floor(iw / 60)));
    labels.forEach((l, i) => { if (i % step === 0 || i === labels.length - 1) s += `<text x="${x(i)}" y="${H - 6}" text-anchor="middle" font-size="11" fill="#8a8f99">${l}</text>`; });
    s += `<path d="${line} L${x(vals.length - 1)} ${T + ih} L${x(0)} ${T + ih} Z" fill="url(#g-${metric})"/>`;
    s += `<path d="${line}" fill="none" stroke="${c.color}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>`;
    if (vals.length <= 31) pts.forEach(p => { s += `<circle cx="${p[0]}" cy="${p[1]}" r="3.5" fill="#fff" stroke="${c.color}" stroke-width="2"/>`; });
    s += `<line data-cross x1="0" x2="0" y1="${T}" y2="${T + ih}" stroke="#1f2530" stroke-opacity=".25" stroke-width="1" visibility="hidden"/><circle data-dot r="5.5" fill="${c.color}" stroke="#fff" stroke-width="2.5" visibility="hidden"/>`;
    s += `<rect x="${L}" y="${T}" width="${iw}" height="${ih}" fill="transparent" data-hit/>`;
    svg.setAttribute('viewBox', `0 0 ${W} ${H}`); svg.innerHTML = s;
    const sum = vals.reduce((a, b) => a + b, 0); totalEl.textContent = c.fmt(sum); subEl.textContent = c.sub;
    const hit = svg.querySelector('[data-hit]'), cross = svg.querySelector('[data-cross]'), dot = svg.querySelector('[data-dot]');
    hit.addEventListener('mousemove', e => {
      const r = svg.getBoundingClientRect(); const mx = e.clientX - r.left;
      const i = Math.max(0, Math.min(vals.length - 1, Math.round((mx - L) / (iw / Math.max(1, vals.length - 1)))));
      const [px, py] = pts[i]; cross.setAttribute('x1', px); cross.setAttribute('x2', px); cross.setAttribute('visibility', 'visible');
      dot.setAttribute('cx', px); dot.setAttribute('cy', py); dot.setAttribute('visibility', 'visible');
      tip.innerHTML = `<div class="font-bold">${labels[i]}</div><div>Ciro: <b>${tl(data.ciro[i])}</b></div><div>Sipariş: <b>${int(data.siparis[i])}</b></div>` + (data.ziyaretci.some(v => v) ? `<div>Ziyaretçi: <b>${int(data.ziyaretci[i])}</b></div>` : '');
      tip.style.left = px + 'px'; tip.style.top = (py - 10) + 'px'; tip.classList.remove('hidden');
    });
    hit.addEventListener('mouseleave', () => { cross.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden'); tip.classList.add('hidden'); });
  };
  box.querySelectorAll('[data-metric]').forEach(b => b.onclick = () => { metric = b.dataset.metric; box.querySelectorAll('[data-metric]').forEach(x => x.classList.toggle('is-active', x === b)); draw(); });
  draw(); let rt; window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(draw, 150); });
});
