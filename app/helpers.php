<?php
/* ── Dil / URL yardımcıları ── */
function base_path(): string
{
    global $config;
    if (!empty($config['base_url'])) return rtrim($config['base_url'], '/');
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (defined('IS_ADMIN')) $dir = preg_replace('#/admin$#', '', $dir);
    return $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');
}

function locale(): string { return $GLOBALS['LOCALE'] ?? 'tr'; }

/** Sitede kullanılan diller: kod => görünen ad */
function locales(): array { return ['tr' => 'Türkçe', 'en' => 'English', 'el' => 'Ελληνικά', 'de' => 'Deutsch', 'fr' => 'Français', 'ru' => 'Русский', 'ar' => 'العربية']; }
/** Türkçe dışındaki ek diller (veritabanında *_en, *_de ... sütunları) */
function extra_locales(): array { return ['en', 'de', 'fr', 'ru', 'ar']; }
/** Geçiş alanları veritabanında hazır mı (migration 14)? */
function loc_ready(): bool { return !empty($GLOBALS['LOC_READY']); }
function is_rtl(): bool { return locale() === 'ar'; }
/** SQL için: loc_cols('c', 'name', 'category_name') → "c.name_en category_name_en, c.name_de category_name_de, ..." */
function loc_cols(string $alias, string $col, string $as = ''): string
{
    $o = [];
    foreach (extra_locales() as $l) if ($l === 'en' || !empty($GLOBALS['LOC_READY'])) $o[] = $alias . '.' . $col . '_' . $l . ' ' . ($as ?: $col) . '_' . $l;
    return implode(', ', $o);
}

/** Dile göre site içi URL: url('/kategori/x') → /sirkalip/en/kategori/x */
function url(string $path = '/', ?string $loc = null): string
{
    $loc = $loc ?? locale();
    $prefix = $loc === 'tr' ? '' : '/' . $loc;
    $path = '/' . ltrim($path, '/');
    return base_path() . $prefix . ($path === '/' && $prefix ? '' : $path);
}

function asset(string $p): string
{
    $file = ROOT . '/assets/' . ltrim($p, '/');
    $v = is_file($file) ? filemtime($file) : 0;
    return base_path() . '/assets/' . ltrim($p, '/') . '?v=' . $v;
}

/** Fiyatlar KDV hariç mi girildi? (Ayarlar › Genel) */
function prices_ex_vat(): bool
{
    static $v = null;
    if ($v === null) $v = !empty(setting('general', [])['pricesExcludeVat']);
    return $v;
}

/** Fiyatın yanına "+ KDV" etiketi */
function vat_tag(string $cls = 'text-[11px]'): string
{
    return prices_ex_vat() ? ' <span class="' . $cls . ' font-bold text-muted whitespace-nowrap">+ ' . L('KDV', 'VAT') . '</span>' : '';
}

