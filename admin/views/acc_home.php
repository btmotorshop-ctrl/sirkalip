<?php
$title = 'Ana Sayfa';
admin_view('layout_top', compact('title'));
$tl = function ($v) { return 'TL ' . number_format((float)$v, 2, ',', '.'); };
$parts = explode(' ', tr_date_long()); $month = $parts[1] ?? 'Aylık';
$assetTotal = $stockValue + $receivable + max($cash, 0);
$kv = function ($v) { return '<strong data-count="' . round((float)$v, 2) . '" data-pre="TL ">TL ' . number_format((float)$v, 2, ',', '.') . '</strong>'; };
$pct = function ($v, $t) { return $t > 0 ? max(2, min(100, round($v / $t * 100))) : 0; };
?>
<div class="ah-head">
  <div>
    <h1 class="ah-date"><?= e(tr_date_long()) ?></h1>
    <div class="ah-rates">
      <?php if (!empty($rates['USD'])): ?><span class="ah-rate u">$ <?= number_format((float)$rates['USD'], 4, ',', '.') ?></span><?php endif; ?>
      <?php if (!empty($rates['EUR'])): ?><span class="ah-rate e">€ <?= number_format((float)$rates['EUR'], 4, ',', '.') ?></span><?php endif; ?>
    </div>
  </div>
  <div class="ah-mini">
    <div><b>Bugünkü Satış</b><span><?= $tl($todaySales) ?></span></div>
    <div><b>Bugünkü Tahsilat</b><span><?= $tl($todayIn) ?></span></div>
  </div>
</div>

<div class="ah-kpis">
  <?php foreach ([
    ['a', 'trending-up', $monthSales, $month . ' Cirosu', 'calendar', $sales30, 'Son 30 Günlük Ciro'],
    ['b', 'receipt', $monthExp, $month . ' Masrafları', 'calendar', $exp30, 'Son 30 Günlük Masraf'],
  ] as [$tn, $ic1, $v1, $l1, $ic2, $v2, $l2]): ?>
  <div class="ah-flip" data-flip title="Çevirmek için tıklayın" tabindex="0">
    <div class="ah-flip-in">
      <div class="ah-kpi <?= $tn ?> ah-face"><?= icon($ic1, '') ?><div><?= $kv($v1) ?><span><?= e($l1) ?></span></div></div>
      <div class="ah-kpi <?= $tn ?> ah-face ah-back"><?= icon($ic2, '') ?><div><?= $kv($v2) ?><span><?= e($l2) ?></span></div></div>
    </div>
  </div>
  <?php endforeach; ?>
  <a class="ah-kpi c" href="<?= aurl('acc/stock') ?>"><?= icon('banknote', '') ?><div><strong data-count="<?= round((float)$stockValue, 2) ?>" data-pre=""><?= number_format((float)$stockValue, 2, ',', '.') ?></strong><span>Stok Değeri</span></div></a>
</div>

