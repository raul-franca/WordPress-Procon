#!/usr/bin/env bash
#
# baixar-imagens.sh — Baixa TODAS as imagens do site de referência do PROCON PE.
#
# Uso:
#   ./scripts/baixar-imagens.sh [BASE_URL] [DESTINO]
#
# Padrões:
#   BASE_URL = http://10.0.0.104:8080
#   DESTINO  = <raiz-do-repo>/imagens-site
#
# O que faz:
#   1. Descobre imagens internas (servidas pelo host) varrendo index.html, CSS,
#      JS, templates em /pages e JSONs em /assets/data.
#   2. Descobre imagens externas (ex.: Unsplash) referenciadas nesses arquivos.
#   3. Baixa as internas em  $DESTINO/servidor/<caminho>  preservando a estrutura.
#   4. Baixa as externas em  $DESTINO/externas/.
#   5. Gera $DESTINO/manifest.tsv com origem -> arquivo local -> status.
#
# Observação: o servidor de referência é instável em caminhos inexistentes
# (algumas rotas ficam penduradas até o timeout). Por isso usamos --max-time.
#
set -uo pipefail

BASE="${1:-http://10.0.0.104:8080}"
BASE="${BASE%/}"
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${2:-$BASE_DIR/imagens-site}"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "BASE    = $BASE"
echo "DESTINO = $OUT"
echo

# ---------------------------------------------------------------------------
# 1. Coleta os arquivos de texto que podem conter referências de imagem
# ---------------------------------------------------------------------------
echo "==> Baixando HTML/CSS/JS/JSON de referência..."
fetch_text() { # <caminho-relativo>
  local rel="$1"
  curl -fsS --max-time 30 "$BASE/$rel" -o "$WORK/$(echo "$rel" | tr '/' '_')" 2>/dev/null \
    && echo "  ok    $rel" || echo "  falha $rel"
}

fetch_text "index.html"
for rel in \
  assets/css/variables.css assets/css/global.css assets/css/header.css \
  assets/css/carousel.css assets/css/footer.css \
  assets/css/pages/home.css assets/css/pages/inner-pages.css \
  app.js assets/js/carousel.js \
  assets/data/noticias.json assets/data/ranking.json ; do
  fetch_text "$rel"
done
for p in home institucional biografia legislacao noticias noticia-detalhe \
         pesquisa-precos ranking transparencia contatos acessibilidade mapa-do-site ; do
  fetch_text "pages/$p.html"
done
echo

# ---------------------------------------------------------------------------
# 2. Extrai todas as URLs de imagem
# ---------------------------------------------------------------------------
EXT='png|jpe?g|jpeg|gif|svg|webp|avif|ico|bmp|tiff'

# Internas: caminhos relativos terminando em extensão de imagem
grep -rhoiE "[A-Za-z0-9_@./-]+\.($EXT)([^A-Za-z0-9]|$)" "$WORK" 2>/dev/null \
  | sed -E "s/([A-Za-z0-9_@./-]+\.($EXT)).*/\1/" \
  | grep -viE '^https?:|^//|^data:' \
  | grep -viE '\.css$|\.js$' \
  | sed 's#^/##' \
  | sort -u > "$WORK/internas.txt"

# Externas: URLs absolutas (Unsplash, CDNs, etc.)
grep -rhoiE 'https?://[^"'"'"' )<>]+' "$WORK" 2>/dev/null \
  | grep -iE "\.($EXT)(\?|$)|images\.unsplash\.com|/image|/photo" \
  | sort -u > "$WORK/externas.txt"

echo "==> Imagens internas encontradas:"; sed 's/^/    /' "$WORK/internas.txt"; echo
echo "==> Imagens externas encontradas:"; sed 's/^/    /' "$WORK/externas.txt"; echo

# ---------------------------------------------------------------------------
# 3. Download
# ---------------------------------------------------------------------------
MANIFEST="$OUT/manifest.tsv"
mkdir -p "$OUT/servidor" "$OUT/externas"
printf 'origem\tdestino_local\ttamanho\tstatus\n' > "$MANIFEST"

while IFS= read -r rel; do
  [ -z "$rel" ] && continue
  dst="$OUT/servidor/$rel"
  mkdir -p "$(dirname "$dst")"
  if curl -fsS --max-time 60 "$BASE/$rel" -o "$dst" 2>/dev/null; then
    printf '%s\t%s\t%s\tOK\n' "$BASE/$rel" "servidor/$rel" "$(wc -c < "$dst" | tr -d ' ')" >> "$MANIFEST"
    echo "  baixado  $rel"
  else
    rm -f "$dst"
    printf '%s\t%s\t-\tFALHOU\n' "$BASE/$rel" "servidor/$rel" >> "$MANIFEST"
    echo "  FALHOU   $rel"
  fi
done < "$WORK/internas.txt"

while IFS= read -r url; do
  [ -z "$url" ] && continue
  base="$(basename "${url%%\?*}")"
  # Unsplash (e afins) não têm extensão no caminho: assume .jpg para o arquivo local
  case "$base" in
    *.*) : ;;
    *)   base="$base.jpg" ;;
  esac
  dst="$OUT/externas/$base"
  if curl -fsS --max-time 60 "$url" -o "$dst" 2>/dev/null; then
    printf '%s\t%s\t%s\tOK\n' "$url" "externas/$base" "$(wc -c < "$dst" | tr -d ' ')" >> "$MANIFEST"
    echo "  baixado  $url -> $base"
  else
    rm -f "$dst"
    printf '%s\t%s\t-\tFALHOU\n' "$url" "externas/$base" >> "$MANIFEST"
    echo "  FALHOU   $url"
  fi
done < "$WORK/externas.txt"

echo
echo "==> Concluído. Manifesto: $MANIFEST"
