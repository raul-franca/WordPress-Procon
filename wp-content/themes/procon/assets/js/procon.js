/**
 * PROCON PE — comportamento do site (substitui o AngularJS da página de referência).
 * Cada bloco só age se encontrar seus elementos na página.
 */
(function () {
  'use strict';

  var body = document.body;

  function salvar(chave, valor) {
    try { localStorage.setItem(chave, valor); } catch (e) {}
  }

  function onClick(seletor, fn) {
    document.addEventListener('click', function (e) {
      var alvo = e.target.closest(seletor);
      if (alvo) fn(alvo, e);
    });
  }

  /* ------------------------------------------------------------
   * Acessibilidade: tamanho da fonte e alto contraste
   * (as preferências salvas já são aplicadas no início do <body>)
   * ------------------------------------------------------------ */
  onClick('[data-a11y]', function (btn) {
    switch (btn.getAttribute('data-a11y')) {
      case 'fonte-mais':
        body.classList.remove('fonte-menor', 'fonte-padrao');
        body.classList.add('fonte-maior');
        salvar('procon-fonte', 'maior');
        break;
      case 'fonte-menos':
        body.classList.remove('fonte-maior', 'fonte-padrao');
        body.classList.add('fonte-menor');
        salvar('procon-fonte', 'menor');
        break;
      case 'fonte-padrao':
        body.classList.remove('fonte-maior', 'fonte-menor');
        salvar('procon-fonte', 'padrao');
        break;
      case 'contraste':
        salvar('procon-contraste', body.classList.toggle('alto-contraste') ? 'true' : 'false');
        break;
    }
  });

  /* ------------------------------------------------------------
   * Menu principal: mobile, dropdowns e busca do cabeçalho
   * ------------------------------------------------------------ */
  var nav = document.getElementById('main-nav');
  var toggle = document.querySelector('.menu-toggle');
  var overlay = document.querySelector('.mobile-nav-overlay');

  function setMenu(aberto) {
    if (!nav) return;
    nav.classList.toggle('open', aberto);
    if (overlay) overlay.classList.toggle('open', aberto);
    if (toggle) toggle.setAttribute('aria-expanded', aberto ? 'true' : 'false');
  }

  if (toggle) toggle.addEventListener('click', function () { setMenu(!nav.classList.contains('open')); });
  if (overlay) overlay.addEventListener('click', function () { setMenu(false); });

  function fecharDropdowns(exceto) {
    document.querySelectorAll('.nav-item.open').forEach(function (item) {
      if (item === exceto) return;
      item.classList.remove('open');
      var b = item.querySelector(':scope > .nav-link');
      if (b) b.setAttribute('aria-expanded', 'false');
    });
  }

  onClick('.nav-item > button.nav-link', function (btn, e) {
    e.stopPropagation();
    var item = btn.parentElement;
    fecharDropdowns(item);
    var aberto = item.classList.toggle('open');
    btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.nav-item') && !e.target.closest('.menu-toggle')) fecharDropdowns(null);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { fecharDropdowns(null); setMenu(false); }
  });

  var searchBtn = document.querySelector('.header-search-btn');
  var searchBar = document.querySelector('.header-search-bar');
  if (searchBtn && searchBar) {
    searchBtn.addEventListener('click', function () {
      var aberto = searchBar.classList.toggle('open');
      searchBtn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
      searchBtn.setAttribute('aria-label', aberto ? 'Fechar busca' : 'Abrir busca');
      searchBar.setAttribute('aria-hidden', aberto ? 'false' : 'true');
      if (aberto) {
        setTimeout(function () {
          var input = searchBar.querySelector('input');
          if (input) input.focus();
        }, 300);
      }
    });
  }

  /* ------------------------------------------------------------
   * Institucional: abas (aceita âncora na URL, ex.: /institucional/#lgpd)
   * ------------------------------------------------------------ */
  var instBtns = document.querySelectorAll('.inst-nav-btn[data-secao]');
  if (instBtns.length) {
    var mostrarSecao = function (secao) {
      var existe = document.querySelector('.inst-content [data-secao="' + secao + '"]');
      if (!existe) return;
      instBtns.forEach(function (b) {
        var ativo = b.getAttribute('data-secao') === secao;
        b.classList.toggle('active', ativo);
        b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
      });
      document.querySelectorAll('.inst-content [data-secao]').forEach(function (art) {
        art.hidden = art.getAttribute('data-secao') !== secao;
      });
    };
    instBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        mostrarSecao(b.getAttribute('data-secao'));
        if (history.replaceState) history.replaceState(null, '', '#' + b.getAttribute('data-secao'));
      });
    });
    var porHash = function () { if (location.hash) mostrarSecao(location.hash.slice(1)); };
    window.addEventListener('hashchange', porHash);
    porHash();
  }

  /* ------------------------------------------------------------
   * Contatos: filtro de postos por tipo
   * ------------------------------------------------------------ */
  var filtrosPostos = document.querySelectorAll('.postos-filtros .filtro-btn[data-filtro]');
  filtrosPostos.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var filtro = btn.getAttribute('data-filtro');
      filtrosPostos.forEach(function (b) {
        var ativo = b === btn;
        b.classList.toggle('active', ativo);
        b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
      });
      document.querySelectorAll('.contato-table tbody tr[data-tipo]').forEach(function (tr) {
        tr.hidden = filtro !== 'todos' && tr.getAttribute('data-tipo') !== filtro;
      });
    });
  });

  /* ------------------------------------------------------------
   * Ranking: período (mensal/anual), busca por empresa e segmento
   * ------------------------------------------------------------ */
  var ranking = document.querySelector('.ranking-table');
  if (ranking) {
    var busca = document.querySelector('[data-ranking="busca"]');
    var segmento = document.querySelector('[data-ranking="segmento"]');
    var vazio = document.querySelector('[data-ranking="vazio"]');
    var rotulo = document.querySelector('[data-ranking="periodo-rotulo"]');
    var periodoAtivo = 'mensal';

    var filtrarRanking = function () {
      var q = busca ? busca.value.trim().toLowerCase() : '';
      var seg = segmento ? segmento.value : '';
      var visiveis = 0;
      ranking.querySelectorAll('tbody tr[data-periodo]').forEach(function (tr) {
        var matchPeriodo = tr.getAttribute('data-periodo') === periodoAtivo;
        var matchSeg = !seg || tr.getAttribute('data-segmento') === seg;
        var matchBusca = !q || (tr.getAttribute('data-empresa') || '').toLowerCase().indexOf(q) >= 0;

        var ok = matchPeriodo && matchSeg && matchBusca;
        tr.hidden = !ok;
        if (ok) visiveis++;
      });
      if (vazio) vazio.hidden = visiveis > 0;
    };

    if (busca) busca.addEventListener('input', filtrarRanking);
    if (segmento) segmento.addEventListener('change', filtrarRanking);

    document.querySelectorAll('[data-ranking-periodo]').forEach(function (btn, _, todos) {
      btn.addEventListener('click', function () {
        todos.forEach(function (b) { b.classList.toggle('active', b === btn); });
        periodoAtivo = btn.getAttribute('data-ranking-periodo') || 'mensal';
        var rotuloMensal = btn.getAttribute('data-rotulo-mensal') || 'Mensal';
        var rotuloAnual  = btn.getAttribute('data-rotulo-anual')  || 'Anual';
        if (rotulo) {
          rotulo.textContent = periodoAtivo === 'mensal' ? rotuloMensal : rotuloAnual;
        }
        filtrarRanking();
      });
    });

    filtrarRanking();
  }

  /* ------------------------------------------------------------
   * Pesquisa de preços: seleção de categoria, abas, anos (2023-2026), meses e PDF
   * ------------------------------------------------------------ */
  var pesquisaCards = document.querySelectorAll('.pesquisa-card[data-slug]');
  var painel = document.querySelector('.pesquisa-painel');
  var vazioPesquisa = document.querySelector('.pesquisa-empty-state');

  if (pesquisaCards.length && painel) {
    var slugAtivo = null;
    var anoAtivo = '2026';

    var anosContainer  = painel.querySelector('[data-container="anos"]');
    var mesesContainer = painel.querySelector('[data-container="meses"]');
    var tagAnoAtivo    = painel.querySelector('[data-campo="ano-ativo"]');
    var iframe         = painel.querySelector('.pesquisa-iframe');
    var avisoSemPdf    = painel.querySelector('[data-estado="sem-pdf"]');
    var linkAbrir      = painel.querySelector('[data-pdf-acao="abrir"]');
    var linkBaixar     = painel.querySelector('[data-pdf-acao="baixar"]');

    var setTexto = function (sel, valor) {
      painel.querySelectorAll(sel).forEach(function (el) { el.textContent = valor; });
    };

    var fecharPainel = function () {
      slugAtivo = null;
      painel.hidden = true;
      if (vazioPesquisa) vazioPesquisa.hidden = false;
      pesquisaCards.forEach(function (c) {
        c.classList.remove('pesquisa-card-ativa');
        c.setAttribute('aria-pressed', 'false');
      });
      if (iframe) iframe.removeAttribute('src');
    };

    var mostrarAba = function (aba) {
      painel.querySelectorAll('.viewer-tab').forEach(function (t) {
        var on = t.getAttribute('data-aba') === aba;
        t.classList.toggle('active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      painel.querySelectorAll('[data-painel-aba]').forEach(function (p) {
        p.hidden = p.getAttribute('data-painel-aba') !== aba;
      });
    };

    var obterAcervoCategoria = function (slug) {
      var globalAcervo = window.PROCON_PESQUISAS_ACERVO || {};
      return globalAcervo[slug] || {};
    };

    var carregarPdfNoPainel = function (url, titulo) {
      if (url && url !== '#') {
        if (iframe) {
          iframe.hidden = false;
          iframe.setAttribute('src', url);
          iframe.setAttribute('title', titulo || 'Relatório PDF');
        }
        if (avisoSemPdf) avisoSemPdf.hidden = true;
        if (linkAbrir)  { linkAbrir.href = url; linkAbrir.style.pointerEvents = 'auto'; linkAbrir.style.opacity = '1'; }
        if (linkBaixar) { linkBaixar.href = url; linkBaixar.style.pointerEvents = 'auto'; linkBaixar.style.opacity = '1'; }
      } else {
        if (iframe) {
          iframe.removeAttribute('src');
          iframe.hidden = true;
        }
        if (avisoSemPdf) avisoSemPdf.hidden = false;
        if (linkAbrir)  { linkAbrir.href = '#'; linkAbrir.style.pointerEvents = 'none'; linkAbrir.style.opacity = '0.45'; }
        if (linkBaixar) { linkBaixar.href = '#'; linkBaixar.style.pointerEvents = 'none'; linkBaixar.style.opacity = '0.45'; }
      }
    };

    var renderizarMeses = function () {
      if (!mesesContainer || !slugAtivo) return;
      if (tagAnoAtivo) tagAnoAtivo.textContent = anoAtivo;

      var acervoCat = obterAcervoCategoria(slugAtivo);
      var listaMeses = acervoCat[anoAtivo] || [];

      var html = '<span class="pesquisa-filtro-rotulo">Mês:</span>';
      if (listaMeses.length > 0) {
        listaMeses.forEach(function (item, idx) {
          var ativoClass = idx === 0 ? ' active' : '';
          html += '<button type="button" class="pesquisa-pill' + ativoClass + '" data-mes-idx="' + idx + '">' + item.mes + '</button>';
        });
        mesesContainer.innerHTML = html;
        carregarPdfNoPainel(listaMeses[0].url, listaMeses[0].titulo);

        mesesContainer.querySelectorAll('[data-mes-idx]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            mesesContainer.querySelectorAll('.pesquisa-pill').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var item = listaMeses[parseInt(btn.getAttribute('data-mes-idx'), 10)];
            if (item) carregarPdfNoPainel(item.url, item.titulo);
          });
        });
      } else {
        html += '<span style="color:#64748b; font-size:0.8rem;">Sem edições em ' + anoAtivo + '</span>';
        mesesContainer.innerHTML = html;
        carregarPdfNoPainel('', '');
      }
    };

    // Cliques nos seletores de ano
    if (anosContainer) {
      anosContainer.querySelectorAll('[data-ano-pill]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          anoAtivo = btn.getAttribute('data-ano-pill');
          anosContainer.querySelectorAll('.pesquisa-pill').forEach(function (b) { b.classList.remove('active'); });
          btn.classList.add('active');
          renderizarMeses();
        });
      });
    }

    // Clique nos cards de categoria
    pesquisaCards.forEach(function (card) {
      card.addEventListener('click', function () {
        var slug = card.getAttribute('data-slug');
        if (slugAtivo === slug && !painel.hidden) {
          fecharPainel();
          return;
        }

        slugAtivo = slug;
        pesquisaCards.forEach(function (c) {
          var on = c === card;
          c.classList.toggle('pesquisa-card-ativa', on);
          c.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        var nome      = card.getAttribute('data-nome') || '';
        var emoji     = card.getAttribute('data-emoji') || '📊';
        var descricao = card.getAttribute('data-descricao') || '';

        setTexto('[data-campo="nome"]', nome);
        setTexto('[data-campo="emoji"]', emoji);
        setTexto('[data-campo="descricao"]', descricao);
        painel.setAttribute('aria-label', 'Pesquisa selecionada: ' + nome);

        painel.hidden = false;
        if (vazioPesquisa) vazioPesquisa.hidden = true;

        mostrarAba('online');

        // Escolhe o ano mais recente com PDF cadastrado ou o ano ativo
        var acervoCat = obterAcervoCategoria(slugAtivo);
        var anosDisponiveis = ['2026', '2025', '2024', '2023'];
        var primeiroComPdf = anosDisponiveis.find(function (a) { return (acervoCat[a] || []).length > 0; });
        anoAtivo = primeiroComPdf || '2026';

        if (anosContainer) {
          anosContainer.querySelectorAll('[data-ano-pill]').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-ano-pill') === anoAtivo);
          });
        }

        renderizarMeses();

        setTimeout(function () {
          painel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 80);
      });
    });

    // Abas
    painel.querySelectorAll('.viewer-tab').forEach(function (t) {
      t.addEventListener('click', function () { mostrarAba(t.getAttribute('data-aba')); });
    });

    // Botão fechar
    var fechar = painel.querySelector('[data-acao="fechar"]');
    if (fechar) fechar.addEventListener('click', fecharPainel);
  }
})();
