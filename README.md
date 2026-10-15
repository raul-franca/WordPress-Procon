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

WordPress 6 + MariaDB 11.4 com **Elementor** e o tema filho **PROCON Pernambuco**, que reproduz
o portal de referência (`http://10.0.0.104:8080/`, SPA em AngularJS) — visual, menu, rodapé,
páginas, notícias e recursos de acessibilidade.

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
| `scripts/setup-procon.php` | Monta o site (páginas, notícias, menus, contatos) |
| `scripts/conteudo/` | HTML de cada seção das páginas + `paginas.json` (manifesto) |
| `scripts/dados/noticias.json` | Notícias importadas como posts |
| `scripts/ferramentas/` | Conversão da página de referência → `scripts/conteudo/` |

## WP-CLI

O serviço `wpcli` só sobe sob demanda (profile `cli`) e usa as mesmas variáveis do site:

```bash
docker compose run --rm wpcli plugin list
```

## Montar o site

```bash
docker compose run --rm wpcli plugin install elementor --activate
docker compose run --rm wpcli theme install hello-elementor
docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php
```

O script é idempotente e faz:

1. Ativa o tema **PROCON Pernambuco**, define o nome do site e os links permanentes `/%postname%/`
   (escreve as regras no `.htaccess`).
2. Importa logo, ícone do site (brasão recortado do logo — o `favicon.ico` da referência dá 404)
   e as 8 imagens das notícias.
3. Configura o **Kit do Elementor** só com cores e tipografias globais (para widgets nativos);
   estilos de body/títulos/links/botões ficam com o CSS de referência.
4. Cria as categorias (na ordem da referência) e importa as **8 notícias** como posts, com imagem
   destacada e data original. O post de exemplo "Olá, mundo!" vai para a lixeira.
5. Cria **14 páginas** no Elementor a partir de `scripts/conteudo/` + a página **Notícias** (lista
   de posts). Início vira a página inicial.
6. Cria **6 menus** e os associa às posições do tema.
7. Preenche os contatos e redes em **Personalizar › PROCON — Contato e redes** (só campos vazios).

### Proteção das edições

Cada página guarda o hash do conteúdo gerado. Se você editar uma página no Elementor, uma nova
execução do script **mantém** a sua versão e avisa. O mesmo vale para menus já existentes.
Para recriar tudo a partir de `scripts/conteudo/`:

```bash
docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php sobrescrever
```

## Páginas

| URL | Página | Interações (assets/js/procon.js) |
|---|---|---|
| `/` | Início — banner com busca, estatísticas, carrossel de notícias, Acesso Rápido, Serviços, Fale Conosco, Órgãos, YouTube | Carrossel (`carousel.js`) |
| `/institucional/` | Informações Institucionais (7 abas) | Abas; âncoras `#quem-somos`, `#missao`, `#organograma`, `#lgpd`, `#etica`, `#conselho`, `#identidade` |
| `/institucional/<cargo>/` | 5 biografias (gerente-geral, secretario-executivo, gerente-juridico, gerente-fiscalizacao, gerente-atendimento) | — |
| `/legislacao/` | Competências e Legislação | — |
| `/noticias/` | Lista de notícias (6 por página), busca e filtro por categoria | — (templates PHP) |
| `/<notícia>/` | Detalhe da notícia + outras notícias + compartilhar | — |
| `/pesquisa-de-precos/` | 15 pesquisas; painel com visualização online | Seleção de categoria, abas do painel |
| `/ranking-de-reclamacoes/` | Top 10 empresas | Busca, filtro por segmento, período |
| `/transparencia/` | Portal da Transparência (15 categorias) | — |
| `/contatos/` | Fale Conosco — sede, Expresso Cidadão e municípios (15 postos) | Filtro por tipo de posto |
| `/acessibilidade/` | Recursos de acessibilidade | Botões de fonte/contraste |
| `/mapa-do-site/` | Mapa do site | — |

Cada seção é um contêiner do Elementor com um widget **HTML** (ou **Shortcode**, no carrossel),
preservando o markup e as classes da referência. Dá para reordenar seções, editar o HTML ou
inserir widgets nativos do Elementor (que usam as cores/tipografias globais do Kit).

