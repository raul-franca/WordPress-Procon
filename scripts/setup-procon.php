<?php
/**
 * Aplica a identidade visual do PROCON PE no WordPress + Elementor.
 * Idempotente: pode ser executado novamente sem duplicar mídia, página ou menu.
 *
 * Uso: docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

const PROCON_PROJECT = '/project';

/**
 * Importa um arquivo para a Biblioteca de Mídia (reaproveita se já importado).
 */
function procon_import_media( $path, $title ) {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'post_status' => 'inherit',
		'meta_key'    => '_procon_source',
		'meta_value'  => basename( $path ),
		'fields'      => 'ids',
		'numberposts' => 1,
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}

	$tmp = wp_tempnam( basename( $path ) );
	copy( $path, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $path ), 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $path . ': ' . $id->get_error_message() );
	}
	update_post_meta( $id, '_procon_source', basename( $path ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	WP_CLI::log( "Mídia importada: {$title} (#{$id})" );
	return (int) $id;
}

function procon_eid() {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
}

function procon_widget( $type, $settings ) {
	return array( 'id' => procon_eid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}

function procon_container( $settings, $elements, $inner = false ) {
	return array( 'id' => procon_eid(), 'elType' => 'container', 'isInner' => $inner, 'settings' => $settings, 'elements' => $elements );
}

// ---------------------------------------------------------------------------
// 1. Tema
// ---------------------------------------------------------------------------
if ( 'procon' !== get_stylesheet() ) {
	switch_theme( 'procon' );
	WP_CLI::log( 'Tema "PROCON Pernambuco" ativado.' );
}

// ---------------------------------------------------------------------------
// 2. Mídia: logo, ícone do site e imagens do carrossel
// ---------------------------------------------------------------------------
$logo_id = procon_import_media( PROCON_PROJECT . '/logo-procon.png', 'Logo PROCON Pernambuco' );
$icon_id = procon_import_media( PROCON_PROJECT . '/wp-content/themes/procon/assets/img/icone-procon.png', 'Brasão de Pernambuco' );

set_theme_mod( 'custom_logo', $logo_id );
update_option( 'site_icon', $icon_id );

$carousel = array();
foreach ( glob( PROCON_PROJECT . '/page-procon/imagens/carrossel-*.jpg' ) as $i => $file ) {
	$id         = procon_import_media( $file, 'Imagem de destaque ' . ( $i + 1 ) );
	$carousel[] = array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) );
}

// ---------------------------------------------------------------------------
// 3. Kit global do Elementor: cores, tipografia, botões e largura
// ---------------------------------------------------------------------------
update_option( 'elementor_disable_color_schemes', 'yes' );
update_option( 'elementor_disable_typography_schemes', 'yes' );

$kit_id = (int) get_option( 'elementor_active_kit' );
if ( ! $kit_id ) {
	WP_CLI::error( 'Kit ativo do Elementor não encontrado.' );
}

$font = function ( $family_weight, $size = null, $line = null ) {
	list( $weight ) = (array) $family_weight;
	$t = array(
		'typography_typography'  => 'custom',
		'typography_font_family' => 'Roboto',
		'typography_font_weight' => (string) $weight,
	);
	if ( $size ) {
		$t['typography_font_size'] = array( 'unit' => 'px', 'size' => $size, 'sizes' => array() );
	}
	if ( $line ) {
		$t['typography_line_height'] = array( 'unit' => 'em', 'size' => $line, 'sizes' => array() );
	}
	return $t;
};

$prefixed = function ( $prefix, $typo ) {
	$out = array();
	foreach ( $typo as $k => $v ) {
		$out[ $prefix . '_' . $k ] = $v;
	}
	return $out;
};

