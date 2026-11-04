<?php
admin_view('layout_top', compact('title'));
admin_view('page_head', ['title' => $title, 'crumbs' => [['Ayarlar', null], [$title, null]]]);
?>
<div class="grid gap-4 lg:grid-cols-4">
  <div class="acard h-fit p-2 text-sm">
    <?php foreach ($schema as $k => [$t]): ?><a href="<?= aurl("settings/$k") ?>" class="block rounded px-3 py-2 font-semibold <?= $k === $key ? 'bg-brand-50 text-brand' : 'hover:bg-soft' ?>"><?= e($t) ?></a><?php endforeach; ?>
    <div class="my-2 border-t border-line"></div>
    <?php foreach ([['Kargo Firmaları', 'r/shipping_companies'], ['Banka Hesapları', 'r/bank_accounts'], ['Döviz Kurları', 'r/currency_rates'], ['Kullanıcılar', 'r/admins']] as [$l, $h]): ?><a href="<?= aurl($h) ?>" class="block rounded px-3 py-2 font-semibold hover:bg-soft"><?= $l ?></a><?php endforeach; ?>
  </div>
  <form method="post" class="acard p-6 lg:col-span-3">
    <?= csrf_field() ?>
    <?php if ($key === 'pos'): $pc = pos_config(); ?>
    <div class="mb-5 rounded-xl border <?= $pc['ready'] ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' ?> p-4 text-sm">
      <div class="flex items-center gap-2 font-bold <?= $pc['ready'] ? 'text-green-700' : 'text-amber-700' ?>"><?= icon('credit-card', 'h-5 w-5') ?> <?= $pc['ready'] ? 'Kredi kartı ile ödeme AKTİF (' . e($pc['provider']) . ')' : 'Kredi kartı ile ödeme henüz aktif değil' ?></div>
      <ul class="mt-2 list-disc space-y-1 pl-5 text-ink/80">
        <li>Aktif POS'u seçip ilgili firmanın API bilgilerini girin ve kaydedin. Ardından <a class="font-semibold text-brand underline" href="<?= aurl('settings/payment') ?>">Ödeme Ayarları</a> sayfasında “Kredi Kartı” açık olmalı.</li>
        <li>Kart bilgileri sitenize hiç girilmez; müşteri iyzico / PayTR'nin güvenli (3D Secure) ödeme formunda öder.</li>
        <li><b>iyzico:</b> Test için sandbox-merchant.iyzipay.com'dan alınan sandbox anahtarlarını kullanıp “Test Modu”nu açın.</li>
        <li><b>PayTR:</b> PayTR Mağaza Paneli › Destek & Kurulum › Bildirim URL alanına şunu yazın: <code class="rounded bg-white px-1.5 py-0.5 text-xs font-bold"><?= e(site_origin() . url('/odeme/paytr-bildirim')) ?></code></li>
      </ul>
    </div>
    <?php endif; ?>
    <div class="grid gap-4 md:grid-cols-2">
      <?php foreach ($fields as $f => $spec):
        $wide = $spec[1] === 'textarea'; $cur = $val[$f] ?? ''; ?>
        <div class="<?= $wide ? 'md:col-span-2' : '' ?>">
          <?php if ($spec[1] === 'password'): ?>
            <label class="block"><span class="alabel"><?= e($spec[0]) ?></span><input type="password" name="<?= $f ?>" placeholder="<?= $cur ? '•••••••• (kayıtlı – değiştirmek için yazın)' : e((string)($spec[3] ?? '')) ?>" class="ainput"></label>
          <?php else: admin_view('field', ['f' => $f, 'spec' => $spec, 'val' => $cur, 'err' => null]); endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-6 border-t border-line pt-5"><button class="abtn-primary"><?= icon('check', 'h-4 w-4') ?> Kaydet</button></div>
  </form>
</div>
<?php admin_view('layout_bottom'); ?>
