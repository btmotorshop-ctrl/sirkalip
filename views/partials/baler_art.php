<svg viewBox="0 0 600 300" class="<?= e($class ?? '') ?>" aria-hidden="true">
  <ellipse cx="300" cy="285" rx="270" ry="12" fill="#000" opacity=".35"/>
  <path d="M20 205 L150 185" stroke="#2b3240" stroke-width="10" stroke-linecap="round"/>
  <rect x="120" y="200" width="200" height="40" rx="12" fill="#b8040f"/>
  <?php for ($i = 0; $i < 14; $i++): ?><path d="M<?= 132 + $i * 13 ?> 238 l-4 18" stroke="#9ca3af" stroke-width="3" stroke-linecap="round"/><?php endfor; ?>
  <path d="M190 70 h330 a14 14 0 0 1 14 14 v140 h-344z" fill="#e30613"/>
  <path d="M190 70 h330 a14 14 0 0 1 14 14 v26 h-344z" fill="#f43f3f"/>
  <rect x="215" y="120" width="120" height="70" rx="6" fill="#b8040f"/>
  <rect x="360" y="120" width="150" height="16" rx="4" fill="#b8040f"/>
  <text x="375" y="165" fill="#fff" font-size="26" font-weight="900" font-family="Arial">SIR KALIP</text>
  <circle cx="205" cy="110" r="46" fill="#1f2530"/><circle cx="205" cy="110" r="30" fill="#e30613"/><circle cx="205" cy="110" r="8" fill="#1f2530"/>
  <rect x="520" y="130" width="60" height="70" rx="4" fill="#9a3412"/>
  <circle cx="430" cy="235" r="44" fill="#111"/><circle cx="430" cy="235" r="22" fill="#e30613"/><circle cx="430" cy="235" r="7" fill="#111"/>
</svg>
