<?php
function admin_user(): ?array { return $_SESSION['admin'] ?? null; }

function admin_login(string $email, string $pass): bool
{
    $st = db()->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin','staff') AND is_active = 1");
    $st->execute([trim($email)]);
    $u = $st->fetch();
    if (!$u || !password_verify($pass, $u['password_hash'])) return false;
    session_regenerate_id(true);
    $_SESSION['admin'] = ['id' => (int)$u['id'], 'name' => $u['first_name'] . ' ' . $u['last_name'], 'email' => $u['email'], 'role' => $u['role']];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
    log_action('login');
    return true;
}

function require_admin(): void
{
    if (!admin_user()) { header('Location: ' . aurl('login')); exit; }
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf() . '">'; }
function check_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf(), $_POST['_csrf'] ?? '')) { http_response_code(419); exit('Oturum süresi doldu, sayfayı yenileyin.'); }
}

function log_action(string $action, ?string $entity = null, ?int $id = null, $detail = null): void
{
    try {
        db()->prepare('INSERT INTO activity_logs (user_id, action, entity, entity_id, detail) VALUES (?,?,?,?,?)')
            ->execute([admin_user()['id'] ?? null, $action, $entity, $id, $detail === null ? null : json_encode($detail, JSON_UNESCAPED_UNICODE)]);
    } catch (Throwable $e) {}
}

function aurl(string $p = ''): string { return base_path() . '/admin/' . ltrim($p, '/'); }

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function redirect(string $to): void { header('Location: ' . $to); exit; }

function slugify(string $s): string
{
    $s = strtr(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $s), 'UTF-8'), ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u', 'İ' => 'i']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
