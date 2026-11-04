<?php
function field_options(array $spec): array
{
    $o = $spec[2] ?? [];
    if (is_callable($o)) return $o();
    return is_array($o) ? $o : [];
}
function field_required(array $spec): bool { return in_array('required', $spec, true); }

function crud_pk(array $r): string { return $r['pk'] ?? 'id'; }

function crud_list(string $key, array $r): void
{
    $pk = crud_pk($r);
    $where = [$r['where'] ?? '1=1']; $params = [];
    if (!empty($r['search'] ?? null) && ($q = trim($_GET['q'] ?? '')) !== '') {
        $where[] = '(' . implode(' OR ', array_map(function ($c) { return "`$c` LIKE ?"; }, $r['search'])) . ')';
        foreach ($r['search'] as $_) $params[] = "%$q%";
    } elseif (($q = trim($_GET['q'] ?? '')) !== '') {
        $first = array_keys($r['fields'])[0];
        $where[] = "`$first` LIKE ?"; $params[] = "%$q%";
    }
    foreach ($r['filters'] ?? [] as $f) {
        if (isset($_GET[$f]) && $_GET[$f] !== '') { $where[] = "`$f` = ?"; $params[] = $_GET[$f]; }
    }
    $page = max(1, (int)($_GET['page'] ?? 1)); $per = 25;
    $w = implode(' AND ', $where);
    $total = db()->prepare("SELECT COUNT(*) FROM `{$r['table']}` WHERE $w"); $total->execute($params); $total = (int)$total->fetchColumn();
    $st = db()->prepare("SELECT * FROM `{$r['table']}` WHERE $w ORDER BY " . ($r['order'] ?? "`$pk` DESC") . " LIMIT $per OFFSET " . (($page - 1) * $per));
    $st->execute($params);
    $rows = $st->fetchAll();
    admin_view($r['list_view'] ?? 'crud_list', compact('key', 'r', 'rows', 'total', 'page', 'per', 'pk'));
}

function crud_display(array $r, string $col, $val): string
{
    if ($val === null || $val === '') return '<span class="text-muted">—</span>';
    $spec = $r['fields'][$col] ?? null;
    $type = $spec[1] ?? (preg_match('/^(is_|email_opt|sms_opt)/', $col) ? 'bool' : 'text');
    if (preg_match('/^is_/', $col)) $type = 'bool';
    if ($type === 'bool') return $val ? '<span class="badge-ok">Evet</span>' : '<span class="badge-muted">Hayır</span>';
    if ($type === 'select') { $o = field_options($spec); return e($o[$val] ?? $val); }
    if ($type === 'image') return '<img src="' . e(media_url($val)) . '" alt="" class="h-10 max-w-24 rounded bg-soft object-contain p-1">';
    if ($type === 'money') return money($val);
    if ($type === 'color') return '<span class="inline-block h-4 w-8 rounded" style="background:' . e($val) . '"></span>';
    if ($type === 'date') return e(date('d.m.Y', strtotime((string)$val)));
    if (preg_match('/_at$/', $col)) return e(date('d.m.Y H:i', strtotime((string)$val)));
    $s = strip_tags((string)$val);
    return e(mb_strlen($s) > 60 ? mb_substr($s, 0, 60) . '…' : $s);
}

function crud_form(string $key, array $r, ?string $id): void
{
    $pk = crud_pk($r);
    $row = [];
    if ($id !== null) {
        $st = db()->prepare("SELECT * FROM `{$r['table']}` WHERE `$pk` = ?" . (isset($r['where']) ? " AND {$r['where']}" : ''));
        $st->execute([$id]); $row = $st->fetch() ?: [];
        if (!$row) { flash('Kayıt bulunamadı', 'err'); redirect(aurl("r/$key")); }
    } else {
        foreach ($r['fields'] as $f => $spec) if (($spec[1] ?? '') === 'bool') $row[$f] = in_array($f, ['is_active', 'is_popular'], true) ? 1 : 0;
        foreach ($_GET as $k => $v) if (isset($r['fields'][$k])) $row[$k] = $v;
    }
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($r['readonly'])) {
        [$data, $errors] = crud_collect($r, $row);
        if (!$errors) {
            try {
                if ($id === null) {
                    $data += $r['defaults'] ?? [];
                    $cols = array_keys($data);
                    db()->prepare("INSERT INTO `{$r['table']}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")")->execute(array_values($data));
                    $newId = $pk === 'id' ? db()->lastInsertId() : $data[$pk];
                    log_action('create', $r['table'], (int)$newId, null);
                } else {
                    $sets = implode(',', array_map(function ($c) { return "`$c` = ?"; }, array_keys($data)));
                    db()->prepare("UPDATE `{$r['table']}` SET $sets WHERE `$pk` = ?")->execute(array_merge(array_values($data), [$id]));
                    log_action('update', $r['table'], (int)$id, null);
                }
                if (!empty($r['after_save'])) ($r['after_save'])($data);
                flash('Kayıt başarıyla kaydedildi.' . auto_translate_note($r['table'], (int)($id === null ? $newId : $id)));
                redirect(aurl("r/$key"));
            } catch (PDOException $e) {
                $errors['_'] = strpos($e->getMessage(), 'Duplicate') !== false ? 'Bu değer zaten kayıtlı (benzersiz olmalı).' : $e->getMessage();
            }
            $row = array_merge($row, $data);
        } else {
            $row = array_merge($row, $_POST);
        }
    }
    admin_view($r['form_view'] ?? 'crud_form', compact('key', 'r', 'row', 'id', 'errors', 'pk'));
}