/** Veritabanındaki görsel yolunu mevcut kurulum dizinine göre düzeltir (/sirkalip → alan adı kökü vb.) */
function media_url(?string $u): string
{
    $u = (string)$u;
    if ($u === '' || preg_match('#^(https?:)?//#i', $u) || strpos($u, 'data:') === 0) return $u;
    foreach (['/uploads/', '/assets/'] as $k) { $p = strpos($u, $k); if ($p !== false) return base_path() . substr($u, $p); }
    return $u;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/** Çeviri: t('hero.title1', ['count'=>3]) */
function t(string $key, array $vars = []): string
{
    static $cache = [];
    $loc = locale();
    if (!isset($cache[$loc])) {
        $cache[$loc] = require ROOT . "/lang/{$loc}.php";
        if (!in_array($loc, ['tr', 'en'], true)) $cache[$loc] = array_replace_recursive(require ROOT . '/lang/en.php', $cache[$loc]);
    }
    $v = $cache[$loc];
    foreach (explode('.', $key) as $k) { if (!is_array($v) || !isset($v[$k])) return $key; $v = $v[$k]; }
    foreach ($vars as $k => $val) $v = str_replace('{' . $k . '}', (string)$val, $v);
    return (string)$v;
}

/** Veritabanı satırından dile göre alan: tr('name', $row) → name_tr / name_en */
function tr_field(array $row, string $field): string
{
    $loc = locale();
    if ($loc !== 'tr') {
        $v = $row[$field . '_' . $loc] ?? '';
        if ($v !== '' && $v !== null) return (string)$v;
        $en = $row[$field . '_en'] ?? '';   // çeviri yoksa önce İngilizce, sonra Türkçe
        if ($en !== '' && $en !== null) return (string)$en;
    }
    return (string)($row[$field . '_tr'] ?? '');
}

function money($v): string
{
    return number_format((float)$v, 2, locale() === 'tr' ? ',' : '.', locale() === 'tr' ? '.' : ',') . ' TL';
}

/** İletişim formu spam/bot tespiti (basit puanlama; eşik 3). Şüpheliyi spam kutusuna düşürür. */
function contact_is_spam(string $name, string $email, string $message): bool
{
    $hay = function_exists('mb_strtolower') ? mb_strtolower($name . ' ' . $message, 'UTF-8') : strtolower($name . ' ' . $message);
    $score = 0;
    $links = preg_match_all('#https?://|www\.|\.com|\.ru\b|\.xyz\b|\.online\b|\.site\b|\.top\b|\.icu\b#i', $message);
    if ($links >= 1) $score += 2;
    if ($links >= 3) $score += 2;
    if (preg_match('#https?://|www\.#i', $name . ' ' . $email)) $score += 3;
    foreach (['seo', 'backlink', 'back link', 'link building', 'ranking', 'rank your', 'rank higher', 'search index', 'google search', 'search engine', 'first page of google', 'traffic to your', 'web design', 'web development', 'website design', 'digital marketing', 'marketing team', 'increase your', 'boost your', 'grow your', 'guest post', 'crypto', 'bitcoin', 'forex', 'casino', 'payday', 'investment opportunity', 'business proposal', 'reach out', 'outreach', 'i came across', 'promote your', 'advertising services'] as $w)
        if (strpos($hay, $w) !== false) $score += 2;
    $msgLen = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);
    if ($links >= 1 && !preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $message) && $msgLen > 120) $score += 1;
    return $score >= 3;
}

/** Basit HTTP GET (cURL varsa onu, yoksa file_get_contents kullanır). */
function fx_http_get(string $url): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => 'sirkalip']);
        $r = curl_exec($ch); curl_close($ch);
        if (is_string($r) && $r !== '') return $r;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'user_agent' => 'sirkalip'], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $r = @file_get_contents($url, false, $ctx);
    return is_string($r) ? $r : '';
}

/** TCMB efektif satış kurlarını günde bir kez çeker ve currency_rates tablosunu günceller. */
function fx_auto_refresh(): void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $last = db()->query("SELECT MAX(DATE(updated_at)) FROM currency_rates WHERE code IN ('EUR','USD')")->fetchColumn();
        if ($last === date('Y-m-d')) return;                  // bugün zaten güncellenmiş
        // Aynı gün tekrar tekrar denememek için önce zaman damgasını güncelle
        db()->exec("UPDATE currency_rates SET updated_at = NOW() WHERE code IN ('EUR','USD')");
        $xml = fx_http_get('https://www.tcmb.gov.tr/kurlar/today.xml');
        if ($xml === '') return;
        $doc = @simplexml_load_string($xml);
        if (!$doc) return;
        foreach ($doc->Currency as $c) {
            $code = (string)$c['CurrencyCode'];
            if ($code !== 'USD' && $code !== 'EUR') continue;
            $sell = (float)str_replace(',', '.', (string)$c->BanknoteSelling);   // efektif satış
            if ($sell <= 0) $sell = (float)str_replace(',', '.', (string)$c->ForexSelling);
            if ($sell > 0) db()->prepare('UPDATE currency_rates SET rate = ? WHERE code = ?')->execute([$sell, $code]);
        }
    } catch (Throwable $e) { /* kur güncellenemezse mevcut değerler kullanılır */ }
}

