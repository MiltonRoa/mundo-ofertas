<?php
/**
 * Fase 20 (29/08, Milton):
 *  1. Topbar: el número de WhatsApp no se veía en el celular (iOS auto-linkea
 *     teléfonos con su color oscuro) → link explícito verde + CSS.
 *  2. Portada: FUERA el hero de presentación ("Comprá fácil...") — la página
 *     arranca con una franja finita + los PRODUCTOS ("Lo nuevo de hoy").
 * Correr: php ~/bin/wp eval-file ~/fase20.php
 */

echo "== 1. TOPBAR ==\n";
$html = '<span class="tb-a"><strong>🚚 Delivery en todo Paraguay</strong></span>'
      . '<span class="tb-b"> &nbsp;·&nbsp; ✈️ Traemos de USA en 2 a 3 semanas</span>'
      . '<span class="tb-c"> &nbsp;·&nbsp; 📱 <a href="https://wa.me/595992805800" '
      . 'style="color:#25D366;font-weight:700">WhatsApp 0992 805 800</a></span>';
set_theme_mod('topbar_left', $html);
$css = wp_get_custom_css();
if (strpos($css, 'fase20') === false) {
    $css .= "\n/* fase20: numero de whatsapp siempre visible en la topbar */\n"
        . "#top-bar a, #top-bar a[href^=\"tel\"]{color:#25D366!important;font-weight:700}\n";
    wp_update_custom_css_post($css);
    echo "CSS topbar agregado\n";
}
echo "topbar actualizada\n";

echo "== 2. PORTADA SIN HERO ==\n";
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;
if (strpos($c, 'tarvo-banner-slim') !== false) {
    echo "ya estaba aplicado\n";
} else {
    $pos = strpos($c, '[/section]');
    if ($pos === false) { echo "estructura inesperada — abortando\n"; return; }
    $hero = substr($c, 0, $pos + 10);
    echo "hero que se elimina (inicio): " . str_replace("\n", " ", substr($hero, 0, 120)) . "...\n";
    $resto = ltrim(substr($c, $pos + 10));
    $banner = '[section label="tarvo-banner-slim" bg_color="rgb(15,26,56)" padding="24px"]'
        . '[row][col span="12" align="center"]'
        . '<h1 style="color:#fff;font-size:1.7em;margin:0">Las mejores ofertas, todos los días</h1>'
        . '<p style="color:#aab6e0;margin:.35em 0 0">Marcas internacionales · '
        . 'Pedís por WhatsApp y te llega a tu puerta</p>'
        . '[/col][/row][/section]' . "\n";
    wp_update_post(['ID' => $fid, 'post_content' => $banner . $resto]);
    echo "portada actualizada: franja slim + productos arriba\n";
}
wp_cache_flush();
echo "== FASE 20 LISTA ==\n";
