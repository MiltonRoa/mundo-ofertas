<?php
/**
 * Fase 19 — topbar responsive (28/08): en el celular se partía en 2 líneas
 * desalineadas. Ahora: móvil = solo "Delivery + WhatsApp" centrado en una
 * línea; desktop = la frase completa. Correr: php ~/bin/wp eval-file ~/fase19.php
 */
$html = '<span class="tb-a"><strong>🚚 Delivery en todo Paraguay</strong></span>'
      . '<span class="tb-b"> &nbsp;·&nbsp; ✈️ Traemos de USA en 2 a 3 semanas</span>'
      . '<span class="tb-c"> &nbsp;·&nbsp; 📱 WhatsApp 0992 805 800</span>';
set_theme_mod('topbar_left', $html);
echo "topbar_left actualizada\n";

$css = wp_get_custom_css();
if (strpos($css, 'fase19') === false) {
    $css .= "\n/* fase19: topbar prolija en movil */\n"
        . "@media (max-width: 599px){\n"
        . "  .tb-b{display:none}\n"
        . "  #top-bar .flex-row{justify-content:center}\n"
        . "  #top-bar .flex-left{margin:0 auto;text-align:center;font-size:.92em;white-space:nowrap}\n"
        . "}\n";
    wp_update_custom_css_post($css);
    echo "CSS responsive agregado\n";
} else {
    echo "CSS ya estaba\n";
}
wp_cache_flush();
echo "== FASE 19 LISTA ==\n";
