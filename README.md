# PROCON Pernambuco — site em WordPress

Este projeto coloca no ar, na sua máquina, uma cópia do site do **PROCON PE**
(originalmente em `http://10.0.0.104:8080`) usando **WordPress + Docker**.

Você não precisa saber WordPress nem programar para rodar: é só seguir os passos abaixo.

> **O que é Docker?** É um programa que "empacota" o WordPress, o banco de dados e as
> ferramentas necessárias para rodarem sozinhos, sem você precisar instalar nada no
> computador além dele. Você liga e desliga esse pacote com um comando.

---

## 1. Antes de começar (pré-requisitos)

Você precisa apenas de:

| O que | Como saber se já tem |
|---|---|
| **Docker Desktop** instalado e **aberto** (ícone da baleia na barra do sistema) | Abra o app "Docker Desktop". Se não tiver, baixe em <https://www.docker.com/products/docker-desktop> |
| Um **terminal** (Terminal, iTerm, etc.) | Já vem no Mac |
| Um **navegador** | Chrome, Safari, Firefox… |

> **Dica:** no Windows, use o **PowerShell**. Os comandos são quase iguais.

Para conferir se o Docker está pronto, rode:

```bash
docker --version
```

Se aparecer algo como `Docker version 24.x.x`, está tudo certo. Se der erro, abra o
Docker Desktop e espere ele terminar de iniciar.

---

## 2. Primeira vez: colocar o site no ar

### Passo 1 — Abra o terminal na pasta do projeto

```bash
cd /Users/raulfranca/projetos/wordPress-Procon
```

### Passo 2 — Crie o arquivo de configuração `.env`

O arquivo `.env` guarda as senhas e a **porta** onde o site vai rodar. Ele **não** vem no
projeto (por segurança), então copie o modelo:

```bash
cp .env.example .env
```

Abra o `.env` em qualquer editor de texto e ajuste a porta se quiser:

```ini
WP_PORT=8020        # é aqui que o site vai abrir: http://localhost:8020
```

> Se a porta `8020` já estiver em uso, troque por outra, por exemplo `8090`.
> Anote o número: você vai usá-lo no navegador.
>
> **Atenção:** se a pasta `data/` já existir (site já instalado antes), **mantenha a
> mesma porta** que estava sendo usada. Trocar a porta depois de instalado faz os links
> internos do WordPress pararem de funcionar. Neste projeto a porta em uso é a **8020**.

### Passo 3 — Suba os serviços

```bash
docker compose up -d
```

Na **primeira vez** isso pode demorar alguns minutos (baixa o WordPress e o banco).
Quando terminar, os contêineres estarão rodando em segundo plano.

Para ver se está tudo de pé:

```bash
docker compose ps
```

Você deve ver os serviços `wordpress` e `db` como `Up`/`running`.

### Passo 4 — Conclua a instalação do WordPress

Abra no navegador:

```
http://localhost:8020
```

Na primeira vez, o WordPress pede para você criar o **usuário administrador**
(título do site, e-mail, usuário e senha). Escolha o que preferir e **anote a senha**.

