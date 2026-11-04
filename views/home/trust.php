<section class="border-b border-line">
  <div class="container-x no-scrollbar flex gap-6 overflow-x-auto py-5 md:grid md:grid-cols-5">
    <?php foreach (['truck', 'shield-check', 'rotate-ccw', 'headset', 'badge-check'] as $i => $ic): $n = $i + 1; ?>
      <div class="flex min-w-[190px] items-center gap-3">
        <?= icon($ic, 'h-9 w-9 shrink-0 text-brand', 1.6) ?>
        <div><div class="text-[13.5px] font-bold"><?= e(t("trust.t$n")) ?></div><div class="text-xs text-muted"><?= e(t("trust.d$n")) ?></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
