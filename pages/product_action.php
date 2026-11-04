<?php
if (!is_post() || !csrf_ok()) go(url('/'));
$pid = (int)($_POST['product_id'] ?? 0);
$st = db()->prepare('SELECT slug FROM products WHERE id = ?'); $st->execute([$pid]); $slug = $st->fetchColumn();
if (!$slug) go(url('/'));
$back = url('/urun/' . $slug);
switch ($_POST['action'] ?? '') {
    case 'review':
        $r = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $auto = !empty(setting('product', [])['autoApproveReviews']);
        db()->prepare('INSERT INTO reviews (product_id, user_id, name, rating, comment, is_approved) VALUES (?,?,?,?,?,?)')->execute([$pid, customer()['id'] ?? null, mb_substr(trim($_POST['name'] ?? ''), 0, 100), $r, trim($_POST['comment'] ?? ''), $auto ? 1 : 0]);
        flash_msg(L('Teşekkürler! Yorumunuz onaylandıktan sonra yayınlanacak.', 'Thank you! Your review will be published after approval.'));
        go($back . '#tab-reviews');
    case 'question':
        $q = trim($_POST['question'] ?? '') . (($em = trim($_POST['email'] ?? '')) ? "\n[" . $em . ']' : '');
        db()->prepare('INSERT INTO questions (product_id, user_id, name, question) VALUES (?,?,?,?)')->execute([$pid, customer()['id'] ?? null, mb_substr(trim($_POST['name'] ?? ''), 0, 100), $q]);
        flash_msg(L('Sorunuz alındı. En kısa sürede cevaplayacağız.', 'Your question has been received.'));
        go($back . '#tab-qa');
    case 'stock_alert':
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if ($email) db()->prepare("INSERT INTO stock_alerts (product_id, email, kind) VALUES (?, ?, 'stock')")->execute([$pid, $email]);
        flash_msg($email ? L('Ürün stoğa girdiğinde size haber vereceğiz.', 'We will notify you when it is back in stock.') : L('Geçerli bir e-posta girin.', 'Enter a valid email.'), $email ? 'ok' : 'err');
        go($back);
}
go($back);
