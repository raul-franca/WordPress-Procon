<?php
/**
 * Personalizar > PROCON: contato e redes sociais exibidos no rodapé.
 * Campos vazios não são exibidos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function procon_social_networks() {
	return array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'youtube'   => 'YouTube',
		'x-twitter' => 'X (Twitter)',
	);
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_section( 'procon_contato', array(
		'title'    => 'PROCON — Contato e redes',
		'priority' => 30,
	) );

	$text_fields = array(
		'procon_endereco' => array( 'Endereço', 'sanitize_textarea_field', 'textarea' ),
		'procon_telefone' => array( 'Telefone', 'sanitize_text_field', 'text' ),
		'procon_email'    => array( 'E-mail', 'sanitize_email', 'email' ),
	);
	foreach ( $text_fields as $id => list( $label, $sanitize, $type ) ) {
		$wp_customize->add_setting( $id, array( 'default' => '', 'sanitize_callback' => $sanitize ) );
		$wp_customize->add_control( $id, array( 'label' => $label, 'section' => 'procon_contato', 'type' => $type ) );
	}

	foreach ( procon_social_networks() as $slug => $label ) {
		$id = 'procon_social_' . str_replace( '-', '_', $slug );
		$wp_customize->add_setting( $id, array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
		$wp_customize->add_control( $id, array( 'label' => $label . ' (URL)', 'section' => 'procon_contato', 'type' => 'url' ) );
	}

	$wp_customize->add_setting( 'procon_vlibras', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
	$wp_customize->add_control( 'procon_vlibras', array( 'label' => 'Exibir widget VLibras', 'section' => 'procon_contato', 'type' => 'checkbox' ) );
} );
