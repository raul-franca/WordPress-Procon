/* Controles de acessibilidade da barra do governo: tamanho da fonte e alto contraste. */
(function () {
  var root = document.documentElement;
  var MIN = 80, MAX = 150, PASSO = 10;

  function salvar(chave, valor) {
    try { localStorage.setItem(chave, valor); } catch (e) {}
  }

  function fonteAtual() {
    return parseInt(root.style.fontSize, 10) || 100;
  }

  function aplicarFonte(pct) {
    pct = Math.min(MAX, Math.max(MIN, pct));
    root.style.fontSize = pct + '%';
    salvar('procon-fonte', String(pct));
  }

  var botaoContraste = document.querySelector('[data-a11y="contraste"]');
  if (botaoContraste) {
    botaoContraste.setAttribute('aria-pressed', root.classList.contains('alto-contraste') ? 'true' : 'false');
  }

  document.addEventListener('click', function (evento) {
    var botao = evento.target.closest('[data-a11y]');
    if (!botao) return;

    switch (botao.getAttribute('data-a11y')) {
      case 'fonte-mais':
        aplicarFonte(fonteAtual() + PASSO);
        break;
      case 'fonte-menos':
        aplicarFonte(fonteAtual() - PASSO);
        break;
      case 'contraste':
        var ativo = root.classList.toggle('alto-contraste');
        botao.setAttribute('aria-pressed', ativo ? 'true' : 'false');
        salvar('procon-contraste', ativo ? '1' : '0');
        break;
    }
  });
})();
