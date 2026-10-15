"""
Converte os templates AngularJS da página de referência em HTML estático para o Elementor.

Uso (a partir da raiz do projeto):
  scripts/ferramentas/baixa-referencia.sh /tmp/ref
  node scripts/ferramentas/extrai-dados.js /tmp/ref/app.js /tmp/ref/dados.json
  python3 -I scripts/ferramentas/gera-conteudo.py /tmp/ref scripts
Gera <saida>/conteudo/<pagina>/<NN-secao>.html, <saida>/conteudo/paginas.json e <saida>/dados/*.json
"""
import html
import json
import os
import re
import sys

REF, OUT = sys.argv[1], sys.argv[2]
CONTEUDO = os.path.join(OUT, 'conteudo')
DADOS = os.path.join(OUT, 'dados')

dados = json.load(open(os.path.join(REF, 'dados.json')))
ranking = json.load(open(os.path.join(REF, 'assets/data/ranking.json')))
noticias = json.load(open(os.path.join(REF, 'assets/data/noticias.json')))

# Seções do Institucional (âncoras usadas no menu)
ANCORA_INST = {
    'Quem Somos': 'quem-somos', 'Missão, Visão e Valores': 'missao',
    'Estrutura Organizacional': 'organograma', 'LGPD': 'lgpd',
    'Ética e Integridade': 'etica', 'Conselho': 'conselho',
    'Identidade Institucional': 'identidade',
}


def esc(t):
    return html.escape(str(t), quote=True)


def rota(href):
    """'#/x/y' -> '{{site}}/x/y/'  ('{{site}}' é trocado por home_url() no setup)."""
    if not href.startswith('#/'):
        return href
    caminho = href[2:].strip('/')
    return '{{site}}/' + (caminho + '/' if caminho else '')


def troca_rotas(s):
    return re.sub(r'href="(#/[^"]*)"', lambda m: 'href="%s"' % rota(m.group(1)), s)


def limpa(s):
    s = re.sub(r'<!--.*?-->', '', s, flags=re.S)
    s = troca_rotas(s)
    s = re.sub(r'\n\s*\n+', '\n', s)
    return s.strip() + '\n'


def sem_cabecalho(s):
    """Remove breadcrumb e page-hero (o tema os gera a partir do título/resumo da página)."""
    s = re.sub(r'<nav class="breadcrumb".*?</nav>\s*', '', s, count=1, flags=re.S)
    s = re.sub(r'<div class="page-hero">.*?</div>\s*</div>\s*', '', s, count=1, flags=re.S)
    return s


def secoes(s):
    """Divide o template da home nos comentários '<!-- ── NOME ──'."""
    partes = re.split(r'<!-- ── ([^─]+?) ─+ -->', s)
    return {partes[i].strip(): partes[i + 1] for i in range(1, len(partes), 2)}


def ler(p):
    return open(os.path.join(REF, 'pages', p + '.html')).read()


paginas = []


def grava(slug, nome, conteudo):
    pasta = os.path.join(CONTEUDO, slug)
    os.makedirs(pasta, exist_ok=True)
    with open(os.path.join(pasta, nome + '.html'), 'w') as f:
        f.write(limpa(conteudo))
    return {'tipo': 'html', 'arquivo': slug + '/' + nome + '.html'}


def pagina(slug, titulo, resumo='', secs=(), pai='', menu_ordem=0):
    paginas.append({'slug': slug, 'titulo': titulo, 'resumo': resumo, 'pai': pai, 'secoes': list(secs)})


# ---------------------------------------------------------------- HOME
home = secoes(ler('home'))

hero = home['HERO BANNER']
hero = hero.replace('<form class="search-form hero-search-form" role="search" aria-label="Busca principal" ng-submit="buscar()">',
                    '<form class="search-form hero-search-form" role="search" aria-label="Busca principal" method="get" action="{{site}}/">')
hero = re.sub(r'\s*ng-model="searchQuery"', '\n               name="s"', hero)

