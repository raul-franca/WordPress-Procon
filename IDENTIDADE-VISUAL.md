# Identidade Visual — Início (PROCON Pernambuco)

> Levantamento extraído da página `http://10.0.0.104:8080/` (ambiente de desenvolvimento).
> Data: 15/10/2026.

---

## 1. Tipografia

A fonte principal é a **Roboto**, carregada via Google Fonts.

| Item | Valor |
|------|-------|
| Família principal | `Roboto` |
| Fallback | `'Open Sans', Arial, sans-serif` |
| Fonte do token `--font-primary` | `'Roboto', 'Open Sans', Arial, sans-serif` |
| Pesos carregados (Google Fonts) | 300 (Light), 400 (Regular), 500 (Medium), 700 (Bold) |
| Origem | `https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700` |

Não há `@font-face` local — a fonte vem 100% do Google Fonts (com `preconnect` para `fonts.googleapis.com` e `fonts.gstatic.com`).

### Escala tipográfica (tokens `--font-size-*`)

| Token | Valor | Equivalente |
|-------|-------|-------------|
| `--font-size-xs` | 0.75rem | 12px |
| `--font-size-sm` | 0.875rem | 14px |
| `--font-size-base` | 1rem | 16px |
| `--font-size-lg` | 1.125rem | 18px |
| `--font-size-xl` | 1.25rem | 20px |
| `--font-size-2xl` | 1.5rem | 24px |
| `--font-size-3xl` | 2rem | 32px |
| `--font-size-hero` | 2.5rem | 40px |

### Uso aplicado (computado na página)

| Elemento | Tamanho | Peso | Line-height |
|----------|---------|------|-------------|
| `body` | 16px | 400 | 1.65 |
| `h1` | 40px | 700 | 48px |
| `h2` | 24px | 700 | 39.6px |
| `h3` | 18px | 700 | 24.3px |
| `p` | 14px | 500 | 23.1px |
| `a` | 16px | 700 | — |
| `button` | 12px | 600 | — |

---

## 2. Bibliotecas de ícones

**Não há biblioteca de ícones externa** (nenhum Font Awesome, Material Icons/Symbols,
Bootstrap Icons, Lucide, Feather, Remix, Boxicons, Ionicons etc. — nenhum `link`/`script` de CDN de ícones).

Os ícones são **SVG inline** embutidos direto no HTML, no padrão do **Material Design**:

- `viewBox="0 0 24 24"` (ícones de UI)
- `fill="currentColor"` (herdam a cor do texto via CSS — fácil de tematizar)
- `aria-hidden="true"` (acessibilidade correta: são decorativos)

Foram encontrados **32 `<svg>`** na página, sendo **17 únicos**. Os ícones da interface
foram exportados para a pasta `icones/`:

| Arquivo | Ícone | Origem | Onde aparece |
|---------|-------|--------|--------------|
| `search.svg` | Lupa | Material Design | Botão "Buscar" |
| `chevron-right.svg` | Seta direita | Material Design | "Ver todas", "Leia mais" |
| `chevron-left.svg` | Seta esquerda | Material Design | Navegação do carrossel |
| `calendar.svg` | Calendário | Material Design | Data dos cards de notícia |
| `location.svg` | Pin de local | Material Design | Endereço (rodapé) |
| `phone.svg` | Telefone | Material Design | Contato (rodapé) |
| `email.svg` | Envelope | Material Design | E-mail (rodapé) |
| `youtube.svg` | YouTube | Logo de marca | "Assistir Vídeos" / redes sociais |
| `instagram.svg` | Instagram | Logo de marca | Redes sociais |
| `facebook.svg` | Facebook | Logo de marca | Redes sociais |
| `x-twitter.svg` | X (Twitter) | Logo de marca | Redes sociais |

> Recomendação: como já seguem o padrão Material Design 24×24 com `currentColor`,
> dá para padronizar usando o pacote `@material-symbols` ou um sprite SVG único,
> caso o número de ícones cresça.

---

## 3. Paleta de cores (tokens `--color-*`)

### Cores de marca

