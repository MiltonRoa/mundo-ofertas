<?php
/**
 * Fase 15 — Tarvo web (28/08, pedidos de Milton):
 *   1. FIX tienda vacía (diagnóstico + display de productos)
 *   2. Topbar nueva (fuera "Compra gestionada por Tarvo" como primer texto)
 *   3. Renombrar categorías VISIBLES: Nacionales → "Entrega rápida",
 *      Importados → "Importados de USA" (los slugs quedan: los usa woo_sync)
 *   4. Portada: copy sin "nacionales" + carrusel de productos tras el hero
 *   5. Franja informativa y footer con el mismo criterio
 * Correr: php ~/bin/wp eval-file ~/fase15.php  (desde ~/public_html)
 */

function tarvo_ren($s) {
    // frase del hero primero, después el resto (solo con mayúscula: los
    // slugs/URLs en minúscula NO se tocan)
    $s = str_replace('Productos nacionales e importados de USA',
                     'Miles de productos de marcas internacionales', $s);
    $s = str_replace('Importados de USA', '__IUSA__', $s);
    $s = str_replace('Importados', '__IUSA__', $s);
    $s = str_replace('Nacionales', 'Entrega rápida', $s);
    return str_replace('__IUSA__', 'Importados de USA', $s);
}

echo "== 1. DIAG TIENDA ==\n";
$shop = wc_get_page_id('shop');
echo "shop_page_id=$shop status=" . get_post_status($shop)
    . " slug=" . get_post_field('post_name', $shop)
    . " tpl=" . var_export(get_post_meta($shop, '_wp_page_template', true), true) . "\n";
echo "opt shop_page_display=" . var_export(get_option('woocommerce_shop_page_display'), true)
    . " cat_archive=" . var_export(get_option('woocommerce_category_archive_display'), true) . "\n";
echo "mod shop_page_display=" . var_export(get_theme_mod('shop_page_display'), true) . "\n";
$vis = get_term_by('slug', 'exclude-from-catalog', 'product_visibility');
$pubs = wp_count_posts('product');
echo "productos publish={$pubs->publish} exclude-from-catalog=" . ($vis ? $vis->count : 0) . "\n";

update_option('woocommerce_shop_page_display', '');   // '' = mostrar productos
remove_theme_mod('shop_page_display');
$tpl = get_post_meta($shop, '_wp_page_template', true);
if ($tpl && $tpl !== 'default') {
    update_post_meta($shop, '_wp_page_template', 'default');
    echo "template de tienda vuelto a default (era $tpl)\n";
}
echo "fix tienda aplicado\n";

echo "== 2/5. TOPBAR + MODS ==\n";
$mods = get_theme_mods();
foreach ($mods as $k => $v) {
    if (!is_string($v) || $v === '') continue;
    $orig = $v;
    if (strpos($v, 'Compra gestionada por Tarvo') !== false) {
        $v = '<strong>🚚 Delivery en todo Paraguay</strong> &nbsp;·&nbsp; '
           . '✈️ Traemos de USA en 2 a 3 semanas &nbsp;·&nbsp; '
           . '📱 WhatsApp 0992 805 800';
        echo "topbar reescrita (mod: $k)\n";
    } else {
        $v = tarvo_ren($v);
    }
    if ($v !== $orig) { set_theme_mod($k, $v); echo "mod $k actualizado\n"; }
}

echo "== 3. CATEGORIAS ==\n";
foreach ([['nacionales', 'Entrega rápida'], ['importados', 'Importados de USA']] as $par) {
    $t = get_term_by('slug', $par[0], 'product_cat');
    if ($t && $t->name !== $par[1]) {
        wp_update_term($t->term_id, 'product_cat', ['name' => $par[1]]);
        echo "cat {$par[0]} -> {$par[1]}\n";
    } else {
        echo "cat {$par[0]}: " . ($t ? 'ya estaba' : 'NO EXISTE') . "\n";
    }
}
$items = get_posts(['post_type' => 'nav_menu_item', 'numberposts' => -1]);
foreach ($items as $it) {
    if (in_array($it->post_title, ['Nacionales', 'Importados'])) {
        $nuevo = $it->post_title === 'Nacionales' ? 'Entrega rápida' : 'Importados de USA';
        wp_update_post(['ID' => $it->ID, 'post_title' => $nuevo]);
        echo "menu item {$it->ID}: {$nuevo}\n";
    }
}

echo "== 4. PORTADA ==\n";
$fid = (int) get_option('page_on_front');
$p = get_post($fid);
if ($p) {
    $c = tarvo_ren($p->post_content);
    if (strpos($c, 'tarvo-ofertas-hoy') === false) {
        $bloque = "\n" . '[section label="tarvo-ofertas-hoy" bg_color="rgb(255,255,255)" padding="30px"]'
            . '[title style="center" text="⚡ Lo nuevo de hoy" size="130"]'
            . '[ux_products style="normal" type="slider" columns="5" columns__md="3" '
            . 'slider_nav_style="simple" slider_bullets="true" products="18" '
            . 'orderby="date" order="desc" show_rating="false"]'
            . '[/section]' . "\n";
        $pos = strpos($c, '[/section]');
        if ($pos !== false) {
            $c = substr($c, 0, $pos + 10) . $bloque . substr($c, $pos + 10);
            echo "carrusel de productos insertado tras el hero\n";
        } else {
            $c = $bloque . $c;
            echo "carrusel insertado al inicio (no encontré [/section])\n";
        }
    }
    if ($c !== $p->post_content) {
        wp_update_post(['ID' => $fid, 'post_content' => $c]);
        echo "portada $fid actualizada\n";
    } else {
        echo "portada sin cambios\n";
    }
} else {
    echo "NO ENCONTRE page_on_front\n";
}

echo "== 5. FOOTER ==\n";
$wh = get_option('widget_custom_html');
$ch = false;
if (is_array($wh)) {
    foreach ($wh as $i => $w) {
        if (is_array($w) && isset($w['content'])) {
            $n = tarvo_ren($w['content']);
            if ($n !== $w['content']) { $wh[$i]['content'] = $n; $ch = true; }
        }
    }
}
if ($ch) { update_option('widget_custom_html', $wh); echo "footer actualizado\n"; }
else { echo "footer sin cambios\n"; }

wp_cache_flush();
echo "== FASE 15 LISTA ==\n";