/** EUR/USD kurları (1 birim = ? TL), currency_rates tablosundan. */
function fx_rates(): array
{
    static $r = null;
    if ($r !== null) return $r;
    fx_auto_refresh();
    $r = ['EUR' => 0.0, 'USD' => 0.0];
    try {
        foreach (db()->query("SELECT code, rate FROM currency_rates WHERE code IN ('EUR','USD')") as $row)
            $r[$row['code']] = (float)$row['rate'];
    } catch (Throwable $e) {}
    return $r;
}

/** TL fiyatın €/$ karşılığı (ikincil, küçük gösterim). Kur yoksa boş döner. */
function price_fx($tl, string $cls = 'mt-0.5 text-xs font-semibold text-muted'): string
{
    $tl = (float)$tl; if ($tl <= 0) return '';
    $fx = fx_rates(); $out = [];
    if (!empty($fx['EUR'])) $out[] = '€' . number_format($tl / $fx['EUR'], 2, ',', '.');
    if (!empty($fx['USD'])) $out[] = '$' . number_format($tl / $fx['USD'], 2, ',', '.');
    return $out ? '<div class="' . $cls . '">' . implode(' · ', $out) . '</div>' : '';
}

/** Lucide ikonları (inline SVG) */
function icon(string $name, string $class = 'h-5 w-5', float $stroke = 2): string
{
    static $icons = null;
    if ($icons === null) $icons = require ROOT . '/app/icons.php';
    $body = $icons[$name] ?? $icons['package'];
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $stroke . '" stroke-linecap="round" stroke-linejoin="round" class="' . e($class) . '" aria-hidden="true">' . $body . '</svg>';
}

function social(string $name, string $class = 'h-4 w-4'): string
{
    $p = [
        'facebook'  => 'M14 8h3V4h-3c-2.8 0-4 1.7-4 4.3V10H7v4h3v8h4v-8h3l1-4h-4V8.5c0-.3.2-.5.5-.5z',
        'instagram' => 'M12 7.3A4.7 4.7 0 1 0 12 16.7 4.7 4.7 0 0 0 12 7.3zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm5-8.9a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2zM7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9z',
        'youtube'   => 'M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15V9l5.2 3L10 15z',
        'tiktok'    => 'M12.53.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
        'whatsapp'  => 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.8-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2c0 1.3.9 2.5 1.1 2.7.1.2 1.8 2.8 4.4 3.9 1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.4-.3z',
    ];
    return '<svg viewBox="0 0 24 24" class="' . e($class) . '" fill="currentColor" aria-hidden="true"><path d="' . $p[$name] . '"/></svg>';
}

/** Ürün görseli yoksa kullanılan yer tutucu */
function part_art(?string $kind, string $extra = ''): string
{
    $map = [
        'pickup' => ['cog', 'text-accent', 'from-orange-50 to-orange-100'],
        'knotter' => ['anchor', 'text-ink-2', 'from-slate-50 to-slate-100'],
        'gearbox' => ['settings2', 'text-ink-2', 'from-slate-50 to-slate-100'],
        'needle' => ['minus', 'text-brand', 'from-red-50 to-red-100'],
        'chamber' => ['box', 'text-brand', 'from-red-50 to-red-100'],
        'knife' => ['scissors', 'text-ink-2', 'from-slate-50 to-slate-100'],
        'gear' => ['cog', 'text-ink-2', 'from-slate-50 to-slate-100'],
        'bearing' => ['circle-dot', 'text-slate-500', 'from-slate-50 to-slate-100'],
        'spring' => ['waves', 'text-ink', 'from-slate-50 to-slate-100'],
        'shaft' => ['link2', 'text-ink-2', 'from-slate-50 to-slate-100'],
        'bolt' => ['wrench', 'text-slate-600', 'from-slate-50 to-slate-100'],
        'other' => ['puzzle', 'text-ink-2', 'from-slate-50 to-slate-100'],
    ];
    [$ic, $color, $bg] = $map[$kind ?? 'other'] ?? $map['other'];
    return '<div class="flex aspect-square w-full items-center justify-center rounded-md bg-gradient-to-br ' . $bg . ' ' . $extra . '">' . icon($ic, 'h-1/2 w-1/2 ' . $color, 1.4) . '</div>';
}

