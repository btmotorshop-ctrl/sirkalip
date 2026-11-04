<?php
/**
 * Otomatik çeviri: Türkçe içerikten EN / DE / FR / RU / AR çevirilerini üretir.
 * Servis: Google Cloud Translation (v2) veya DeepL. Anahtar: Ayarlar → Çeviri Ayarları.
 * Kural: boş alanlar çevrilir; daha önce makine çevirisi olan alanlar, Türkçe metin değişirse yenilenir;
 *        elle düzenlenmiş çeviriler asla ezilmez.
 */

function tr_cfg(): array
{
    $c = setting('translate', []);
    return (is_array($c) ? $c : []) + ['provider' => 'free', 'apiKey' => '', 'auto' => true];
}
function tr_ready(): bool { $c = tr_cfg(); return loc_ready() && ($c['provider'] === 'free' || trim((string)$c['apiKey']) !== ''); }
function tr_auto_on(): bool { return tr_ready() && !empty(tr_cfg()['auto']); }

function tr_build(string $text, string $loc, bool $html): array
{
    $c = tr_cfg(); $key = trim((string)$c['apiKey']);
    if ($c['provider'] === 'deepl') {
        $host = substr($key, -3) === ':fx' ? 'api-free.deepl.com' : 'api.deepl.com';
        $f = ['text' => $text, 'source_lang' => 'TR', 'target_lang' => ['en' => 'EN-GB', 'de' => 'DE', 'fr' => 'FR', 'ru' => 'RU', 'ar' => 'AR'][$loc]];
        if ($html) $f['tag_handling'] = 'html';
        return ["https://$host/v2/translate", http_build_query($f), ['Authorization: DeepL-Auth-Key ' . $key]];
    }
    $f = ['q' => $text, 'source' => 'tr', 'target' => $loc, 'format' => $html ? 'html' : 'text', 'key' => $key];
    return ['https://translation.googleapis.com/language/translate/v2', http_build_query($f), []];
}

