<?php
$cur  = $GLOBALS['CURRENT_PATH'] ?? '/';
$dark = !empty($dark);
$all  = locales();
$curLabel = $all[locale()] ?? strtoupper(locale());
?>
<details class="relative" data-locale-dd>
  <summary class="flex items-center gap-1.5 font-semibold <?= $dark ? 'text-sm text-ink' : 'text-white/90 hover:text-white' ?>" aria-label="Dil / Language" title="Dil / Language">
    <?= icon('globe', 'h-4 w-4') ?>
    <span><?= e($curLabel) ?></span>
    <?= icon('chevron-down', 'sk-caret h-3.5 w-3.5') ?>
  </summary>
  <div class="absolute right-0 top-full z-50 mt-2 min-w-[170px] overflow-hidden rounded-lg border border-line bg-white py-1 text-ink shadow-xl">
    <?php foreach ($all as $code => $label): ?>
      <a href="<?= e(url($cur, $code)) ?>" hreflang="<?= $code ?>" lang="<?= $code ?>"
         class="flex items-center justify-between gap-4 px-4 py-2 text-sm hover:bg-soft <?= $code === locale() ? 'font-bold text-brand' : 'font-medium text-ink' ?>">
        <span><?= e($label) ?></span>
        <span class="text-[11px] font-semibold uppercase text-muted"><?= $code ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</details>
