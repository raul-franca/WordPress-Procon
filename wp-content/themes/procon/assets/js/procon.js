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
   * Ranking: período, busca por empresa e segmento
   * ------------------------------------------------------------ */
  var ranking = document.querySelector('.ranking-table');
  if (ranking) {
    var busca = document.querySelector('[data-ranking="busca"]');
    var segmento = document.querySelector('[data-ranking="segmento"]');
    var vazio = document.querySelector('[data-ranking="vazio"]');
    var rotulo = document.querySelector('[data-ranking="periodo-rotulo"]');

    var filtrarRanking = function () {
      var q = busca ? busca.value.trim().toLowerCase() : '';
      var seg = segmento ? segmento.value : '';
      var visiveis = 0;
      ranking.querySelectorAll('tbody tr').forEach(function (tr) {
        var ok = (!seg || tr.getAttribute('data-segmento') === seg) &&
                 (!q || tr.getAttribute('data-empresa').toLowerCase().indexOf(q) >= 0);
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
        if (rotulo) rotulo.textContent = btn.getAttribute('data-ranking-periodo') === 'mensal'
          ? 'Mensal (Último Mês)' : 'Anual (Acumulado 12 meses)';
      });
    });
  }

  /* ------------------------------------------------------------
   * Pesquisa de preços: seleção de categoria e painel de visualização
   * ------------------------------------------------------------ */
  var pesquisaCards = document.querySelectorAll('.pesquisa-card[data-slug]');
  var painel = document.querySelector('.pesquisa-painel');
  var vazioPesquisa = document.querySelector('.pesquisa-empty-state');
  if (pesquisaCards.length && painel) {
    var ativa = null;

    var setTexto = function (sel, valor) {
      painel.querySelectorAll(sel).forEach(function (el) { el.textContent = valor; });
    };

    var fecharPainel = function () {
      ativa = null;
      painel.hidden = true;
      if (vazioPesquisa) vazioPesquisa.hidden = false;
      pesquisaCards.forEach(function (c) {
        c.classList.remove('pesquisa-card-ativa');
        c.setAttribute('aria-pressed', 'false');
      });
      var iframe = painel.querySelector('iframe');
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

    pesquisaCards.forEach(function (card) {
      card.addEventListener('click', function () {
        if (ativa === card) { fecharPainel(); return; }
        ativa = card;
        pesquisaCards.forEach(function (c) {
          var on = c === card;
          c.classList.toggle('pesquisa-card-ativa', on);
          c.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        var nome = card.getAttribute('data-nome');
        var url = card.getAttribute('data-url');
        setTexto('[data-campo="nome"]', nome);
        setTexto('[data-campo="ano"]', card.getAttribute('data-ano'));
        setTexto('[data-campo="descricao"]', card.getAttribute('data-descricao'));
        painel.querySelectorAll('[data-campo="url"]').forEach(function (a) { a.setAttribute('href', url); });
        var iframe = painel.querySelector('iframe');
        if (iframe) {
          iframe.setAttribute('src', url);
          iframe.setAttribute('title', 'Pesquisa de Preços — ' + nome);
        }
        painel.setAttribute('aria-label', 'Pesquisa selecionada: ' + nome);
        painel.hidden = false;
        if (vazioPesquisa) vazioPesquisa.hidden = true;
        mostrarAba('online');
        setTimeout(function () { painel.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100);
      });
    });

    painel.querySelectorAll('.viewer-tab').forEach(function (t) {
      t.addEventListener('click', function () { mostrarAba(t.getAttribute('data-aba')); });
    });
    var fechar = painel.querySelector('[data-acao="fechar"]');
    if (fechar) fechar.addEventListener('click', fecharPainel);
  }
})();