function setting(string $key, $default = null)
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $st = db()->prepare('SELECT value FROM settings WHERE `key` = ?');
        $st->execute([$key]);
        $v = $st->fetchColumn();
        $cache[$key] = $v === false ? $default : json_decode($v, true);
    }
    return $cache[$key];
}

function view(string $__view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require ROOT . "/views/{$__view}.php";
}

function cart_count(): int { return array_sum($_SESSION['cart'] ?? []); }

/** Satır içi çeviri: L('Sepetim', 'My Cart') */
/** Diğer diller için çeviri sözlüğü: İngilizce metin → çeviri (lang/strings_{dil}.php) */
function lang_dict(string $loc): array
{
    static $dict = [];
    if (!isset($dict[$loc])) { $f = ROOT . "/lang/strings_{$loc}.php"; $dict[$loc] = is_file($f) ? (require $f) : []; }
    return $dict[$loc];
}

function L(string $tr, string $en): string
{
    $loc = locale();
    if ($loc === 'tr') return $tr;
    if ($loc === 'en') return $en;
    return lang_dict($loc)[$en] ?? $en;
}

/** Değişkenli çeviri: LV('{brand} için ...', 'For {brand} ...', ['brand' => $x]) – şablonlardaki {anahtar} yer tutucuları doldurulur */
function LV(string $tr, string $en, array $vars = []): string
{
    $s = L($tr, $en);
    foreach ($vars as $k => $v) $s = str_replace('{' . $k . '}', (string)$v, $s);
    return $s;
}

function flash_msg(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) { $_SESSION['sflash'] = [$msg, $type]; return null; }
    $f = $_SESSION['sflash'] ?? null; unset($_SESSION['sflash']); return $f;
}

function go(string $to): void { header('Location: ' . $to); exit; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_input(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_ok(): bool { return hash_equals(csrf_token(), $_POST['_csrf'] ?? ''); }

function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function product_url(array $p): string { return url('/urun/' . $p['slug']); }

function product_main_image(array $p): ?string
{
    static $cache = [];
    $id = (int)$p['id'];
    if (!array_key_exists($id, $cache)) {
        $st = db()->prepare("SELECT url FROM product_images WHERE product_id = ? AND kind <> 'video' ORDER BY is_main DESC, sort_order LIMIT 1");
        $st->execute([$id]); $cache[$id] = ($u = $st->fetchColumn()) ? media_url($u) : null;
    }
    return $cache[$id];
}

function product_thumb(array $p, string $cls = ''): string
{
    $img = product_main_image($p);
    return $img ? '<img src="' . e($img) . '" alt="' . e(tr_field($p, 'name')) . '" loading="lazy" class="aspect-square w-full rounded-md bg-white object-contain ' . $cls . '">' : part_art($p['icon'] ?? 'other', $cls);
}

function stars(float $r, string $cls = 'h-4 w-4'): string
{
    $h = '';
    for ($i = 1; $i <= 5; $i++) $h .= icon('star', $cls . ' ' . ($r >= $i - 0.25 ? 'fill-amber-400 text-amber-400' : 'text-slate-300'));
    return '<span class="inline-flex">' . $h . '</span>';
}

function breadcrumb(array $items): string
{
    $h = '<nav class="container-x flex flex-wrap items-center gap-1 py-4 text-xs text-muted"><a href="' . url('/') . '" class="hover:text-brand">' . L('Ana Sayfa', 'Home') . '</a>';
    foreach ($items as [$label, $href]) $h .= ' <span>›</span> ' . ($href ? '<a href="' . e($href) . '" class="hover:text-brand">' . e($label) . '</a>' : '<span class="text-ink">' . e($label) . '</span>');
    return $h . '</nav>';
}

function wa_link(string $text = ''): string
{
    $wa = setting('whatsapp', []);
    return 'https://wa.me/' . preg_replace('/\D/', '', $wa['number'] ?? '') . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function tr_upper(string $s): string { return mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], $s), 'UTF-8'); }

