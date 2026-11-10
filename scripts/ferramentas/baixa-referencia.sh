#!/usr/bin/env bash
# Baixa os templates, dados e CSS da página de referência (SPA AngularJS) para uma pasta local.
# Uso: scripts/ferramentas/baixa-referencia.sh <pasta-destino> [url-base]
set -euo pipefail

DEST="${1:?informe a pasta de destino}"
BASE="${2:-http://10.0.0.104:8080}"

mkdir -p "$DEST"/pages "$DEST"/assets/data "$DEST"/assets/css/pages
curl -fsS "$BASE/" -o "$DEST/index.html"
curl -fsS "$BASE/app.js" -o "$DEST/app.js"
curl -fsS "$BASE/assets/js/carousel.js" -o "$DEST/assets/carousel.js"

for p in home institucional biografia legislacao noticias noticia-detalhe pesquisa-precos ranking \
         transparencia contatos acessibilidade mapa-do-site; do
  curl -fsS "$BASE/pages/$p.html" -o "$DEST/pages/$p.html"
done
for d in noticias ranking; do
  curl -fsS "$BASE/assets/data/$d.json" -o "$DEST/assets/data/$d.json"
done
for c in variables global header carousel footer pages/home pages/inner-pages; do
  curl -fsS "$BASE/assets/css/$c.css" -o "$DEST/assets/css/$c.css"
done
echo "Referência salva em $DEST"
