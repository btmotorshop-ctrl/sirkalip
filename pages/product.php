<?php
$st = db()->prepare('SELECT p.*, c.slug category_slug, c.name_tr category_name_tr, ' . loc_cols('c', 'name', 'category_name') . ', b.name brand_name, b.slug brand_slug
  FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN brands b ON b.id = p.brand_id WHERE p.slug = ? AND p.is_active = 1 AND p.deleted_at IS NULL');
$st->execute([$params[1]]);
$p = $st->fetch();
if (!$p) { http_response_code(404); view('layout/top', ['title' => '404']); view('404'); view('layout/bottom'); exit; }
$id = (int)$p['id'];
db()->prepare('UPDATE products SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
track_visit(true, $id);
$images = db()->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY is_main DESC, sort_order")->fetchAll();
$specs = db()->query("SELECT k.name_tr, " . loc_cols('k', 'name') . ", k.unit, s.value FROM product_specs s JOIN spec_keys k ON k.id = s.spec_key_id WHERE s.product_id = $id ORDER BY k.sort_order")->fetchAll();
$compat = db()->query("SELECT b.name brand, b.slug, m.name model FROM product_compat pc JOIN machine_models m ON m.id = pc.machine_model_id JOIN brands b ON b.id = m.brand_id WHERE pc.product_id = $id ORDER BY b.sort_order, m.name")->fetchAll(PDO::FETCH_GROUP);
$refs = db()->query("SELECT kind, code FROM product_refs WHERE product_id = $id")->fetchAll();
$tabs = db()->query("SELECT * FROM product_tabs WHERE product_id = $id OR product_id IS NULL ORDER BY sort_order")->fetchAll();
$reviews = db()->query("SELECT * FROM reviews WHERE product_id = $id AND is_approved = 1 ORDER BY id DESC")->fetchAll();
$questions = db()->query("SELECT * FROM questions WHERE product_id = $id AND is_published = 1 ORDER BY id DESC")->fetchAll();
$rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$related = db()->query("SELECT p.*, c.slug category_slug FROM product_related r JOIN products p ON p.id = r.related_id JOIN categories c ON c.id = p.category_id WHERE r.product_id = $id AND p.is_active = 1 AND p.deleted_at IS NULL")->fetchAll();
if (count($related) < 6) {
    $ex = implode(',', array_merge([$id], array_map(function ($r) { return (int)$r['id']; }, $related)));
    $related = array_merge($related, db()->query("SELECT p.*, c.slug category_slug FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id NOT IN ($ex) AND p.is_active = 1 AND p.deleted_at IS NULL ORDER BY p.category_id = {$p['category_id']} DESC, p.sales_count DESC LIMIT " . (8 - count($related)))->fetchAll());
}
$viewed = array_values(array_diff($_SESSION['viewed'] ?? [], [$id])); array_unshift($viewed, $id); $_SESSION['viewed'] = array_slice($viewed, 0, 12);
$ps = setting('product', []);
view('layout/top', ['title' => tr_field($p, 'name') . ' ' . $p['code'], 'meta' => $p['seo_description'] ?: strip_tags((string)tr_field($p, 'short_desc')), 'product' => $p]);
view('product', compact('p', 'images', 'specs', 'compat', 'refs', 'tabs', 'reviews', 'questions', 'rating', 'related', 'ps'));
view('layout/bottom');
