<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment gateway selector (online + optional SnappPay / DigiPay installment methods).
 *
 * @var array $args { amount: int (Toman), theme?: 'light'|'dark' }
 */
if (!function_exists('nasim_available_payment_methods')) {
    return;
}

$amount  = isset($args['amount']) ? (int) $args['amount'] : 0;
$theme   = ($args['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
$methods = nasim_available_payment_methods($amount);

if (!$methods) {
    return;
}

if (1 === count($methods)) {
    printf('<input type="hidden" name="gateway" value="%s" />', esc_attr($methods[0]['id']));
    return;
}

$is_dark = 'dark' === $theme;
?>
<fieldset class="space-y-2 border-0 p-0 m-0" data-gateway-selector>
  <legend class="block text-xs font-extrabold mb-2 <?php echo $is_dark ? 'text-ink-200' : 'text-foreground'; ?>">
    روش پرداخت را انتخاب کن
  </legend>
  <div class="space-y-2">
    <?php foreach ($methods as $idx => $m) :
        $checked = 0 === $idx;
        $badge_cls = !empty($m['installment'])
            ? ($is_dark ? 'bg-mint-500/20 text-mint-200 border border-mint-400/30' : 'bg-mint-100 text-mint-700')
            : ($is_dark ? 'bg-violet-500/20 text-violet-200 border border-violet-400/30' : 'bg-violet-100 text-violet-800');
    ?>
    <label class="group flex items-start gap-3 rounded-2xl border p-3 cursor-pointer transition-all <?php echo $is_dark
        ? 'border-ink-700/80 bg-ink-950/60 hover:border-violet-400/60 has-[:checked]:border-violet-400 has-[:checked]:bg-ink-800/80'
        : 'border-border bg-sand/40 hover:border-violet-400 has-[:checked]:border-violet-600 has-[:checked]:bg-violet-50/70'; ?>">
      <input
        type="radio"
        name="gateway"
        value="<?php echo esc_attr($m['id']); ?>"
        <?php checked($checked); ?>
        class="mt-1 size-4 accent-violet-500 shrink-0 cursor-pointer"
      />
      <span class="flex-1 min-w-0">
        <span class="flex items-center justify-between gap-2">
          <strong class="text-xs sm:text-[13px] font-extrabold <?php echo $is_dark ? 'text-cream' : 'text-foreground'; ?>">
            <?php echo esc_html($m['label']); ?>
          </strong>
          <span class="shrink-0 rounded-full px-2 py-0.5 text-[10.5px] font-extrabold <?php echo esc_attr($badge_cls); ?>">
            <?php echo esc_html($m['badge']); ?>
          </span>
        </span>
        <span class="block mt-0.5 text-[11.5px] font-medium <?php echo $is_dark ? 'text-ink-300' : 'text-muted-foreground'; ?>">
          <?php echo esc_html($m['desc']); ?>
        </span>
      </span>
    </label>
    <?php endforeach; ?>
  </div>
</fieldset>
