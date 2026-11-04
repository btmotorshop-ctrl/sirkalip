<?php
if (acc_shell()) { admin_view('acc_shell_top', get_defined_vars()); return; }
$u = admin_user();
$route = $GLOBALS['ADMIN_ROUTE'] ?? '';
$full = $route . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$menu = admin_menu();
// Hangi alt bağlantı etkin? Önce tam eşleşme (sorgu dahil), yoksa yalnız rota eşleşmesi
$activeLink = null;
foreach ($menu as $gi => $g) foreach ($g[3] as $ci => $c) if ($c[1] === $full) { $activeLink = [$gi, $ci]; break 2; }
if (!$activeLink) foreach ($menu as $gi => $g) foreach ($g[3] as $ci => $c) if (strpos($c[1], '?') === false && $c[1] === $route) { $activeLink = [$gi, $ci]; break 2; }
$initial = mb_strtoupper(mb_substr($u['name'], 0, 1));
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Yönetim Paneli') ?> | SIR KALIP Kontrol Paneli</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/admin-ui.css') ?>">
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
</head>
<body class="admin admin-ui">
<div class="ui-overlay" data-ui-close></div>
<aside class="ui-side" id="uiSide">
  <a href="<?= aurl() ?>" class="ui-brand">
    <img src="<?= asset('img/logo-sm.png') ?>" alt="SIR KALIP">
    <span>KONTROL PANELİ</span>
  </a>
  <div class="ui-search">
    <?= icon('search', 'h-4 w-4') ?>
    <input id="uiSearch" type="search" placeholder="Menüde ara…" autocomplete="off" aria-label="Menüde ara">
    <kbd>Ctrl K</kbd>
  </div>
  <nav class="ui-nav" id="uiNav">
    <?php foreach ($menu as $gi => [$label, $ic, $r, $children]):
      $groupActive = ($activeLink && $activeLink[0] === $gi) || (!$children && ($r === $route || ($r === 'dashboard' && $route === '')));
      if (!$children): ?>
        <a href="<?= aurl($r) ?>" class="ui-item ui-solo <?= $groupActive ? 'is-on' : '' ?>" data-k="<?= e(mb_strtolower($label)) ?>"><?= icon($ic, 'h-4 w-4') ?><span><?= e($label) ?></span></a>
      <?php else: ?>
        <div class="ui-group <?= $groupActive ? 'is-open' : '' ?>">
          <button type="button" class="ui-head <?= $groupActive ? 'is-on' : '' ?>" data-toggle>
            <?= icon($ic, 'h-4 w-4') ?><span><?= e($label) ?></span><?= icon('chevron-down', 'ui-chev h-4 w-4') ?>
          </button>
          <div class="ui-sub">
            <?php foreach ($children as $ci => $c): $on = $activeLink && $activeLink === [$gi, $ci]; ?>
              <a href="<?= aurl($c[1]) ?>" class="ui-link <?= $on ? 'is-on' : '' ?>" data-k="<?= e(mb_strtolower($c[0] . ' ' . ($c[2] ?? '') . ' ' . $label)) ?>"><?= e($c[0]) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <div class="ui-empty" id="uiEmpty">Sonuç bulunamadı</div>
  </nav>
  <div class="ui-foot">
    <a href="<?= e(url('/')) ?>" target="_blank"><?= icon('store', 'h-4 w-4') ?> Siteyi Görüntüle</a>
    <a href="<?= aurl('logout') ?>"><?= icon('log-out', 'h-4 w-4') ?> Çıkış Yap</a>
  </div>
</aside>

<div class="ui-wrap">
  <header class="ui-top">
    <button type="button" class="ui-burger" data-ui-open aria-label="Menüyü aç"><?= icon('menu', 'h-5 w-5') ?></button>
    <div class="ui-top-title"><?= e($title ?? '') ?></div>
    <div class="ui-top-right">
      <a href="<?= aurl('products/new') ?>" class="ui-btn-primary"><?= icon('plus', 'h-4 w-4') ?><span>Yeni Ürün</span></a>
      <a href="<?= aurl('orders') ?>" class="ui-iconbtn" title="Siparişler"><?= icon('bell', 'h-5 w-5') ?></a>
      <div class="ui-user" id="uiUser">
        <button type="button" class="ui-avatar" aria-label="Hesap"><?= e($initial) ?></button>
        <div class="ui-user-menu">
          <div class="ui-user-info"><b><?= e($u['name']) ?></b><span><?= e($u['email']) ?></span></div>
          <a href="<?= aurl('r/admins') ?>">Kullanıcılar</a>
          <a href="<?= aurl('settings/general') ?>">Ayarlar</a>
          <a href="<?= aurl('logout') ?>" class="is-danger">Oturumu Kapat</a>
        </div>
      </div>
    </div>
  </header>
  <main class="ui-main">
  <?php if ($f = flash()): ?>
    <div class="mb-4 rounded-md border px-4 py-3 text-sm font-semibold <?= $f[1] === 'err' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700' ?>"><?= e($f[0]) ?></div>
  <?php endif; ?>
