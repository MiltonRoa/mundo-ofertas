#!/bin/bash
# FASE 34 (23/11) — delivery FUERA del precio (ok Milton "si hacelo y
# ajustar los textos"): reemplaza los textos "delivery incluido" /
# "todo incluido" en TODA la web (portada, Cómo comprar, footer y las
# ~2.000 fichas de producto, que llevan el boilerplate en description).
# Idempotente. Uso: bash fase34.sh
cd ~/public_html || exit 1
WP=~/bin/wp
chmod +x $WP 2>/dev/null

$WP search-replace 'Entrega rápida con delivery incluido' 'Entrega rápida en todo Paraguay' --report-changed-only 2>/dev/null | tail -1
$WP search-replace 'Precio con delivery incluido (zona de cobertura y recargo a coordinar). Entrega rápida en Paraguay.' 'Entrega rápida en Paraguay — delivery a coordinar según tu zona.' --report-changed-only 2>/dev/null | tail -1
$WP search-replace 'Importado de USA. Precio final en guaraníes, todo incluido. Te lo traemos del exterior: llega en 2 a 3 semanas.' 'Importado de USA — importación y courier incluidos en el precio; delivery a coordinar según tu zona. Llega en 2 a 3 semanas.' --report-changed-only 2>/dev/null | tail -1
$WP search-replace 'precio final, todo incluido' 'importación y courier incluidos' --report-changed-only 2>/dev/null | tail -1
$WP search-replace 'con delivery incluido' 'con delivery a coordinar' --report-changed-only 2>/dev/null | tail -1
$WP search-replace 'Delivery incluido' 'Delivery a coordinar' --report-changed-only 2>/dev/null | tail -1
$WP search-replace 'delivery incluido' 'delivery a coordinar' --report-changed-only 2>/dev/null | tail -1
# botón de WhatsApp (mu-plugin) por si arma el mensaje con ese texto
sed -i 's/[Dd]elivery incluido/delivery a coordinar/g' wp-content/mu-plugins/*.php 2>/dev/null
$WP cache flush > /dev/null 2>&1
echo "FASE34-OK"
