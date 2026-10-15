<?php
/**
 * Cabeçalho: barra do governo, controles de acessibilidade, logo, menu principal e busca.
 * Markup idêntico ao index.html da página de referência.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$procon_home = is_front_page();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<script>
try {
  var procF = localStorage.getItem('procon-fonte');
  if (procF === 'maior') document.body.classList.add('fonte-maior');
  else if (procF === 'menor') document.body.classList.add('fonte-menor');
  if (localStorage.getItem('procon-contraste') === 'true') document.body.classList.add('alto-contraste');
} catch (e) {}
</script>
<?php wp_body_open(); ?>

<?php if ( get_theme_mod( 'procon_vlibras', true ) ) : ?>
<div vw class="enabled">
  <div vw-access-button class="active"></div>
  <div vw-plugin-wrapper>
    <div class="vw-plugin-top-wrapper"></div>
  </div>
</div>
<?php endif; ?>

<a class="skip-link" href="#main-content">Ir para o conteúdo principal</a>

<div class="gov-bar" role="navigation" aria-label="Barra do Governo de Pernambuco">
  <div class="container">
    <?php
    wp_nav_menu( array(
        'theme_location' => 'procon-gov-bar',
        'container'      => false,
        'menu_class'     => 'gov-bar-links',
        'depth'          => 1,
        'fallback_cb'    => false,
        'items_wrap'     => '<ul id="%1$s" class="%2$s" aria-label="Links do Governo do Estado">%3$s</ul>',
    ) );
    ?>

    <div class="gov-bar-access" role="group" aria-label="Controles de acessibilidade">
      <button class="access-btn" type="button" data-a11y="fonte-mais" aria-label="Aumentar tamanho da fonte" title="Aumentar fonte">A+</button>
      <button class="access-btn" type="button" data-a11y="fonte-padrao" aria-label="Tamanho de fonte padrão" title="Fonte padrão">A</button>
      <button class="access-btn" type="button" data-a11y="fonte-menos" aria-label="Diminuir tamanho da fonte" title="Diminuir fonte">A-</button>
      <button class="access-btn" type="button" data-a11y="contraste" aria-label="Alternar alto contraste" title="Alto contraste">⊙</button>
      <a class="access-btn" href="<?php echo esc_url( home_url( '/acessibilidade/' ) ); ?>" aria-label="Página de acessibilidade">♿</a>
      <a class="access-btn" href="<?php echo esc_url( home_url( '/mapa-do-site/' ) ); ?>" aria-label="Mapa do site" title="Mapa do site">📋</a>
    </div>
  </div>
</div>

<header class="site-header" role="banner">
  <div class="header-inner">
    <a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="PROCON Pernambuco — Página inicial">
      <div class="site-logo-brasao" aria-hidden="true">
        <img src="<?php echo esc_url( procon_logo_url() ); ?>" alt="" class="brasao-img" onerror="this.parentElement.style.display='none'">
      </div>
      <div class="site-logo-text">
        <span class="site-logo-title">PROCON PE</span>
        <span class="site-logo-subtitle">Governo de Pernambuco</span>
      </div>
    </a>

    <nav class="main-nav" id="main-nav" role="navigation" aria-label="Menu principal">
      <?php
      wp_nav_menu( array(
          'theme_location' => 'procon-principal',
          'container'      => false,
          'depth'          => 2,
          'fallback_cb'    => false,
          'walker'         => new Procon_Nav_Walker(),
          'items_wrap'     => '<ul class="nav-list" role="list">%3$s</ul>',
      ) );
      ?>
    </nav>

    <div class="d-flex align-center gap-sm">
      <?php if ( ! $procon_home ) : // Na página inicial a busca fica no banner. ?>
      <button class="header-search-btn" type="button" aria-label="Abrir busca" aria-expanded="false" title="Pesquisar">
        <?php echo procon_icon( 'search', 18 ); // phpcs:ignore ?>
      </button>
      <?php endif; ?>
      <button class="menu-toggle" type="button" aria-label="Abrir menu de navegação" aria-expanded="false" aria-controls="main-nav">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>

  <?php if ( ! $procon_home ) : ?>
  <div class="header-search-bar" role="search" aria-label="Busca no site" aria-hidden="true">
    <div class="container">
      <form class="search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input class="search-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="O que você está procurando?" aria-label="Campo de busca" autocomplete="off" id="header-search-input">
        <button class="search-btn" type="submit" aria-label="Buscar">
          <?php echo procon_icon( 'search', 18 ); // phpcs:ignore ?>
          Buscar
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</header>

<div class="mobile-nav-overlay" aria-hidden="true"></div>

<main id="main-content" tabindex="-1">
