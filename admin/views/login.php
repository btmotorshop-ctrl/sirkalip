<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Giriş | SIR KALIP Kontrol Paneli</title><link rel="stylesheet" href="<?= asset('css/style.css') ?>"><link rel="icon" href="<?= asset('img/favicon.png') ?>"></head>
<body class="flex min-h-screen items-center justify-center bg-[#1b2a4e] p-4">
  <form method="post" class="w-full max-w-sm rounded-xl bg-white p-8 shadow-2xl">
    <?= csrf_field() ?>
    <div class="mb-6 flex items-center gap-2">
      <img src="<?= asset('img/logo-sm.png') ?>" class="h-16 w-auto" alt="SIR KALIP">
      <div class="text-[10px] font-semibold tracking-widest text-muted">KONTROL PANELİ</div>
    </div>
    <?php if ($err): ?><div class="mb-4 rounded bg-red-50 px-3 py-2 text-sm font-semibold text-red-700"><?= e($err) ?></div><?php endif; ?>
    <label class="block"><span class="alabel">E-posta</span><input name="email" type="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>" class="ainput"></label>
    <label class="mt-4 block"><span class="alabel">Şifre</span><input name="password" type="password" required class="ainput"></label>
    <button class="abtn-primary mt-6 w-full">Giriş Yap</button>
    <a href="<?= e(url('/')) ?>" class="mt-4 block text-center text-xs font-semibold text-muted hover:text-brand">← Siteye dön</a>
  </form>
</body></html>
