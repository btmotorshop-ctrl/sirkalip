<?php /* $name, $label, $type, $value, $error, $attrs */ ?>
<label class="block">
  <span class="mb-1 block text-xs font-semibold text-muted"><?= e($label) ?></span>
  <?php if (($type ?? 'text') === 'textarea'): ?>
    <textarea name="<?= e($name) ?>" rows="<?= $rows ?? 4 ?>" <?= $attrs ?? '' ?> class="w-full rounded-md border <?= !empty($error) ? 'border-brand' : 'border-line' ?> p-3 text-sm outline-none focus:border-brand"><?= e($value ?? '') ?></textarea>
  <?php else: ?>
    <input type="<?= e($type ?? 'text') ?>" name="<?= e($name) ?>" value="<?= e($value ?? '') ?>" <?= $attrs ?? '' ?> class="h-11 w-full rounded-md border <?= !empty($error) ? 'border-brand' : 'border-line' ?> px-3 text-sm outline-none focus:border-brand">
  <?php endif; ?>
  <?php if (!empty($error)): ?><span class="mt-1 block text-xs font-semibold text-brand"><?= e($error) ?></span><?php endif; ?>
</label>
