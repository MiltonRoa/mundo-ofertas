<?php
// FASE 30 (22/11): ESTADISTICAS PROPIAS — pedido Milton "ver un informe
// estandar de cuantas personas entran, que productos, cuantos clicks".
// mu-plugin tarvo-stats.php: cuenta server-side cada visita real del
// front (filtra bots) en wa_state/views.json {dia: {pv: n, prod:
// {TRV-X: n}}}, poda 35 dias. El EC2 lo lee via r.php (ver full=1)
// y manda el reporte diario por Telegram (reporte.py).
// Uso: php ~/bin/wp eval-file ~/fase30.php
$mu = WP_CONTENT_DIR . '/mu-plugins/tarvo-stats.php';
$php = <<<'PHP'
<?php
// Tarvo (22/11): contador de visitas server-side (wa_state/views.json).
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()
        || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!$ua || preg_match('/bot|crawl|spider|slurp|curl|wget|facebook'
                           . 'externalhit|preview|monitor/i', $ua)) {
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
echo "mu-plugin instalado: $mu\n";
echo "FASE30-OK\n";
