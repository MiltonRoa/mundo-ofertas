<?php
/**
 * Fase 21b (30/08): hero de destacados v2 — fondo CLARO amigable, título
 * centrado, columnas alineadas al medio y lista "Recién llegados" en modo
 * fila (mostraba 1 solo: quedó como slider de a uno).
 * Correr: php ~/bin/wp eval-file ~/fase21b.php
 */
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
$c = $p->post_content;

$i = strpos($c, '[section label="tarvo-hero-destacados"');
$j = ($i !== false) ? strpos($c, '[/section]', $i) : false;
if ($i === false || $j === false) { echo "no encontré el hero — abortando\n"; return; }

$hero = '[section label="tarvo-hero-destacados" bg_color="rgb(238,243,255)" padding="34px"]'
    . '[row h_align="center"][col span="12" align="center"]'
    . '<h1 style="color:#0f1a38;font-size:2em;margin:0 0 .2em">🔥 Destacados de hoy</h1>'
    . '<p style="color:#5a668c;margin:0 0 .8em">Una selección distinta en cada visita</p>'
    . '[/col][/row]'
    . '[row v_align="middle" h_align="center"]'
    . '[col span="8" span__md="12"]'
    . '[ux_products style="shade" type="slider" depth="2" slider_nav_style="simple" '
    . 'slider_bullets="true" columns="3" columns__md="2" columns__sm="2" '
    . 'products="9" image_height="330px" image_size="medium" orderby="rand" '
    . 'show_rating="false" text_align="left"]'
    . '[/col]'
    . '[col span="4" span__md="12" bg_color="rgb(255,255,255)" bg_radius="10" '
    . 'padding="20px" depth="2"]'
    . '<h3 style="margin:0 0 .6em;color:#0f1a38">⚡ Recién llegados</h3>'
    . '[ux_products style="row" type="row" col_spacing="xsmall" columns="1" '
    . 'products="4" image_width="28" orderby="date" order="desc" '
    . 'show_rating="false" show_add_to_cart="false"]'
    . '[button text="Ver toda la tienda" color="primary" style="outline" '
    . 'size="small" expand="true" link="/tienda/"]'
    . '[/col]'
    . '[/row][/section]';

$c = substr($c, 0, $i) . $hero . substr($c, $j + 10);
wp_update_post(['ID' => $fid, 'post_content' => $c]);
wp_cache_flush();
echo "hero v2 aplicado (fondo claro, centrado, lista en fila)\n";
echo "== FASE 21b LISTA ==\n";
