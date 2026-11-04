<?php
/** PDO bağlantısı. Veritabanı yoksa otomatik kurulum yapar. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    global $config;
    $c = $config['db'];
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], $opts);
    } catch (PDOException $e) {
        if ((int)$e->errorInfo[1] === 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
            install_database($c);
            $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], $opts);
        } else {
            http_response_code(500);
            exit('<h2 style="font-family:sans-serif">Veritabanına bağlanılamadı</h2><p style="font-family:sans-serif">XAMPP Control Panel\'den <b>MySQL</b>\'i başlatın ve sayfayı yenileyin.<br><small>' . htmlspecialchars($e->getMessage()) . '</small></p>');
        }
    }
    // cPanel vb.: veritabanı panelden boş oluşturulmuşsa tabloları kur
    if (!$pdo->query("SHOW TABLES LIKE 'settings'")->fetchColumn()) {
        install_database($c);
        $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], $opts);
    }
    return $pdo;
}

/** İlk çalıştırmada veritabanını, tabloları ve örnek verileri oluşturur. */
function install_database(array $c): void
{
    $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};charset=utf8mb4", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    // Paylaşımlı hostingde kullanıcının veritabanı oluşturma yetkisi olmayabilir; veritabanı zaten varsa sorun değil
    try { $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci"); } catch (PDOException $e) {}
    $pdo->exec("USE `{$c['name']}`");
    $pdo->exec('SET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS=0');
    foreach (['schema.sql', 'data.sql'] as $f) {
        $sql = file_get_contents(ROOT . '/install/' . $f);
        foreach (split_sql($sql) as $stmt) $pdo->exec($stmt);
    }
    $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, email, password_hash, permissions, is_active) VALUES ('admin','Admin','SIR KALIP','admin@sirkalip.com',?, '[\"*\"]',1)");
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function split_sql(string $sql): array
{
    $out = []; $buf = ''; $inStr = false; $q = '';
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $buf .= $ch;
        if ($inStr) {
            if ($ch === '\\') { $buf .= $sql[++$i] ?? ''; continue; }
            if ($ch === $q) $inStr = false;
        } elseif ($ch === "'" || $ch === '"' || $ch === '`') { $inStr = true; $q = $ch; }
        elseif ($ch === ';') { if (trim($buf) !== ';') $out[] = trim($buf); $buf = ''; }
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return array_values(array_filter($out, function ($s) { return $s !== '' && $s !== ';'; }));
}
