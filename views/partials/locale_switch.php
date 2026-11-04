<?php
$cur = $GLOBALS['CURRENT_PATH'] ?? '/';
?>
<nav class="flex flex-wrap items-center gap-x-2 gap-y-1 font-semibold <?= !empty($dark) ? 'text-sm text-ink' : '' ?>" aria-label="Dil / Language">
  <?= icon('globe', 'h-4 w-4') ?>
  <?php foreach (locales() as $code => $label): ?>
    <a href="<?= e(url($cur, $code)) ?>" hreflang="<?= $code ?>" lang="<?= $code ?>" title="<?= e($label) ?>" <?= $code === locale() ? 'aria-current="true" style="text-decoration:underline;text-underline-offset:3px;font-weight:800"' : 'style="opacity:.75"' ?>><?= strtoupper($code) ?></a>
  <?php endforeach; ?>
</nav>