function crud_collect(array $r, array $old): array
{
    $data = []; $errors = [];
    foreach ($r['fields'] as $f => $spec) {
        $type = $spec[1]; $v = $_POST[$f] ?? null;
        switch ($type) {
            case 'bool': $v = isset($_POST[$f]) ? 1 : 0; break;
            case 'number': case 'money': $v = ($v === '' || $v === null) ? null : str_replace(',', '.', $v); break;
            case 'select': $v = ($v === '' ? null : $v); break;
            case 'datetime': case 'date': $v = $v ? date('Y-m-d H:i:s', strtotime($v)) : null; break;
            case 'password':
                if ($v === '' || $v === null) { if (!empty($old)) continue 2; $errors[$f] = 'Şifre gerekli'; continue 2; }
                $data['password_hash'] = password_hash($v, PASSWORD_DEFAULT); continue 2;
            case 'image':
                $v = trim((string)($_POST[$f] ?? ''));
                if (!empty($_POST[$f . '__remove'])) $v = '';
                if (!empty($_FILES[$f]['tmp_name']) && $_FILES[$f]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) { $errors[$f] = 'Sadece JPG, PNG, WEBP, GIF veya SVG yükleyebilirsiniz'; break; }
                    if ($_FILES[$f]['size'] > 5 * 1024 * 1024) { $errors[$f] = 'Dosya en fazla 5 MB olabilir'; break; }
                    $dir = ROOT . '/uploads/' . preg_replace('/[^a-z_]/', '', $r['table']);
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    $base = slugify((string)($_POST[$spec[2] ?? 'name'] ?? '') ?: pathinfo($_FILES[$f]['name'], PATHINFO_FILENAME)) ?: 'gorsel';
                    $name = $base . '-' . bin2hex(random_bytes(3)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                    if (move_uploaded_file($_FILES[$f]['tmp_name'], "$dir/$name")) $v = base_path() . '/uploads/' . basename($dir) . '/' . $name;
                }
                if ($v === '') $v = null;
                break;
            case 'slug': $v = slugify(trim((string)$v) !== '' ? (string)$v : (string)($_POST[$spec[2]] ?? '')); break;
            case 'idlist': $v = trim((string)$v) === '' ? null : json_encode(array_values(array_filter(array_map('intval', explode(',', $v))))); break;
            case 'json':
                $v = trim((string)$v);
                if ($v === '') { $v = null; break; }
                json_decode($v); if (json_last_error()) { $errors[$f] = 'Geçerli JSON girin, ör. {"min_total": 1000}'; }
                break;
            default: $v = is_string($v) ? trim($v) : $v; if ($v === '') $v = null;
        }
        if (field_required($spec) && ($v === null || $v === '')) $errors[$f] = $spec[0] . ' zorunludur';
        $data[$f] = $v;
    }
    return [$data, $errors];
}

function crud_delete(string $key, array $r, string $id): void
{
    if (!empty($r['readonly']) && $key !== 'not_found_logs') redirect(aurl("r/$key"));
    $pk = crud_pk($r);
    db()->prepare("DELETE FROM `{$r['table']}` WHERE `$pk` = ?" . (isset($r['where']) ? " AND {$r['where']}" : ''))->execute([$id]);
    log_action('delete', $r['table'], (int)$id);
    flash('Kayıt silindi.');
    redirect(aurl("r/$key"));
}
