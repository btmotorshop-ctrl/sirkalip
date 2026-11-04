<?php
/* Müşteri oturumu */
function customer(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['customer_id'])) {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $st->execute([$_SESSION['customer_id']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function customer_login(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$u['id'];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
    // Oturumdaki favorileri hesaba aktar
    foreach ($_SESSION['favs'] ?? [] as $pid) db()->prepare('INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)')->execute([$u['id'], $pid]);
}

function require_customer(): array
{
    $u = customer();
    if (!$u) go(url('/giris') . '?r=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ''));
    return $u;
}

/* Favoriler */
function fav_ids(): array
{
    if ($u = customer()) return array_map('intval', db()->query('SELECT product_id FROM favorites WHERE user_id = ' . (int)$u['id'])->fetchAll(PDO::FETCH_COLUMN));
    return array_map('intval', $_SESSION['favs'] ?? []);
}

function fav_toggle(int $pid): bool
{
    $on = !in_array($pid, fav_ids(), true);
    if ($u = customer()) {
        if ($on) db()->prepare('INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)')->execute([$u['id'], $pid]);
        else db()->prepare('DELETE FROM favorites WHERE user_id = ? AND product_id = ?')->execute([$u['id'], $pid]);
    } else {
        $f = $_SESSION['favs'] ?? [];
        $_SESSION['favs'] = $on ? array_values(array_unique(array_merge($f, [$pid]))) : array_values(array_diff($f, [$pid]));
    }
    return $on;
}