> Já existe um ambiente instalado nesta máquina, então talvez apareça o site direto —
> nesse caso, pule para o Passo 5. Para descobrir/alterar a senha do admin, veja
> [Problemas comuns](#6-problemas-comuns).

### Passo 5 — Instale o Elementor e o tema base

O site usa o editor **Elementor** e o tema base **Hello Elementor**. Rode estes dois comandos:

```bash
docker compose run --rm wpcli plugin install elementor --activate
docker compose run --rm wpcli theme install hello-elementor
```

> O comando `wpcli` é uma ferramenta de linha de comando do WordPress. Ele baixa sozinho
> o que falta e não deixa nada instalado "de sobra" no seu computador.

### Passo 6 — Monte o conteúdo do site

```bash
docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php
```

Esse comando monta o site inteiro: tema, páginas, notícias, menus e contatos.

### Passo 7 — Pronto!

Atualize o navegador em `http://localhost:8020`. Você verá o site do PROCON.

Para entrar no painel de administração: `http://localhost:8020/wp-admin`

---

## 3. Comandos do dia a dia

| Quero… | Comando |
|---|---|
| Ligar o site | `docker compose up -d` |
| Desligar o site (mantém os dados) | `docker compose down` |
| Reiniciar | `docker compose restart` |
| Ver se está rodando | `docker compose ps` |
| Ver os "logs" (mensagens de erro) | `docker compose logs -f wordpress` |
| Rodar um comando do WordPress | `docker compose run --rm wpcli <comando>` |
| **Apagar tudo e recomeçar do zero** | `docker compose down -v` e depois `rm -rf data/` |

> Rodar esses comandos sempre de dentro da pasta do projeto (Passo 1).

---

## 4. Onde fica cada coisa (mapa do projeto)

| Pasta / arquivo | Para que serve |
|---|---|
| `docker-compose.yml` | Descreve o "pacote" (WordPress + banco) |
| `.env` | Suas senhas e a porta (não é salvo no Git) |
| `data/db/` | Dados do banco de dados (não é salvo no Git) |
| `data/wordpress/` | Site, plugins e imagens enviadas (não é salvo no Git) |
| `wp-content/themes/procon/` | **O tema do site** — é aqui que ficam o visual e os códigos |
| `logo-procon.png` | Logo usado no site |
| `page-procon/imagens/` | As 8 imagens do carrossel de notícias |
| `page-procon/icones/` | Ícones SVG da interface |
| `scripts/setup-procon.php` | O "botão mágico" que monta o site |
| `scripts/conteudo/` | O texto de cada página (HTML) |
| `scripts/dados/noticias.json` | As notícias do site |
| `imagens-site/` | Imagens baixadas do site de referência |
| `IDENTIDADE-VISUAL.md` | Cores, fontes e ícones oficiais |

---

## 5. Como o site é montado

Sempre que você roda `... eval-file /project/scripts/setup-procon.php`, ele faz (de forma
**segura**, sem duplicar o que já existe):

1. Ativa o tema **PROCON Pernambuco**, define o nome do site e os links `/%postname%/`.
2. Importa logo, ícone e as 8 imagens das notícias para a **Biblioteca de Mídia**.
3. Configura o **Kit do Elementor** (cores e tipografias globais).
4. Cria as categorias e importa as **8 notícias** como posts, com imagem e data.
5. Cria **14 páginas** e transforma a **Início** na página inicial do site.
6. Cria os **6 menus** do site.
7. Preenche contatos e redes sociais (apenas campos ainda vazios).

### Suas edições são protegidas

Cada página guarda uma "impressão digital" do conteúdo gerado. Se você editar uma página no
Elementor e rodar o script de novo, **ele mantém a sua versão** e apenas avisa.

Para forçar a recriação de tudo a partir dos arquivos de `scripts/conteudo/`:

```bash
docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php sobrescrever
```

---

## 6. Problemas comuns

**O site não abre / "não foi possível conectar"**
- Confira se o Docker Desktop está aberto e se `docker compose ps` mostra tudo `Up`.
- Confirme a porta no `.env` (`WP_PORT`) e use a mesma no navegador.
- Se a porta estiver ocupada, troque o valor de `WP_PORT`, rode `docker compose up -d` de novo.

**"port is already allocated"**
- Outra coisa está usando a porta escolhida. Troque `WP_PORT` no `.env`.

**Docker não encontrado / erro ao rodar `docker`**
- Abra o Docker Desktop e espere o ícone parar de "carregar".

**Esqueci a senha do painel**

```bash
docker compose run --rm wpcli user list
docker compose run --rm wpcli user update SEU_USUARIO --user_pass=nova-senha
```

**Quero começar de novo, do zero** (apaga tudo):

```bash
docker compose down -v
rm -rf data/
docker compose up -d
```

**Erro de permissão ao enviar imagens**
- Ajuste o dono da pasta de uploads:
  `docker compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content/uploads`

**Comando `docker-compose` (com hífen) não funciona**
- Use `docker compose` (com espaço), que é a versão nova. Os dois fazem o mesmo.

---

## 7. Personalizar contatos e redes sociais

No painel: **Aparência › Personalizar › PROCON — Contato e redes**.
Preencha endereço, telefone, e-mail e redes sociais. O rodapé mostra apenas o que estiver
preenchido. O widget de acessibilidade em Libras (**VLibras**) já vem ativado.

---

## 8. Páginas e menus (referência)

| URL | Página |
|---|---|
| `/` | Início — busca, estatísticas, carrossel de notícias, Acesso Rápido, Serviços, Fale Conosco, Órgãos e YouTube |
| `/institucional/` | Informações Institucionais (7 abas) |
| `/institucional/<cargo>/` | 5 biografias (gerente-geral, secretário-executivo, gerente-jurídico, gerente-fiscalização, gerente-atendimento) |
| `/legislacao/` | Competências e Legislação |
| `/noticias/` | Lista de notícias (6 por página), com busca e filtro por categoria |
| `/<notícia>/` | Página de uma notícia + outras notícias + compartilhar |
| `/pesquisa-de-precos/` | 15 pesquisas; painel com visualização online |
| `/ranking-de-reclamacoes/` | Top 10 empresas reclamadas |
| `/transparencia/` | Portal da Transparência (15 categorias) |
| `/contatos/` | Fale Conosco — sede, Expresso Cidadão e municípios |
| `/acessibilidade/` | Recursos de acessibilidade |
| `/mapa-do-site/` | Mapa do site |

**Menus** (Aparência › Menus): Principal (cabeçalho), Barra do Governo (topo do site) e as
colunas do rodapé.

---

## 9. Para quem for mexer no código (tema `procon`)

| Arquivo | Função |
|---|---|
| `assets/css/referencia/*.css` | Cópia fiel do CSS do site original — **não editar** |
| `assets/css/wp-ajustes.css` | Ajustes para o WordPress e correções de defeitos |
| `assets/js/procon.js` | Acessibilidade, menu, busca, abas e filtros |
| `assets/js/carousel.js` | Carrossel de notícias |
| `header.php` / `footer.php` | Cabeçalho e rodapé |
| `inc/*.php` | Menus, ícones, contatos (Personalizador) e o shortcode das notícias |
| `home.php`, `archive.php`, `search.php`, `single.php` | Lista e detalhe de notícias |

O shortcode do carrossel é `[procon_noticias_carrossel quantidade="8"]`.

O CSS do Hello Elementor é desligado pelo tema, pois o CSS de referência já cuida do layout.

---

## 10. Avançado: regerar conteúdo e imagens a partir do site original

Se o site de referência (`http://10.0.0.104:8080`) mudar, dá para refazer o conteúdo:

```bash
scripts/ferramentas/baixa-referencia.sh /tmp/ref
node scripts/ferramentas/extrai-dados.js /tmp/ref/app.js /tmp/ref/dados.json
python3 -I scripts/ferramentas/gera-conteudo.py /tmp/ref scripts
docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php sobrescrever
```

E para baixar **todas** as imagens do site original:

```bash
./scripts/baixar-imagens.sh
```

Elas vão para a pasta `imagens-site/` (veja `imagens-site/README.md`).