acesso = home['ACESSO RÁPIDO']
cards = []
for it in dados['acessoRapido']:
    alvo = ' target="_blank" rel="noopener noreferrer"' if it.get('target') == '_blank' else ''
    cards.append(
        '      <a class="acesso-card" href="%s"%s aria-label="%s: %s" role="listitem">\n'
        '        <span class="acesso-icon" aria-hidden="true">%s</span>\n'
        '        <span class="acesso-label">%s</span>\n'
        '        <span class="acesso-desc">%s</span>\n'
        '      </a>' % (rota(it['href']), alvo, esc(it['titulo']), esc(it['desc']), it['icon'], esc(it['titulo']), esc(it['desc'])))
acesso = re.sub(r'<a class="acesso-card".*?</a>', '\n'.join(cards), acesso, flags=re.S)

pagina('inicio', 'Início', '', [
    grava('inicio', '01-hero', hero),
    {'tipo': 'shortcode', 'shortcode': '[procon_noticias_carrossel quantidade="8"]'},
    grava('inicio', '03-acesso-rapido', acesso),
    grava('inicio', '04-servicos-contato', home['SERVIÇOS + CONTATO RÁPIDO']),
    grava('inicio', '05-orgaos', home['LINKS ÚTEIS']),
    grava('inicio', '06-youtube', home['CANAL DO YOUTUBE']),
])

# ---------------------------------------------------------------- INSTITUCIONAL
inst = sem_cabecalho(ler('institucional'))
primeira = 'quem-somos'


def btn_inst(m):
    secao = m.group(1)
    ativo = secao == primeira
    return '<button type="button" class="inst-nav-btn%s" data-secao="%s" aria-pressed="%s">' % (
        ' active' if ativo else '', secao, 'true' if ativo else 'false')


inst = re.sub(r'<button class="inst-nav-btn"[^>]*?vm\.setSecao\(\'([^\']+)\'\)[^>]*>', btn_inst, inst)
inst = re.sub(r'<article ng-if="vm\.secaoAtiva === \'([^\']+)\'"',
              lambda m: '<article data-secao="%s"%s' % (m.group(1), '' if m.group(1) == primeira else ' hidden'), inst)
inst = inst.replace('<main class="inst-content"', '<div class="inst-content"').replace('</main>', '</div>')
pagina('institucional', 'Informações Institucionais', 'Conheça o PROCON Pernambuco — sua história, estrutura e missão',
       [grava('institucional', '01-conteudo', inst)])

# Biografias (filhas do Institucional)
for slug, p in dados['liderancas'].items():
    itens = []
    if p.get('email'):
        itens.append('<div class="bio-contato-item"><span aria-hidden="true">✉️</span> <a href="mailto:%s">%s</a></div>' % (esc(p['email']), esc(p['email'])))
    if p.get('telefone'):
        tel = re.sub(r'[^\d+]', '', p['telefone'].split('/')[0])
        itens.append('<div class="bio-contato-item"><span aria-hidden="true">📞</span> <a href="tel:%s">%s</a></div>' % (tel, esc(p['telefone'])))
    if p.get('formacao'):
        itens.append('<div class="bio-contato-item"><span aria-hidden="true">🎓</span> <span>%s</span></div>' % esc(p['formacao']))
    atrib = ''
    if p.get('atribuicoes'):
        atrib = ('<div style="margin-top:var(--space-xl)">\n<h3 style="color:var(--color-primary);margin-bottom:var(--space-md)">Principais Atribuições</h3>\n'
                 '<ul class="bio-atribuicoes">\n%s\n</ul>\n</div>') % '\n'.join('<li>%s</li>' % esc(a) for a in p['atribuicoes'])
    bio = '''<section class="section">
  <div class="container">
    <div class="bio-layout">
      <aside class="bio-card" aria-label="Informações do gestor">
        <div class="bio-avatar" aria-hidden="true">
          <div class="bio-avatar-placeholder">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="64" height="64" fill="rgba(255,255,255,.6)"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          </div>
        </div>
        <div class="bio-card-info">
          <h2 class="bio-nome">%s</h2>
          <p class="bio-cargo-tag">%s</p>
          %s
        </div>
        <a href="#/institucional" class="btn btn-outline btn-sm" style="margin-top:var(--space-lg);width:100%%;justify-content:center">← Voltar ao Institucional</a>
      </aside>
      <article class="bio-content" aria-label="Biografia">
        <h2 class="section-title">Sobre</h2>
        <div class="bio-texto">%s</div>
        %s
      </article>
    </div>
  </div>
</section>''' % (esc(p.get('nome') or 'A definir'), esc(p['cargo']), '\n          '.join(itens), p['biografia'], atrib)
    pagina(slug, p['cargo'], p['departamento'], [grava(slug, '01-biografia', bio)], pai='institucional')

