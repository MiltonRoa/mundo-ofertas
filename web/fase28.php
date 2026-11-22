<?php
// FASE 28 (22/11): DIETA DE TAMANOS DE IMAGEN. El disco (3GB) esta lleno
// con 2.7G en uploads/2026 y CERO huerfanos: son las ~8 copias por foto
// que WP genera (768/1024/1536/2048/large/medium_large) y la tienda no
// usa. Se instala mu-plugin que deja solo thumbnail/medium/los 3 de Woo
// y se desactivan large y medium_large; despues se corre
// wp media regenerate (borra las copias viejas y crea solo las nuevas).
// Uso: php ~/bin/wp eval-file ~/fase28.php
$mu = WP_CONTENT_DIR . '/mu-plugins/tarvo-media.php';
$php = <<<'PHP'
<?php
// Tarvo (22/11, disco lleno): solo los tamanos que la web usa.
add_filter('intermediate_image_sizes_advanced', function ($s) {
    $keep = array('thumbnail', 'medium', 'woocommerce_thumbnail',
                  'woocommerce_single', 'woocommerce_gallery_thumbnail');
    foreach (array_keys($s) as $k) {
        if (!in_array($k, $keep, true)) { unset($s[$k]); }
    }
    return $s;
});
// sin copia "-scaled" de 2560px
add_filter('big_image_size_threshold', '__return_false');
PHP;
file_put_contents($mu, $php);
echo "mu-plugin instalado: $mu\n";
update_option('medium_large_size_w', 0);
update_option('medium_large_size_h', 0);
update_option('large_size_w', 0);
update_option('large_size_h', 0);
echo "large y medium_large desactivados\n";
echo "FASE28-OK (ahora correr wp media regenerate)\n";
