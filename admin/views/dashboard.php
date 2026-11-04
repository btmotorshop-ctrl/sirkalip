<?php
$title = 'Anasayfa';
admin_view('layout_top', compact('title'));
$periods = ['today' => 'Bugün', '7' => 'Son 7 Gün', '15' => 'Son 15 Gün', '30' => 'Son 30 Gün', 'month' => 'Bu Ay', '90' => 'Son 3 Ay'];
$tone = [ // ikon kutusu renkleri
  'red' => ['bg-red-50 text-red-600', 'from-red-500 to-rose-500'], 'blue' => ['bg-blue-50 text-blue-600', 'from-blue-500 to-sky-500'],
  'green' => ['bg-emerald-50 text-emerald-600', 'from-emerald-500 to-teal-500'], 'violet' => ['bg-violet-50 text-violet-600', 'from-violet-500 to-fuchsia-500'],
  'orange' => ['bg-orange-50 text-orange-600', 'from-orange-500 to-amber-500'], 'teal' => ['bg-teal-50 text-teal-600', 'from-teal-500 to-cyan-500'],
];
$sl = order_status_labels(); $ml = payment_method_labels();
$statusColors = ['pending' => '#eda100', 'preparing' => '#2a78d6', 'shipped' => '#4a3aa7', 'delivered' => '#1baf7a', 'cancelled' => '#e34948', 'returned' => '#8a8984'];
$hour = (int)date('G'); $greet = $hour < 12 ? 'Günaydın' : ($hour < 18 ? 'İyi günler' : 'İyi akşamlar');
$fmtChange = function ($v) {
  if ($v === null) return '<span class="text-xs font-semibold text-muted">— önceki dönem veri yok</span>';
  $up = $v >= 0; return '<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold ' . ($up ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700') . '">' . icon($up ? 'trending-up' : 'trending-down', 'h-3.5 w-3.5') . '%' . number_format(abs($v), 1, ',', '.') . '</span><span class="ml-1 text-[11px] text-muted">önceki döneme göre</span>';
};
$chartData = ['labels' => array_column($series, 'l'), 'ciro' => array_column($series, 't'), 'siparis' => array_column($series, 'c'), 'ziyaretci' => array_column($series, 'v')];
?>
<!-- Başlık -->
<div class="mb-5 flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-2xl font-extrabold"><?= $greet ?>, <?= e(explode(' ', admin_user()['name'])[0]) ?> 👋</h1>
    <p class="mt-1 text-sm text-muted"><?= e(tr_date_long()) ?> · Bugün <b class="text-ink"><?= (int)$todayTotals['c'] ?> sipariş</b>, <b class="text-ink"><?= money($todayTotals['t']) ?></b> ciro</p>
  </div>
  <div class="flex flex-wrap items-center gap-2">
    <div class="flex flex-wrap gap-1 rounded-xl bg-white p-1 text-xs font-semibold shadow-sm ring-1 ring-line">
      <?php foreach ($periods as $k => $l): ?><a href="?p=<?= $k ?>" class="rounded-lg px-3 py-2 transition <?= (string)$p === (string)$k ? 'bg-[#1b2a4e] text-white shadow' : 'text-muted hover:bg-soft hover:text-ink' ?>"><?= $l ?></a><?php endforeach; ?>
    </div>
    <a href="<?= aurl('reports/sales') ?>?from=<?= substr($from, 0, 10) ?>&to=<?= substr($to, 0, 10) ?>" class="abtn h-10"><?= icon('file-spreadsheet', 'h-4 w-4') ?> Detaylı Rapor</a>
  </div>
</div>

<!-- KPI kartları -->
<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
  <?php foreach ($kpis as [$label, $value, $chg, $ic, $tn, $link]): ?>
    <<?= $link ? 'a href="' . aurl($link) . '"' : 'div' ?> class="group relative overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line transition hover:-translate-y-0.5 hover:shadow-lg">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r <?= $tone[$tn][1] ?>"></div>
      <div class="flex items-start justify-between">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl <?= $tone[$tn][0] ?>"><?= icon($ic, 'h-5 w-5') ?></span>
        <?php if ($link): ?><?= icon('arrow-right', 'h-4 w-4 text-muted opacity-0 transition group-hover:opacity-100') ?><?php endif; ?>
      </div>
      <div class="mt-3 text-[26px] font-black leading-none tracking-tight text-ink"><?= $value ?></div>
      <div class="mt-1 text-sm font-semibold text-muted"><?= e($label) ?></div>
      <div class="mt-3 flex flex-wrap items-center"><?= $fmtChange($chg) ?></div>
    </<?= $link ? 'a' : 'div' ?>>
  <?php endforeach; ?>
</div>

<!-- Uyarı / iş listesi -->
<?php $todo = [
  ['Hazırlanacak Sipariş', $counts['to_prepare'], 'clipboard-list', 'orders?status=pending', 'blue'],
  ['Ödeme Bekleyen', $counts['pay_wait'], 'banknote', 'orders?payment=waiting', 'orange'],
  ['Kritik Stok', $counts['critical'], 'triangle-alert', 'stock?critical=1', 'red'],
  ['Okunmamış Mesaj', $counts['m_wait'], 'message-square', 'r/contact_messages?is_read=0', 'violet'],
  ['Cevap Bekleyen Soru', $counts['q_wait'], 'circle-help', 'r/questions', 'teal'],
  ['Onay Bekleyen Yorum', $counts['r_wait'], 'star', 'r/reviews?is_approved=0', 'orange'],
  ['Havale Bildirimi', $counts['t_wait'], 'receipt', 'r/transfer_notices', 'green'],
  ['İade Talebi', $counts['ret_wait'], 'undo2', 'r/return_requests?status=pending', 'red'],
]; ?>
<div class="mt-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-line">
  <div class="mb-3 flex items-center gap-2 text-sm font-bold"><?= icon('zap', 'h-4 w-4 text-amber-500') ?> Yapılacaklar <span class="text-xs font-normal text-muted">— tıklayınca ilgili listeye gider</span></div>
  <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8">
    <?php foreach ($todo as [$l, $v, $ic, $h, $tn]): $has = (int)$v > 0; ?>
      <a href="<?= aurl($h) ?>" class="flex items-center gap-3 rounded-xl p-3 transition <?= $has ? 'bg-soft hover:bg-white hover:shadow-md hover:ring-1 hover:ring-line' : 'opacity-60 hover:opacity-100' ?>">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg <?= $tone[$tn][0] ?>"><?= icon($ic, 'h-4 w-4') ?></span>
        <span class="min-w-0"><span class="block text-lg font-black leading-none <?= $has ? 'text-ink' : 'text-muted' ?>"><?= (int)$v ?></span><span class="block truncate text-[11px] font-semibold text-muted"><?= $l ?></span></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-3">
  <!-- Satış grafiği -->
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line xl:col-span-2" data-chart='<?= e(json_encode($chartData)) ?>'>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div><div class="font-bold"><?= $days === 1 ? 'Bugünkü Saatlik Satışlar' : 'Satış Grafiği' ?></div><div class="text-xs text-muted"><?= date('d.m.Y', strtotime($from)) ?> – <?= date('d.m.Y', strtotime($to)) ?></div></div>
      <div class="flex gap-1 rounded-lg bg-soft p-1 text-xs font-bold">
        <?php foreach (['ciro' => 'Ciro', 'siparis' => 'Sipariş', 'ziyaretci' => 'Ziyaretçi'] as $k => $l): if ($k === 'ziyaretci' && $days === 1) continue; ?><button type="button" data-metric="<?= $k ?>" class="metric-btn rounded-md px-3 py-1.5 <?= $k === 'ciro' ? 'is-active' : '' ?>"><?= $l ?></button><?php endforeach; ?>
      </div>
    </div>
    <div class="mt-3 flex items-baseline gap-2"><span class="text-2xl font-black" data-chart-total></span><span class="text-xs text-muted" data-chart-sub></span></div>
    <div class="relative mt-2 h-64" data-chart-area><svg class="h-full w-full overflow-visible" data-chart-svg></svg><div data-chart-tip class="pointer-events-none absolute z-10 hidden -translate-x-1/2 -translate-y-full rounded-lg bg-ink px-3 py-2 text-xs text-white shadow-xl"></div></div>
  </div>

  <!-- Sipariş durumu (halka) -->
  <?php $tot = array_sum($statusCounts); ?>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line">
    <div class="font-bold">Sipariş Durumu</div><div class="text-xs text-muted">Seçili dönemde verilen siparişler</div>
    <div class="mt-4 flex items-center gap-5">
      <div class="relative h-40 w-40 shrink-0">
        <svg viewBox="0 0 42 42" class="h-full w-full -rotate-90">
          <circle cx="21" cy="21" r="15.9" fill="none" stroke="#eef1f6" stroke-width="5"></circle>
          <?php $off = 0; foreach ($sl as $k => [$l]): $v = (int)($statusCounts[$k] ?? 0); if (!$v || !$tot) continue; $len = $v / $tot * 100; ?>
            <circle cx="21" cy="21" r="15.9" fill="none" stroke="<?= $statusColors[$k] ?>" stroke-width="5" stroke-dasharray="<?= max(0.1, $len - 0.8) ?> <?= 100 - max(0.1, $len - 0.8) ?>" stroke-dashoffset="<?= -$off ?>" pathLength="100"><title><?= $l ?>: <?= $v ?></title></circle>
          <?php $off += $len; endforeach; ?>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-3xl font-black"><?= $tot ?></span><span class="text-xs font-semibold text-muted">Sipariş</span></div>
      </div>
      <ul class="flex-1 space-y-2 text-sm">
        <?php foreach ($sl as $k => [$l]): $v = (int)($statusCounts[$k] ?? 0); ?>
          <li><a href="<?= aurl('orders?status=' . $k) ?>" class="flex items-center gap-2 rounded-md px-1 py-0.5 hover:bg-soft"><span class="h-2.5 w-2.5 rounded-full" style="background: <?= $statusColors[$k] ?>"></span><span class="flex-1 text-ink-2"><?= $l ?></span><b><?= $v ?></b></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-3">
  <!-- Son siparişler -->
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-line xl:col-span-2">
    <div class="flex items-center justify-between p-5 pb-3"><div class="font-bold">Son Siparişler</div><a href="<?= aurl('orders') ?>" class="text-xs font-bold text-brand">Tümünü Gör ›</a></div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm"><thead><tr class="border-y border-line bg-soft/60 text-left text-xs text-muted"><th class="px-5 py-2.5 font-bold">Sipariş</th><th class="px-3 font-bold">Müşteri</th><th class="px-3 font-bold">Ödeme</th><th class="px-3 font-bold">Durum</th><th class="px-5 text-right font-bold">Tutar</th></tr></thead><tbody>
        <?php foreach ($lastOrders as $o): ?>
          <tr class="border-b border-line last:border-0 hover:bg-soft/50">
            <td class="px-5 py-3"><a href="<?= aurl('orders/' . $o['id']) ?>" class="font-mono font-bold text-brand">#<?= e($o['order_no']) ?></a><div class="text-[11px] text-muted"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></div></td>
            <td class="px-3"><div class="flex items-center gap-2"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#1b2a4e] to-[#2a78d6] text-xs font-bold text-white"><?= e(mb_strtoupper(mb_substr($o['customer_name'], 0, 1))) ?></span><span class="font-semibold"><?= e($o['customer_name']) ?></span></div></td>
            <td class="px-3"><span class="<?= payment_status_labels()[$o['payment_status']][1] ?>"><?= e($ml[$o['payment_method']] ?? '') ?></span></td>
            <td class="px-3"><span class="<?= $sl[$o['status']][1] ?>"><?= $sl[$o['status']][0] ?></span></td>
            <td class="px-5 text-right font-extrabold"><?= money($o['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$lastOrders): ?><tr><td colspan="5" class="px-5 py-12 text-center text-muted"><?= icon('clipboard-list', 'mx-auto mb-2 h-8 w-8') ?>Henüz sipariş yok. Siteden gelen siparişler burada görünecek.</td></tr><?php endif; ?>
      </tbody></table>
    </div>
  </div>

  <!-- En çok satanlar -->
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line">
    <div class="flex items-center justify-between"><div class="font-bold">En Çok Satan Ürünler</div><a href="<?= aurl('reports/bestsellers') ?>" class="text-xs font-bold text-brand">Rapor ›</a></div>
    <?php $maxQ = max(1, max(array_map('intval', array_column($topProducts, 'q')) ?: [1])); ?>
    <ul class="mt-4 space-y-3">
      <?php foreach ($topProducts as $i => $tp): $pr = ['id' => $tp['id'], 'icon' => null, 'name_tr' => $tp['name']]; ?>
        <li><a href="<?= aurl('products/' . $tp['id']) ?>" class="flex items-center gap-3 rounded-lg hover:bg-soft">
          <span class="relative h-11 w-11 shrink-0 overflow-hidden rounded-lg bg-soft ring-1 ring-line"><?= product_thumb($pr) ?><span class="absolute left-0 top-0 flex h-4 w-4 items-center justify-center rounded-br bg-ink text-[9px] font-bold text-white"><?= $i + 1 ?></span></span>
          <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold"><?= e($tp['name']) ?></span>
            <span class="mt-1 flex items-center gap-2"><span class="h-1.5 flex-1 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full bg-gradient-to-r from-orange-500 to-red-500" style="width: <?= round($tp['q'] / $maxQ * 100) ?>%"></span></span><span class="text-[11px] font-bold text-muted"><?= (int)$tp['q'] ?> adet</span></span></span>
        </a></li>
      <?php endforeach; ?>
      <?php if (!$topProducts): ?><li class="py-8 text-center text-sm text-muted">Henüz satış yok.</li><?php endif; ?>
    </ul>
  </div>
</div>

<div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
  <!-- Kategori satışları -->
  <?php $bars = function ($title, $rows, $valKey, $fmt, $grad, $empty, $link = null) { $mx = max(1, max(array_map('floatval', array_column($rows, $valKey)) ?: [1])); ?>
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line">
      <div class="flex items-center justify-between"><div class="font-bold"><?= $title ?></div><?php if ($link): ?><a href="<?= aurl($link) ?>" class="text-xs font-bold text-brand">Rapor ›</a><?php endif; ?></div>
      <ul class="mt-4 space-y-3">
        <?php foreach ($rows as $r): ?>
          <li class="text-sm"><div class="flex justify-between gap-2"><span class="truncate font-semibold text-ink-2"><?= e($r['n'] ?: '—') ?></span><b class="shrink-0"><?= $fmt($r[$valKey]) ?></b></div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-soft"><div class="h-full rounded-full bg-gradient-to-r <?= $grad ?>" style="width: <?= max(3, round($r[$valKey] / $mx * 100)) ?>%"></div></div></li>
        <?php endforeach; ?>
        <?php if (!$rows): ?><li class="py-6 text-center text-sm text-muted"><?= $empty ?></li><?php endif; ?>
      </ul>
    </div>
  <?php }; ?>
  <?php $bars('Kategoriye Göre Satış', $catSales, 't', 'money', 'from-blue-500 to-sky-400', 'Bu dönemde satış yok.', 'reports/categories'); ?>
  <?php $bars('İllere Göre Satış', $citySales, 't', 'money', 'from-emerald-500 to-teal-400', 'Bu dönemde satış yok.', 'reports/cities'); ?>

  <!-- Ödeme tipleri -->
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line">
    <div class="font-bold">Ödeme Yöntemleri</div>
    <?php $pt = array_sum(array_map(function ($x) { return (int)$x['c']; }, $payTypes)) ?: 0; $pc = ['card' => ['from-violet-500 to-fuchsia-500', 'credit-card', 'bg-violet-50 text-violet-600'], 'transfer' => ['from-blue-500 to-sky-400', 'banknote', 'bg-blue-50 text-blue-600'], 'cod' => ['from-orange-500 to-amber-400', 'truck', 'bg-orange-50 text-orange-600']]; ?>
    <ul class="mt-4 space-y-4">
      <?php foreach ($ml as $k => $l): $c = (int)($payTypes[$k]['c'] ?? 0); $pcnt = $pt ? round($c / $pt * 100) : 0; ?>
        <li class="flex items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg <?= $pc[$k][2] ?>"><?= icon($pc[$k][1], 'h-4 w-4') ?></span>
          <div class="min-w-0 flex-1"><div class="flex justify-between text-sm"><span class="font-semibold"><?= $l ?></span><span class="text-muted"><b class="text-ink"><?= $c ?></b> · %<?= $pcnt ?></span></div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-soft"><div class="h-full rounded-full bg-gradient-to-r <?= $pc[$k][0] ?>" style="width: <?= $pcnt ?>%"></div></div></div></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Kritik stok -->
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line">
    <div class="flex items-center justify-between"><div class="font-bold">Stokta Azalan Ürünler</div><a href="<?= aurl('stock?critical=1') ?>" class="text-xs font-bold text-brand">Tümü ›</a></div>
    <ul class="mt-3 divide-y divide-line">
      <?php foreach ($critical as $c): ?>
        <li class="flex items-center justify-between gap-2 py-2.5 text-sm"><a href="<?= aurl('products/' . $c['id']) ?>" class="min-w-0 hover:text-brand"><span class="block truncate font-semibold"><?= e($c['name_tr']) ?></span><span class="text-[11px] text-muted"><?= e($c['code']) ?> · kritik: <?= (int)$c['critical_stock'] ?></span></a>
          <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-black <?= $c['stock'] <= 0 ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-800' ?>"><?= $c['stock'] <= 0 ? 'Tükendi' : (int)$c['stock'] . ' adet' ?></span></li>
      <?php endforeach; ?>
      <?php if (!$critical): ?><li class="flex flex-col items-center py-6 text-center text-sm text-emerald-700"><?= icon('check', 'mb-1 h-7 w-7') ?>Tüm ürünlerde stok yeterli</li><?php endif; ?>
    </ul>
  </div>
</div>

<!-- Genel durum + hızlı işlemler -->
<div class="mt-4 grid gap-4 xl:grid-cols-3">
  <div class="grid grid-cols-2 content-start gap-4 sm:grid-cols-4 xl:col-span-2">
    <?php foreach ([['Satıştaki Ürün', number_format((int)$counts['active_products'], 0, ',', '.'), 'package', 'blue', 'products?active=1'], ['Toplam Üye', number_format((int)$counts['members'], 0, ',', '.'), 'users', 'violet', 'r/members'], ['Dönemde Yeni Üye', $newMembers, 'user-plus', 'green', 'r/members'], ['Stok Değeri (alış)', money($counts['stock_value']), 'wallet', 'orange', 'stock']] as [$l, $v, $ic, $tn, $h]): ?>
      <a href="<?= aurl($h) ?>" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-line hover:shadow-md"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?= $tone[$tn][0] ?>"><?= icon($ic, 'h-5 w-5') ?></span><span class="min-w-0"><span class="block truncate text-lg font-black leading-tight"><?= $v ?></span><span class="block truncate text-xs font-semibold text-muted"><?= $l ?></span></span></a>
    <?php endforeach; ?>
  </div>
  <div class="rounded-2xl bg-gradient-to-br from-[#1b2a4e] to-[#2b3f73] p-5 text-white shadow-sm">
    <div class="font-bold">Hızlı İşlemler</div>
    <div class="mt-3 grid grid-cols-2 gap-2 text-xs font-bold">
      <?php foreach ([['Yeni Ürün', 'products/new', 'package-plus'], ['Excel ile Yükle', 'products/bulk', 'file-spreadsheet'], ['Kampanya Oluştur', 'r/campaigns/new', 'percent'], ['Banner Ekle', 'r/banners/new', 'image'], ['Stok Güncelle', 'stock', 'refresh-ccw'], ['Genel Ayarlar', 'settings/general', 'settings']] as [$l, $h, $ic]): ?>
        <a href="<?= aurl($h) ?>" class="flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2.5 transition hover:bg-white hover:text-ink"><?= icon($ic, 'h-4 w-4') ?><?= $l ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php admin_view('layout_bottom'); ?>