## Menus (Aparência › Menus)

| Posição | Menu |
|---|---|
| Menu principal (cabeçalho) | **Principal** — Início, Institucional ▾, Pesquisa de Preços, Transparência ▾, Ranking, Notícias, Contato |
| Barra do governo (topo) | **Barra do Governo** — PERNAMBUCO, SECRETARIAS, PORTAL DO SERVIDOR, NOTÍCIAS DO GOV., SJDH, OUVIDORIA, TRANSPARÊNCIA |
| Rodapé — Serviços / Inf. Institucionais / Órgãos Parceiros | Colunas de links do rodapé |
| Rodapé — Links inferiores | Acessibilidade, Mapa do Site, Política de Privacidade |

No menu principal, itens com subitens viram dropdown. Para um **separador** no submenu, crie um
link personalizado (URL `#`) com a classe CSS `dropdown-divider` (ative "Classes CSS" em
*Opções de tela*).

## Tema filho `procon`

| Arquivo | Função |
|---|---|
| `assets/css/referencia/*.css` | **Cópia fiel** do CSS da referência (variables, global, header, carousel, footer, home, inner-pages). Não editar — só removido o `@import` da fonte, carregada pelo tema |
| `assets/css/wp-ajustes.css` | Adaptações ao WordPress/Elementor e correções de defeitos da referência (ver abaixo) |
| `assets/js/procon.js` | Substitui o AngularJS: acessibilidade, menu/dropdown/mobile, busca do cabeçalho, abas, filtros e painel de pesquisa |
| `assets/js/carousel.js` | Carrossel de notícias da referência (só a inicialização foi adaptada) |
| `header.php` / `footer.php` | Markup do `index.html` da referência, com menus do WordPress |
| `inc/class-procon-nav-walker.php` | Gera o markup `nav-item` / `nav-link` / `dropdown-menu` do menu principal |
| `inc/template-tags.php` | Breadcrumb, faixa de título (`page-hero`), data no formato "28 de setembro de 2026" |
| `inc/shortcodes.php` | `[procon_noticias_carrossel quantidade="8"]` — seção "Últimas Notícias" |
| `inc/customizer.php` | Personalizar › PROCON — Contato e redes (rodapé) e VLibras |
| `inc/icons.php` | Ícones SVG Material Design + redes sociais (`procon_icon( 'search', 18 )`) |
| `home.php`, `archive.php`, `search.php`, `template-parts/noticias-lista.php` | Lista de notícias (página Notícias, categorias e busca) |
| `single.php` | Detalhe da notícia |
| `page.php`, `404.php`, `index.php` | Páginas sem Elementor, não encontrado e fallback |

O título da página vira o `<h1>` da faixa azul e o **resumo** (excerpt) vira o subtítulo.

O CSS do Hello Elementor é desligado (filtros `hello_elementor_enqueue_*`), pois o CSS de
referência já traz reset, tipografia e layout.

### Correções de defeitos da referência (`wp-ajustes.css`)

- Card de pesquisa selecionado ficava com texto branco sobre fundo branco (`.pesquisa-card` declarado duas vezes).
- No celular, o logo mostrava parte do texto ao lado do brasão (recorte de 76px com imagem reduzida).
- No celular, havia rolagem horizontal em Início, Contatos, biografias e Transparência
  (grids sem `min-width: 0`, `.btn` com `nowrap`, `.contato-canais-topo` e `.bio-layout` sem regra responsiva).

## Regerar o conteúdo a partir da referência

Se a página de referência mudar:

```bash
scripts/ferramentas/baixa-referencia.sh /tmp/ref
node scripts/ferramentas/extrai-dados.js /tmp/ref/app.js /tmp/ref/dados.json
python3 -I scripts/ferramentas/gera-conteudo.py /tmp/ref scripts
docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php sobrescrever
```

O conversor remove o AngularJS, expande listas (`ng-repeat`) com os dados do `app.js` e troca as
rotas `#/pagina` por URLs do WordPress (`{{site}}` é substituído por `home_url()` no setup).
O CSS de referência deve ser copiado manualmente para `assets/css/referencia/` se mudar.
