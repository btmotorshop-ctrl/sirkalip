<?php
// $f, $spec, $val, $err
$type = $spec[1]; $req = field_required($spec) ? ' <span class="text-brand">*</span>' : '';
$name = e($f);
if ($type === 'bool'): ?>
  <label class="flex items-center gap-2 py-2 text-sm font-semibold"><input type="checkbox" name="<?= $name ?>" value="1" <?= $val ? 'checked' : '' ?> class="h-4 w-4 accent-[#e30613]"> <?= e($spec[0]) ?></label>
<?php else: ?>
  <label class="block">
    <span class="alabel"><?= e($spec[0]) ?><?= $req ?></span>
    <?php if ($type === 'textarea' || $type === 'json'): ?>
      <textarea name="<?= $name ?>" rows="<?= $type === 'json' ? 3 : 4 ?>" class="ainput h-auto py-2 <?= $type === 'json' ? 'font-mono text-xs' : '' ?>"><?= e($val) ?></textarea>
    <?php elseif ($type === 'html'): ?>
      <textarea data-rte name="<?= $name ?>" rows="10" class="ainput h-auto py-2 font-mono text-xs" placeholder="HTML veya düz metin"><?= e($val) ?></textarea>
    <?php elseif ($type === 'select'): ?>
      <select name="<?= $name ?>" class="ainput"><option value="">— Seçiniz —</option>
        <?php foreach (field_options($spec) as $k => $v): ?><option value="<?= e($k) ?>" <?= (string)$val === (string)$k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
    <?php elseif ($type === 'image'): ?>
      <div class="flex items-start gap-4 rounded-md border border-line p-3">
        <div class="flex h-24 w-40 shrink-0 items-center justify-center overflow-hidden rounded bg-[repeating-conic-gradient(#f3f4f6_0_25%,#fff_0_50%)] bg-[length:16px_16px]" data-img-preview>
          <?php if ($val): ?><img src="<?= e(media_url($val)) ?>" alt="" class="max-h-full max-w-full object-contain"><?php else: ?><span class="text-xs text-muted">Görsel yok</span><?php endif; ?>
        </div>
        <div class="min-w-0 flex-1 space-y-2">
          <input type="file" name="<?= $name ?>" accept="image/*" data-img-input class="block w-full text-xs file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
          <input type="text" name="<?= $name ?>" value="<?= e($val) ?>" placeholder="veya görsel adresi (URL)" class="ainput h-9 text-xs">
          <?php if ($val): ?><label class="flex items-center gap-1.5 text-xs text-muted"><input type="checkbox" name="<?= $name ?>__remove" value="1"> Görseli kaldır</label><?php endif; ?>
          <?php if (!empty($spec[3]) && is_string($spec[3])): ?><div class="text-[11px] text-muted"><?= e($spec[3]) ?></div><?php endif; ?>
        </div>
      </div>
    <?php elseif ($type === 'idlist'): $arr = $val ? json_decode((string)$val, true) : null; ?>
      <input name="<?= $name ?>" value="<?= e(is_array($arr) ? implode(',', $arr) : $val) ?>" class="ainput">
    <?php else:
      $it = ['number' => 'number', 'money' => 'number', 'email' => 'email', 'password' => 'password', 'color' => 'color', 'date' => 'date', 'datetime' => 'datetime-local'][$type] ?? 'text';
      if ($type === 'datetime' && $val) $val = date('Y-m-d\TH:i', strtotime((string)$val));
      if ($type === 'password') $val = '';
      if ($type === 'date' && $val) $val = substr((string)$val, 0, 10); ?>
      <input type="<?= $it ?>" name="<?= $name ?>" value="<?= e($val) ?>" <?= in_array($type, ['number', 'money'], true) ? 'step="any"' : '' ?> class="ainput <?= $type === 'color' ? 'h-10 w-24 p-1' : '' ?>">
    <?php endif; ?>
    <?php if (!empty($err)): ?><span class="mt-1 block text-xs font-semibold text-brand"><?= e($err) ?></span><?php endif; ?>
  </label>
<?php endif;
