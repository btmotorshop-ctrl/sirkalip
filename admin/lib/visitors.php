<?php
/** Detaylı Ziyaretçi ve Ürün Görüntüleme Raporları */

function page_visitors(): void
{
    $pdo = db();
    $from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
    $to   = $_GET['to']   ?? date('Y-m-d');
    $type = $_GET['type'] ?? '';  // product, page, ''
    $ip   = $_GET['ip']   ?? '';
    $country = $_GET['country'] ?? '';

    $where = ['v.created_at BETWEEN ? AND ?'];
    $bind  = [$from . ' 00:00:00', $to . ' 23:59:59'];

    if ($type) { $where[] = 'v.page_type = ?'; $bind[] = $type; }
    if ($ip)   { $where[] = 'v.ip = ?'; $bind[] = $ip; }
    if ($country) { $where[] = 'v.country = ?'; $bind[] = $country; }

    $wSQL = implode(' AND ', $where);

    // Özet kartlar
    $summary = $pdo->prepare("SELECT
        COUNT(*) total_views,
        COUNT(DISTINCT ip) unique_ips,
        COUNT(DISTINCT session_id) unique_sessions,
        SUM(page_type = 'product') product_views,
        SUM(device_type = 'mobile') mobile,
        SUM(device_type = 'desktop') desktop,
        SUM(device_type = 'tablet') tablet
      FROM visitor_logs v WHERE $wSQL");
    $summary->execute($bind);
    $stats = $summary->fetch();

    // Ülke dağılımı
    $countries = $pdo->prepare("SELECT COALESCE(country, 'Bilinmiyor') c, COUNT(*) cnt, COUNT(DISTINCT ip) ips FROM visitor_logs v WHERE $wSQL GROUP BY c ORDER BY cnt DESC LIMIT 20");
    $countries->execute($bind);
    $countryList = $countries->fetchAll();

    // Tarayıcı dağılımı
    $browsers = $pdo->prepare("SELECT browser, COUNT(*) cnt FROM visitor_logs v WHERE $wSQL GROUP BY browser ORDER BY cnt DESC");
    $browsers->execute($bind);
    $browserList = $browsers->fetchAll();

    // İşletim sistemi dağılımı
    $oses = $pdo->prepare("SELECT os, COUNT(*) cnt FROM visitor_logs v WHERE $wSQL GROUP BY os ORDER BY cnt DESC");
    $oses->execute($bind);
    $osList = $oses->fetchAll();

    // En çok görüntülenen ürünler
    $topProducts = $pdo->prepare("SELECT v.product_id, p.name_tr, p.code, COUNT(*) cnt, COUNT(DISTINCT v.ip) ips
      FROM visitor_logs v LEFT JOIN products p ON p.id = v.product_id
      WHERE $wSQL AND v.page_type = 'product' AND v.product_id IS NOT NULL
      GROUP BY v.product_id ORDER BY cnt DESC LIMIT 20");
    $topProducts->execute($bind);
    $topProductList = $topProducts->fetchAll();

    // En çok ziyaret eden IP'ler
    $topIps = $pdo->prepare("SELECT ip, country, city, COUNT(*) cnt, MAX(created_at) last_visit FROM visitor_logs v WHERE $wSQL GROUP BY ip ORDER BY cnt DESC LIMIT 30");
    $topIps->execute($bind);
    $topIpList = $topIps->fetchAll();

    // Günlük trend
    $daily = $pdo->prepare("SELECT DATE(created_at) d, COUNT(*) cnt, COUNT(DISTINCT ip) ips FROM visitor_logs v WHERE $wSQL GROUP BY d ORDER BY d");
    $daily->execute($bind);
    $dailyList = $daily->fetchAll();

    // Son ziyaretler (son 100)
    $recent = $pdo->prepare("SELECT v.*, p.name_tr product_name, p.code product_code
      FROM visitor_logs v LEFT JOIN products p ON p.id = v.product_id
      WHERE $wSQL ORDER BY v.created_at DESC LIMIT 100");
    $recent->execute($bind);
    $recentList = $recent->fetchAll();

    // Ülke listesi (filtre için)
    $allCountries = $pdo->query("SELECT DISTINCT COALESCE(country, 'Bilinmiyor') c FROM visitor_logs ORDER BY c")->fetchAll(PDO::FETCH_COLUMN);

    admin_view('visitors', compact('stats', 'countryList', 'browserList', 'osList', 'topProductList', 'topIpList', 'dailyList', 'recentList', 'allCountries', 'from', 'to', 'type', 'ip', 'country'));
}
