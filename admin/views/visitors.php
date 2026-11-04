<?php
$title = 'Ziyaretçi ve Ürün Görüntüleme Raporu';
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Raporlar', '#'], [$title, null]], 'actions' => '']);
?>
<style>
.vcard{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;min-width:0}
.vcard h3{font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px}
.vgrid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:20px}
.vstat{background:linear-gradient(135deg,#f8fafc,#eef2f7);border:1px solid #e2e8f0;border-radius:10px;padding:16px;text-align:center}
.vstat .num{font-size:28px;font-weight:800;color:#1e293b}.vstat .lbl{font-size:12px;color:#64748b;margin-top:4px}
.vtable{width:100%;border-collapse:collapse;font-size:13px}
.vtable th{background:#f8fafc;text-align:left;padding:8px 12px;font-size:12px;color:#475569;border-bottom:2px solid #e2e8f0;white-space:nowrap}
.vtable td{padding:8px 12px;border-bottom:1px solid #eef2f6;vertical-align:middle}
.vtable tr:hover{background:#f1f5f9}
.vtable td.r{text-align:right}
.vfilter{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;align-items:flex-end}
.vfilter label{font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:3px}
.vfilter input,.vfilter select{height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:13px}
.vfilter button{height:36px;padding:0 16px;background:#2563eb;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer}
.vfilter button:hover{background:#1d4ed8}
.vbadge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600}
.vb-mobile{background:#dbeafe;color:#1e40af}.vb-desktop{background:#dcfce7;color:#166534}.vb-tablet{background:#fef3c7;color:#92400e}
.vb-product{background:#ede9fe;color:#5b21b6}.vb-page{background:#f1f5f9;color:#475569}
.vbar{height:6px;border-radius:3px;background:#e2e8f0;overflow:hidden;margin-top:4px}
.vbar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,#3b82f6,#1d4ed8)}
.vsplit{display:grid;gap:16px;grid-template-columns:1fr 1fr}
@media(max-width:900px){.vsplit{grid-template-columns:1fr}}
.vip{font-family:monospace;font-size:12px;color:#475569}
.flag{margin-right:4px}
</style>

<!-- Filtreler -->
<form class="vfilter" method="get" action="<?= aurl('visitors') ?>">
  <div><label>Başlangıç</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div><label>Bitiş</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <div><label>Sayfa Tipi</label>
    <select name="type"><option value="">Tümü</option><option value="product" <?= $type === 'product' ? 'selected' : '' ?>>Ürün Görüntüleme</option><option value="page" <?= $type === 'page' ? 'selected' : '' ?>>Sayfa Görüntüleme</option></select>
  </div>
  <div><label>Ülke</label>
    <select name="country"><option value="">Tümü</option><?php foreach ($allCountries as $c): ?><option value="<?= e($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
  </div>
  <div><label>IP Adresi</label><input type="text" name="ip" value="<?= e($ip) ?>" placeholder="Filtrele..."></div>
  <div><button type="submit"><?= icon('search', 'h-4 w-4') ?> Filtrele</button></div>
  <?php if ($type || $ip || $country || $from !== date('Y-m-d', strtotime('-7 days'))): ?><div><a href="<?= aurl('visitors') ?>" style="font-size:12px;color:#ef4444">✕ Sıfırla</a></div><?php endif; ?>
</form>

<!-- Özet Kartları -->
<div class="vgrid">
  <div class="vstat"><div class="num"><?= number_format((int)$stats['total_views'], 0, ',', '.') ?></div><div class="lbl">Toplam Görüntüleme</div></div>
  <div class="vstat"><div class="num"><?= number_format((int)$stats['unique_ips'], 0, ',', '.') ?></div><div class="lbl">Tekil IP</div></div>
  <div class="vstat"><div class="num"><?= number_format((int)$stats['unique_sessions'], 0, ',', '.') ?></div><div class="lbl">Tekil Oturum</div></div>
  <div class="vstat"><div class="num"><?= number_format((int)$stats['product_views'], 0, ',', '.') ?></div><div class="lbl">Ürün Görüntüleme</div></div>
  <div class="vstat" style="background:linear-gradient(135deg,#dcfce7,#d1fae5)"><div class="num"><?= number_format((int)$stats['desktop'], 0, ',', '.') ?></div><div class="lbl">🖥️ Masaüstü</div></div>
  <div class="vstat" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe)"><div class="num"><?= number_format((int)$stats['mobile'], 0, ',', '.') ?></div><div class="lbl">📱 Mobil</div></div>
  <div class="vstat" style="background:linear-gradient(135deg,#fef3c7,#fde68a)"><div class="num"><?= number_format((int)$stats['tablet'], 0, ',', '.') ?></div><div class="lbl">📟 Tablet</div></div>
</div>

<div class="vsplit">
  <!-- Ülke Dağılımı -->
  <div class="vcard">
    <h3>🌍 Ülke Dağılımı</h3>
    <table class="vtable">
      <thead><tr><th>Ülke</th><th>Ziyaret</th><th>Tekil IP</th><th style="width:100px"></th></tr></thead>
      <tbody>
      <?php $maxC = $countryList ? (int)$countryList[0]['cnt'] : 1; foreach ($countryList as $c): $pct = round($c['cnt'] / max($maxC, 1) * 100); ?>
        <tr><td><a href="<?= aurl('visitors') ?>?from=<?= e($from) ?>&to=<?= e($to) ?>&country=<?= urlencode($c['c']) ?>"><?= e($c['c']) ?></a></td><td class="r"><?= number_format((int)$c['cnt'], 0, ',', '.') ?></td><td class="r"><?= number_format((int)$c['ips'], 0, ',', '.') ?></td><td><div class="vbar"><div class="vbar-fill" style="width:<?= $pct ?>%"></div></div></td></tr>
      <?php endforeach; ?>
      <?php if (!$countryList): ?><tr><td colspan="4" style="text-align:center;color:#94a3b8">Veri yok</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- En Çok Görüntülenen Ürünler -->
  <div class="vcard">
    <h3>📦 En Çok Görüntülenen Ürünler</h3>
    <table class="vtable">
      <thead><tr><th>#</th><th>Ürün</th><th>Kod</th><th>Görüntüleme</th><th>Tekil IP</th></tr></thead>
      <tbody>
      <?php $i = 1; foreach ($topProductList as $pr): ?>
        <tr><td><?= $i++ ?></td><td><?= e($pr['name_tr'] ?? '—') ?></td><td style="font-family:monospace;font-size:12px"><?= e($pr['code'] ?? '') ?></td><td class="r"><b><?= number_format((int)$pr['cnt'], 0, ',', '.') ?></b></td><td class="r"><?= number_format((int)$pr['ips'], 0, ',', '.') ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$topProductList): ?><tr><td colspan="5" style="text-align:center;color:#94a3b8">Ürün görüntüleme verisi yok</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="vsplit" style="margin-top:16px">
  <!-- Tarayıcı Dağılımı -->
  <div class="vcard">
    <h3>🌐 Tarayıcı Dağılımı</h3>
    <table class="vtable">
      <thead><tr><th>Tarayıcı</th><th>Ziyaret</th><th></th></tr></thead>
      <tbody>
      <?php $maxB = $browserList ? (int)$browserList[0]['cnt'] : 1; foreach ($browserList as $b): $pct = round($b['cnt'] / max($maxB, 1) * 100); ?>
        <tr><td><?= e($b['browser'] ?? 'Bilinmiyor') ?></td><td class="r"><?= number_format((int)$b['cnt'], 0, ',', '.') ?></td><td style="width:100px"><div class="vbar"><div class="vbar-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#8b5cf6,#6d28d9)"></div></div></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- İşletim Sistemi Dağılımı -->
  <div class="vcard">
    <h3>💻 İşletim Sistemi Dağılımı</h3>
    <table class="vtable">
      <thead><tr><th>İşletim Sistemi</th><th>Ziyaret</th><th></th></tr></thead>
      <tbody>
      <?php $maxO = $osList ? (int)$osList[0]['cnt'] : 1; foreach ($osList as $o): $pct = round($o['cnt'] / max($maxO, 1) * 100); ?>
        <tr><td><?= e($o['os'] ?? 'Bilinmiyor') ?></td><td class="r"><?= number_format((int)$o['cnt'], 0, ',', '.') ?></td><td style="width:100px"><div class="vbar"><div class="vbar-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#f59e0b,#d97706)"></div></div></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- En Aktif IP'ler -->
<div class="vcard" style="margin-top:16px">
  <h3>🔍 En Aktif Ziyaretçiler (IP)</h3>
  <div style="overflow-x:auto">
  <table class="vtable">
    <thead><tr><th>IP Adresi</th><th>Ülke</th><th>Şehir</th><th>Ziyaret Sayısı</th><th>Son Ziyaret</th></tr></thead>
    <tbody>
    <?php foreach ($topIpList as $ti): ?>
      <tr>
        <td><a href="<?= aurl('visitors') ?>?from=<?= e($from) ?>&to=<?= e($to) ?>&ip=<?= urlencode($ti['ip']) ?>" class="vip"><?= e($ti['ip']) ?></a></td>
        <td><?= e($ti['country'] ?? '—') ?></td>
        <td><?= e($ti['city'] ?? '—') ?></td>
        <td class="r"><b><?= number_format((int)$ti['cnt'], 0, ',', '.') ?></b></td>
        <td><?= e(date('d.m.Y H:i', strtotime($ti['last_visit']))) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Son Ziyaretler -->
<div class="vcard" style="margin-top:16px">
  <h3>📋 Son Ziyaretler (Son 100)</h3>
  <div style="overflow-x:auto">
  <table class="vtable">
    <thead><tr><th>Tarih</th><th>IP</th><th>Ülke / Şehir</th><th>Sayfa</th><th>Ürün</th><th>Cihaz</th><th>Tarayıcı</th><th>İ.S.</th><th>Kaynak</th></tr></thead>
    <tbody>
    <?php foreach ($recentList as $r): ?>
      <tr>
        <td style="white-space:nowrap;font-size:12px"><?= e(date('d.m H:i', strtotime($r['created_at']))) ?></td>
        <td><a href="<?= aurl('visitors') ?>?from=<?= e($from) ?>&to=<?= e($to) ?>&ip=<?= urlencode($r['ip']) ?>" class="vip"><?= e($r['ip']) ?></a></td>
        <td style="font-size:12px"><?= e(($r['country'] ?? '') . ($r['city'] ? ' / ' . $r['city'] : '')) ?: '—' ?></td>
        <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($r['page_url']) ?>"><?= e($r['page_url']) ?></td>
        <td><?php if ($r['product_id']): ?><span class="vbadge vb-product"><?= e($r['product_code'] ?? '') ?></span> <span style="font-size:12px"><?= e(mb_strimwidth($r['product_name'] ?? '', 0, 30, '…')) ?></span><?php else: ?><span class="vbadge vb-page">sayfa</span><?php endif; ?></td>
        <td><span class="vbadge vb-<?= $r['device_type'] ?>"><?= e($r['device_type']) ?></span></td>
        <td style="font-size:12px"><?= e($r['browser'] ?? '') ?></td>
        <td style="font-size:12px"><?= e($r['os'] ?? '') ?></td>
        <td style="font-size:11px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($r['referer'] ?? '') ?>"><?php
          $ref = $r['referer'] ?? '';
          if ($ref) { $host = parse_url($ref, PHP_URL_HOST) ?: $ref; echo e($host); } else { echo '<span style="color:#94a3b8">Direkt</span>'; }
        ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$recentList): ?><tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:20px">Henüz ziyaretçi kaydı yok</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php admin_view('layout_bottom'); ?>