# ---------------------------------------------------------------- PÁGINAS ESTÁTICAS
pagina('legislacao', 'Competências e Legislação',
       'Base legal que orienta a atuação do PROCON Pernambuco na defesa dos direitos do consumidor',
       [grava('legislacao', '01-conteudo', sem_cabecalho(ler('legislacao')))])

acess = sem_cabecalho(ler('acessibilidade'))
for ng, a11y in (('aumentarFonte()', 'fonte-mais'), ('resetarFonte()', 'fonte-padrao'),
                 ('diminuirFonte()', 'fonte-menos'), ('toggleContraste()', 'contraste')):
    acess = re.sub(r'\s*ng-click="%s"' % re.escape(ng), ' type="button" data-a11y="%s"' % a11y, acess)
pagina('acessibilidade', 'Acessibilidade', 'Recursos e funcionalidades para uma navegação mais inclusiva',
       [grava('acessibilidade', '01-conteudo', acess)])

mapa = sem_cabecalho(ler('mapa-do-site'))
mapa = re.sub(r'<li><a href="#/institucional">([^<]+)</a></li>',
              lambda m: '<li><a href="{{site}}/institucional/#%s">%s</a></li>' % (ANCORA_INST.get(m.group(1), ''), m.group(1)), mapa)
mapa = mapa.replace('/institucional/#"', '/institucional/"')
mapa = mapa.replace('<a href="#/">Página Inicial</a>', '<a href="{{site}}/">Página Inicial</a>')
pagina('mapa-do-site', 'Mapa do Site', 'Visão geral de todas as páginas e seções do portal PROCON Pernambuco',
       [grava('mapa-do-site', '01-conteudo', mapa)])

# Transparência
transp = sem_cabecalho(ler('transparencia'))
cards = []
for it in dados['transparencia']:
    cards.append(
        '      <a class="transp-card" href="%s" target="_blank" rel="noopener" role="listitem" aria-label="Acessar: %s">\n'
        '        <div class="transp-card-icon" aria-hidden="true">%s</div>\n'
        '        <div class="transp-card-body">\n'
        '          <h3 class="transp-card-title">%s</h3>\n'
        '          <span class="transp-card-link-icon" aria-hidden="true">↗</span>\n'
        '        </div>\n'
        '      </a>' % (esc(it['href']), esc(it['titulo']), it['icon'], esc(it['titulo'])))
transp = re.sub(r'<a class="transp-card".*?</a>', '\n'.join(cards), transp, flags=re.S)
pagina('transparencia', 'Portal da Transparência',
       'Acesso às informações públicas do PROCON Pernambuco, conforme a Lei de Acesso à Informação (Lei 12.527/2011)',
       [grava('transparencia', '01-conteudo', transp)])

