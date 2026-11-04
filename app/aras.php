<?php
/**
 * Aras Kargo entegrasyonu (Aras dokümanlarına göre)
 *  - SetOrder              : gönderi / sipariş bilgisi gönderimi (parça bazlı veya sipariş bazlı)
 *  - CancelDispatch        : henüz şubede işlem görmemiş gönderiyi iptal
 *  - GetQueryJSON (tip 39) : entegrasyon koduna göre son durum / takip no (Sorgulama servisi)
 * Kullanıcı bilgileri veritabanında "settings › aras" altında tutulur (Yönetim › Ayarlar › Aras Kargo); kodda şifre yoktur.
 * Not: SetOrder ve Sorgulama servisleri AYRI kullanıcı bilgisi kullanır.
 */
const ARAS_TEST_SETORDER_USER = 'neodyum';   // Aras'ın SetOrder dokümanında yayınladığı test kullanıcısı (yalnızca Test ortamı)
const ARAS_TEST_SETORDER_PASS = 'nd2580';

/** Aras'ın dokümanında yayınladığı Sorgulama servisi TEST ortamı bilgileri (yalnızca Test ortamında kullanılır) */
const ARAS_TEST_QUERY_CODE = '1932448851342';
const ARAS_TEST_QUERY_USER = 'a@59N#ZGgDBc';
const ARAS_TEST_QUERY_PASS = 'u*q0vLw@MB5*';

