<?php
header('Content-Type: text/plain; charset=utf-8');
$host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
echo "User-agent: *\nDisallow: " . base_path() . "/admin/\nDisallow: " . base_path() . "/sepet\nDisallow: " . base_path() . "/odeme\nDisallow: " . base_path() . "/hesabim\nSitemap: " . $host . base_path() . "/sitemap.xml\n";
