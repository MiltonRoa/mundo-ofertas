<?php
// FASE 35 (24/11): METRICAS SIN RUIDO PROPIO (Milton "no son mis clicks
// los que suman tanto?"). Reescribe el mu-plugin tarvo-stats.php:
//   1. Respeta la cookie de autoexclusion tarvo_yo (se setea visitando
//      /r.php?yo=1 una vez por dispositivo — Milton y Claude).
//   2. Filtro de bots AMPLIADO: python/powershell/go-http/java/okhttp/
//      headless (las herramientas de verificacion de Claude contaban
//      como visitas).
// Uso: php ~/bin/wp eval-file ~/fase35.php
$mu = WP_CONTENT_DIR . '/mu-plugins/tarvo-stats.php';
$php = <<<'PHP'
<?php
// Tarvo (22/11, v2 24/11): contador de visitas server-side
// (wa_state/views.json). v2: cookie de autoexclusion tarvo_yo
// (se setea en /r.php?yo=1) + filtro de bots ampliado.
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()
        || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (!empty($_COOKIE['tarvo_yo'])) {   // Milton / Claude: no contar
        return;
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!$ua || preg_match('/bot|crawl|spider|slurp|curl|wget|facebook'
                           . 'externalhit|preview|monitor|python|'
                           . 'powershell|go-http|httpclient|java|'
                           . 'okhttp|headless/i', $ua)) {
        return;
    }
    $dir = ABSPATH . 'wa_state';
    if (!is_dir($dir)) { @mkdir($dir, 0700); }
    $h = @fopen($dir . '/views.json', 'c+');
    if (!$h || !flock($h, LOCK_EX)) { return; }
    $data = json_decode(stream_get_contents($h) ?: '{}', true) ?: array();
    $d = date('Y-m-d');
    if (!isset($data[$d])) { $data[$d] = array('pv' => 0, 'prod' => array()); }
    $data[$d]['pv']++;
    if (function_exists('is_product') && is_product()) {
        $p = wc_get_product(get_queried_object_id());
        $sku = $p ? $p->get_sku() : '';
        if ($sku) {
            $data[$d]['prod'][$sku] = ($data[$d]['prod'][$sku] ?? 0) + 1;
        }
    }
    foreach (array_keys($data) as $k) {   // poda 35 dias
        if ($k < date('Y-m-d', strtotime('-35 days'))) { unset($data[$k]); }
    }
    ftruncate($h, 0); rewind($h);
    fwrite($h, json_encode($data));
    flock($h, LOCK_UN); fclose($h);
}, 1);
PHP;
file_put_contents($mu, $php);
echo "mu-plugin v2 instalado: $mu\n";
echo "FASE35-OK\n";
