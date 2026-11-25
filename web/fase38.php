<?php
// FASE 38 (25/11, Milton: catálogo mayorista Garibaldi, mínimo Gs 1M):
// SECCIÓN MAYORISTA en la web.
// (1) mu-plugin tarvo-mayorista.php:
//     · los productos Mayorista NO aparecen en /tienda/, búsqueda,
//       portada (shortcodes) ni rubros retail — solo en su sección;
//     · aviso de pedido mínimo en cada ficha mayorista;
//     · mínimo combinado Gs 1.000.000 exigido en carrito/checkout.
// (2) item "Mayorista" en el menú Principal (dropdown propio con sus
//     subcategorías — fuera del dropdown Categorías; fase36 ya lo saltea).
// Correr DESPUÉS de que gar_sync.py creó las categorías en Woo.
// Uso: php ~/bin/wp --path=$HOME/public_html eval-file ~/fase38.php
$mu = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($mu)) { mkdir($mu, 0755, true); }
$code = <<<'PHP'
<?php
// tarvo-mayorista.php (fase38, 25/11) — sección de venta al por mayor.
// Los productos Mayorista viven SOLO en su sección (fuera de tienda,
// búsqueda, portada y rubros retail) y el carrito exige el pedido
// mínimo combinado de Gs 1.000.000 entre productos de la sección.

function tarvo_may_term() {
    static $id = null;
    if ($id === null) {
        $t = get_term_by('name', 'Mayorista', 'product_cat');
        $id = $t ? (int) $t->term_id : 0;
    }
    return $id;
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

// 3) fuera de los [ux_products] de la portada (usan el shortcode WC)
add_filter('woocommerce_shortcode_products_query', function ($args) {
    if (!tarvo_may_term()) { return $args; }
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

// (2) menú: "Mayorista" como dropdown propio en Principal
$salida = 'menu=skip';
$menu = wp_get_nav_menu_object('Principal');
$may = get_term_by('name', 'Mayorista', 'product_cat');
if ($menu && $may) {
    $items = wp_get_nav_menu_items($menu->term_id);
    $por_term = array();
    foreach ($items as $it) {
        if ($it->object === 'product_cat') {
            $por_term[(int) $it->object_id] = $it;
        }
    }
    if (isset($por_term[$may->term_id])) {
        $padre = $por_term[$may->term_id]->db_id;
    } else {
        $padre = wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-type' => 'taxonomy',
            'menu-item-object' => 'product_cat',
            'menu-item-object-id' => $may->term_id,
            'menu-item-title' => 'Mayorista',
            'menu-item-status' => 'publish'));
    }
    $n = 0;
    $hijos = get_terms(array('taxonomy' => 'product_cat',
                             'parent' => $may->term_id,
                             'hide_empty' => false));
    foreach ($hijos as $h) {
        if (isset($por_term[$h->term_id])) { continue; }
        $r = wp_update_nav_menu_item($menu->term_id, 0, array(
            'menu-item-type' => 'taxonomy',
            'menu-item-object' => 'product_cat',
            'menu-item-object-id' => $h->term_id,
            'menu-item-parent-id' => $padre,
            'menu-item-title' => $h->name,
            'menu-item-status' => 'publish'));
        if (!is_wp_error($r)) { $n++; }
    }
    $salida = "menu=ok subcats=$n";
} elseif (!$may) {
    $salida = 'menu=SIN-CATEGORIA (correr gar_sync primero y repetir)';
}
echo "FASE38-OK $salida mu="
    . substr(md5_file($mu . '/tarvo-mayorista.php'), 0, 10) . "\n";