$kit = (array) get_post_meta( $kit_id, '_elementor_page_settings', true );
$kit = array_merge( $kit, array(
	'system_colors'     => array(
		array( '_id' => 'primary', 'title' => 'Primária', 'color' => '#00295B' ),
		array( '_id' => 'secondary', 'title' => 'Secundária', 'color' => '#0A7F8E' ),
		array( '_id' => 'text', 'title' => 'Texto', 'color' => '#222222' ),
		array( '_id' => 'accent', 'title' => 'Destaque', 'color' => '#00C2D4' ),
	),
	'custom_colors'     => array(
		array( '_id' => 'pc_prim_dk', 'title' => 'Primária escura', 'color' => '#001A3D' ),
		array( '_id' => 'pc_prim_lt', 'title' => 'Primária clara', 'color' => '#003F8A' ),
		array( '_id' => 'pc_sec_dk', 'title' => 'Secundária escura', 'color' => '#076470' ),
		array( '_id' => 'pc_sec_lt', 'title' => 'Secundária clara', 'color' => '#14B0C3' ),
		array( '_id' => 'pc_gov_bar', 'title' => 'Barra do governo', 'color' => '#001830' ),
		array( '_id' => 'pc_white', 'title' => 'Branco', 'color' => '#FFFFFF' ),
		array( '_id' => 'pc_bg', 'title' => 'Fundo', 'color' => '#F4F7FB' ),
		array( '_id' => 'pc_bg_sec', 'title' => 'Fundo de seção', 'color' => '#EAF2F8' ),
		array( '_id' => 'pc_muted', 'title' => 'Texto secundário', 'color' => '#555555' ),
		array( '_id' => 'pc_border', 'title' => 'Borda', 'color' => '#D0DCE8' ),
	),
	'system_typography' => array(
		array_merge( array( '_id' => 'primary', 'title' => 'Primária (títulos)' ), $font( 700 ) ),
		array_merge( array( '_id' => 'secondary', 'title' => 'Secundária' ), $font( 500 ) ),
		array_merge( array( '_id' => 'text', 'title' => 'Texto' ), $font( 400, 16, 1.65 ) ),
		array_merge( array( '_id' => 'accent', 'title' => 'Destaque (botões)' ), $font( 600, 12 ) ),
	),
	'body_background_background' => 'classic',
	'body_background_color'      => '#F4F7FB',
	'body_color'                 => '#222222',
	'link_normal_color'          => '#0A7F8E',
	'link_hover_color'           => '#076470',
	'h1_color'                   => '#00295B',
	'h2_color'                   => '#00295B',
	'h3_color'                   => '#00295B',
	'button_text_color'          => '#FFFFFF',
	'button_background_color'    => '#0A7F8E',
	'button_hover_text_color'    => '#FFFFFF',
	'button_hover_background_color' => '#076470',
	'button_border_radius'       => array( 'unit' => 'px', 'top' => '9999', 'right' => '9999', 'bottom' => '9999', 'left' => '9999', 'isLinked' => true ),
	'button_padding'             => array( 'unit' => 'px', 'top' => '12', 'right' => '24', 'bottom' => '12', 'left' => '24', 'isLinked' => false ),
	'container_width'            => array( 'unit' => 'px', 'size' => 1280, 'sizes' => array() ),
	'space_between_widgets'      => array( 'column' => '16', 'row' => '16', 'unit' => 'px', 'isLinked' => true ),
	'site_name'                  => get_bloginfo( 'name' ),
	'site_logo'                  => array( 'id' => $logo_id, 'url' => wp_get_attachment_url( $logo_id ) ),
	'site_favicon'               => array( 'id' => $icon_id, 'url' => wp_get_attachment_url( $icon_id ) ),
) );
$kit = array_merge(
	$kit,
	$prefixed( 'body_typography', $font( 400, 16, 1.65 ) ),
	$prefixed( 'h1_typography', $font( 700, 40, 1.2 ) ),
	$prefixed( 'h2_typography', $font( 700, 24, 1.65 ) ),
	$prefixed( 'h3_typography', $font( 700, 18, 1.35 ) ),
	$prefixed( 'link_normal_typography', array( 'typography_typography' => 'custom', 'typography_font_weight' => '700' ) ),
	$prefixed( 'button_typography', $font( 600, 12 ) ),
	array( 'button_typography_text_transform' => 'uppercase' )
);
update_post_meta( $kit_id, '_elementor_page_settings', $kit );
WP_CLI::log( "Kit do Elementor #{$kit_id} atualizado (cores, tipografia, botões, largura 1280px)." );