| Token | Hex | Uso |
|-------|-----|-----|
| `--color-primary` | `#00295b` | Azul institucional (header, títulos) |
| `--color-primary-dark` | `#001a3d` | Azul escuro (hover/estados) |
| `--color-primary-light` | `#003f8a` | Azul claro |
| `--color-secondary` | `#0a7f8e` | Teal (destaques, botão Buscar) |
| `--color-secondary-dark` | `#076470` | Teal escuro |
| `--color-secondary-light` | `#14b0c3` | Teal claro |
| `--color-accent` | `#00c2d4` | Ciano de acento (números, links) |
| `--color-gov-bar` | `#001830` | Barra superior "Governo" |

### Neutros e superfícies

| Token | Hex | Uso |
|-------|-----|-----|
| `--color-white` | `#ffffff` | Branco |
| `--color-bg` | `#f4f7fb` | Fundo geral |
| `--color-bg-section` | `#eaf2f8` | Fundo de seção alternada |
| `--color-text` | `#222222` | Texto principal |
| `--color-text-muted` | `#555555` | Texto secundário |
| `--color-border` | `#d0dce8` | Bordas |

### Alto contraste (acessibilidade)

| Token | Hex | Uso |
|-------|-----|-----|
| `--hc-bg` | `#000000` | Fundo (modo alto contraste) |
| `--hc-text` | `#ffff00` | Texto (amarelo) |
| `--hc-link` | `#00ff00` | Links (verde) |
| `--hc-border` | `#ffffff` | Bordas (branco) |

---

## 4. Design tokens

### Espaçamento (`--space-*`)

| Token | Valor |
|-------|-------|
| `--space-xs` | 0.25rem (4px) |
| `--space-sm` | 0.5rem (8px) |
| `--space-md` | 1rem (16px) |
| `--space-lg` | 1.5rem (24px) |
| `--space-xl` | 2rem (32px) |
| `--space-2xl` | 3rem (48px) |
| `--space-3xl` | 4rem (64px) |

### Raio de borda (`--radius-*`)

| Token | Valor |
|-------|-------|
| `--radius-sm` | 4px |
| `--radius-md` | 8px |
| `--radius-lg` | 16px |
| `--radius-pill` | 9999px (pílula) |

### Sombras (`--shadow-*`)

| Token | Valor |
|-------|-------|
| `--shadow-sm` | `0 1px 4px rgba(0,0,0,.10)` |
| `--shadow-md` | `0 4px 16px rgba(0,41,91,.12)` |
| `--shadow-lg` | `0 8px 32px rgba(0,41,91,.16)` |

### Transições (`--transition-*`)

| Token | Valor |
|-------|-------|
| `--transition-fast` | 150ms ease |
| `--transition-normal` | 250ms ease |
| `--transition-slow` | 400ms ease |

### Layout

| Token | Valor |
|-------|-------|
| `--container-max` | 1280px |
| `--container-pad` | 1.25rem (20px) |

---

## 5. Stack / bibliotecas detectadas

| Recurso | Versão / origem |
|---------|-----------------|
| **AngularJS** | 1.8.3 (`ajax.googleapis.com`) — binding dos cards de notícia (`ng-scope`, `ng-binding`) |
| **VLibras** | `vlibras.gov.br/app/vlibras-plugin.js` — tradutor de Libras (acessibilidade gov.br) |
| **Google Fonts** | Roboto |

### CSS organizado por módulos

```
assets/css/variables.css      → tokens (cores, fontes, espaçamento)
assets/css/global.css         → base/reset
assets/css/header.css         → cabeçalho
assets/css/carousel.css       → carrossel de notícias
assets/css/footer.css         → rodapé
assets/css/pages/home.css     → página inicial
assets/css/pages/inner-pages.css → páginas internas
```

---

## 6. Imagens

| Arquivo | Origem | Observação |
|---------|--------|------------|
| `logo-procon.png` | `assets/img/` (local, 89 KB) | Logo oficial — **baixado separadamente** (ver nota abaixo) |
| `favicon.ico` | `assets/img/` | ⚠️ **Retorna 404** — referenciado no HTML mas ainda não existe no servidor |
| `imagens/carrossel-1..8-*.jpg` | Unsplash (CDN externo) | 8 fotos *placeholder* dos cards de notícia (1600px) |

> As 8 imagens do carrossel são fotos de banco (Unsplash) usadas como *placeholder* —
> provavelmente serão trocadas pelas imagens reais das notícias em produção.

> **Faltando criar:** o `favicon.ico` está referenciado no `<head>` mas retorna 404.
