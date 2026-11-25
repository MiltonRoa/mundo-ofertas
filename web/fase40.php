<?php
// FASE 40 (25/11, Milton "en la portada poné la sección mayorista, con
// un fondo diferente para destacar"):
// (1) tarvo-mayorista.php v2: el filtro de shortcodes deja pasar los
//     productos Mayorista cuando el shortcode los pide EXPLÍCITAMENTE
//     (sin esto la banda nueva saldría vacía — la v1 los excluía de
//     todos los [ux_products]).
// (2) Portada Inicio: banda "Sección Mayorista" en AZUL ELÉCTRICO
//     #2445ff (fondo distinto de la franja navy y de las bandas
//     blancas/grises), entre la franja de confianza y Tecnología:
//     título + bajada con el mínimo + 10 productos + botón.
// Uso: php ~/bin/wp --path=$HOME/public_html eval-file ~/fase40.php
$mu = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($mu)) { mkdir($mu, 0755, true); }
$code = <<<'PHP'
<?php
// tarvo-mayorista.php (fase38 v1, fase40 v2) — sección de venta al por
// mayor. Los productos Mayorista viven SOLO en su sección (fuera de
// tienda, búsqueda, portada y rubros retail), SALVO que un shortcode
// los pida explícitamente (banda de portada, fase40). El carrito exige
// el pedido mínimo combinado de Gs 1.000.000.

function tarvo_may_term() {
    static $id = null;
    if ($id === null) {
        $t = get_term_by('name', 'Mayorista', 'product_cat');
        $id = $t ? (int) $t->term_id : 0;
    }
    return $id;
}

function tarvo_may_familia() {
    static $fam = null;
    if ($fam === null) {
        $fam = array('ids' => array(), 'slugs' => array());
        $may = tarvo_may_term();
        if ($may) {
            $ids = array_merge(array($may),
                               (array) get_term_children($may, 'product_cat'));
            foreach ($ids as $tid) {
                $t = get_term($tid, 'product_cat');
                if ($t && !is_wp_error($t)) {
                    $fam['ids'][] = (int) $t->term_id;
                    $fam['slugs'][] = $t->slug;
                }
            }
        }
    }
    return $fam;
}

function tarvo_may_es($product_id) {
    $may = tarvo_may_term();
    if (!$may) { return false; }
    $terms = wp_get_post_terms($product_id, 'product_cat',
                               array('fields' => 'ids'));
    if (is_wp_error($terms)) { return false; }
    foreach ($terms as $t) {
        if ((int) $t === $may
                || in_array($may, get_ancestors($t, 'product_cat'))) {
            return true;
        }
    }
    return false;
}

function tarvo_may_notin($tax) {
    $tax = is_array($tax) ? $tax : array();
    $tax[] = array('taxonomy' => 'product_cat', 'field' => 'term_id',
                   'terms' => array(tarvo_may_term()),
                   'operator' => 'NOT IN', 'include_children' => true);
    return $tax;
}

// 1) fuera de /tienda/ y de los archivos de rubro retail
add_action('woocommerce_product_query', function ($q) {
    if (!tarvo_may_term()) { return; }
    if ($q->is_tax('product_cat')) {
        $slug = $q->get('product_cat');
        if ($slug) {
            $partes = explode('/', $slug);
            $term = get_term_by('slug', end($partes), 'product_cat');
            if ($term && ($term->term_id == tarvo_may_term()
                    || in_array(tarvo_may_term(),
                                get_ancestors($term->term_id, 'product_cat')))) {
                return; // la propia sección Mayorista se muestra normal
            }
        }
    }
    $q->set('tax_query', tarvo_may_notin($q->get('tax_query')));
});

// 2) fuera de la búsqueda del sitio
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query() || !$q->is_search()
            || !tarvo_may_term()) {
        return;
    }
    $q->set('tax_query', tarvo_may_notin($q->get('tax_query')));
});

// 3) shortcodes ([ux_products]): fuera, SALVO pedido explícito de la
//    familia Mayorista (v2, fase40 — la banda de portada los muestra)
add_filter('woocommerce_shortcode_products_query', function ($args) {
    if (!tarvo_may_term()) { return $args; }
    $fam = tarvo_may_familia();
    if (!empty($args['tax_query']) && is_array($args['tax_query'])) {
        foreach ($args['tax_query'] as $cl) {
            if (!is_array($cl)
                    || (isset($cl['taxonomy']) && $cl['taxonomy'] !== 'product_cat')) {
                continue;
            }
            $op = isset($cl['operator']) ? strtoupper($cl['operator']) : 'IN';
            if ($op === 'NOT IN') { continue; }
            $terms = isset($cl['terms']) ? (array) $cl['terms'] : array();
            foreach ($terms as $t) {
                if (in_array($t, $fam['ids']) || in_array($t, $fam['slugs'])) {
                    return $args; // lo pidieron a propósito: no excluir
                }
            }
        }
    }
    $args['tax_query'] = tarvo_may_notin(
        isset($args['tax_query']) ? $args['tax_query'] : array());
    return $args;
});

