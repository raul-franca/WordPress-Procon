# page-procon

Pacote de identidade visual da página inicial do **PROCON PE** (`10.0.0.104:8080`).

## Conteúdo

```
page-procon/
├── IDENTIDADE-VISUAL.md   → tipografia, ícones, cores, tokens e stack
├── imagens/               → 8 imagens do carrossel (Unsplash, 1600px)
├── icones/                → 11 ícones SVG da interface (Material Design + logos sociais)
└── README.md
```

## Observações

- **logo-procon.png** — está no servidor local (`assets/img/`, 89 KB). Como esta sessão
  não está vinculada ao seu computador, baixei o logo direto pelo navegador para a sua
  pasta **Downloads** (mova-o para cá, dentro de `page-procon/`).
- **favicon.ico** — referenciado no `<head>` mas retorna **404** (ainda não existe).
- As 8 imagens do carrossel são *placeholders* do Unsplash.

---

# Ambiente WordPress (Docker)

WordPress 6 + MariaDB 11.4 com **Elementor** e o tema filho **PROCON Pernambuco**,
que aplica a identidade descrita em [`IDENTIDADE-VISUAL.md`](IDENTIDADE-VISUAL.md).

## Subir o ambiente

```bash
cp .env.example .env   # ajuste senhas e a porta (WP_PORT)
docker compose up -d
```

Acesse `http://localhost:${WP_PORT}` (atualmente **8020**).

| Caminho no host | Conteúdo |
|---|---|
| `data/db/` | Dados do MariaDB (não versionado) |
| `data/wordpress/` | Núcleo, plugins e uploads do WordPress (não versionado) |
| `wp-content/themes/procon/` | Tema filho — **versionado**, montado em `/var/www/html/wp-content/themes/procon` |
| `scripts/setup-procon.php` | Script que aplica a identidade visual |

## WP-CLI

O serviço `wpcli` só sobe sob demanda (profile `cli`) e usa as mesmas variáveis do site:

```bash
docker compose run --rm wpcli plugin list
```

## Aplicar a identidade visual

```bash
docker compose run --rm wpcli plugin install elementor --activate
docker compose run --rm wpcli theme install hello-elementor
docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php
```

O script é idempotente e faz:

1. Ativa o tema **PROCON Pernambuco** (filho do Hello Elementor).
2. Importa para a Biblioteca de Mídia o logo, o ícone do site (brasão recortado do logo,
   pois o `favicon.ico` da página de referência retorna 404) e as 8 imagens do carrossel.
3. Configura o **Kit global do Elementor**: cores de marca/neutras, tipografia Roboto
   (pesos 400/500/600/700), botões em pílula, largura de container 1280px.
4. Cria a página **Início** em Elementor (carrossel, cards de notícia, faixa de vídeos) e
   a define como página inicial. Se a página já tiver conteúdo Elementor, ela **não** é sobrescrita.
5. Cria o menu **Principal** na posição "Menu principal (cabeçalho)".

## Tema filho `procon`

| Arquivo | Função |
|---|---|
| `assets/css/variables.css` | Tokens (`--color-*`, `--font-*`, `--space-*`, `--radius-*`, `--shadow-*`) idênticos à referência |
| `assets/css/global.css` | Base tipográfica e **modo alto contraste** (`--hc-*`) |
| `assets/css/header.css` / `footer.css` | Barra do governo, cabeçalho e rodapé |
| `assets/css/home.css` | Classes para blocos do Elementor: `procon-hero`, `procon-cards`, `procon-card`, `procon-date`, `procon-link-more`, `procon-cta`, `procon-section-head` |
| `assets/js/acessibilidade.js` | Botões A- / A+ e alto contraste (preferência salva no navegador) |
| `inc/icons.php` | Ícones SVG inline Material Design (`procon_icon( 'search' )`), mesmos de `page-procon/icones/` |
| `inc/customizer.php` | **Aparência › Personalizar › PROCON — Contato e redes**: endereço, telefone, e-mail, redes sociais e VLibras |

O rodapé só exibe os contatos e as redes sociais que forem preenchidos no Personalizador.
O widget **VLibras** vem ativo por padrão.