# ---------------------------------------------------------------- CONTATOS
cont = sem_cabecalho(ler('contatos'))
for f in ('todos', 'sede', 'expresso', 'municipio'):
    cont = re.sub(r'<button class="filtro-btn" ng-class="\{\'active\': vm\.filtro === \'%s\'\}"[^>]*>' % f,
                  '<button type="button" class="filtro-btn%s" data-filtro="%s" aria-pressed="%s">' % (
                      ' active' if f == 'todos' else '', f, 'true' if f == 'todos' else 'false'), cont)
TIPO = {'sede': 'SEDE', 'expresso': 'EXPRESSO CIDADÃO', 'municipio': 'MUNICÍPIO'}
linhas = []
for p in dados['postos']:
    resp = ''
    if p.get('responsavel'):
        resp += '<span><strong>%s</strong><br></span>' % esc(p['responsavel'])
    if p.get('coordenador'):
        resp += '<span style="font-size:var(--font-size-xs);color:var(--color-text-muted)">%s</span>' % esc(p['coordenador'])
    contatos = ''
    for k in ('fone', 'fone2'):
        if p.get(k):
            contatos += '<div><a href="tel:%s" class="contato-tel">📞 %s</a></div>' % (re.sub(r'[^\d+]', '', p[k]), esc(p[k]))
    for k in ('email', 'email2'):
        if p.get(k):
            contatos += '<div><a href="mailto:%s" class="contato-email">✉ %s</a></div>' % (esc(p[k]), esc(p[k]))
    end = esc(p['endereco']) + '<br>'
    if p.get('cep'):
        end += '<span>CEP %s</span>' % esc(p['cep'])
    if p.get('ref'):
        end += '<span style="display:block;font-size:var(--font-size-xs);color:var(--color-text-muted);margin-top:.25rem">📌 %s</span>' % esc(p['ref'])
    linhas.append('''          <tr class="row-{t}" data-tipo="{t}">
            <td data-label="Cidade"><strong>{mun}</strong></td>
            <td data-label="Unidade"><span class="posto-unidade-nome">{nome}</span></td>
            <td data-label="Tipo de Vínculo"><span class="badge-tipo badge-{t}"><span>{tipo}</span></span></td>
            <td data-label="Responsável">{resp}</td>
            <td data-label="Telefone / E-mail">{cont}</td>
            <td data-label="Endereço"><address style="font-style:normal;line-height:1.6">{end}</address></td>
            <td data-label="Horário"><span class="horario-badge">{hor}</span></td>
          </tr>'''.format(t=p['tipo'], mun=esc(p['municipio']), nome=esc(p['nome']), tipo=TIPO[p['tipo']],
                          resp=resp, cont=contatos, end=end, hor=esc(p.get('horario') or 'Consultar')))
cont = re.sub(r'<tr ng-repeat=.*?</tr>', '\n'.join(linhas), cont, flags=re.S)
pagina('contatos', 'Fale Conosco', 'Sede, postos Expresso Cidadão e unidades municipais em todo o Estado de Pernambuco',
       [grava('contatos', '01-conteudo', cont)])

# ---------------------------------------------------------------- PESQUISA DE PREÇOS
pesq = sem_cabecalho(ler('pesquisa-precos'))
ICONE = dict(re.findall(r'<span ng-switch-when="([^"]+)">([^<]+)</span>', pesq))
CHEVRON = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">'
           '<path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>')
cards = []
for c in dados['pesquisas']:
    cards.append('''        <button type="button" class="pesquisa-card" role="listitem" aria-label="Ver pesquisa: {nome}" aria-pressed="false"
           data-slug="{slug}" data-nome="{nome}" data-ano="{ano}" data-descricao="{desc}" data-url="{url}">
          <div class="pesquisa-card-icon" aria-hidden="true"><span>{ico}</span></div>
          <div class="pesquisa-card-body">
            <h3 class="pesquisa-card-title">{nome}</h3>
            <span class="pesquisa-card-ano">Ref: {ano}</span>
          </div>
          <div class="pesquisa-card-action" aria-hidden="true">{chev}</div>
        </button>'''.format(slug=c['slug'], nome=esc(c['nome']), ano=esc(c['ano_atual']), desc=esc(c['descricao']),
                            url=esc(c['url_procon']), ico=ICONE.get(c['slug'], '📊'), chev=CHEVRON))