function tr_parse(string $body, int $code, bool $html): string
{
    $j = json_decode($body, true);
    if ($code >= 400 || !is_array($j)) {
        $msg = is_array($j) ? (string)($j['error']['message'] ?? $j['message'] ?? '') : '';
        throw new RuntimeException('Çeviri servisi hatası (HTTP ' . $code . ')' . ($msg !== '' ? ': ' . $msg : ''));
    }
    $t = $j['data']['translations'][0]['translatedText'] ?? $j['translations'][0]['text'] ?? null;
    if ($t === null) throw new RuntimeException('Çeviri servisi beklenmeyen bir yanıt verdi.');
    return $html ? (string)$t : html_entity_decode((string)$t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** İstekleri paralel çalıştırır. $reqs: anahtar => [url, post, headers] → anahtar => ['body','code','err'] */
function tr_exec(array $reqs): array
{
    $out = [];
    foreach (array_chunk($reqs, 6, true) as $chunk) {
        $mh = curl_multi_init(); $hs = [];
        foreach ($chunk as $k => [$url, $post, $hdr]) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45, CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/x-www-form-urlencoded'], $hdr)]);
            curl_multi_add_handle($mh, $ch); $hs[$k] = $ch;
        }
        do { $st = curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 1.0); } while ($run && $st === CURLM_OK);
        foreach ($hs as $k => $ch) {
            $out[$k] = ['body' => (string)curl_multi_getcontent($ch), 'code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE), 'err' => curl_error($ch)];
            curl_multi_remove_handle($mh, $ch); curl_close($ch);
        }
        curl_multi_close($mh);
    }
    return $out;
}

/** Birden çok metni paralel çevirir. $jobs: anahtar => ['text','loc','html'] → anahtar => ['ok','text'|'err'] */
function translate_many(array $jobs): array
{
    if (tr_cfg()['provider'] === 'free') return tr_free_many($jobs);
    $reqs = []; foreach ($jobs as $k => $j) $reqs[$k] = tr_build($j['text'], $j['loc'], $j['html']);
    $out = [];
    foreach (tr_exec($reqs) as $k => $r) {
        try {
            if ($r['err'] !== '' && $r['code'] === 0) throw new RuntimeException('Çeviri servisine ulaşılamadı: ' . $r['err']);
            $out[$k] = ['ok' => true, 'text' => tr_parse($r['body'], $r['code'], $jobs[$k]['html'])];
        } catch (Throwable $e) { $out[$k] = ['ok' => false, 'err' => $e->getMessage()]; }
    }
    return $out;
}

/* ── Ücretsiz (anahtarsız) sağlayıcı: Google'ın herkese açık web çeviri uç noktası. Resmî/garantili değildir. ── */
function tr_free_parse(string $body, int $code, string $cerr): string
{
    if ($code === 0) throw new RuntimeException('Çeviri servisine ulaşılamadı' . ($cerr !== '' ? ': ' . $cerr : '.'));
    if ($code === 429 || $code === 403) throw new RuntimeException('Ücretsiz servis istek sınırına ulaştı veya sunucunuzu engelledi (HTTP ' . $code . '). Biraz bekleyip tekrar deneyin ya da Google/DeepL anahtarı kullanın.');
    $j = json_decode($body, true);
    if ($code >= 400 || !is_array($j) || !isset($j[0]) || !is_array($j[0])) throw new RuntimeException('Ücretsiz çeviri servisi geçerli yanıt vermedi (HTTP ' . $code . ').');
    $o = ''; foreach ($j[0] as $seg) if (isset($seg[0]) && is_string($seg[0])) $o .= $seg[0];
    return $o;
}

/** HTML'i bozmadan çevirir: yalnızca metin düğümleri çevrilir, etiketler/biçimlendirme aynen kalır. */
function tr_free_many(array $jobs): array
{
    $units = []; $plans = [];
    foreach ($jobs as $k => $j) {
        if (!$j['html']) { $units["$k#0"] = [$j['text'], $j['loc']]; $plans[$k] = ['dom' => null, 'uid' => "$k#0"]; continue; }
        $dom = new DOMDocument('1.0', 'UTF-8'); libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="__r">' . $j['text'] . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); libxml_clear_errors();
        $nodes = []; $i = 0;
        foreach ((new DOMXPath($dom))->query('//text()[normalize-space(.) != ""][not(ancestor::script) and not(ancestor::style)]') as $n) {
            preg_match('/^(\s*)(.*?)(\s*)$/su', $n->nodeValue, $m); $uid = "$k#" . (++$i);
            $units[$uid] = [$m[2], $j['loc']]; $nodes[] = [$n, $m[1], $m[3], $uid];
        }
        $plans[$k] = ['dom' => $dom, 'nodes' => $nodes];
    }
    $res = [];
    foreach (array_chunk($units, 6, true) as $chunk) {
        $reqs = [];
        foreach ($chunk as $uid => [$t, $l]) $reqs[$uid] = ['https://translate.googleapis.com/translate_a/single?client=gtx&sl=tr&tl=' . $l . '&dt=t&ie=UTF-8&oe=UTF-8', http_build_query(['q' => $t]), []];
        foreach (tr_exec($reqs) as $uid => $r) {
            try { $res[$uid] = ['ok' => true, 'text' => tr_free_parse($r['body'], $r['code'], $r['err'])]; }
            catch (Throwable $e) { $res[$uid] = ['ok' => false, 'err' => $e->getMessage()]; }
        }
    }
    $out = [];
    foreach ($plans as $k => $p) {
        if ($p['dom'] === null) { $out[$k] = $res[$p['uid']]; continue; }
        $bad = null; foreach ($p['nodes'] as $nd) if (!$res[$nd[3]]['ok']) { $bad = $res[$nd[3]]['err']; break; }
        if ($bad !== null) { $out[$k] = ['ok' => false, 'err' => $bad]; continue; }
        foreach ($p['nodes'] as [$n, $lead, $trail, $uid]) $n->nodeValue = $lead . $res[$uid]['text'] . $trail;
        $root = $p['dom']->getElementById('__r') ?: (new DOMXPath($p['dom']))->query('//div[@id="__r"]')->item(0); $h = '';
        foreach ($root->childNodes as $c) $h .= $p['dom']->saveHTML($c);
        $out[$k] = ['ok' => true, 'text' => $h];
    }
    return $out;
}

/** Çevrilebilir tablolar: tablo => [alan tabanları] (hem *_tr hem *_de sütunu olanlar) */
function translatable_map(): array
{
    static $m = null;
    if ($m !== null) return $m;
    $m = [];
    $q = function ($like) { return db()->query("SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME LIKE '$like' AND DATA_TYPE IN ('varchar','text','mediumtext','longtext')")->fetchAll(PDO::FETCH_ASSOC); };
    $have = []; foreach ($q('%\\\\_de') as $r) $have[$r['t'] . '.' . $r['c']] = 1;
    $hasId = []; foreach (db()->query("SELECT TABLE_NAME t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'id'")->fetchAll(PDO::FETCH_COLUMN) as $t) $hasId[$t] = 1;
    foreach ($q('%\\\\_tr') as $r) {
        $base = substr($r['c'], 0, -3);
        if (in_array($r['t'], ['email_templates'], true) || empty($hasId[$r['t']]) || empty($have[$r['t'] . '.' . $base . '_de'])) continue;
        $m[$r['t']][] = $base;
    }
    return $m;
}
function tr_is_html(string $base): bool { return in_array($base, ['desc', 'content', 'body', 'answer', 'text'], true); }
function tr_meta_table(): void
{
    static $d = false; if ($d) return;
    db()->exec("CREATE TABLE IF NOT EXISTS translation_meta (tbl VARCHAR(40) NOT NULL, row_id INT NOT NULL, field VARCHAR(40) NOT NULL, loc CHAR(2) NOT NULL, src_hash CHAR(32) NOT NULL, out_hash CHAR(32) NOT NULL, PRIMARY KEY (tbl, row_id, field, loc)) DEFAULT CHARSET=utf8mb4");
    $d = true;
}

/** Bir satırda çevrilmesi gereken alanlar: 'alan.dil' => iş */
function tr_plan(string $table, array $row, array $meta, bool $force = false): array
{
    $jobs = [];
    foreach (translatable_map()[$table] ?? [] as $base) {
        $src = trim((string)($row[$base . '_tr'] ?? ''));
        if ($src === '' || mb_strlen($src) > 30000) continue;
        foreach (extra_locales() as $loc) {
            $cur = (string)($row[$base . '_' . $loc] ?? ''); $m = $meta[$base . '.' . $loc] ?? null;
            $machine = $m && md5($cur) === $m['out_hash'];
            if (trim($cur) === '' || ($machine && ($m['src_hash'] !== md5($src) || $force)))
                $jobs[$base . '.' . $loc] = ['text' => $src, 'loc' => $loc, 'html' => tr_is_html($base), 'base' => $base, 'src' => md5($src)];
        }
    }
    return $jobs;
}
function tr_load_meta(string $table, int $id): array
{
    tr_meta_table();
    $st = db()->prepare('SELECT field, loc, src_hash, out_hash FROM translation_meta WHERE tbl = ? AND row_id = ?'); $st->execute([$table, $id]);
    $meta = []; foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $meta[$r['field'] . '.' . $r['loc']] = $r;
    return $meta;
}

/** Tek satırı çevirir. Dönüş: ['done' => çevrilen alan sayısı, 'errors' => [...]] */
function translate_row(string $table, int $id, bool $force = false): array
{
    if (!tr_ready() || !isset(translatable_map()[$table])) return ['done' => 0, 'errors' => []];
    $st = db()->prepare("SELECT * FROM `$table` WHERE id = ?"); $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['done' => 0, 'errors' => []];
    $jobs = tr_plan($table, $row, tr_load_meta($table, $id), $force);
    if (!$jobs) return ['done' => 0, 'errors' => []];
    $res = translate_many($jobs); $sets = []; $vals = []; $errors = [];
    $up = db()->prepare('INSERT INTO translation_meta (tbl, row_id, field, loc, src_hash, out_hash) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE src_hash = VALUES(src_hash), out_hash = VALUES(out_hash)');
    foreach ($res as $k => $r) {
        $j = $jobs[$k];
        if (!$r['ok']) { $errors[$r['err']] = $r['err']; continue; }
        $sets[] = '`' . $j['base'] . '_' . $j['loc'] . '` = ?'; $vals[] = $r['text'];
        $up->execute([$table, $id, $j['base'], $j['loc'], $j['src'], md5($r['text'])]);
    }
    if ($sets) { $vals[] = $id; db()->prepare("UPDATE `$table` SET " . implode(', ', $sets) . ' WHERE id = ?')->execute($vals); }
    return ['done' => count($sets), 'errors' => array_values($errors)];
}

/** Kayıt sonrası otomatik çeviri; kullanıcıya gösterilecek kısa not döndürür */
function auto_translate_note(string $table, int $id): string
{
    if (!tr_auto_on()) return '';
    try {
        $r = translate_row($table, $id);
        if ($r['errors']) return ' (Otomatik çeviri uyarısı: ' . $r['errors'][0] . ')';
        return $r['done'] ? ' ' . $r['done'] . ' alan diğer dillere otomatik çevrildi.' : '';
    } catch (Throwable $e) { return ' (Otomatik çeviri yapılamadı: ' . $e->getMessage() . ')'; }
}

function tr_table_label(string $t): string
{
    return ['products' => 'Ürünler', 'categories' => 'Kategoriler', 'spec_keys' => 'Teknik özellik adları', 'pages' => 'Sayfalar, makaleler, sözleşmeler', 'faqs' => 'Sıkça sorulan sorular',
        'banners' => 'Bannerlar', 'product_tabs' => 'Ürün detay sekmeleri', 'menus' => 'Menüler', 'menu_items' => 'Menü öğeleri'][$t] ?? $t;
}

/* ───────── Yönetim sayfaları ───────── */
function page_translate(): void
{
    $tables = [];
    if (tr_ready()) {
        foreach (array_keys(translatable_map()) as $t) {
            $total = (int)db()->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); $pending = 0;
            foreach (db()->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC) as $row) if (tr_plan($t, $row, tr_load_meta($t, (int)$row['id']))) $pending++;
            $tables[] = ['table' => $t, 'label' => tr_table_label($t), 'total' => $total, 'pending' => $pending];
        }
    }
    admin_view('translate', ['ready' => tr_ready(), 'cfg' => tr_cfg(), 'tables' => $tables]);
}

