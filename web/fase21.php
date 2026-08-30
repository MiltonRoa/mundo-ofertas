<?php
/**
 * Fase 21 (29/08, Milton "algo grande con destacados, fondo de otro color,
 * opciones en verticales, más pro"): HERO DE PRODUCTOS — sección navy grande:
 * izquierda slider de tarjetas VERTICALES altas con overlay (destacados),
 * derecha panel blanco con lista vertical de recién llegados. Reemplaza a la
 * franja slim (fase20) y absorbe a "Lo nuevo de hoy" (se elimina el duplicado).
 * Correr: php ~/bin/wp eval-file ~/fase21.php
 */
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;

function quitar_seccion($c, $label) {
    $i = strpos($c, '[section label="' . $label . '"');
    if ($i === false) return [$c, false];
    $j = strpos($c, '[/section]', $i);
    if ($j === false) return [$c, false];
    return [substr($c, 0, $i) . substr($c, $j + 10), true];
}

$hero = '[section label="tarvo-hero-destacados" bg_color="rgb(15,26,56)" padding="36px"]'
    . '[row]'
    . '[col span="8" span__md="12"]'
    . '<h1 style="color:#fff;font-size:1.9em;margin:0 0 .5em">🔥 Destacados de hoy</h1>'
    . '[ux_products style="shade" type="slider" depth="2" slider_nav_style="simple" '
    . 'slider_nav_color="light" slider_bullets="true" columns="3" columns__md="2" '
    . 'columns__sm="2" products="9" image_height="330px" image_size="medium" '
    . 'orderby="rand" show_rating="false" text_align="left"]'
    . '[/col]'
    . '[col span="4" span__md="12" bg_color="rgb(255,255,255)" bg_radius="10" '
    . 'padding="20px" depth="2"]'
    . '<h3 style="margin:0 0 .6em">⚡ Recién llegados</h3>'
    . '[ux_products style="row" col_spacing="xsmall" columns="1" products="4" '
    . 'image_width="28" orderby="date" order="desc" show_rating="false" '
    . 'show_add_to_cart="false"]'
    . '[button text="Ver toda la tienda" color="primary" style="outline" '
    . 'size="small" expand="true" link="/tienda/"]'
    . '[/col]'
    . '[/row][/section]' . "\n";

list($c, $ok1) = quitar_seccion($c, 'tarvo-banner-slim');
echo "franja slim: " . ($ok1 ? 'eliminada' : 'NO ENCONTRADA') . "\n";
list($c, $ok2) = quitar_seccion($c, 'tarvo-ofertas-hoy');
echo "lo-nuevo-de-hoy (duplicado): " . ($ok2 ? 'absorbido por el hero' : 'no estaba') . "\n";
$c = $hero . ltrim($c);
wp_update_post(['ID' => $fid, 'post_content' => $c]);
echo "hero de destacados instalado\n";

$css = wp_get_custom_css();
if (strpos($css, 'fase21') === false) {
    $css .= "\n/* fase21: hero destacados */\n"
        . ".flickity-prev-next-button svg{filter:drop-shadow(0 1px 2px rgba(0,0,0,.4))}\n";
    wp_update_custom_css_post($css);
}
wp_cache_flush();
echo "== FASE 21 LISTA ==\n";
