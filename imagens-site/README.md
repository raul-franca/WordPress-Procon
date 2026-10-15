# Imagens do site de referência — `http://10.0.0.104:8080`

Inventário e download de **todas as imagens** usadas pela página de referência do
PROCON PE (SPA AngularJS). Lista completa em [`manifest.tsv`](manifest.tsv).

## Onde cada imagem é referenciada

O site não tem imagens em `<img>` estáticas: o conteúdo (carrossel de notícias) é
renderizado por AngularJS a partir dos dados em `assets/data/noticias.json`.

| Imagem | Referência |
|---|---|
| `servidor/assets/img/logo-procon.png` | `index.html` (cabeçalho e rodapé) |
| `servidor/assets/img/favicon.ico` | `<link rel="icon">` — **retorna 404**, não existe |
| `servidor/assets/uploads/1790636942583_WhatsApp_Image_2026-09-21_at_10_22_23.jpeg` | `assets/data/noticias.json` (notícia "teste", id 1790636958576) |
| `externas/photo-*.jpg` | `assets/data/noticias.json` → campo `imagem_url` (Unsplash, ids 1–8) |

> As fotos de `/pages/biografia.html` (`vm.pessoa.foto`) não têm URL nos dados —
> usam o placeholder `.bio-avatar-placeholder`, portanto não há imagem a baixar.

## Estrutura

```
imagens-site/
├── manifest.tsv     → origem, arquivo local, tamanho e status
├── externas/        → imagens hospedadas fora do site (Unsplash) — 8 arquivos
└── servidor/        → imagens servidas pelo próprio host (espelha os caminhos)
    └── assets/
        ├── img/logo-procon.png
        └── uploads/1790636942583_WhatsApp_Image_2026-09-21_at_10_22_23.jpeg
```

## Como completar / refazer o download

```bash
./scripts/baixar-imagens.sh                 # usa http://10.0.0.104:8080 e ./imagens-site
./scripts/baixar-imagens.sh http://host:porta /caminho/de/saida
```

O script varre `index.html`, os CSS, os JS, os templates em `/pages` e os JSON de
`/assets/data`, extrai as referências internas e externas, baixa tudo preservando a
estrutura e reescreve o `manifest.tsv`.

### Status: download concluído

- 8 imagens externas (Unsplash, 800px — mesma URL carregada pelo site): **OK**.
- `logo-procon.png` (89.509 B): **OK**.
- Upload `1790636942583_WhatsApp_Image_2026-09-21_at_10_22_23.jpeg` (109.026 B): **OK**.
- `favicon.ico`: **404** no servidor — não existe (não há o que baixar).

Único item "FALHOU" no `manifest.tsv` é o `favicon.ico`, que retorna 404 na origem.
