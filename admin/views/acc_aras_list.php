<?php
$title = 'Kargolar (Aras)';
$ac = aras_config(); $urls = aras_urls($ac);
admin_view('layout_top', compact('title'));
$refreshAll = '<form method="post" action="' . aurl('acc/aras-refresh') . '" class="inline">' . csrf_field() . '<button class="abtn">' . icon('refresh-ccw', 'h-4 w-4') . ' Tüm durumları yenile</button></form> ';
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ön Muhasebe', aurl('acc')], ['Kargolar', null]],
  'actions' => $refreshAll . '<a class="abtn" href="' . aurl('settings/aras') . '">Aras ayarları</a>']);
?>
<?php if (!$ac['ready'] || !$ac['queryReady']): ?>
<div class="pf-err" style="background:#fff6dc;border-color:#f0e2b0;color:#7d5a0b">
  <?= !$ac['ready'] ? 'Gönderi (SetOrder) bilgileri eksik veya entegrasyon kapalı. ' : '' ?><?= !$ac['queryReady'] ? 'Durum sorgulama için Sorgulama (XML) servisi bilgileri eksik. ' : '' ?>
  <a href="<?= aurl('settings/aras') ?>" style="font-weight:700;color:#18a371">Aras Kargo ayarlarını açın</a>
</div>
<?php endif; ?>
<div class="acard">
  <div class="overflow-x-auto">
    <table class="atable">
      <thead><tr><th>Tarih</th><th>Alıcı</th><th>İl / İlçe</th><th>Parça</th><th>Entegrasyon Kodu</th><th>Durum</th><th>Takip No</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
        $st = !$r['ok'] ? '<span class="badge-muted" style="background:#fdecea;color:#b3261e">Hata</span>' : ($r['cancelled'] ? '<span class="badge-muted">İptal</span>' : ((int)$r['status_code'] === 6 ? '<span class="badge-ok">Teslim edildi</span>' : '<span class="badge-ok" style="background:#e3f1fa;color:#1d79ab">Gönderildi</span>')); ?>
        <tr>
          <td><?= e(substr((string)$r['created_at'], 0, 16)) ?></td>
          <td><?= e($r['receiver']) ?><div class="text-xs text-muted"><?= $r['source'] === 'order' ? 'Site siparişi #' . (int)$r['ref_id'] : ($r['source'] === 'party' ? 'Ön Muhasebe' : '') ?></div></td>
          <td><?= e($r['city'] . ' / ' . $r['town']) ?></td>
          <td><?= (int)$r['pieces'] ?></td>
          <td><code><?= e($r['integration_code']) ?></code></td>
          <td><?= $st ?><div class="text-xs text-muted" style="max-width:260px"><?= e($r['ok'] ? ($r['status_text'] ?: '') : $r['message']) ?><?= $r['checked_at'] ? ' · ' . e(substr((string)$r['checked_at'], 5, 11)) : '' ?></div></td>
          <td><b><?= e($r['tracking_no'] ?: '—') ?></b></td>
          <td class="text-right" style="white-space:nowrap">
            <?php if ($r['ok'] && !$r['cancelled']): ?>
              <a class="abtn" href="<?= aurl('acc/aras-label/' . (int)$r['id']) ?>">Etiket</a>
              <form method="post" action="<?= aurl('acc/aras-status/' . (int)$r['id']) ?>" class="inline"><?= csrf_field() ?><button class="abtn">Durum</button></form>
              <?php if (!$r['tracking_no']): ?><form method="post" action="<?= aurl('acc/aras-cancel/' . (int)$r['id']) ?>" class="inline" data-confirm="Aras gönderisi iptal edilsin mi? (Şubede kabul edilmişse iptal edilemez)"><?= csrf_field() ?><button class="abtn">İptal</button></form><?php endif; ?>
            <?php endif; ?>
            <button type="button" class="abtn" onclick="var t=document.getElementById('sp<?= (int)$r['id'] ?>');t.hidden=!t.hidden">Destek bilgisi</button>
          </td>
        </tr>
        <tr id="sp<?= (int)$r['id'] ?>" hidden><td colspan="8" style="background:#f7f9fb">
          <div class="text-xs" style="margin-bottom:6px"><b>Aras Entegrasyon Destek Formu için:</b> Ortam: <b><?= e($r['env'] === 'test' ? 'Test (Sandbox)' : 'Canlı (Prod)') ?></b> · Metot: <b><?= e($r['method'] ?: 'SetOrder') ?></b> · Link: <b><?= e(($r['env'] === 'test' ? aras_urls(['mode' => 'test'])['order'] : aras_urls(['mode' => 'live'])['order'])) ?></b> · Integration Code: <b><?= e($r['integration_code']) ?></b> (şifre maskelidir)</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <label class="block"><span class="alabel">Request</span><textarea readonly rows="9" class="ainput" style="font-family:monospace;font-size:11px;height:auto"><?= e((string)$r['req']) ?></textarea></label>
            <label class="block"><span class="alabel">Response</span><textarea readonly rows="9" class="ainput" style="font-family:monospace;font-size:11px;height:auto"><?= e((string)$r['res']) ?></textarea></label>
          </div>
        </td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" class="py-12 text-center text-muted">Henüz Aras gönderisi yok. Müşteri ekranındaki "Kargo Siparişi Oluştur" veya sipariş ekranındaki "Aras Kargo'ya Gönder" ile oluşturabilirsiniz.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_view('layout_bottom'); ?>