// 4) aviso de pedido mínimo en la ficha (entre precio y descripción)
add_action('woocommerce_single_product_summary', function () {
    global $product;
    if (!$product || !tarvo_may_es($product->get_id())) { return; }
    $link = get_term_link(tarvo_may_term(), 'product_cat');
    echo '<div style="background:#eef2ff;border:1px solid #2445ff;'
       . 'border-radius:8px;padding:10px 14px;margin:10px 0;'
       . 'font-size:.92em;line-height:1.45">🏷️ <strong>Venta mayorista'
       . '</strong> — pedido mínimo combinado <strong>Gs 1.000.000'
       . '</strong> en productos de la sección '
       . '<a href="' . esc_url($link) . '">Mayorista</a> '
       . '(combiná los artículos que quieras).</div>';
}, 15);

// 5) mínimo combinado en carrito/checkout
add_action('woocommerce_check_cart_items', function () {
    if (!function_exists('WC') || !WC()->cart) { return; }
    $suma = 0;
    foreach (WC()->cart->get_cart() as $item) {
        if (tarvo_may_es($item['product_id'])) {
            $suma += (float) $item['data']->get_price()
                     * (int) $item['quantity'];
        }
    }
    if ($suma > 0 && $suma < 1000000) {
        $falta = number_format(1000000 - $suma, 0, ',', '.');
        wc_add_notice('Pedido mayorista: el mínimo combinado en productos '
            . 'de la sección Mayorista es Gs 1.000.000 — te faltan '
            . 'Gs ' . $falta . '. Podés combinar distintos artículos '
            . 'de la sección para llegar.', 'error');
    }
});
PHP;
file_put_contents($mu . '/tarvo-mayorista.php', $code);
$mu_md5 = substr(md5_file($mu . '/tarvo-mayorista.php'), 0, 10);

// (2) banda Mayorista en la portada (la página configurada como front)
$pg = get_post((int) get_option('page_on_front'));
if (!$pg) { $pg = get_page_by_path('inicio'); }
if (!$pg) { echo "FASE40-ERROR: no encuentro la portada\n"; return; }
echo "portada: {$pg->ID} ({$pg->post_title})\n";
$c = $pg->post_content;
$may = get_term_by('name', 'Mayorista', 'product_cat');
if (!$may) { echo "FASE40-ERROR: sin categoria Mayorista\n"; return; }

if (strpos($c, 'Ver toda la sección Mayorista') !== false) {
    echo "FASE40-OK banda=ya-estaba mu=$mu_md5\n";
    return;
}
$banda = '[section bg_color="rgb(36,69,255)" padding="46px" dark="true"]'
    . '[title style="center" text="🏪 Nuevo · Sección Mayorista" size="120"]'
    . '[row h_align="center"][col span="10" align="center"]'
    . '<p style="color:#fff;font-size:1.06em;margin:0 0 4px">Precios al '
    . 'por mayor <strong>por unidad</strong>, ideales para despensas, '
    . 'bazares y revendedores. Pedido mínimo combinado '
    . '<strong>Gs 1.000.000</strong> — combiná los artículos que quieras.'
    . '</p>[/col][/row]'
    . '[ux_products style="normal" columns="5" columns__md="3" '
    . 'products="10" cat="' . $may->term_id . '" orderby="date" '
    . 'show_rating="false"]'
    . '[row h_align="center"][col span="12" align="center"]'
    . '[button text="Ver toda la sección Mayorista" color="white" '
    . 'style="outline" size="large" '
    . 'link="/categoria-producto/mayorista/"][/col][/row]'
    . '[/section]' . "\n";

$ancla = '[section bg_color="rgb(247,248,252)" padding="42px"]'
       . '[title style="center" text="💻 Tecnología"';
$pos = strpos($c, $ancla);
if ($pos === false) {
    // plan B: antes de "Descubrí Tarvo"
    $ancla2 = '[title style="center" text="✨ Descubrí Tarvo"';
    $pos = strpos($c, $ancla2);
    if ($pos === false) { echo "FASE40-ERROR: sin ancla en Inicio\n"; return; }
    $ini = strrpos(substr($c, 0, $pos), '[section');
    $pos = ($ini === false) ? $pos : $ini;
    echo "aviso: ancla Tecnología no encontrada, uso Descubrí Tarvo\n";
}
$c = substr($c, 0, $pos) . $banda . substr($c, $pos);
wp_update_post(array('ID' => $pg->ID, 'post_content' => $c));
echo "FASE40-OK banda=insertada mu=$mu_md5\n";
