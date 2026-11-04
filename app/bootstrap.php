<?php
declare(strict_types=1);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Istanbul');

define('ROOT', dirname(__DIR__));
$config = require ROOT . '/config.php';
if (is_file(ROOT . '/config.local.php')) { $config = array_replace_recursive($config, require ROOT . '/config.local.php'); } // yalnızca yerel bilgisayarda bulunur
if (!empty($config['debug'])) { ini_set('display_errors', '1'); error_reporting(E_ALL); }

// SSL: http:// ile gelen istekleri https:// adresine yönlendir (.htaccess çalışmazsa yedek)
$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (PHP_SAPI !== 'cli' && !$__https && !empty($_SERVER['HTTP_HOST']) && !in_array(explode(':', $_SERVER['HTTP_HOST'])[0], ['localhost', '127.0.0.1'], true)) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}
if ($__https) ini_set('session.cookie_secure', '1');

if (session_status() === PHP_SESSION_NONE) session_start();

require ROOT . '/app/db.php';
require ROOT . '/app/helpers.php';
require ROOT . '/app/queries.php';
require ROOT . '/app/migrate.php';
require ROOT . '/app/cart.php';
require ROOT . '/app/customer.php';
require ROOT . '/app/payment.php';
require ROOT . '/app/aras.php';
try { run_migrations(); } catch (Throwable $e) { error_log('migrate: ' . $e->getMessage()); }
