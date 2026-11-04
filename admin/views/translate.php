<?php
admin_view('layout_top', ['title' => 'Otomatik Çeviri']);
admin_view('page_head', ['title' => 'Otomatik Çeviri', 'crumbs' => [['Ayarlar', null], ['Otomatik Çeviri', null]]]);
?>
<div class="acard max-w-3xl p-6">
  <form method="post" action="<?= aurl('translate/shipped') ?>" class="mb-5 rounded border border-line bg-slate-50 p-4">
    <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
    <p class="text-sm"><b>Hazır çeviriler:</b> Ürün adları, açıklamalar, kategoriler, sayfalar ve SSS için sitenin içinde hazır gelen Almanca, Fransızca, Rusça, Arapça ve İngilizce çeviriler. Site ilk açıldığında otomatik uygulanır; gerekirse buradan tekrar uygulayabilirsiniz (dolu alanlara dokunmaz). Dış servis gerekmez.</p>
    <button class="abtn-primary mt-3">Hazır çevirileri uygula</button>
  </form>
  <?php if (!$ready): ?>
    <div class="rounded bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
      Otomatik çeviri henüz kapalı. <a class="underline" href="<?= aurl('settings/translate') ?>">Çeviri Ayarları</a> sayfasından bir çeviri servisi seçin (ücretsiz seçenek anahtar gerektirmez; Google Cloud Translation ve DeepL için anahtar girilir).
      <?php if (!loc_ready()): ?><br>Not: Dil sütunları veritabanında henüz oluşmamış; siteyi bir kez açıp bu sayfayı yenileyin.<?php endif; ?>
    </div>
  <?php else: ?>
    <p class="text-sm text-muted">Türkçe metinlerden <b>İngilizce, Almanca, Fransızca, Rusça ve Arapça</b> çevirileri otomatik üretilir. Boş alanlar doldurulur; Türkçe metni değişen makine çevirileri yenilenir; <b>elle yazdığınız çevirilere dokunulmaz</b>. Ürün/kategori vb. kaydedildiğinde de otomatik çalışır (ayarlardan kapatılabilir).</p>
    <div class="mt-4 flex flex-wrap gap-2">
      <button type="button" id="trTest" class="abtn">Bağlantıyı dene</button>
      <button type="button" id="trRun" class="abtn-primary">Eksik çevirileri oluştur</button>
      <label class="flex items-center gap-2 text-xs font-semibold"><input type="checkbox" id="trForce"> Makine çevirilerini baştan yenile</label>
    </div>
    <div id="trMsg" class="mt-3 text-sm font-semibold"></div>
    <table class="mt-4 w-full text-sm">
      <thead><tr class="border-b border-line text-left text-xs text-muted"><th class="py-2">İçerik</th><th>Toplam</th><th>Çevrilecek</th><th>Durum</th></tr></thead>
      <tbody>
      <?php foreach ($tables as $t): ?>
        <tr class="border-b border-line" data-row="<?= e($t['table']) ?>"><td class="py-2 font-semibold"><?= e($t['label']) ?></td><td><?= (int)$t['total'] ?></td><td data-pending><?= (int)$t['pending'] ?></td><td data-state class="text-muted"><?= $t['pending'] ? 'Bekliyor' : 'Tamam' ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php if ($ready): ?>
<script>
(function () {
  var csrf = <?= json_encode(csrf()) ?>, url = <?= json_encode(aurl('translate/run')) ?>, msg = document.getElementById('trMsg');
  function post(o) { var f = new FormData(); f.append('_csrf', csrf); for (var k in o) f.append(k, o[k]); return fetch(url, { method: 'POST', body: f }).then(function (r) { return r.json(); }); }
  document.getElementById('trTest').onclick = function () {
    msg.textContent = 'Deneniyor...'; msg.className = 'mt-3 text-sm font-semibold';
    post({ do: 'test' }).then(function (j) { if (j.ok) { msg.textContent = 'Bağlantı başarılı. Örnek (Almanca): ' + j.sample; msg.className = 'mt-3 text-sm font-semibold text-green-700'; } else { msg.textContent = 'Hata: ' + j.error; msg.className = 'mt-3 text-sm font-semibold text-red-700'; } });
  };
  document.getElementById('trRun').onclick = async function () {
    var btn = this; btn.disabled = true; msg.className = 'mt-3 text-sm font-semibold'; var total = 0, force = document.getElementById('trForce').checked ? 1 : 0;
    var rows = document.querySelectorAll('[data-row]');
    for (var i = 0; i < rows.length; i++) {
      var tr = rows[i], table = tr.dataset.row, after = 0, st = tr.querySelector('[data-state]'), pend = tr.querySelector('[data-pending]');
      st.textContent = 'Çevriliyor...';
      while (true) {
        var j; try { j = await post({ table: table, after: after, force: force }); } catch (e) { msg.textContent = 'Bağlantı hatası, tekrar deneyin.'; msg.className = 'mt-3 text-sm font-semibold text-red-700'; btn.disabled = false; return; }
        if (j.error) { msg.textContent = 'Hata: ' + j.error; msg.className = 'mt-3 text-sm font-semibold text-red-700'; btn.disabled = false; return; }
        total += j.translated;
        if (j.errors && j.errors.length) { st.textContent = 'Hata – durduruldu'; st.className = 'text-red-700 font-semibold'; msg.textContent = 'Durduruldu: ' + j.errors[0] + ' (bu turda ' + total + ' alan çevrildi)'; msg.className = 'mt-3 text-sm font-semibold text-red-700'; btn.disabled = false; return; }
        after = j.last; st.textContent = 'Çevriliyor... (#' + after + ')';
        if (j.done) break;
      }
      st.textContent = 'Tamam'; st.className = 'text-green-700 font-semibold'; pend.textContent = '0';
    }
    if (!msg.className.includes('red')) { msg.textContent = 'Bitti. ' + total + ' alan çevrildi.'; msg.className = 'mt-3 text-sm font-semibold text-green-700'; }
    btn.disabled = false;
  };
})();
</script>
<?php endif; ?>
<?php admin_view('layout_bottom'); ?>