/** Basit ziyaret sayacı (günlük tekil ziyaretçi, sayfa ve ürün görüntülenme) */
function track_visit(bool $product = false, ?int $productId = null): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || preg_match('/bot|crawl|spider|slurp|preview/i', $_SERVER['HTTP_USER_AGENT'] ?? '')) return;
    try {
        $today = date('Y-m-d'); $new = ($_SESSION['visit_day'] ?? '') !== $today; $_SESSION['visit_day'] = $today;
        db()->prepare('INSERT INTO page_visits (d, visitors, pageviews, product_views) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE visitors = visitors + VALUES(visitors), pageviews = pageviews + 1, product_views = product_views + VALUES(product_views)')
            ->execute([$today, $new ? 1 : 0, $product ? 1 : 0]);

        // Detaylı ziyaretçi kaydı
        track_visitor_detail($product ? 'product' : 'page', $productId);
    } catch (Throwable $e) {}
}

/** Detaylı ziyaretçi kaydı: IP, ülke, şehir, cihaz, tarayıcı */
function track_visitor_detail(string $pageType = 'page', ?int $productId = null): void
{
    try {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = get_client_ip();

        // Cihaz tipi
        $device = 'desktop';
        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $ua)) $device = 'mobile';
        elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $ua)) $device = 'tablet';

        // Tarayıcı
        $browser = 'Diğer';
        if (preg_match('/Edg\//i', $ua)) $browser = 'Edge';
        elseif (preg_match('/OPR|Opera/i', $ua)) $browser = 'Opera';
        elseif (preg_match('/Chrome/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/Safari/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/MSIE|Trident/i', $ua)) $browser = 'IE';

        // İşletim sistemi
        $os = 'Diğer';
        if (preg_match('/Windows/i', $ua)) $os = 'Windows';
        elseif (preg_match('/Macintosh|Mac OS/i', $ua)) $os = 'macOS';
        elseif (preg_match('/Linux/i', $ua)) $os = 'Linux';
        elseif (preg_match('/Android/i', $ua)) $os = 'Android';
        elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) $os = 'iOS';

        // Referer
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer && strlen($referer) > 500) $referer = substr($referer, 0, 500);

        // Sayfa URL
        $pageUrl = $_SERVER['REQUEST_URI'] ?? '/';
        if (strlen($pageUrl) > 500) $pageUrl = substr($pageUrl, 0, 500);

        // IP'den ülke/şehir (ip-api.com — ücretsiz, dakikada 45 istek)
        $country = null; $city = null;
        $geoKey = 'geo_' . md5($ip);
        if (isset($_SESSION[$geoKey])) {
            $geo = $_SESSION[$geoKey];
            $country = $geo['country'] ?? null;
            $city = $geo['city'] ?? null;
        } else {
            $geo = @json_decode((string)@file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city&lang=tr", false, stream_context_create(['http' => ['timeout' => 2]])), true);
            if ($geo && ($geo['status'] ?? '') === 'success') {
                $country = $geo['country'] ?? null;
                $city = $geo['city'] ?? null;
            }
            $_SESSION[$geoKey] = ['country' => $country, 'city' => $city];
        }

        $sid = session_id() ?: null;

        db()->prepare('INSERT INTO visitor_logs (ip, country, city, device_type, browser, os, referer, page_url, page_type, product_id, session_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$ip, $country, $city, $device, $browser, $os, $referer, $pageUrl, $pageType, $productId, $sid]);
    } catch (Throwable $e) {}
}

/** Gerçek IP adresini al (proxy/CDN arkasında da çalışır) */
function get_client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function tr_date_long(?int $ts = null): string
{
    $ts = $ts ?? time();
    $m = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $d = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    return date('j', $ts) . ' ' . $m[(int)date('n', $ts)] . ' ' . date('Y', $ts) . ', ' . $d[(int)date('w', $ts)];
}


/** Banner satırlarından geçerli dile uygun olanları seçer: o dile özel banner varsa yalnızca onlar, yoksa "Tüm diller" olanlar. */
function banner_pick(array $rows): array
{
    $loc = locale();
    $own = array_values(array_filter($rows, function ($r) use ($loc) { return ($r['locale'] ?? '') === $loc; }));
    if ($own) return $own;
    return array_values(array_filter($rows, function ($r) { return in_array($r['locale'] ?? '', ['', 'all'], true); }));
}
