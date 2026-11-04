<?php
/* SIR KALIP – ön denetleyici (router) */
require __DIR__ . '/app/bootstrap.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$base = base_path();
if ($base && strpos($path, $base) === 0) $path = substr($path, strlen($base));
$path = '/' . trim(rawurldecode($path), '/');

// Dil: /en/, /de/, /fr/, /ru/, /ar/ ... → ilgili dil, diğerleri Türkçe
$GLOBALS['LOCALE'] = $config['default_locale'];
if (preg_match('#^/(en|de|fr|ru|ar)(/|$)#', $path, $m)) {
    $GLOBALS['LOCALE'] = $m[1];
    $path = '/' . trim(substr($path, 3), '/');
}

$GLOBALS['CURRENT_PATH'] = $path;

// Rotalar
$routes = [
    '#^/$#'                                => 'home',
    '#^/kategoriler$#'                     => 'categories',
    '#^/kategori/([a-z0-9-]+)$#'           => 'listing',
    '#^/(urunler|yeni-urunler|kampanyalar|arama|parca-bul)$#' => 'listing',
    '#^/markalar$#'                        => 'brands',
    '#^/marka/([a-z0-9-]+)$#'              => 'listing',
    '#^/urun/([a-z0-9-]+)$#'               => 'product',
    '#^/sepet$#'                           => 'cart',
    '#^/sepet/(ekle|guncelle|sil|kupon|kupon-kaldir|ozet)$#' => 'cart_action',
    '#^/odeme$#'                           => 'checkout',
    '#^/odeme/(kart|iyzico-donus|paytr-bildirim)(?:/([A-Z0-9]+))?$#' => 'pay',
    '#^/siparis-tamamlandi/([A-Z0-9]+)$#'  => 'order_done',
    '#^/(giris|uye-ol|cikis|sifremi-unuttum)$#' => 'auth',
    '#^/hesabim(?:/([a-z-]+))?(?:/([A-Z0-9]+))?$#' => 'account',
    '#^/favoriler$#'                       => 'favorites',
    '#^/favori$#'                          => 'fav_toggle',
    '#^/iletisim$#'                        => 'contact',
    '#^/sss$#'                             => 'faq',
    '#^/(teknik-bilgiler|blog)(?:/([a-z0-9-]+))?$#' => 'articles',
    '#^/sozlesmeler/([a-z0-9-]+)$#'        => 'page',
    '#^/(hakkimizda|kargo|iade)$#'         => 'page',
    '#^/siparis-takip$#'                   => 'order_track',
    '#^/urun-islem$#'                      => 'product_action',
    '#^/sitemap\.xml$#'                    => 'sitemap',
    '#^/robots\.txt$#'                     => 'robots',
];

foreach ($routes as $re => $page) {
    if (preg_match($re, $path, $params)) {
        require ROOT . "/pages/{$page}.php";
        exit;
    }
}

// 301 yönlendirmeleri ve 404 kaydı
try {
    $st = db()->prepare('SELECT to_path FROM redirects WHERE from_path = ?'); $st->execute([$path]);
    if ($to = $st->fetchColumn()) { db()->prepare('UPDATE redirects SET hits = hits + 1 WHERE from_path = ?')->execute([$path]); header('Location: ' . (preg_match('#^https?://#', $to) ? $to : base_path() . $to), true, 301); exit; }
    $st = db()->prepare('UPDATE not_found_logs SET hits = hits + 1, last_seen_at = NOW() WHERE path = ?'); $st->execute([$path]);
    if (!$st->rowCount()) db()->prepare('INSERT INTO not_found_logs (path) VALUES (?)')->execute([mb_substr($path, 0, 250)]);
} catch (Throwable $e) {}

http_response_code(404);
view('layout/top', ['title' => '404']);
view('404');
view('layout/bottom');
