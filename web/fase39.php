<?php
// FASE 39 (25/11, Milton "¿por qué algunos salen con la imagen grande y
// otros chica?" al pegar fichas en WhatsApp): WhatsApp muestra tarjeta
// GRANDE solo si og:image es >= 300x300 — las fotos Garibaldi vienen de
// 280px del PDF y caían a miniatura. FIX: tarvo-og.php v2 — para SKU
// MAY-* el og:image apunta a la versión 512px re-hosteada en el repo
// (gar_fotos512/<SKU>.jpg, generadas 25/11); el resto sigue igual.
// OJO: WhatsApp cachea previews unos días — links ya pegados pueden
// seguir chicos un tiempo.
// Uso: php ~/bin/wp --path=$HOME/public_html eval-file ~/fase39.php
$dir = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$code = <<<'PHP'
<?php
/* Tarvo: Open Graph en fichas de producto + botón compartir WhatsApp.
   mu-plugin: sobrevive a cambios de tema. v2 (fase39): og:image 512px
   para mayoristas (MAY-*) — la foto original de 280px hacía que
   WhatsApp mostrara la preview chica. */

// --- Open Graph: WhatsApp/Facebook/Telegram muestran la FOTO del producto ---
add_action('wp_head', function () {
    if (!function_exists('is_product') || !is_product()) return;
    global $post;
    $p = wc_get_product($post->ID);
    if (!$p) return;
    $title = wp_strip_all_tags($p->get_name());
    $desc  = wp_strip_all_tags($p->get_short_description() ?: $p->get_description());
    $desc  = mb_substr(trim(preg_replace('/\s+/u', ' ', $desc)), 0, 200);
    if ($desc === '') $desc = 'Compra gestionada por Tarvo — pedinos por WhatsApp.';
    $url = get_permalink($post->ID);
    $img = wp_get_attachment_image_url($p->get_image_id(), 'large');
    $w = 0; $h = 0;
    $sku = $p->get_sku();
    if ($sku && strpos($sku, 'MAY-') === 0) {
        $img = 'https://miltonroa.github.io/mundo-ofertas/gar_fotos512/'
             . rawurlencode($sku) . '.jpg';
        $w = 512; $h = 512;
    } elseif ($img) {
        $meta = wp_get_attachment_metadata($p->get_image_id());
        if (!empty($meta['width'])) { $w = $meta['width']; $h = $meta['height']; }
    }
    echo "\n" . '<meta property="og:type" content="product" />' . "\n";
    printf('<meta property="og:site_name" content="Tarvo" />' . "\n");
    printf('<meta property="og:title" content="%s" />' . "\n", esc_attr($title));
    printf('<meta property="og:description" content="%s" />' . "\n", esc_attr($desc));
    printf('<meta property="og:url" content="%s" />' . "\n", esc_url($url));
    if ($img) {
        printf('<meta property="og:image" content="%s" />' . "\n", esc_url($img));
        printf('<meta property="og:image:secure_url" content="%s" />' . "\n", esc_url($img));
        if ($w) {
            printf('<meta property="og:image:width" content="%d" />' . "\n", $w);
            printf('<meta property="og:image:height" content="%d" />' . "\n", $h);
        }
    }
    if ($p->get_price()) {
        printf('<meta property="product:price:amount" content="%s" />' . "\n", esc_attr($p->get_price()));
        echo '<meta property="product:price:currency" content="PYG" />' . "\n";
    }
}, 4);

// --- Botón "Compartir en WhatsApp" (debajo del botón Pedir) ---
add_action('woocommerce_single_product_summary', function () {
    global $post;
    $url   = get_permalink($post->ID);
    $texto = rawurlencode('Mirá este producto de Tarvo: ' . get_the_title($post->ID) . ' ' . $url);
    echo '<p class="tarvo-compartir"><a class="button" target="_blank" rel="noopener" '
        . 'style="background:#128C7E;color:#fff;width:100%;text-align:center" '
        . 'href="https://wa.me/?text=' . $texto . '">📤 Compartir en WhatsApp</a></p>';
}, 32);
PHP;

file_put_contents($dir . '/tarvo-og.php', $code);
echo "FASE39-OK tarvo-og v2 md5=" . substr(md5($code), 0, 10) . "\n";
