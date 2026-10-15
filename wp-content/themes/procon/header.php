<?php
/**
 * Cabeçalho: barra do governo + cabeçalho institucional (logo, menu e busca).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content">Pular para o conteúdo</a>

<div class="gov-bar">
	<div class="container gov-bar__inner">
		<a href="https://www.pe.gov.br" target="_blank" rel="noopener">Governo de Pernambuco</a>
		<div class="a11y-tools" role="group" aria-label="Acessibilidade">
			<button type="button" data-a11y="fonte-menos" aria-label="Diminuir tamanho da fonte">A-</button>
			<button type="button" data-a11y="fonte-mais" aria-label="Aumentar tamanho da fonte">A+</button>
			<button type="button" data-a11y="contraste" aria-pressed="false">Alto contraste</button>
		</div>
	</div>
</div>

<header class="site-header header-full-width">
	<div class="container site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img src="<?php echo esc_url( procon_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — Governo de Pernambuco" width="194" height="72">
		</a>

		<nav class="site-nav" aria-label="Menu principal">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'procon-principal',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
			?>
		</nav>

		<form class="site-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label for="procon-busca" class="screen-reader-text">Buscar no site</label>
			<input id="procon-busca" type="search" name="s" placeholder="O que você procura?" value="<?php echo esc_attr( get_search_query() ); ?>">
			<button type="submit"><?php echo procon_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Buscar</button>
		</form>
	</div>
</header>
