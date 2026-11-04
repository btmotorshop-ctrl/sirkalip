<?php
/* SIR KALIP Yönetim Paneli – router */
define('IS_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/menu.php';
require __DIR__ . '/lib/resources.php';
require __DIR__ . '/lib/crud.php';
require __DIR__ . '/lib/pages.php';
require __DIR__ . '/lib/translate.php';
require __DIR__ . '/lib/acc_resources.php';
require __DIR__ . '/lib/accounting.php';
require __DIR__ . '/lib/product_merge.php';
require __DIR__ . '/lib/visitors.php';

$GLOBALS['LOCALE'] = 'tr';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$prefix = base_path() . '/admin';
if (strpos($path, $prefix) === 0) $path = substr($path, strlen($prefix));
$path = trim(rawurldecode($path), '/');
if ($path === 'index.php') $path = '';

function admin_view(string $__view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . "/views/{$__view}.php";
}

if ($path === 'login') { page_login(); exit; }
if ($path === 'logout') { session_destroy(); redirect(aurl('login')); }

require_admin();
check_csrf();
$GLOBALS['ADMIN_ROUTE'] = $path;

$seg = explode('/', $path);
switch (true) {
    case $path === '' || $path === 'dashboard': page_dashboard(); break;
    case $seg[0] === 'products' && !isset($seg[1]): page_products(); break;
    case $seg[0] === 'products' && $seg[1] === 'new': page_product_form(null); break;
    case $seg[0] === 'products' && $seg[1] === 'merge': page_products_merge(); break;
    case $seg[0] === 'products' && $seg[1] === 'bulk': page_products_bulk(); break;
    case $seg[0] === 'products' && $seg[1] === 'sort': page_products_sort(); break;
    case $seg[0] === 'products' && ($seg[2] ?? '') === 'delete': page_product_delete((int)$seg[1]); break;
    case $seg[0] === 'products' && ($seg[2] ?? '') === 'restore': page_product_restore((int)$seg[1]); break;
    case $seg[0] === 'products' && ctype_digit($seg[1]): page_product_form((int)$seg[1]); break;
    case $seg[0] === 'orders' && !isset($seg[1]): page_orders(); break;
    case $seg[0] === 'orders' && ctype_digit($seg[1]): page_order_view((int)$seg[1]); break;
    case $seg[0] === 'stock': page_stock(); break;
    case $seg[0] === 'acc': page_acc($seg); break;
    case $seg[0] === 'translate' && ($seg[1] ?? '') === 'shipped': page_translate_shipped(); break;
    case $seg[0] === 'translate' && ($seg[1] ?? '') === 'run': page_translate_run(); break;
    case $seg[0] === 'translate': page_translate(); break;
    case $seg[0] === 'settings': page_settings($seg[1] ?? 'general'); break;
    case $seg[0] === 'seo': page_seo_bulk($seg[1] ?? 'products'); break;
    case $seg[0] === 'reports': page_reports($seg[1] ?? 'sales'); break;
    case $seg[0] === 'visitors': page_visitors(); break;
    case $seg[0] === 'r' && isset($seg[1]):
        $res = resources();
        if (!isset($res[$seg[1]])) { page_soon($path); break; }
        $r = $res[$seg[1]];
        if (!isset($seg[2])) crud_list($seg[1], $r);
        elseif ($seg[2] === 'new') crud_form($seg[1], $r, null);
        elseif (($seg[3] ?? '') === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') crud_delete($seg[1], $r, $seg[2]);
        else crud_form($seg[1], $r, $seg[2]);
        break;
    default: page_soon($path);
}
