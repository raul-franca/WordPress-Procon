<?php
/**
 * Funções de template: breadcrumb, cabeçalho de página e cards de notícia
 * (markup idêntico ao da página de referência).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data no formato da referência: "28 de setembro de 2026".
 */
function procon_data( $post = null ) {
	return get_the_date( 'd \d\e F \d\e Y', $post );
}

/**
 * Primeira categoria da notícia (nome).
 */
function procon_categoria( $post = null ) {
	$cats = get_the_category( $post ? get_post( $post )->ID : 0 );
	return $cats ? $cats[0]->name : '';
}

/**
 * Breadcrumb. $itens = [ [ 'Rótulo', 'url' ], ..., [ 'Página atual' ] ].
 */
function procon_breadcrumb( array $itens ) {
	echo '<nav class="breadcrumb" aria-label="Localização na página"><div class="container"><ol class="breadcrumb-list">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Início</a></li>';
	foreach ( $itens as $item ) {
		if ( isset( $item[1] ) ) {
			echo '<li><a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a></li>';
		} else {
			echo '<li aria-current="page">' . esc_html( $item[0] ) . '</li>';
		}
	}
	echo '</ol></div></nav>';
}

/**
 * Faixa azul com título e subtítulo (div.page-hero).
 */
function procon_page_hero( $titulo, $subtitulo = '' ) {
	echo '<div class="page-hero"><div class="container">';
	echo '<h1>' . esc_html( $titulo ) . '</h1>';
	if ( $subtitulo ) {
		echo '<p>' . esc_html( $subtitulo ) . '</p>';
	}
	echo '</div></div>';
}

/**
 * Breadcrumb + faixa da página atual. Título = título da página; subtítulo = resumo.
 */
function procon_page_header( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$itens = array();
	foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestral ) {
		$itens[] = array( get_the_title( $ancestral ), get_permalink( $ancestral ) );
	}
	$itens[] = array( get_the_title( $post ) );

	procon_breadcrumb( $itens );
	procon_page_hero( get_the_title( $post ), has_excerpt( $post ) ? get_the_excerpt( $post ) : '' );
}

/**
 * URL da página de notícias (definida em Configurações > Leitura).
 */
function procon_noticias_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/' );
}
