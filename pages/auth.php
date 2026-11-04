<?php
$act = $params[1];
$r = $_GET['r'] ?? ($_POST['r'] ?? '');
$safeR = (is_string($r) && strpos($r, '/') === 0 && strpos($r, '//') !== 0) ? $r : url('/hesabim');
if ($act === 'cikis') { unset($_SESSION['customer_id']); session_regenerate_id(true); flash_msg(L('Çıkış yaptınız.', 'You have logged out.')); go(url('/')); }
if (customer() && $act !== 'sifremi-unuttum') go($safeR);
$errors = []; $d = $_POST;
if (is_post()) {
    if (!csrf_ok()) $errors['_'] = L('Oturum süresi doldu, tekrar deneyin.', 'Session expired.');
    elseif ($act === 'giris') {
        $st = db()->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer'"); $st->execute([trim($d['email'] ?? '')]); $u = $st->fetch();
        if ($u && !$u['is_active']) $errors['_'] = L('Hesabınız pasif durumda. Bizimle iletişime geçin.', 'Your account is inactive.');
        elseif ($u && password_verify($d['password'] ?? '', $u['password_hash'])) { customer_login($u); flash_msg(L('Hoş geldiniz, ', 'Welcome, ') . $u['first_name'] . '!'); go($safeR); }
        else $errors['_'] = L('E-posta veya şifre hatalı.', 'Incorrect email or password.');
    } elseif ($act === 'uye-ol') {
        foreach (['first_name' => L('Ad', 'First name'), 'last_name' => L('Soyad', 'Last name'), 'email' => 'E-posta', 'phone' => L('Telefon', 'Phone')] as $k => $l) if (trim($d[$k] ?? '') === '') $errors[$k] = $l . ' ' . L('zorunludur', 'is required');
        if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = L('Geçerli bir e-posta girin', 'Invalid email');
        if (strlen($d['password'] ?? '') < 6) $errors['password'] = L('Şifre en az 6 karakter olmalı', 'Min 6 characters');
        elseif (($d['password'] ?? '') !== ($d['password2'] ?? '')) $errors['password2'] = L('Şifreler eşleşmiyor', 'Passwords do not match');
        if (empty($d['kvkk'])) $errors['kvkk'] = L('KVKK metnini onaylamanız gerekiyor', 'Please accept the privacy notice');
        if (!$errors) { $st = db()->prepare('SELECT id FROM users WHERE email = ?'); $st->execute([trim($d['email'])]); if ($st->fetchColumn()) $errors['email'] = L('Bu e-posta ile kayıtlı bir hesap zaten var', 'Email already registered'); }
        if (!$errors) {
            db()->prepare("INSERT INTO users (role, first_name, last_name, email, phone, password_hash, company_name, email_opt_in, sms_opt_in) VALUES ('customer',?,?,?,?,?,?,?,?)")
              ->execute([trim($d['first_name']), trim($d['last_name']), trim($d['email']), trim($d['phone']), password_hash($d['password'], PASSWORD_DEFAULT), trim($d['company_name'] ?? '') ?: null, !empty($d['opt_in']) ? 1 : 0, !empty($d['opt_in']) ? 1 : 0]);
            $st = db()->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([db()->lastInsertId()]); customer_login($st->fetch());
            flash_msg(L('Üyeliğiniz oluşturuldu. Hoş geldiniz!', 'Your account has been created. Welcome!'));
            go($safeR);
        }
    } elseif ($act === 'sifremi-unuttum') {
        flash_msg(L('Kayıtlı bir hesap varsa şifre sıfırlama bağlantısı e-posta adresinize gönderilecektir. Acil durumlarda bizi arayabilirsiniz.', 'If an account exists, a reset link will be sent.'));
        go(url('/giris'));
    }
}
$titles = ['giris' => L('Giriş Yap', 'Log In'), 'uye-ol' => L('Üye Ol', 'Sign Up'), 'sifremi-unuttum' => L('Şifremi Unuttum', 'Forgot Password')];
view('layout/top', ['title' => $titles[$act]]);
?>
<?= breadcrumb([[$titles[$act], null]]) ?>
<section class="container-x pb-8">
  <div class="mx-auto grid max-w-5xl overflow-hidden rounded-2xl border border-line md:grid-cols-2">
    <div class="p-6 md:p-10">
      <div class="mb-6 flex rounded-lg bg-soft p-1 text-sm font-bold">
        <a href="<?= url('/giris') ?><?= $r ? '?r=' . rawurlencode($r) : '' ?>" class="flex-1 rounded-md py-2 text-center <?= $act === 'giris' ? 'bg-white shadow-sm' : 'text-muted' ?>"><?= L('Giriş Yap', 'Log In') ?></a>
        <a href="<?= url('/uye-ol') ?><?= $r ? '?r=' . rawurlencode($r) : '' ?>" class="flex-1 rounded-md py-2 text-center <?= $act === 'uye-ol' ? 'bg-white shadow-sm' : 'text-muted' ?>"><?= L('Üye Ol', 'Sign Up') ?></a>
      </div>
      <?php if (!empty($errors['_'])): ?><div class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700"><?= e($errors['_']) ?></div><?php endif; ?>
      <form method="post" class="space-y-4">
        <?= csrf_input() ?><input type="hidden" name="r" value="<?= e($r) ?>">
        <?php if ($act === 'giris'): ?>
          <?php view('partials/field', ['name' => 'email', 'label' => L('E-posta', 'Email'), 'type' => 'email', 'value' => $d['email'] ?? '', 'attrs' => 'required autofocus autocomplete="email"']); ?>
          <?php view('partials/field', ['name' => 'password', 'label' => L('Şifre', 'Password'), 'type' => 'password', 'attrs' => 'required autocomplete="current-password"']); ?>
          <div class="text-right"><a href="<?= url('/sifremi-unuttum') ?>" class="text-xs font-semibold text-muted hover:text-brand"><?= L('Şifremi unuttum', 'Forgot password?') ?></a></div>
          <button class="btn-primary h-12 w-full"><?= L('Giriş Yap', 'Log In') ?></button>
        <?php elseif ($act === 'uye-ol'): ?>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <?php view('partials/field', ['name' => 'first_name', 'label' => L('Ad', 'First Name') . ' *', 'value' => $d['first_name'] ?? '', 'error' => $errors['first_name'] ?? null, 'attrs' => 'autocomplete="given-name"']); ?>
            <?php view('partials/field', ['name' => 'last_name', 'label' => L('Soyad', 'Last Name') . ' *', 'value' => $d['last_name'] ?? '', 'error' => $errors['last_name'] ?? null, 'attrs' => 'autocomplete="family-name"']); ?>
          </div>
          <?php view('partials/field', ['name' => 'email', 'label' => L('E-posta', 'Email') . ' *', 'type' => 'email', 'value' => $d['email'] ?? '', 'error' => $errors['email'] ?? null]); ?>
          <?php view('partials/field', ['name' => 'phone', 'label' => L('Cep Telefonu', 'Mobile') . ' *', 'type' => 'tel', 'value' => $d['phone'] ?? '', 'error' => $errors['phone'] ?? null]); ?>
          <?php view('partials/field', ['name' => 'company_name', 'label' => L('Firma Adı (bayi / kurumsal ise)', 'Company (optional)'), 'value' => $d['company_name'] ?? '']); ?>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <?php view('partials/field', ['name' => 'password', 'label' => L('Şifre', 'Password') . ' *', 'type' => 'password', 'error' => $errors['password'] ?? null, 'attrs' => 'autocomplete="new-password"']); ?>
            <?php view('partials/field', ['name' => 'password2', 'label' => L('Şifre Tekrar', 'Repeat') . ' *', 'type' => 'password', 'error' => $errors['password2'] ?? null, 'attrs' => 'autocomplete="new-password"']); ?>
          </div>
          <label class="flex items-start gap-2 text-xs"><input type="checkbox" name="kvkk" value="1" <?= !empty($d['kvkk']) ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 accent-[#e30613]"><span><a href="<?= url('/sozlesmeler/kvkk') ?>" target="_blank" class="font-semibold text-brand underline"><?= L('KVKK Aydınlatma Metni', 'Privacy Notice') ?></a><?= L('\'ni okudum, kabul ediyorum.', ' — I accept.') ?></span></label>
          <?php if (!empty($errors['kvkk'])): ?><span class="block text-xs font-semibold text-brand"><?= e($errors['kvkk']) ?></span><?php endif; ?>
          <label class="flex items-start gap-2 text-xs"><input type="checkbox" name="opt_in" value="1" <?= !empty($d['opt_in']) ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 accent-[#e30613]"><span><?= L('Kampanya ve fırsatlardan e-posta/SMS ile haberdar olmak istiyorum.', 'I want to receive campaign emails/SMS.') ?></span></label>
          <button class="btn-primary h-12 w-full"><?= L('Üye Ol', 'Create Account') ?></button>
        <?php else: ?>
          <p class="text-sm text-muted"><?= L('Hesabınıza kayıtlı e-posta adresinizi girin.', 'Enter the email of your account.') ?></p>
          <?php view('partials/field', ['name' => 'email', 'label' => L('E-posta', 'Email'), 'type' => 'email', 'attrs' => 'required']); ?>
          <button class="btn-primary h-12 w-full"><?= L('Sıfırlama Bağlantısı Gönder', 'Send Reset Link') ?></button>
        <?php endif; ?>
      </form>
    </div>
    <div class="hidden flex-col justify-center bg-ink p-10 text-white md:flex">
      <h2 class="text-2xl font-extrabold"><?= L('Üyelerimize özel avantajlar', 'Member benefits') ?></h2>
      <ul class="mt-6 space-y-4 text-sm text-white/85">
        <?php foreach ([['clipboard-list', L('Siparişlerinizi kolayca takip edin', 'Track your orders easily')], ['map-pin', L('Adreslerinizi kaydedin, hızlı ödeme yapın', 'Save addresses for faster checkout')], ['heart', L('Favori parçalarınızı listeleyin', 'Keep a list of favorite parts')], ['percent', L('Bayi ve kampanya fiyatlarından yararlanın', 'Access dealer and campaign prices')]] as [$ic, $tx]): ?>
          <li class="flex items-center gap-3"><span class="rounded-full bg-white/10 p-2"><?= icon($ic, 'h-5 w-5 text-accent') ?></span><?= e($tx) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
<?php view('layout/bottom');