grade = '      <div class="pesquisa-grid" role="list" aria-label="Categorias de pesquisa de preços">\n%s\n      </div>' % '\n'.join(cards)
painel = '''      <div class="pesquisa-painel" aria-live="polite" hidden>
        <div class="pesquisa-painel-header">
          <div>
            <h2 class="pesquisa-painel-title">
              <span data-campo="nome"></span>
              <span class="tag tag-secondary" style="font-size:.75rem;vertical-align:middle" data-campo="ano"></span>
            </h2>
            <p class="pesquisa-painel-desc" data-campo="descricao"></p>
          </div>
          <div class="d-flex gap-sm" style="flex-shrink:0;flex-wrap:wrap">
            <a class="btn btn-primary btn-sm" href="#" data-campo="url" target="_blank" rel="noopener" aria-label="Ver página completa da pesquisa no site do PROCON PE">🔗 Ver no site do PROCON PE</a>
            <button type="button" class="btn btn-outline btn-sm" data-acao="fechar" aria-label="Fechar painel de visualização">✕ Fechar</button>
          </div>
        </div>
        <div class="pesquisa-viewer">
          <div class="pesquisa-viewer-tabs" role="tablist" aria-label="Opções de visualização">
            <button type="button" class="viewer-tab active" data-aba="online" role="tab" aria-selected="true">🌐 Visualizar Online</button>
            <button type="button" class="viewer-tab" data-aba="sobre" role="tab" aria-selected="false">ℹ️ Sobre a Pesquisa</button>
          </div>
          <div data-painel-aba="online" role="tabpanel" aria-label="Visualização online da pesquisa">
            <div class="pesquisa-iframe-wrapper">
              <iframe class="pesquisa-iframe" title="Pesquisa de Preços" allowfullscreen loading="lazy" sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
              <div class="pesquisa-iframe-overlay">
                <p>Caso o conteúdo não apareça corretamente:</p>
                <a class="btn btn-primary" href="#" data-campo="url" target="_blank" rel="noopener">🔗 Abrir em nova aba</a>
              </div>
            </div>
          </div>
          <div data-painel-aba="sobre" role="tabpanel" class="pesquisa-sobre" aria-label="Informações sobre a pesquisa" hidden>
            <div class="pesquisa-sobre-grid">
              <div>
                <h3>O que é esta pesquisa?</h3>
                <p data-campo="descricao"></p>
              </div>
              <div>
                <h3>Como usar</h3>
                <ul>
                  <li>Compare os preços levantados antes de fazer suas compras</li>
                  <li>Se encontrar preço acima, denuncie ao PROCON PE</li>
                  <li>Os dados são coletados em estabelecimentos de todo o Estado</li>
                </ul>
              </div>
              <div>
                <h3>Período de referência</h3>
                <p>Ano de referência: <strong data-campo="ano"></strong></p>
                <p style="margin-top:.5rem">Atualizado periodicamente pela equipe técnica do PROCON PE.</p>
              </div>
              <div>
                <h3>Denunciar preço abusivo</h3>
                <p>Encontrou preço acima do levantado?</p>
                <a href="mailto:denuncia@procon.pe.gov.br" class="btn btn-primary btn-sm" style="margin-top:.5rem">✉ Enviar Denúncia</a>
              </div>
            </div>
            <div style="text-align:center;margin-top:var(--space-xl)">
              <a class="btn btn-primary" href="#" data-campo="url" target="_blank" rel="noopener" aria-label="Ver pesquisa completa no site oficial do PROCON PE">Ver pesquisa completa no site do PROCON PE →</a>
            </div>
          </div>
        </div>
      </div>
      <div class="pesquisa-empty-state" aria-live="polite">
        <span aria-hidden="true">👆</span>
        <p>Selecione uma categoria acima para visualizar a pesquisa de preços</p>
      </div>'''