function aras_cities(): array
{
    return ['Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin', 'Aydın', 'Balıkesir', 'Bartın', 'Batman', 'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul', 'İzmir', 'Kahramanmaraş', 'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kilis', 'Kırıkkale', 'Kırklareli', 'Kırşehir', 'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Mardin', 'Mersin', 'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Osmaniye', 'Rize', 'Sakarya', 'Samsun', 'Şanlıurfa', 'Siirt', 'Sinop', 'Sivas', 'Şırnak', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak'];
}

/** Türkçe karakterleri sadeleştirip küçük harfe çevirir (karşılaştırma anahtarı) */
function aras_key(string $s): string
{
    $s = strtr($s, ['İ' => 'i', 'I' => 'i', 'ı' => 'i', 'Ş' => 's', 'ş' => 's', 'Ğ' => 'g', 'ğ' => 'g', 'Ü' => 'u', 'ü' => 'u', 'Ö' => 'o', 'ö' => 'o', 'Ç' => 'c', 'ç' => 'c', 'â' => 'a', 'Â' => 'a', 'î' => 'i', 'û' => 'u']);
    return mb_strtolower($s, 'UTF-8');
}

/** Firma adresinden [il, ilçe] tahmin eder (örn. "... Selçuklu/KONYA" → ['Konya','Selçuklu']) */
function aras_guess_place(string $addr): array
{
    $addr = trim((string)preg_replace('/\s+/u', ' ', $addr));
    if ($addr === '') return ['', ''];
    $key = aras_key($addr); $best = null;
    foreach (aras_cities() as $il) {
        if (preg_match_all('/(?<![a-z])' . preg_quote(aras_key($il), '/') . '(?![a-z])/u', $key, $m, PREG_OFFSET_CAPTURE) && $m[0]) {
            $pos = mb_strlen(substr($key, 0, end($m[0])[1]), 'UTF-8');
            if ($best === null || $pos > $best[1]) $best = [$il, $pos];
        }
    }
    if (!$best) return ['', ''];
    $before = trim(mb_substr($addr, 0, $best[1], 'UTF-8'));
    $town = '';
    if (preg_match('/([\p{L}]+(?: [\p{L}]+)?)\s*[\/,\-]\s*$/u', $before, $m)) $town = $m[1];
    elseif (preg_match('/([\p{L}]+)$/u', $before, $m) && !preg_match('/^(mah|mahallesi|sok|sokak|cad|caddesi|bulvar|blv|no|sanayi|sitesi|osb|org|bolgesi|kat|daire)\.?$/', aras_key($m[1]))) $town = $m[1];
    if ($town !== '' && aras_key($town) === aras_key($best[0])) $town = '';
    if ($town !== '') $town = mb_convert_case(mb_strtolower(strtr($town, ['İ' => 'i', 'I' => 'ı']), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    return [$best[0], $town];
}

/** Ayar formunda boş kalan alanların otomatik değerleri (firma bilgileri + Aras test ortamı) */
function aras_defaults(): array
{
    $g = setting('general', []) ?: [];
    [$city, $town] = aras_guess_place((string)($g['address'] ?? ''));
    return ['enabled' => true, 'mode' => 'test', 'shipType' => 'piece', 'payor' => '1', 'weight' => 1,
        'dummyName' => 'SIRKALIP Parça Bazlı', 'senderCity' => $city, 'senderTown' => $town];
}

/** Ayar sayfasında gösterilecek değerler: kayıtlı olanlar korunur, boşlar otomatik doldurulur */
function aras_form_values(array $val): array
{
    foreach (aras_defaults() as $k => $v) {
        if (!array_key_exists($k, $val) || $val[$k] === '' || $val[$k] === null) $val[$k] = $v;
    }
    if (($val['mode'] ?? '') === 'test') {
        foreach (['setUsername' => ARAS_TEST_SETORDER_USER, 'customerCode' => ARAS_TEST_QUERY_CODE, 'username' => ARAS_TEST_QUERY_USER] as $k => $v) {
            if (trim((string)($val[$k] ?? '')) === '') $val[$k] = $v;
        }
    }
    return $val;
}

function aras_config(): array
{
    $c = setting('aras', []) ?: [];
    foreach (aras_defaults() as $k => $v) {
        if (!array_key_exists($k, $c) || $c[$k] === '' || $c[$k] === null) $c[$k] = $v;
    }
    $c += ['customerCode' => '', 'username' => '', 'password' => '', 'setUsername' => '', 'setPassword' => '', 'senderAddressId' => ''];
    $test = $c['mode'] === 'test';
    // Aras'ın test bilgileri canlı ortamda geçersizdir: canlıya geçince boş sayılır
    if (!$test) {
        if (trim((string)$c['setUsername']) === ARAS_TEST_SETORDER_USER) { $c['setUsername'] = ''; $c['setPassword'] = ''; }
        if (trim((string)$c['username']) === ARAS_TEST_QUERY_USER) { $c['customerCode'] = ''; $c['username'] = ''; $c['password'] = ''; }
    }
    $c['user'] = trim((string)$c['setUsername']) !== '' ? trim((string)$c['setUsername']) : ($test ? ARAS_TEST_SETORDER_USER : '');
    $c['pass'] = (string)$c['setPassword'] !== '' ? (string)$c['setPassword'] : ($test && $c['user'] === ARAS_TEST_SETORDER_USER ? ARAS_TEST_SETORDER_PASS : '');
    if ($test && (trim((string)$c['username']) === '' || trim((string)$c['username']) === ARAS_TEST_QUERY_USER)) {
        $c['customerCode'] = ARAS_TEST_QUERY_CODE; $c['username'] = ARAS_TEST_QUERY_USER;
        if ((string)$c['password'] === '') $c['password'] = ARAS_TEST_QUERY_PASS;
    }
    $c['ready'] = !empty($c['enabled']) && $c['user'] !== '' && $c['pass'] !== '';
    $c['why'] = empty($c['enabled']) ? 'Ayarlarda "Aras Kargo entegrasyonu AKTİF" kutusu işaretli değil veya kayıt edilmemiş (kutuyu işaretleyip Kaydet\'e basın).'
        : ($c['user'] === '' || $c['pass'] === '' ? 'Gönderi (SetOrder) kullanıcı adı / şifresi eksik' . ($test ? '.' : ' (canlı ortam için Aras\'tan alınan bilgiler gerekir).') : '');
    if (!extension_loaded('soap')) $c['why'] = 'PHP "soap" eklentisi kapalı: XAMPP php.ini içinde extension=soap satırını açıp Apache\'yi yeniden başlatın.';
    if ($c['why'] !== '') $c['ready'] = false;
    $c['queryReady'] = !empty($c['enabled']) && trim((string)$c['username']) !== '' && (string)$c['password'] !== '' && trim((string)$c['customerCode']) !== '';
    return $c;
}

function aras_urls(array $c): array
{
    return $c['mode'] === 'test'
        ? ['order' => 'https://customerservicestest.araskargo.com.tr/arascargoservice/arascargoservice.asmx', 'query' => 'https://customerservicestest.araskargo.com.tr/ArasCargoIntegrationService.svc']
        : ['order' => 'https://customerws.araskargo.com.tr/arascargoservice.asmx', 'query' => 'https://customerservices.araskargo.com.tr/ArasCargoCustomerIntegrationService/ArasCargoIntegrationService.svc'];
}

/** Yeni entegrasyon kodu üretir (yalnızca rakam). Sipariş: sabit kod (tekrar gönderim bilgiyi günceller); müşteri: her gönderi için yeni kod. */
function aras_code(string $source, int $id): string
{
    return $source === 'order' ? '1' . str_pad((string)$id, 8, '0', STR_PAD_LEFT) : '2' . str_pad((string)$id, 7, '0', STR_PAD_LEFT) . date('ymdHis');
}

function aras_phone(string $s): string
{
    $p = preg_replace('/\D/', '', $s);
    if (strlen($p) >= 12 && substr($p, 0, 2) === '90') $p = substr($p, 2);
    if (strlen($p) === 11 && $p[0] === '0') $p = substr($p, 1);
    return $p;
}

function aras_num($v): string
{
    $f = max(0.1, (float)str_replace(',', '.', (string)$v));
    return rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
}

function aras_mask(string $xml): string
{
    $xml = preg_replace('#(<(?:\w+:)?(?:Password|password)>)(.*?)(</)#s', '$1********$3', $xml);
    return preg_replace('#(&lt;Password&gt;)(.*?)(&lt;/Password&gt;)#s', '$1********$3', $xml);
}

function aras_client(string $url): SoapClient
{
    return new SoapClient($url . '?wsdl', ['trace' => 1, 'exceptions' => true, 'connection_timeout' => 15, 'cache_wsdl' => WSDL_CACHE_MEMORY,
        'stream_context' => stream_context_create(['http' => ['timeout' => 25]])]);
}

/** İç içe sonuç nesnesinde ilk eşleşen alanı bulur */
function aras_find($o, array $keys)
{
    $o = json_decode(json_encode($o), true);
    $walk = function ($a) use (&$walk, $keys) {
        if (!is_array($a)) return null;
        foreach ($keys as $k) if (array_key_exists($k, $a) && !is_array($a[$k])) return $a[$k];
        foreach ($a as $v) { $r = $walk($v); if ($r !== null) return $r; }
        return null;
    };
    return is_array($o) ? $walk($o) : $o;
}

/**
 * Aras'a gönderi kaydı açar (SetOrder).
 * $d: code, name, phone, city, town, address, pieces, weight, desc, payor, cod, cod_amount, cod_type, cargo_type(file|parcel), source, ref_id
 * Dönüş: [başarılı mı, mesaj, entegrasyon kodu, günlük id]
 */
function aras_send(array $d): array
{
    $c = aras_config();
    $code = preg_replace('/[^A-Za-z0-9]/', '', (string)($d['code'] ?? ''));
    $d['code'] = $code;
    $fail = function (string $msg, array $dbg = []) use ($d, $code) { return [false, $msg, $code, aras_log($d, $code, 0, $msg, $dbg)]; };
    if (!$c['ready']) return [false, 'Aras Kargo ayarları tamamlanmamış veya kapalı (Yönetim › Ayarlar › Aras Kargo). SetOrder kullanıcı adı/şifresi gerekir.', $code, 0];
    if (!class_exists('SoapClient')) return [false, 'PHP "soap" eklentisi kapalı. XAMPP için php.ini içinde ";extension=soap" satırının başındaki noktalı virgülü kaldırıp Apache\'yi yeniden başlatın.', $code, 0];
    if (strlen($code) < 2 || strlen($code) > 32) return [false, 'Entegrasyon kodu 2-32 karakter olmalıdır.', $code, 0];

    $name = mb_substr(trim((string)($d['name'] ?? '')), 0, 100); $addr = trim((string)($d['address'] ?? ''));
    $city = mb_substr(trim((string)($d['city'] ?? '')), 0, 32); $town = mb_substr(trim((string)($d['town'] ?? '')), 0, 32);
    $phone = aras_phone((string)($d['phone'] ?? ''));
    if ($name === '' || $addr === '' || $city === '' || $town === '') return [false, 'Alıcı adı, il, ilçe ve adres zorunludur.', $code, 0];
    if (strlen($phone) !== 10) return [false, 'Alıcı telefonu 10 haneli olmalıdır (başında 0 olmadan, alan koduyla, örn. 5321234567).', $code, 0];
    if (mb_strlen($addr) > 250) $addr = mb_substr($addr, 0, 250);

    $file = ($d['cargo_type'] ?? 'parcel') === 'file';
    $pieces = $file ? 1 : max(1, min(99, (int)($d['pieces'] ?? 1)));       // Dosya gönderileri tek parça olabilir (70025)
    $cod = !empty($d['cod']);
    $payor = ((string)($d['payor'] ?? $c['payor']) === '2') ? 2 : 1;
    $amt = 0.0;
    if ($cod) {
        if ($payor === 2) return [false, 'Alıcı ödemeli tahsilatlı kargo gönderisi yapılamaz. Ödeme tipini "Gönderici Ödemeli" seçin.', $code, 0];
        $amt = (float)str_replace(',', '.', str_replace('.', '', (string)($d['cod_amount'] ?? '0')));
        if (!empty($d['cod_amount_num'])) $amt = (float)$d['cod_amount_num'];
        if ($amt < 5 || $amt > 5000) return [false, 'Tahsilat tutarı 5 TL ile 5000 TL arasında olmalıdır.', $code, 0];
    }
    $w = aras_num($d['weight'] ?? 1);
    $desc = mb_substr(trim((string)($d['desc'] ?? '')), 0, 255);

    $order = [
        'UserName' => $c['user'], 'Password' => $c['pass'],
        'TradingWaybillNumber' => substr($code, -16), 'InvoiceNumber' => substr($code, -20), 'IntegrationCode' => $code,
        'ReceiverName' => $name, 'ReceiverAddress' => $addr, 'ReceiverPhone1' => $phone, 'ReceiverCityName' => $city, 'ReceiverTownName' => $town,
        'PayorTypeCode' => $payor, 'IsWorldWide' => 0, 'IsCod' => $cod ? '1' : '0',
    ];
    if ($desc !== '') $order['Description'] = $desc;
    if ($cod) { $order['CodAmount'] = $amt; $order['CodCollectionType'] = ((string)($d['cod_type'] ?? '0') === '1') ? '1' : '0'; $order['CodBillingType'] = '0'; }
    if (($c['shipType'] ?? 'piece') !== 'order') {       // Parça bazlı: PieceCount + PieceDetails (BarcodeNumber = IntegrationCode + 2 haneli parça sırası)
        $order['PieceCount'] = $pieces; $order['VolumetricWeight'] = $w; $order['Weight'] = $w;
        $pd = [];
        for ($i = 1; $i <= $pieces; $i++) $pd[] = ['VolumetricWeight' => $w, 'Weight' => $w, 'BarcodeNumber' => $code . str_pad((string)$i, 2, '0', STR_PAD_LEFT), 'ProductNumber' => '', 'Description' => ''];
        $order['PieceDetails'] = ['PieceDetail' => $pd];
    }
    if (trim((string)($c['senderAddressId'] ?? '')) !== '') $order['SenderAccountAddressId'] = trim((string)$c['senderAddressId']);

    $url = aras_urls($c)['order'];
    $dbg = ['method' => 'SetOrder', 'env' => $c['mode'], 'url' => $url];
    try {
        $cl = aras_client($url);
        $r = $cl->SetOrder(['userName' => $c['user'], 'password' => $c['pass'], 'orderInfo' => ['Order' => [$order]]]);
        $dbg['req'] = aras_mask((string)$cl->__getLastRequest()); $dbg['res'] = (string)$cl->__getLastResponse();
    } catch (Throwable $e) {
        if (isset($cl)) { $dbg['req'] = aras_mask((string)$cl->__getLastRequest()); $dbg['res'] = (string)$cl->__getLastResponse(); }
        return $fail('Aras servisine ulaşılamadı: ' . mb_substr($e->getMessage(), 0, 200), $dbg);
    }
    $rc = aras_find($r, ['ResultCode']); $rm = trim((string)aras_find($r, ['ResultMessage']));
    $d['pieces'] = $pieces; $d['weight'] = $w; $d['payor'] = $payor; $d['ship_type'] = $c['shipType'] ?? 'piece';
    if ($rc !== null && (string)$rc === '0') {
        $lid = aras_log($d, $code, 1, $rm ?: 'Gönderi oluşturuldu', $dbg);
        return [true, 'Gönderi Aras Kargo\'ya iletildi. Entegrasyon kodu: ' . $code, $code, $lid];
    }
    return $fail('Aras Kargo hatası' . ($rc !== null ? " ($rc)" : '') . ': ' . ($rm ?: 'Yanıt alınamadı. Kullanıcı adı/şifre ve Test-Canlı ortamını kontrol edin.'), $dbg);
}

/** Henüz şubede işlem görmemiş gönderiyi iptal eder (CancelDispatch) */
function aras_cancel(int $logId): array
{
    $c = aras_config();
    $row = db()->query('SELECT * FROM aras_shipments WHERE id = ' . (int)$logId)->fetch();
    if (!$row) return [false, 'Kayıt bulunamadı.'];
    if (!$c['ready']) return [false, 'Aras Kargo ayarları tamamlanmamış.'];
    if (!class_exists('SoapClient')) return [false, 'PHP "soap" eklentisi kapalı.'];
    $url = aras_urls($c)['order'];
    $dbg = ['method' => 'CancelDispatch', 'env' => $c['mode'], 'url' => $url];
    try {
        $cl = aras_client($url);
        $r = $cl->CancelDispatch(['userName' => $c['user'], 'password' => $c['pass'], 'integrationCode' => $row['integration_code']]);
        $dbg['req'] = aras_mask((string)$cl->__getLastRequest()); $dbg['res'] = (string)$cl->__getLastResponse();
    } catch (Throwable $e) { return [false, 'Aras servisine ulaşılamadı: ' . mb_substr($e->getMessage(), 0, 200)]; }
    $rc = aras_find($r, ['ResultCode', 'CancelDispatchResult', 'Code']); $rm = trim((string)aras_find($r, ['ResultMessage', 'Message']));
    $map = ['0' => 'İptal talebi alındı.', '1' => 'Gönderi silindi.', '-1' => 'Kayıt bulunamadı.', '-2' => 'Kullanıcı adı ve şifre hatalı.', '936' => 'İptal sırasında sistemsel hata oluştu.', '999' => 'İrsaliyesi kesilmiş / şubede kabul edilmiş gönderi iptal edilemez.'];
    $ok = in_array((string)$rc, ['0', '1'], true);
    if ($ok) db()->prepare('UPDATE aras_shipments SET cancelled = 1, status_text = ?, req = ?, res = ?, method = ? WHERE id = ?')->execute(['İptal edildi', $dbg['req'] ?? null, $dbg['res'] ?? null, 'CancelDispatch', $logId]);
    return [$ok, ($ok ? 'Aras gönderisi iptal edildi.' : 'İptal edilemedi: ') . ($ok ? '' : ($map[(string)$rc] ?? ($rm ?: 'Yanıt alınamadı.')) . ($rc !== null ? " ($rc)" : ''))];
}

/** Sorgulama servisi (GetQueryJSON, tip 39): entegrasyon koduna göre son durum. Dönüş: [ok, mesaj|satır dizisi] */
function aras_query(array $codes): array
{
    $c = aras_config();
    if (!$c['queryReady']) return [false, 'Sorgulama servisi bilgileri (müşteri kodu, kullanıcı adı, şifre) Aras Kargo ayarlarında eksik.'];
    if (!class_exists('SoapClient')) return [false, 'PHP "soap" eklentisi kapalı.'];
    $x = function ($s) { return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
    $login = '<LoginInfo><UserName>' . $x($c['username']) . '</UserName><Password>' . $x($c['password']) . '</Password><CustomerCode>' . $x($c['customerCode']) . '</CustomerCode></LoginInfo>';
    $query = '<QueryInfo><QueryType>39</QueryType><IntegrationCode>' . $x(implode(',', $codes)) . '</IntegrationCode></QueryInfo>';
    try {
        $cl = aras_client(aras_urls($c)['query']);
        $r = $cl->GetQueryJSON(['loginInfo' => $login, 'queryInfo' => $query]);
    } catch (Throwable $e) { return [false, 'Aras sorgulama servisine ulaşılamadı: ' . mb_substr($e->getMessage(), 0, 200)]; }
    $json = (string)aras_find($r, ['GetQueryJSONResult']);
    $j = json_decode($json, true);
    if (!is_array($j)) return [false, 'Sorgulama yanıtı okunamadı: ' . mb_substr($json, 0, 160)];
    $rows = [];
    $walk = function ($a) use (&$walk, &$rows) {
        if (!is_array($a)) return;
        if (isset($a['MUSTERI_OZEL_KODU']) || isset($a['KARGO_TAKIP_NO'])) { $rows[] = $a; return; }
        foreach ($a as $v) $walk($v);
    };
    $walk($j);
    return [true, $rows];
}

function aras_status_label($code): string
{
    return [1 => 'Çıkış şubesinde', 2 => 'Yolda', 3 => 'Teslimat şubesinde', 4 => 'Teslimatta', 5 => 'Parçalı teslimat', 6 => 'Teslim edildi', 7 => 'Yönlendirildi'][(int)$code] ?? '';
}

/** Bir gönderinin son durumunu sorgular ve kaydeder */
function aras_refresh(int $logId): array
{
    $row = db()->query('SELECT * FROM aras_shipments WHERE id = ' . (int)$logId)->fetch();
    if (!$row || !$row['ok']) return [false, 'Gönderi kaydı bulunamadı.'];
    [$ok, $rows] = aras_query([$row['integration_code']]);
    if (!$ok) return [false, $rows];
    $hit = null;
    foreach ($rows as $r) if ((string)($r['MUSTERI_OZEL_KODU'] ?? '') === (string)$row['integration_code'] || count($rows) === 1) { $hit = $r; break; }
    if (!$hit) { db()->prepare('UPDATE aras_shipments SET checked_at = NOW(), status_text = ? WHERE id = ?')->execute(['Şubeye henüz teslim edilmedi (Aras\'ta irsaliye yok)', $logId]); return [true, 'Aras sisteminde henüz irsaliye kaydı yok: kargo şubeye teslim edildiğinde takip numarası oluşur.']; }
    $sc = (int)($hit['DURUM_KODU'] ?? 0);
    $txt = trim((string)($hit['DURUMU'] ?? '')) ?: aras_status_label($sc);
    $trk = trim((string)($hit['KARGO_TAKIP_NO'] ?? ''));
    db()->prepare('UPDATE aras_shipments SET tracking_no = ?, status_code = ?, status_text = ?, checked_at = NOW() WHERE id = ?')->execute([$trk ?: null, $sc ?: null, mb_substr($txt, 0, 160), $logId]);
    if ($row['source'] === 'order' && $trk !== '') {
        db()->prepare('UPDATE shipments SET tracking_no = ?' . ($sc === 6 ? ', delivered_at = NOW()' : '') . ' WHERE order_id = ? AND tracking_no IN (?, ?)')->execute([$trk, (int)$row['ref_id'], $row['integration_code'], $trk]);
    }
    return [true, 'Durum: ' . ($txt ?: 'bilgi yok') . ($trk !== '' ? ' · Takip no: ' . $trk : '')];
}

function aras_log(array $d, string $code, int $ok, string $msg, array $dbg = []): int
{
    try {
        db()->prepare('INSERT INTO aras_shipments (source, ref_id, integration_code, receiver, city, town, pieces, ok, message, payload, method, env, req, res) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([(string)($d['source'] ?? ''), (int)($d['ref_id'] ?? 0) ?: null, $code, mb_substr((string)($d['name'] ?? ''), 0, 191), mb_substr((string)($d['city'] ?? ''), 0, 60), mb_substr((string)($d['town'] ?? ''), 0, 60),
                max(1, (int)($d['pieces'] ?? 1)), $ok, mb_substr($msg, 0, 250), json_encode($d, JSON_UNESCAPED_UNICODE), $dbg['method'] ?? 'SetOrder', $dbg['env'] ?? null, $dbg['req'] ?? null, $dbg['res'] ?? null]);
        return (int)db()->lastInsertId();
    } catch (Throwable $e) { return 0; /* günlük yazılamazsa işlemi bozma */ }
}

/** Code128-B barkodu SVG olarak üretir (dummy etiket için) */
function aras_barcode_svg(string $text, int $h = 60): string
{
    static $p = null;
    if ($p === null) $p = explode(',', '212222,222122,222221,121223,121322,131222,122213,122312,132212,221213,221312,231212,112232,122132,122231,113222,123122,123221,223211,221132,221231,213212,223112,312131,311222,321122,321221,312212,322112,322211,212123,212321,232121,111323,131123,131321,112313,132113,132311,211313,231113,231311,112133,112331,132131,113123,113321,133121,313121,211331,231131,213113,213311,213131,311123,311321,331121,312113,312311,332111,314111,221411,431111,111224,111422,121124,121421,141122,141221,112214,112412,122114,122411,142112,142211,241211,221114,413111,241112,134111,111242,121142,121241,114212,124112,124211,411212,421112,421211,212141,214121,412121,111143,111341,131141,114113,114311,411113,411311,113141,114131,311141,411131,211412,211214,211232,2331112');
    $text = preg_replace('/[^\x20-\x7E]/', '', $text);
    $vals = [104]; $sum = 104;
    foreach (str_split($text) as $i => $ch) { $v = ord($ch) - 32; $vals[] = $v; $sum += $v * ($i + 1); }
    $vals[] = $sum % 103; $vals[] = 106;
    $x = 10; $bars = '';
    foreach ($vals as $v) {
        $pat = $p[$v]; $bar = true;
        for ($k = 0, $n = strlen($pat); $k < $n; $k++) { $w = (int)$pat[$k]; if ($bar) $bars .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $h . '"/>'; $x += $w; $bar = !$bar; }
    }
    $w = $x + 10;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" width="100%" height="' . $h . '" preserveAspectRatio="none" fill="#000" shape-rendering="crispEdges">' . $bars . '</svg>';
}