<div class="ah-grid">
  <div class="ah-col">
    <div class="ah-card">
      <h3><span>Varlıklar</span><span><?= $tl($assetTotal) ?></span></h3>
      <div class="ah-body">
        <?php foreach ([['Stok', $stockValue, aurl('acc/stock')], ['Açık Hesap', $receivable, aurl('acc/open-accounts?type=B')], ['Kasa + Banka', max($cash, 0), aurl('acc/accounts')]] as [$l, $v, $h]): ?>
          <a class="ah-bar g" href="<?= $h ?>"><div class="t"><span><?= e($l) ?></span><em><?= $tl($v) ?></em></div><div class="ah-track"><div class="ah-fill" style="width:<?= $pct($v, $assetTotal) ?>%"></div></div></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="ah-card dk">
      <h3><span>Vadesi Geçen Açık Hesap Alacaklar</span><a href="<?= aurl('acc/balances') ?>" style="color:#fff"><?= icon('refresh-ccw', 'h-4 w-4') ?></a></h3>
      <?php if ($overdue): ?>
        <div class="ah-list"><?php foreach ($overdue as $o): ?>
          <a href="<?= aurl('acc/party/' . $o['id']) ?>"><div class="l1"><b><?= e($o['name']) ?></b><span><?= number_format($o['bal'], 2, ',', '.') ?> TL</span></div><div class="l2"><?= $o['days'] ?> gün</div></a>
        <?php endforeach; ?></div>
        <a class="ah-more" href="<?= aurl('acc/balances') ?>">devamı...</a>
      <?php else: ?><div class="ah-empty">Vadesi geçen alacak yok.</div><?php endif; ?>
    </div>
  </div>

  <div class="ah-col">
    <div class="ah-card">
      <h3><span>Borçlar</span><span><?= $tl($payable) ?></span></h3>
      <div class="ah-body">
        <a class="ah-bar r" href="<?= aurl('acc/open-accounts?type=A') ?>"><div class="t"><span>Açık Hesap</span><em><?= $tl($payable) ?></em></div><div class="ah-track"><div class="ah-fill r" style="width:<?= $payable > 0 ? 100 : 0 ?>%"></div></div></a>
        <?php if ($overdueSupp): ?>
          <div style="font-size:12px;font-weight:700;color:#7b8794;margin:14px 0 4px">VADESİ GEÇEN TEDARİKÇİ BORÇLARI</div>
          <?php foreach ($overdueSupp as $o): ?><a href="<?= aurl('acc/party/' . $o['id']) ?>" style="display:flex;justify-content:space-between;font-size:13.5px;padding:5px 0;color:#3a4553;text-decoration:none"><span><?= e($o['name']) ?> <small style="color:#7b8794">· <?= $o['days'] ?> gün</small></span><b><?= number_format($o['bal'], 2, ',', '.') ?> TL</b></a><?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="ah-card or">
      <h3><span>Yaklaşan Masraflar</span></h3>
      <?php if ($upcoming): ?>
        <div class="ah-list plain"><?php foreach ($upcoming as $x): ?>
          <a href="<?= aurl('r/acc_expenses/' . $x['id']) ?>"><div class="l1"><b><?= acc_date($x['due_at'] ?? $x['exp_at']) ?></b><span><?= number_format($x['amount'], 2, ',', '.') ?> TL</span></div><div class="l2"><?= e($x['description'] ?: $x['category']) ?></div></a>
        <?php endforeach; ?></div>
        <a class="ah-more" href="<?= aurl('r/acc_expenses') ?>">tamamı...</a>
      <?php else: ?><div class="ah-empty">Ödenmemiş masraf yok.</div><?php endif; ?>
    </div>
  </div>

  <div class="ah-col">
    <div class="ah-card bl">
      <h3><span>Hızlı İşlemler</span></h3>
      <div class="ah-quick">
        <a href="<?= aurl('acc/doc/sale/new') ?>">+ Satış Gir</a><a href="<?= aurl('acc/doc/purchase/new') ?>">+ Alış Gir</a>
        <a href="<?= aurl('r/acc_collections/new') ?>">+ Tahsilat</a><a href="<?= aurl('r/acc_payouts/new') ?>">+ Ödeme</a>
        <a href="<?= aurl('r/acc_expenses/new') ?>">+ Masraf</a><a href="<?= aurl('acc/party-edit/customer/new') ?>">+ Müşteri</a>
      </div>
      <div class="ah-body" style="padding-top:0;font-size:13.5px">
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-top:1px solid #e6e9ed"><span>Aylık Alışlar</span><b><?= $tl($monthPur) ?></b></div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-top:1px solid #e6e9ed"><span>Site Siparişleri (bu ay)</span><b><?= $tl($webMonth) ?></b></div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-top:1px solid #e6e9ed"><span>30 Günlük Ciro</span><b><?= $tl($sales30) ?></b></div>
      </div>
    </div>
    <div class="ah-card gr">
      <h3><span>Son İşlemler</span></h3>
      <?php foreach ($events as [$when, $text]): ?>
        <div class="ah-ev"><small><?= e(date('d.m.Y H:i', strtotime((string)$when))) ?></small><?= e($text) ?></div>
      <?php endforeach; ?>
      <?php if (!$events): ?><div class="ah-empty">Henüz işlem yok. Satış, alış, tahsilat ya da masraf girerek başlayın.</div><?php endif; ?>
    </div>
  </div>
</div>
<script>
(function(){
  // sayılar yukarı sayarak gelir
  var fmt=function(n){return n.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})};
  [].forEach.call(document.querySelectorAll('[data-count]'),function(el){
    var end=parseFloat(el.getAttribute('data-count'))||0,pre=el.getAttribute('data-pre')||'',t0=null;
    if(!end||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
    function step(t){t0=t0||t;var k=Math.min(1,(t-t0)/1100),e=1-Math.pow(1-k,3);el.textContent=pre+fmt(end*e);if(k<1)requestAnimationFrame(step)}
    requestAnimationFrame(step);
  });
  var cards=[].slice.call(document.querySelectorAll('[data-flip]'));
  function flip(c){c.classList.toggle('on')}
  cards.forEach(function(c,i){
    c.addEventListener('click',function(){flip(c)});
    c.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();flip(c)}});
  });
  // 10 saniyede bir kendiliğinden döner (fare üzerindeyken beklemez)
  setInterval(function(){cards.forEach(function(c){if(!c.matches(':hover'))flip(c)})},10000);
})();
</script>
<?php admin_view('layout_bottom'); ?>