corpo = re.search(r'<div ng-if="!vm\.loading">.*</div>\s*</div>\s*</section>', pesq, flags=re.S)
inicio = pesq[:pesq.index('<div ng-if="vm.loading"')]
pesq = inicio + grade + '\n' + painel + '\n  </div>\n</section>\n'
pagina('pesquisa-de-precos', 'Pesquisa de Preços',
       'Consulte as pesquisas realizadas pelo PROCON PE e compre com mais segurança. Visualize os dados diretamente ou faça o download.',
       [grava('pesquisa-de-precos', '01-conteudo', pesq)])

# ---------------------------------------------------------------- RANKING
def fmt_num(n):
    return '{:,}'.format(n).replace(',', '.')


lista = (ranking.get('empresas') or ranking.get('ranking') or [])[:10]
segmentos = []
for r in lista:
    if r['segmento'] not in segmentos:
        segmentos.append(r['segmento'])
MEDALHA = {1: '🥇', 2: '🥈', 3: '🥉'}
VAR = {'up': '<span class="var-up" aria-label="Subiu no ranking">▲</span>',
       'down': '<span class="var-down" aria-label="Desceu no ranking">▼</span>',
       'stable': '<span class="var-stable" aria-label="Manteve posição">—</span>'}
VAR_TXT = {'up': 'subiu', 'down': 'desceu', 'stable': 'estável'}
linhas = []
for r in lista:
    pos = r['posicao']
    cor = 'success' if r['resolucao_pct'] >= 75 else ('warning' if r['resolucao_pct'] >= 60 else 'danger')
    pos_html = ('<span title="{0}ª posição" aria-label="{0}ª posição">{1}</span>'.format(pos, MEDALHA[pos])
                if pos in MEDALHA else '<strong>%dº</strong>' % pos)
    linhas.append('''            <tr{top} data-empresa="{emp}" data-segmento="{seg}">
              <td class="rank-pos" aria-label="Posição {pos}">{posh}</td>
              <td class="rank-empresa"><strong>{emp}</strong></td>
              <td><span class="tag tag-light">{seg}</span></td>
              <td class="rank-num" aria-label="{n} reclamações"><strong>{nf}</strong></td>
              <td>
                <div class="resolucao-bar" aria-label="Taxa de resolução: {pct}%">
                  <div class="resolucao-fill resolucao-{cor}" style="width: {pct}%;" role="progressbar" aria-valuenow="{pct}" aria-valuemin="0" aria-valuemax="100"></div>
                  <span class="resolucao-pct">{pct}%</span>
                </div>
              </td>
              <td class="rank-variacao" aria-label="Variação: {vt}">{var}</td>
            </tr>'''.format(top=' class="rank-top3"' if pos <= 3 else '', emp=esc(r['empresa']), seg=esc(r['segmento']),
                            pos=pos, posh=pos_html, n=r['reclamacoes'], nf=fmt_num(r['reclamacoes']), pct=r['resolucao_pct'],
                            cor=cor, vt=VAR_TXT.get(r['variacao'], ''), var=VAR.get(r['variacao'], '')))
