<?php
// FASE 29 (22/11): PIXEL DE META en tarvo.com.py — audiencias para
// retargeting (pedido Milton "armamos una estrategia de mkt"). Pixel
// "Tarvo Web" id 561590873335645 (ex "Datos de Todo Market", renombrado,
// mismo portfolio). mu-plugin: PageView en todo el sitio + ViewContent
// con content_ids=[SKU TRV] en cada ficha → audiencias por producto y
// base para ads dinamicos Advantage+.
// Uso: php ~/bin/wp eval-file ~/fase29.php
$mu = WP_CONTENT_DIR . '/mu-plugins/tarvo-pixel.php';
$php = <<<'PHP'
<?php
// Tarvo (22/11): Pixel de Meta — audiencias y retargeting.
add_action('wp_head', function () {
    $id = '561590873335645';
    echo "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){"
       . "n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};"
       . "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];"
       . "t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];"
       . "s.parentNode.insertBefore(t,s)}(window,document,'script',"
       . "'https://connect.facebook.net/en_US/fbevents.js');"
       . "fbq('init','{$id}');fbq('track','PageView');";
    if (function_exists('is_product') && is_product()) {
        $p = wc_get_product(get_queried_object_id());
        if ($p) {
            $sku = esc_js($p->get_sku());
            $val = (float) $p->get_price();
            echo "fbq('track','ViewContent',{content_ids:['{$sku}'],"
               . "content_type:'product',value:{$val},currency:'PYG'});";
        }
    }
    echo "</script><noscript><img height=\"1\" width=\"1\" "
       . "style=\"display:none\" src=\"https://www.facebook.com/tr?id={$id}"
       . "&ev=PageView&noscript=1\"/></noscript>";
}, 20);
PHP;
file_put_contents($mu, $php);
echo "mu-plugin instalado: $mu\n";
echo "FASE29-OK\n";
