<?php
/**
 * Tema filho PROCON Pernambuco (base: Hello Elementor).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PROCON_THEME_VERSION', '1.0.0' );

require_once __DIR__ . '/inc/icons.php';
require_once __DIR__ . '/inc/customizer.php';

/**
 * Suportes do tema: logo personalizado e menus.
 */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'custom-logo', array(
		'height'      => 379,
		'width'       => 1024,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	register_nav_menus( array(
		'procon-principal' => 'Menu principal (cabeçalho)',
	) );
}, 20 );

/**
 * Fontes e estilos. Ordem: Roboto -> tokens -> base -> cabeçalho -> rodapé.
 */
add_action( 'wp_enqueue_scripts', function () {
	$uri = get_stylesheet_directory_uri() . '/assets';
	$dir = get_stylesheet_directory() . '/assets';
	// Versão = data de modificação do arquivo: qualquer edição invalida o cache do navegador.
	$ver = function ( $file ) use ( $dir ) {
		return (string) ( @filemtime( $dir . $file ) ?: PROCON_THEME_VERSION );
	};

	wp_enqueue_style( 'procon-roboto', 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap', array(), null );
	wp_enqueue_style( 'procon-variables', $uri . '/css/variables.css', array(), $ver( '/css/variables.css' ) );
	wp_enqueue_style( 'procon-global', $uri . '/css/global.css', array( 'procon-variables', 'hello-elementor' ), $ver( '/css/global.css' ) );
	wp_enqueue_style( 'procon-header', $uri . '/css/header.css', array( 'procon-global' ), $ver( '/css/header.css' ) );
	wp_enqueue_style( 'procon-footer', $uri . '/css/footer.css', array( 'procon-global' ), $ver( '/css/footer.css' ) );
	wp_enqueue_style( 'procon-home', $uri . '/css/home.css', array( 'procon-global' ), $ver( '/css/home.css' ) );

	wp_enqueue_script( 'procon-acessibilidade', $uri . '/js/acessibilidade.js', array(), $ver( '/js/acessibilidade.js' ), array( 'strategy' => 'defer' ) );

	if ( get_theme_mod( 'procon_vlibras', true ) ) {
		wp_enqueue_script( 'vlibras', 'https://vlibras.gov.br/app/vlibras-plugin.js', array(), null, array( 'in_footer' => true ) );
		wp_add_inline_script( 'vlibras', "new window.VLibras.Widget('https://vlibras.gov.br/app');" );
	}
}, 20 );

/**
 * Preconnect para o Google Fonts.
 */
add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/**
 * Aplica a classe de alto contraste antes da renderização (evita "piscar").
 */
add_action( 'wp_head', function () {
	echo "<script>try{if(localStorage.getItem('procon-contraste')==='1'){document.documentElement.classList.add('alto-contraste');}var f=localStorage.getItem('procon-fonte');if(f){document.documentElement.style.fontSize=f+'%';}}catch(e){}</script>\n";
}, 1 );

/**
 * URL do logo: usa o logo personalizado (Personalizar > Identidade do site) ou o do tema.
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
