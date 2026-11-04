<?php
header('Content-Type: application/json; charset=utf-8');
$pid = (int)($_POST['id'] ?? 0);
$ok = $pid && db()->query("SELECT 1 FROM products WHERE id = $pid")->fetchColumn();
echo json_encode(['ok' => (bool)$ok, 'on' => $ok ? fav_toggle($pid) : false, 'count' => count(fav_ids())]);
