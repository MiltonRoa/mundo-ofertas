<?php
/**
 * fase26 (22/11): SEO básico — pedido Milton "Tarvo no aparece en Google".
 * 1) Tagline con palabras clave (entra al <title> de la portada).
 * 2) mu-plugin tarvo-seo.php: meta description del sitio (WP pelado no
 *    emite ninguna) + og:site_name. No pisa el og de fichas (tarvo-og.php
 *    corre aparte y este plugin solo actúa donde NO hay producto).
 * Correr con: wp eval-file fase26.php
 */

update_option('blogdescription',
    'Tienda online en Paraguay — Tecnología, Hogar e Importados de USA con delivery');

$mu = WPMU_PLUGIN_DIR . '/tarvo-seo.php';
if (!is_dir(WPMU_PLUGIN_DIR)) { mkdir(WPMU_PLUGIN_DIR, 0755, true); }
file_put_contents($mu, <<<'PHP'
<?php
/* Plugin Name: Tarvo SEO basico (fase26) */
add_action('wp_head', function () {
    if (is_singular('product')) return; // tarvo-og.php maneja las fichas
    $desc = 'Tarvo: tienda online en Paraguay. Tecnología, hogar y cocina, '
          . 'deportes e importados de USA con precio final en guaraníes y '
          . 'delivery. Pedidos por WhatsApp 0992 805 800.';
    if (is_front_page()) {
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    } elseif (is_product_category()) {
        $t = single_term_title('', false);
        echo '<meta name="description" content="' . esc_attr(
            $t . ' al mejor precio en Paraguay. Delivery incluido. Tarvo — '
            . 'compra gestionada, pedidos por WhatsApp.') . '">' . "\n";
    }
    echo '<meta property="og:site_name" content="Tarvo">' . "\n";
}, 4);
PHP
);
echo "fase26 OK: tagline + mu-plugin tarvo-seo.php\n";
echo "title portada ahora: " . get_option('blogname') . " - " . get_option('blogdescription') . "\n";
