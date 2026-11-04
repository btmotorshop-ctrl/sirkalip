<?php
header('Content-Type: application/xml; charset=utf-8');
$host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$urls = [url('/'), url('/kategoriler'), url('/markalar'), url('/teknik-bilgiler'), url('/iletisim'), url('/sss')];
foreach (db()->query('SELECT slug FROM categories WHERE is_active = 1') as $r) $urls[] = url('/kategori/' . $r['slug']);
foreach (db()->query('SELECT slug FROM brands WHERE is_active = 1') as $r) $urls[] = url('/marka/' . $r['slug']);
foreach (db()->query('SELECT slug FROM products WHERE is_active = 1 AND deleted_at IS NULL') as $r) $urls[] = url('/urun/' . $r['slug']);
foreach (db()->query("SELECT slug, kind FROM pages WHERE is_active = 1") as $r) $urls[] = url(($r['kind'] === 'contract' ? '/sozlesmeler/' : ($r['kind'] === 'article' ? '/teknik-bilgiler/' : '/')) . $r['slug']);
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach ($urls as $u) echo '<url><loc>' . e($host . $u) . '</loc></url>';
echo '</urlset>';
