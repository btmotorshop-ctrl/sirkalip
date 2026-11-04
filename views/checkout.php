<?php
$v = function ($k) use ($d) { return e($d[$k] ?? ''); };
$err = function ($k) use ($errors) { return !empty($errors[$k]) ? '<span class="mt-1 block text-xs font-semibold text-brand">' . e($errors[$k]) . '</span>' : ''; };
$cls = function ($k) use ($errors) { return 'h-11 w-full rounded-md border px-3 text-sm outline-none focus:border-brand ' . (!empty($errors[$k]) ? 'border-brand' : 'border-line'); };
$banks = db()->query('SELECT * FROM bank_accounts WHERE is_active = 1')->fetchAll();
?>
<section class="container-x py-6">
  <div class="mb-6 flex items-center justify-center gap-2 text-xs font-semibold sm:gap-4 sm:text-sm">
    <a href="<?= url('/sepet') ?>" class="flex items-center gap-2 text-ok"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-ok text-white"><?= icon('check', 'h-4 w-4') ?></span><?= L('Sepet', 'Cart') ?></a>
    <span class="h-px w-8 bg-line sm:w-16"></span>
    <span class="flex items-center gap-2 text-brand"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand text-white">2</span><?= L('Teslimat & Ödeme', 'Delivery & Payment') ?></span>
    <span class="h-px w-8 bg-line sm:w-16"></span>
    <span class="flex items-center gap-2 text-muted"><span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-line">3</span><?= L('Onay', 'Confirmation') ?></span>
  </div>
  <?php if (!empty($errors['_'])): ?><div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?= e($errors['_']) ?></div><?php endif; ?>
  <?php if (!$u): ?><div class="mb-4 rounded-lg border border-line bg-soft px-4 py-3 text-sm"><?= L('Üye misiniz?', 'Have an account?') ?> <a href="<?= url('/giris') ?>?r=<?= rawurlencode(url('/odeme')) ?>" class="font-bold text-brand"><?= L('Giriş yapın', 'Log in') ?></a> — <?= L('ya da üye olmadan devam edin.', 'or continue as guest.') ?></div><?php endif; ?>

  <form method="post" data-checkout class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
    <?= csrf_input() ?><input type="hidden" name="step" value="place">
    <div class="space-y-6">
      <div class="rounded-xl border border-line p-5 md:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-ink text-sm text-white">1</span><?= L('İletişim ve Teslimat Bilgileri', 'Contact & Delivery') ?></h2>
        <?php if ($addresses): ?>
          <div class="mb-4 flex flex-wrap gap-2"><?php foreach ($addresses as $a): ?><button type="button" data-fill-address='<?= e(json_encode(['full_name' => $a['full_name'], 'phone' => $a['phone'], 'city' => $a['city'], 'district' => $a['district'], 'address' => $a['address'], 'postal_code' => $a['postal_code']], JSON_UNESCAPED_UNICODE)) ?>' class="rounded-md border border-line px-3 py-2 text-left text-xs hover:border-brand"><b><?= e($a['title']) ?></b><br><?= e($a['district'] . ' / ' . $a['city']) ?></button><?php endforeach; ?></div>
        <?php endif; ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label class="block sm:col-span-2"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Ad Soyad', 'Full Name') ?> *</span><input name="full_name" value="<?= $v('full_name') ?>" autocomplete="name" class="<?= $cls('full_name') ?>"><?= $err('full_name') ?></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('E-posta', 'Email') ?> *</span><input type="email" name="email" value="<?= $v('email') ?>" autocomplete="email" class="<?= $cls('email') ?>"><?= $err('email') ?></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Cep Telefonu', 'Mobile Phone') ?> *</span><input type="tel" name="phone" value="<?= $v('phone') ?>" placeholder="05XX XXX XX XX" autocomplete="tel" class="<?= $cls('phone') ?>"><?= $err('phone') ?></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('İl', 'City') ?> *</span><select name="city" class="<?= $cls('city') ?> bg-white"><option value=""><?= L('Seçiniz', 'Select') ?></option><?php foreach ($cities as $c): ?><option <?= ($d['city'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select><?= $err('city') ?></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('İlçe', 'District') ?> *</span><input name="district" value="<?= $v('district') ?>" class="<?= $cls('district') ?>"><?= $err('district') ?></label>
          <label class="block sm:col-span-2"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Açık Adres', 'Address') ?> *</span><textarea name="address" rows="2" placeholder="<?= L('Mahalle, cadde, sokak, no, köy...', 'Street, number...') ?>" class="<?= $cls('address') ?> h-auto py-2"><?= $v('address') ?></textarea><?= $err('address') ?></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Posta Kodu', 'Postal Code') ?></span><input name="postal_code" value="<?= $v('postal_code') ?>" class="<?= $cls('postal_code') ?>"></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('TC Kimlik No (fatura için, isteğe bağlı)', 'National ID (optional)') ?></span><input name="tc_no" value="<?= $v('tc_no') ?>" maxlength="11" class="<?= $cls('tc_no') ?>"></label>
        </div>
        <label class="mt-4 flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="corporate" value="1" data-toggle="#corp" <?= !empty($d['corporate']) ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]"> <?= L('Kurumsal fatura istiyorum', 'I need a corporate invoice') ?></label>
        <div id="corp" class="mt-3 grid gap-4 sm:grid-cols-3 <?= empty($d['corporate']) ? 'hidden' : '' ?>">
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Firma Ünvanı', 'Company') ?></span><input name="company_name" value="<?= $v('company_name') ?>" class="<?= $cls('company_name') ?>"></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Vergi Dairesi', 'Tax Office') ?></span><input name="tax_office" value="<?= $v('tax_office') ?>" class="<?= $cls('tax_office') ?>"></label>
          <label class="block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Vergi No', 'Tax No') ?></span><input name="tax_number" value="<?= $v('tax_number') ?>" class="<?= $cls('tax_number') ?>"></label>
          <div class="sm:col-span-3"><?= $err('company_name') ?></div>
        </div>
        <?php if ($u): ?><label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="save_address" value="1" <?= $addresses ? '' : 'checked' ?> class="h-4 w-4 accent-[#e30613]"> <?= L('Bu adresi hesabıma kaydet', 'Save this address') ?></label>
        <?php else: ?>
          <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="create_account" value="1" data-toggle="#pw" <?= !empty($d['create_account']) ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]"> <?= L('Üyelik oluştur (siparişlerimi takip etmek istiyorum)', 'Create an account') ?></label>
          <div id="pw" class="mt-3 max-w-sm <?= empty($d['create_account']) ? 'hidden' : '' ?>"><input type="password" name="password" placeholder="<?= L('Şifre (en az 6 karakter)', 'Password (min 6)') ?>" class="<?= $cls('password') ?>"><?= $err('password') ?><input type="hidden" name="save_address" value="1"></div>
        <?php endif; ?>
      </div>

      <div class="rounded-xl border border-line p-5 md:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-extrabold"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-ink text-sm text-white">2</span><?= L('Ödeme Yöntemi', 'Payment Method') ?></h2>
        <div class="space-y-3">
          <?php foreach ($methods as $k => $l): ?>
            <label class="block cursor-pointer rounded-lg border-2 p-4 <?= $method === $k ? 'border-brand bg-brand-50/40' : 'border-line' ?>">
              <span class="flex items-center gap-3"><input type="radio" name="payment_method" value="<?= $k ?>" <?= $method === $k ? 'checked' : '' ?> data-recalc class="h-4 w-4 accent-[#e30613]">
                <?= icon(['card' => 'lock', 'transfer' => 'store', 'cod' => 'truck'][$k], 'h-5 w-5 text-brand') ?><b><?= e($l) ?></b>
                <?php if ($k === 'transfer' && !empty($pay['transferDiscount'])): ?><span class="ml-auto rounded bg-green-50 px-2 py-0.5 text-xs font-bold text-ok">%<?= (float)$pay['transferDiscount'] ?> <?= L('indirim', 'off') ?></span><?php endif; ?>
                <?php if ($k === 'cod' && !empty($pay['codFee'])): ?><span class="ml-auto text-xs text-muted">+<?= money($pay['codFee']) ?> <?= L('hizmet bedeli', 'fee') ?></span><?php endif; ?>
              </span>
              <?php if ($method === $k): ?><span class="mt-2 block pl-7 text-xs text-muted">
                <?= $k === 'transfer' ? L('Siparişinizi tamamladıktan sonra aşağıdaki hesaplara ödeme yapabilirsiniz. Açıklamaya sipariş numaranızı yazın.', 'Pay to the accounts below after placing your order; include your order number.') : ($k === 'cod' ? L('Ödemeyi ürünü teslim alırken kargo görevlisine yaparsınız.', 'Pay the courier on delivery.') : L('Siparişi onayladıktan sonra güvenli ödeme sayfasına yönlendirilirsiniz.', 'You will be redirected to the secure payment page.')) ?>
                <?php if ($k === 'transfer') foreach ($banks as $b) echo '<span class="mt-2 block rounded bg-white p-2 text-ink"><b>' . e($b['bank_name']) . '</b> · ' . e($b['account_holder']) . '<br><span class="font-mono">' . e($b['iban']) . '</span></span>'; ?>
              </span><?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
        <label class="mt-5 block"><span class="mb-1 block text-xs font-semibold text-muted"><?= L('Sipariş Notu (isteğe bağlı)', 'Order Note (optional)') ?></span><textarea name="note" rows="2" placeholder="<?= L('Makine modeli, teslimat notu vb.', 'Machine model, delivery note...') ?>" class="w-full rounded-md border border-line p-3 text-sm"><?= $v('note') ?></textarea></label>
      </div>
    </div>

    <aside class="lg:sticky lg:top-6 lg:self-start">
      <div class="rounded-xl border border-line p-5">
        <h2 class="mb-4 text-lg font-extrabold"><?= L('Sipariş Özeti', 'Order Summary') ?></h2>
        <div class="mb-4 max-h-64 space-y-3 overflow-y-auto">
          <?php foreach ($t['items'] as $it): ?><div class="flex gap-3"><div class="relative w-14 shrink-0"><?= product_thumb($it['p']) ?><span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-ink px-1 text-[11px] font-bold text-white"><?= (int)$it['qty'] ?></span></div><div class="min-w-0 flex-1 text-sm"><div class="line-clamp-2 font-semibold"><?= e(tr_field($it['p'], 'name')) ?></div><div class="text-xs text-muted"><?= e($it['p']['code']) ?></div></div><div class="text-sm font-bold"><?= money($it['total']) ?></div></div><?php endforeach; ?>
        </div>
        <div class="border-t border-line pt-4"><?php view('partials/summary', ['t' => $t]); ?></div>
        <label class="mt-5 flex items-start gap-2 text-xs leading-relaxed"><input type="checkbox" name="agree" value="1" <?= !empty($d['agree']) ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 shrink-0 accent-[#e30613]">
          <span><a href="<?= url('/sozlesmeler/on-bilgilendirme') ?>" target="_blank" class="font-semibold text-brand underline"><?= L('Ön Bilgilendirme Formu', 'Preliminary Information') ?></a> <?= L('ve', 'and') ?> <a href="<?= url('/sozlesmeler/mesafeli-satis') ?>" target="_blank" class="font-semibold text-brand underline"><?= L('Mesafeli Satış Sözleşmesi', 'Distance Sales Agreement') ?></a><?= L('\'ni okudum, onaylıyorum.', ' — I have read and accept.') ?></span></label>
        <?= $err('agree') ?>
        <button class="btn-primary mt-4 h-12 w-full text-base"><?= icon('lock', 'h-5 w-5') ?> <?= L('Siparişi Onayla', 'Place Order') ?> · <?= money($t['total']) ?></button>
        <p class="mt-3 text-center text-xs text-muted"><?= L('Bilgileriniz 256-bit SSL ile korunur.', 'Your data is protected with SSL.') ?></p>
      </div>
    </aside>
  </form>
</section>
