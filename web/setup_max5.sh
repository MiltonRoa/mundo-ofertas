#!/bin/bash
# Fase 5: pulido móvil (widgets basura + teléfono nowrap) + salto a HTTPS si el cert ya está
set -uo pipefail
export PATH=$HOME/bin:$PATH
WP="wp --path=$HOME/public_html"

echo "== widgets de blog fuera de sidebar-main =="
for wid in $($WP widget list sidebar-main --field=id 2>/dev/null); do
  $WP widget delete "$wid" && echo "  borrado: $wid"
done

echo "== footer con teléfono nowrap =="
$WP theme mod set footer_left_text '© 2026 <strong>Tarvo</strong> — Compra gestionada en Paraguay · <span style="white-space:nowrap">📲 <a href="https://wa.me/595992805800">0992 805 800</a></span>' >/dev/null && echo "  footer ok"

echo "== template blank en Inicio (re-asegurar) =="
INICIO=$($WP post list --post_type=page --name=inicio --field=ID | head -1)
$WP post meta update "$INICIO" _wp_page_template page-blank.php >/dev/null && echo "  inicio=$INICIO blank ok"

echo "== ¿certificado HTTPS listo? =="
if curl --fail --silent --max-time 15 https://tarvo.com.py/ -o /dev/null; then
  echo "  cert VALIDO — migrando a https..."
  $WP search-replace 'http://tarvo.com.py' 'https://tarvo.com.py' --skip-columns=guid --report-changed-only | tail -3
  $WP rewrite flush >/dev/null 2>&1 || true
  $WP cache flush >/dev/null 2>&1 || true
  echo "  WEB EN HTTPS ✅ (avisale a Claude para que actualice el sincronizador)"
else
  echo "  cert aun no emitido — la web sigue en http; correr este script de nuevo mas tarde"
fi
echo "== FIN FASE 5 =="
