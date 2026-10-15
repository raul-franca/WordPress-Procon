<?php
/**
 * Tema filho PROCON Pernambuco (base: Hello Elementor).
 *
 * O visual vem do CSS da página de referência (assets/css/referencia/), carregado sem
 * alterações; assets/css/wp-ajustes.css concentra as adaptações ao WordPress/Elementor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PROCON_THEME_VERSION', '2.0.0' );

require_once __DIR__ . '/inc/icons.php';
require_once __DIR__ . '/inc/customizer.php';
require_once __DIR__ . '/inc/class-procon-nav-walker.php';
require_once __DIR__ . '/inc/template-tags.php';
require_once __DIR__ . '/inc/shortcodes.php';

/**
 * Suportes do tema e posições de menu.
 */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'custom-logo', array(
		'height'      => 379,
		'width'       => 1024,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'post-thumbnails' );
	add_post_type_support( 'page', 'excerpt' ); // O resumo da página vira o subtítulo do cabeçalho.

	register_nav_menus( array(
		'procon-principal'      => 'Menu principal (cabeçalho)',
		'procon-gov-bar'        => 'Barra do governo (topo)',
		'procon-rodape-servicos' => 'Rodapé — Serviços',
		'procon-rodape-inst'    => 'Rodapé — Inf. Institucionais',
		'procon-rodape-orgaos'  => 'Rodapé — Órgãos Parceiros',
		'procon-rodape-base'    => 'Rodapé — Links inferiores',
	) );
}, 20 );

/**
 * O CSS de referência já traz reset, tipografia e layout: desliga o CSS do Hello Elementor.
 */
add_filter( 'hello_elementor_enqueue_style', '__return_false' );
add_filter( 'hello_elementor_enqueue_theme_style', '__return_false' );
add_filter( 'hello_elementor_header_footer', '__return_false' );

/**
 * Estilos e scripts. A versão é a data de modificação do arquivo (invalida cache a cada edição).
 */
add_action( 'wp_enqueue_scripts', function () {
	$uri = get_stylesheet_directory_uri() . '/assets';
	$dir = get_stylesheet_directory() . '/assets';
	$ver = function ( $file ) use ( $dir ) {
		return (string) ( @filemtime( $dir . $file ) ?: PROCON_THEME_VERSION );
	};

	wp_enqueue_style( 'procon-fontes', 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@400;600;700&display=swap', array(), null );

	// Mesma ordem do <head> da página de referência.
	$deps = array( 'procon-fontes' );
	foreach ( array( 'variables', 'global', 'header', 'carousel', 'footer', 'home', 'inner-pages' ) as $css ) {
		$file = '/css/referencia/' . $css . '.css';
		wp_enqueue_style( 'procon-' . $css, $uri . $file, $deps, $ver( $file ) );
		$deps = array( 'procon-' . $css );
	}
	wp_enqueue_style( 'procon-wp-ajustes', $uri . '/css/wp-ajustes.css', $deps, $ver( '/css/wp-ajustes.css' ) );

	wp_enqueue_script( 'procon', $uri . '/js/procon.js', array(), $ver( '/js/procon.js' ), array( 'in_footer' => true ) );
	if ( is_front_page() ) {
		wp_enqueue_script( 'procon-carousel', $uri . '/js/carousel.js', array(), $ver( '/js/carousel.js' ), array( 'in_footer' => true ) );
	}

	if ( get_theme_mod( 'procon_vlibras', true ) ) {
		wp_enqueue_script( 'vlibras', 'https://vlibras.gov.br/app/vlibras-plugin.js', array(), null, array( 'in_footer' => true ) );
		wp_add_inline_script( 'vlibras', "new window.VLibras.Widget('https://vlibras.gov.br/app');" );
	}
}, 20 );

add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/**
 * Metadados da página de referência.
 */
add_action( 'wp_head', function () {
	echo '<meta name="theme-color" content="#00295b">' . "\n";
	if ( is_front_page() ) {
		echo '<meta name="description" content="' . esc_attr( 'PROCON Pernambuco — Proteção e Defesa do Consumidor. Pesquisa de preços, ranking de reclamações, notícias e atendimento ao cidadão.' ) . '">' . "\n";
	}
}, 2 );

/**
 * Notícias: 6 por página (como na referência) e busca restrita às notícias.
 */
add_action( 'pre_get_posts', function ( WP_Query $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_home() || $query->is_category() || $query->is_search() ) {
		$query->set( 'posts_per_page', 6 );
	}
	if ( $query->is_search() ) {
		$query->set( 'post_type', 'post' );
	}
} );

/**
 * Cabeçalho das páginas internas (breadcrumb + faixa com título) também nas páginas do Elementor.
 */
add_action( 'elementor/page_templates/header-footer/before_content', function () {
	if ( is_page() && ! is_front_page() ) {
		procon_page_header();
	}
} );

/**
 * URL do logo: o definido em Personalizar > Identidade do site ou o do tema.
 */
function procon_logo_url() {
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $src ) {
			return $src;
		}
	}
	return get_stylesheet_directory_uri() . '/assets/img/logo-procon.png';
}
