<?php
// Fase 8 Tarvo — tipografía unificada (Poppins, como el logo) + jerarquía de títulos.
// wp eval-file fase8.php (idempotente por marcador)
$css = wp_get_custom_css();
if (strpos($css, 'Tarvo — tipografía') !== false) { echo "ya aplicado\n"; echo "FIN FASE 8 OK\n"; return; }

$import = "@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');\n";
// quitar un @import previo duplicado si lo hubiera
$css = preg_replace('/^@import[^;]+;\n?/', '', $css);

$typo = <<<CSS

/* Tarvo — tipografía */
body, .nav, .button, input, select, textarea, .price {
  font-family: 'Poppins', system-ui, -apple-system, sans-serif;
}
h1, h2, h3, h4, h5, .widget-title, .cart-item, .header-nav {
  font-family: 'Poppins', system-ui, -apple-system, sans-serif;
}
h1 { font-weight: 800; }
h2 { font-weight: 700; font-size: 1.9em; letter-spacing: -0.02em; }
h3 { font-weight: 700; }
.section h3 { font-size: 1.5em; }
h4 { font-weight: 600; }
.banner h4 { font-size: 1.25em; font-weight: 700; }
.header-nav .nav > li > a { font-size: 15px; font-weight: 600; }
.product-small .title { font-weight: 500; line-height: 1.35; }
.price, .product-small .price { font-weight: 700; }
.widget-title { font-size: 1.05em; font-weight: 700; }
@media (max-width: 549px) {
  h2 { font-size: 1.55em; }
  .section h3 { font-size: 1.35em; }
  .banner h4 { font-size: 1.1em; }
}
CSS;

wp_update_custom_css_post($import . $css . $typo);
echo "tipografia aplicada\n";
echo "FIN FASE 8 OK\n";
