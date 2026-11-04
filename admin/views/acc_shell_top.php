<?php
/** Ön Muhasebe tam ekran kabuğu - üst kısım */
$u = admin_user();
$route = $GLOBALS['ADMIN_ROUTE'] ?? '';
if (preg_match('#^acc/party-edit/(customer|supplier)#', $route, $__m)) $route = 'acc/parties/' . $__m[1];
if (strpos($route, 'acc/doc/proposal') === 0 || $route === 'acc/proposals') $route = 'acc/docs/proposal';
if (strpos($route, 'acc/account') === 0) $route = 'acc/accounts';
if (strpos($route, 'acc/template') === 0) $route = 'acc/templates';
if (preg_match('#^acc/(payment|payments)(/(\d+))?#', $route, $__m)) {
    $__pid = (int)($__m[3] ?? ($_GET['party_id'] ?? 0));
    $__k = $__pid ? acc_q('SELECT kind FROM acc_parties WHERE id = ?', [$__pid])->fetchColumn() : 'customer';
    $route = 'acc/parties/' . ($__k === 'supplier' ? 'supplier' : 'customer');
}
$full = $route . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$menu = [
  ['Ana Sayfa', 'home', 'acc', []],
  ['Müşteriler', 'users', 'acc/parties/customer', []],
  ['Tedarikçiler', 'truck', 'acc/parties/supplier', []],
  ['Satışlar', 'shopping-cart', '', [['Satış Listesi', 'acc/docs/sale'], ['Yeni Satış Gir', 'acc/doc/sale/new']]],
  ['Alışlar', 'package-plus', '', [['Alış Listesi', 'acc/docs/purchase'], ['Yeni Alış Gir', 'acc/doc/purchase/new']]],
  ['Teklifler', 'file-text', 'acc/docs/proposal', []],
  ['Nakit Yönetimi', 'banknote', '', [['Hesaplarım', 'acc/accounts'], ['Tahsilatlar', 'r/acc_collections'], ['Ödemeler', 'r/acc_payouts'], ['Masraflar', 'r/acc_expenses']]],
  ['Ürünler / Stok', 'package', '', [['Ürün Listesi', 'products'], ['Depo Durumu (Stok)', 'acc/stock'], ['Stok Yönetimi', 'stock']]],
  ['Raporlar', 'chart-bar', '', [['Cari Bakiye Raporu', 'acc/balances'], ['Açık Hesap Raporu', 'acc/open-accounts'], ['Stok Raporu', 'acc/stock'], ['Satış Raporu', 'reports/sales']]],
  ['Ayarlar', 'settings', '', [['Teklif ve Özel Şablonlar', 'acc/templates'], ['Hesap Tanımları', 'r/acc_accounts'], ['Banka Hesapları', 'r/bank_accounts'], ['Döviz Kurları', 'r/currency_rates'], ['Genel Ayarlar', 'settings/general'], ['E-posta Hesapları', 'settings/email'], ['Kullanıcılar', 'r/admins']]],
];
$isOn = function ($r) use ($route) { return $r !== '' && ($route === $r || strpos($route, $r . '/') === 0); };
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Ön Muhasebe') ?> | Ön Muhasebe</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/acc.css') ?>">
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
</head>
<body class="admin acc-body">
<aside class="acc-side">
  <a class="acc-brand" href="<?= aurl('acc') ?>"><span><b>sır</b>kalıp<small>ÖN MUHASEBE</small></span></a>
  <ul class="acc-nav">
    <?php foreach ($menu as [$label, $ic, $r, $kids]):
      $on = $r === 'acc' ? ($route === 'acc' || $route === 'acc/home') : $isOn($r);
      foreach ($kids as [, $kr]) if ($isOn($kr)) $on = true; ?>
      <li class="<?= $on ? 'on' : '' ?> <?= $kids && $on ? 'open' : '' ?>">
        <?php if ($kids): ?>
          <button type="button" onclick="this.parentNode.classList.toggle('open')"><?= icon($ic, '') ?><?= e($label) ?><span class="plus">+</span></button>
          <ul class="acc-sub"><?php foreach ($kids as [$kl, $kr]): ?><li><a href="<?= aurl($kr) ?>" class="<?= $isOn($kr) ? 'on' : '' ?>"><?= e($kl) ?></a></li><?php endforeach; ?></ul>
        <?php else: ?>
          <a href="<?= aurl($r) ?>"><?= icon($ic, '') ?><?= e($label) ?></a>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <div class="acc-sep"></div>
  <ul class="acc-nav">
    <li><a href="<?= aurl() ?>"><?= icon('layout-dashboard', '') ?>Yönetim Paneline Dön</a></li>
    <li><a href="<?= e(url('/')) ?>" target="_blank"><?= icon('store', '') ?>Siteyi Görüntüle</a></li>
    <li><a href="<?= aurl('logout') ?>"><?= icon('lock', '') ?>Oturumu Kapat</a></li>
  </ul>
</aside>
<header class="acc-top">
  <button class="burger" type="button" onclick="document.body.classList.toggle(innerWidth>900?'acc-collapsed':'acc-open')" aria-label="Menü"><?= icon('menu', '') ?></button>
  <span class="firm">SIR KALIP – Ön Muhasebe</span>
  <span class="sp"></span>
  <a class="acc-tbtn g" href="<?= aurl('acc/doc/sale/new') ?>">+ Satış Gir</a>
  <a class="acc-tbtn" href="<?= aurl() ?>"><?= icon('layout-dashboard', 'h-4 w-4') ?> Yönetim Paneli</a>
  <span class="acc-user"><i><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></i><?= e($u['name']) ?></span>
</header>
<main class="acc-main">
  <?php if ($f = flash()): ?>
    <div class="mb-4 rounded-md border px-4 py-3 text-sm font-semibold <?= $f[1] === 'err' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700' ?>"><?= e($f[0]) ?></div>
  <?php endif; ?>