// ---------------------------------------------------------------------------
// 4. Página inicial montada no Elementor (conteúdo de exemplo, editável)
// ---------------------------------------------------------------------------
$news = array_slice( $carousel, 0, 3 );
$cards = array();
foreach ( $news as $i => $img ) {
	$cards[] = procon_container(
		array( 'content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => array( 'column' => '12', 'row' => '12', 'unit' => 'px', 'isLinked' => true ), 'css_classes' => 'procon-card', 'padding' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ) ),
		array(
			procon_widget( 'image', array( 'image' => $img, 'image_size' => 'medium_large' ) ),
			procon_widget( 'heading', array( 'title' => wp_date( 'd/m/Y' ), 'header_size' => 'span', '_css_classes' => 'procon-date' ) ),
			procon_widget( 'heading', array( 'title' => 'Título da notícia de exemplo ' . ( $i + 1 ), 'header_size' => 'h3' ) ),
			procon_widget( 'text-editor', array( 'editor' => '<p>Texto de exemplo para o resumo da notícia. Edite este card no Elementor para inserir o conteúdo real.</p>' ) ),
			procon_widget( 'button', array( 'text' => 'Leia mais', 'link' => array( 'url' => '#' ), '_css_classes' => 'procon-link-more' ) ),
		),
		true
	);
}

$youtube = get_theme_mod( 'procon_social_youtube' );
$elementor_data = array(
	// Carrossel de destaque (largura total)
	procon_container(
		array( 'content_width' => 'full', 'css_classes' => 'procon-hero', 'padding' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ) ),
		array( procon_widget( 'image-carousel', array(
			'carousel'       => $carousel,
			'thumbnail_size' => 'full',
			'slides_to_show' => '1',
			'navigation'     => 'both',
			'autoplay'       => 'yes',
			'autoplay_speed' => 5000,
			'infinite'       => 'yes',
			'image_stretch'  => 'yes',
			'link_to'        => 'none',
		) ) )
	),
	// Notícias
	procon_container(
		array( 'flex_direction' => 'column', 'padding' => array( 'unit' => 'px', 'top' => '48', 'right' => '20', 'bottom' => '48', 'left' => '20', 'isLinked' => false ) ),
		array(
			procon_container(
				array( 'content_width' => 'full', 'flex_direction' => 'row', 'flex_justify_content' => 'space-between', 'flex_align_items' => 'center', 'css_classes' => 'procon-section-head' ),
				array(
					procon_widget( 'heading', array( 'title' => 'Notícias', 'header_size' => 'h2' ) ),
					procon_widget( 'button', array( 'text' => 'Ver todas', 'link' => array( 'url' => '#' ), '_css_classes' => 'procon-link-more' ) ),
				),
				true
			),
			procon_container( array( 'content_width' => 'full', 'css_classes' => 'procon-cards' ), $cards, true ),
		)
	),
	// Chamada para os vídeos
	procon_container(
		array(
			'flex_direction'        => 'row',
			'flex_justify_content'  => 'space-between',
			'flex_align_items'      => 'center',
			'flex_wrap'             => 'wrap',
			'css_classes'           => 'procon-cta',
			'background_background' => 'classic',
			'background_color'      => '#00295B',
			'padding'               => array( 'unit' => 'px', 'top' => '48', 'right' => '20', 'bottom' => '48', 'left' => '20', 'isLinked' => false ),
		),
		array(
			procon_container(
				array( 'content_width' => 'full', 'flex_direction' => 'column', 'width' => array( 'unit' => '%', 'size' => 60 ) ),
				array(
					procon_widget( 'heading', array( 'title' => 'Acompanhe o PROCON PE', 'header_size' => 'h2' ) ),
					procon_widget( 'text-editor', array( 'editor' => '<p>Orientações sobre direitos do consumidor em vídeo.</p>' ) ),
				),
				true
			),
			procon_widget( 'button', array( 'text' => 'Assistir vídeos', 'link' => array( 'url' => $youtube ? $youtube : '#', 'is_external' => 'on' ) ) ),
		)
	),
);

$home = get_page_by_path( 'inicio' );
if ( ! $home ) {
	$home_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Início', 'post_name' => 'inicio' ) );
	WP_CLI::log( "Página Início criada (#{$home_id})." );
} else {
	$home_id = $home->ID;
}
if ( ! get_post_meta( $home_id, '_elementor_data', true ) || in_array( '--reset-home', (array) ( $GLOBALS['argv'] ?? array() ), true ) ) {
	update_post_meta( $home_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $home_id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $home_id, '_elementor_version', ELEMENTOR_VERSION );
	update_post_meta( $home_id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $home_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
	WP_CLI::log( 'Conteúdo Elementor da página Início gravado.' );
} else {
	WP_CLI::log( 'Página Início já tem conteúdo Elementor — mantida (não sobrescrita).' );
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

// ---------------------------------------------------------------------------
// 5. Menu principal
// ---------------------------------------------------------------------------
$menu = wp_get_nav_menu_object( 'Principal' );
$menu_id = $menu ? $menu->term_id : wp_create_nav_menu( 'Principal' );
if ( ! wp_get_nav_menu_items( $menu_id ) ) {
	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'     => 'Início',
		'menu-item-object'    => 'page',
		'menu-item-object-id' => $home_id,
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
	) );
}
$locations                     = get_theme_mod( 'nav_menu_locations', array() );
$locations['procon-principal'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

// ---------------------------------------------------------------------------
// 6. Regenera o CSS do Elementor
// ---------------------------------------------------------------------------
\Elementor\Plugin::$instance->files_manager->clear_cache();

WP_CLI::success( 'Identidade visual do PROCON aplicada.' );