function page_translate_run(): void
{
    header('Content-Type: application/json; charset=utf-8');
    @set_time_limit(120);
    try {
        if (!tr_ready()) throw new RuntimeException('Önce Ayarlar → Çeviri Ayarları bölümünden API anahtarını girin.');
        if (($_POST['do'] ?? '') === 'test') {
            $r = translate_many(['t' => ['text' => 'Merhaba, bu bir deneme metnidir.', 'loc' => 'de', 'html' => false]])['t'];
            if (!$r['ok']) throw new RuntimeException($r['err']);
            echo json_encode(['ok' => true, 'sample' => $r['text']], JSON_UNESCAPED_UNICODE); return;
        }
        $t = (string)($_POST['table'] ?? ''); $after = (int)($_POST['after'] ?? 0);
        if (!isset(translatable_map()[$t])) throw new RuntimeException('Geçersiz tablo.');
        $st = db()->prepare("SELECT id FROM `$t` WHERE id > ? ORDER BY id LIMIT 4"); $st->execute([$after]);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN); $n = 0; $last = $after; $errs = [];
        foreach ($ids as $id) { $r = translate_row($t, (int)$id, !empty($_POST['force'])); $n += $r['done']; $last = (int)$id; foreach ($r['errors'] as $e) $errs[$e] = $e; }
        echo json_encode(['ok' => !$errs, 'last' => $last, 'rows' => count($ids), 'translated' => $n, 'done' => count($ids) < 4, 'errors' => array_values($errs)], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) { echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE); }
}

/** Dosyayla gelen hazır çevirileri (ürün, kategori, sayfa, SSS...) boş alanlara uygular */
function page_translate_shipped(): void
{
    check_csrf();
    $n = apply_shipped_translations();
    flash($n ? "Hazır çeviriler uygulandı: $n alan dolduruldu." : 'Uygulanacak yeni hazır çeviri yok (alanlar zaten dolu ya da Türkçe metin değişmiş).');
    redirect(aurl('translate'));
}
