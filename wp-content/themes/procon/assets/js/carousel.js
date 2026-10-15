/**
 * PROCON PE — Carrossel de Notícias (Vanilla JS)
 * Totalmente acessível: suporta teclado, aria-labels e pausa automática
 * Origem: assets/js/carousel.js da página de referência (inicialização adaptada ao WordPress).
 */

(function() {
  'use strict';

  // No WordPress o markup do carrossel já vem renderizado pelo servidor (shortcode).
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarousel);
  } else {
    initCarousel();
  }

  function initCarousel() {
    var track = document.querySelector('.carousel-track');
    if (!track) return;
    track.dataset.carouselInit = 'true';

    var cards = track.querySelectorAll('.carousel-card');
    var dotsContainer = document.querySelector('.carousel-dots');
    var btnPrev = document.querySelector('.carousel-btn-prev');
    var btnNext = document.querySelector('.carousel-btn-next');
    var pauseBtn = document.querySelector('.carousel-pause-btn');

    if (!cards.length) return;

    var currentIndex = 0;
    var autoplayInterval = null;
    var isPaused = false;
    var AUTOPLAY_DELAY = 5000;
    var visibleCount = getVisibleCount();

    var totalSlides = Math.max(0, cards.length - visibleCount + 1);

    // Criar dots
    if (dotsContainer) {
      dotsContainer.innerHTML = '';
      for (var i = 0; i < totalSlides; i++) {
        var dot = document.createElement('button');
        dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', 'Ir para notícia ' + (i + 1));
        dot.setAttribute('type', 'button');
        dot.dataset.index = i;
        dot.addEventListener('click', function() {
          goTo(parseInt(this.dataset.index));
          resetAutoplay();
        });
        dotsContainer.appendChild(dot);
      }
    }

    function getVisibleCount() {
      var w = window.innerWidth;
      if (w <= 600) return 1;
      if (w <= 900) return 2;
      return 3;
    }

    function getCardWidth() {
      if (!cards[0]) return 0;
      var card = cards[0];
      var style = window.getComputedStyle(track);
      var gap = parseFloat(style.gap) || 24;
      return card.offsetWidth + gap;
    }

    function goTo(index) {
      visibleCount = getVisibleCount();
      totalSlides = Math.max(1, cards.length - visibleCount + 1);
      currentIndex = Math.max(0, Math.min(index, totalSlides - 1));

      var offset = currentIndex * getCardWidth();
      track.style.transform = 'translateX(-' + offset + 'px)';

      // Atualizar ARIA
      cards.forEach(function(card, i) {
        var visible = i >= currentIndex && i < currentIndex + visibleCount;
        card.setAttribute('aria-hidden', visible ? 'false' : 'true');
        card.style.visibility = visible ? 'visible' : '';
      });

      // Atualizar dots
      var dots = dotsContainer ? dotsContainer.querySelectorAll('.carousel-dot') : [];
      dots.forEach(function(dot, i) {
        dot.classList.toggle('active', i === currentIndex);
        dot.setAttribute('aria-current', i === currentIndex ? 'true' : 'false');
      });

      // Atualizar botões
      if (btnPrev) btnPrev.disabled = currentIndex === 0;
      if (btnNext) btnNext.disabled = currentIndex >= totalSlides - 1;
    }

    function next() {
      var nextIdx = currentIndex + 1 >= totalSlides ? 0 : currentIndex + 1;
      goTo(nextIdx);
    }

    function prev() {
      var prevIdx = currentIndex - 1 < 0 ? totalSlides - 1 : currentIndex - 1;
      goTo(prevIdx);
    }

    function startAutoplay() {
      if (isPaused) return;
      clearInterval(autoplayInterval);
      autoplayInterval = setInterval(next, AUTOPLAY_DELAY);
    }

    function stopAutoplay() {
      clearInterval(autoplayInterval);
    }

    function resetAutoplay() {
      stopAutoplay();
      startAutoplay();
    }

    // Botões prev/next
    if (btnPrev) {
      btnPrev.addEventListener('click', function() {
        prev();
        resetAutoplay();
      });
    }

    if (btnNext) {
      btnNext.addEventListener('click', function() {
        next();
        resetAutoplay();
      });
    }

    // Botão pausa (acessibilidade)
    if (pauseBtn) {
      pauseBtn.addEventListener('click', function() {
        isPaused = !isPaused;
        if (isPaused) {
          stopAutoplay();
          pauseBtn.innerHTML = '▶ Retomar';
          pauseBtn.setAttribute('aria-label', 'Retomar rotação automática');
        } else {
          startAutoplay();
          pauseBtn.innerHTML = '⏸ Pausar';
          pauseBtn.setAttribute('aria-label', 'Pausar rotação automática');
        }
      });
    }

    // Pausa ao passar o mouse (acessibilidade)
    var wrapper = document.querySelector('.carousel-wrapper');
    if (wrapper) {
      wrapper.addEventListener('mouseenter', stopAutoplay);
      wrapper.addEventListener('mouseleave', function() {
        if (!isPaused) startAutoplay();
      });

      // Pausa ao focar em elemento interno
      wrapper.addEventListener('focusin', stopAutoplay);
      wrapper.addEventListener('focusout', function() {
        if (!isPaused) startAutoplay();
      });
    }

    // Navegação por teclado
    document.addEventListener('keydown', function(e) {
      var focused = document.activeElement;
      if (!wrapper || !wrapper.contains(focused)) return;

      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        prev();
        resetAutoplay();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        next();
        resetAutoplay();
      }
    });

    // Touch/swipe
    var touchStartX = 0;
    var touchEndX = 0;

    if (wrapper) {
      wrapper.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
      }, { passive: true });

      wrapper.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        var diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50) {
          if (diff > 0) next(); else prev();
          resetAutoplay();
        }
      }, { passive: true });
    }

    // Redimensionamento responsivo
    var resizeTimeout;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(function() {
        var newVisible = getVisibleCount();
        if (newVisible !== visibleCount) {
          visibleCount = newVisible;
          totalSlides = Math.max(1, cards.length - visibleCount + 1);
          if (currentIndex >= totalSlides) {
            currentIndex = totalSlides - 1;
          }
          goTo(currentIndex);
        }
      }, 200);
    });

    // Inicializar
    goTo(0);
    startAutoplay();

    // Respeitar prefers-reduced-motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      stopAutoplay();
      isPaused = true;
      if (pauseBtn) {
        pauseBtn.innerHTML = '▶ Retomar';
        pauseBtn.setAttribute('aria-label', 'Retomar rotação automática');
      }
    }
  }

})();