atualizado = ranking.get('ultima_atualizacao', '')
rank = '''<section class="section">
  <div class="container">
      <div class="ranking-header-info">
        <div>
          <h2 class="section-title">As 10 Empresas Mais Reclamadas</h2>
          <p class="text-muted">Período de referência: <strong data-ranking="periodo-rotulo">Mensal (Último Mês)</strong> — Última atualização: {atual}</p>
        </div>
        <div class="d-flex gap-md align-center" style="flex-wrap:wrap">
          <div class="ranking-periodo-toggle" role="group" aria-label="Filtrar por periodicidade">
            <button type="button" class="filtro-btn active" data-ranking-periodo="mensal">📅 Mensal</button>
            <button type="button" class="filtro-btn" data-ranking-periodo="anual">📆 Anual</button>
          </div>
          <div class="search-form" style="width:200px;border-radius:var(--radius-md)">
            <input class="search-input" type="search" data-ranking="busca" placeholder="Buscar empresa..." aria-label="Buscar empresa no ranking" style="padding:.5rem .75rem;font-size:var(--font-size-sm)">
          </div>
          <select data-ranking="segmento" aria-label="Filtrar por segmento" style="padding:.5rem .75rem;border:1px solid var(--color-border);border-radius:var(--radius-md);font-family:var(--font-primary);font-size:var(--font-size-sm);color:var(--color-text)">
            <option value="">Todos os segmentos</option>
{opts}
          </select>
        </div>
      </div>
      <div class="ranking-legenda" role="note" aria-label="Legenda de resolução">
        <span class="legenda-item legenda-success">● Alta resolução (≥75%)</span>
        <span class="legenda-item legenda-warning">● Média resolução (60-74%)</span>
        <span class="legenda-item legenda-danger">● Baixa resolução (&lt;60%)</span>
      </div>
      <div class="table-responsive" role="region" aria-label="Tabela de ranking de reclamações" tabindex="0">
        <table class="ranking-table" aria-label="Ranking de reclamações por empresa">
          <thead>
            <tr>
              <th scope="col" aria-label="Posição">#</th>
              <th scope="col">Empresa</th>
              <th scope="col">Segmento</th>
              <th scope="col" aria-label="Número de reclamações">Reclamações</th>
              <th scope="col" aria-label="Percentual de resolução">Resolução %</th>
              <th scope="col" aria-label="Variação em relação ao período anterior">Variação</th>
            </tr>
          </thead>
          <tbody>
{linhas}
          </tbody>
        </table>
        <p class="text-center text-muted" style="padding:2rem" data-ranking="vazio" hidden>Nenhuma empresa encontrada com os filtros selecionados.</p>
      </div>
      <div class="ranking-nota" style="margin-top:var(--space-xl)">
        <p class="text-muted" style="font-size:var(--font-size-sm)">
          <strong>Nota:</strong> Os dados apresentados referem-se às reclamações registradas diretamente no PROCON Pernambuco.
          O índice de resolução indica o percentual de casos resolvidos pelas empresas dentro do prazo estabelecido.
          Dados atualizados periodicamente.
        </p>
      </div>
  </div>
</section>'''.format(atual=esc(atualizado), opts='\n'.join('            <option value="%s">%s</option>' % (esc(s), esc(s)) for s in segmentos),
                     linhas='\n'.join(linhas))
pagina('ranking-de-reclamacoes', 'Ranking de Reclamações', 'Empresas com maior número de reclamações registradas no PROCON PE',
       [grava('ranking-de-reclamacoes', '01-conteudo', rank)])

# ---------------------------------------------------------------- MANIFESTO E DADOS
os.makedirs(DADOS, exist_ok=True)
json.dump(paginas, open(os.path.join(CONTEUDO, 'paginas.json'), 'w'), ensure_ascii=False, indent=1)

ativas = [n for n in noticias if n.get('ativo')]
saida = []
for n in ativas:
    img = re.search(r'photo-[0-9a-f-]+', n.get('imagem_url', ''))
    saida.append({
        'id': n['id'], 'titulo': n['titulo'], 'resumo': n['resumo'], 'conteudo': n['conteudo'],
        'categoria': n['categoria'], 'publicado_em': n['publicado_em'],
        'imagem': img.group(0) if img else '',
    })
json.dump(saida, open(os.path.join(DADOS, 'noticias.json'), 'w'), ensure_ascii=False, indent=1)

print('páginas:', len(paginas), '| notícias:', len(saida), '| ranking:', len(lista), '| atualizado:', atualizado)
for p in paginas:
    print(' -', p['slug'], '|', p['titulo'], '| pai:', p['pai'] or '-', '|', len(p['secoes']), 'seção(ões)')
