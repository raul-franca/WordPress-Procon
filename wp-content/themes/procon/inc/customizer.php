<?php
/**
 * Personalizar > PROCON — Contato e redes: dados usados no rodapé.
 * Campos vazios não são exibidos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redes sociais na ordem do rodapé de referência: slug do ícone => rótulo.
 */
function procon_social_networks() {
	return array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
		'x-twitter' => 'X (Twitter)',
	);
}

function procon_social_url( $slug ) {
	return get_theme_mod( 'procon_social_' . str_replace( '-', '_', $slug ) );
}

/**
 * Converte um telefone em link tel: ("0800-282-1512" -> "08002821512").
 */
function procon_tel( $numero ) {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $numero );
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_section( 'procon_contato', array(
		'title'    => 'PROCON — Contato e redes',
		'priority' => 30,
	) );

	$campos = array(
		'procon_descricao'       => array( 'Descrição institucional (rodapé)', 'sanitize_textarea_field', 'textarea' ),
		'procon_endereco'        => array( 'Endereço completo', 'sanitize_textarea_field', 'textarea' ),
		'procon_telefone'        => array( 'Telefone gratuito (0800)', 'sanitize_text_field', 'text' ),
		'procon_pabx'            => array( 'PABX', 'sanitize_text_field', 'text' ),
		'procon_pabx_tel'        => array( 'PABX — número para discagem (ex.: +558131817000)', 'sanitize_text_field', 'text' ),
		'procon_whatsapp'        => array( 'WhatsApp (link wa.me)', 'esc_url_raw', 'url' ),
		'procon_email'           => array( 'E-mail', 'sanitize_email', 'email' ),
		'procon_consumidor_gov'  => array( 'Consumidor.gov.br (URL)', 'esc_url_raw', 'url' ),
	);
	foreach ( $campos as $id => list( $label, $sanitize, $type ) ) {
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
