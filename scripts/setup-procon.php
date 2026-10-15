<?php
/**
 * Monta o site do PROCON PE no WordPress a partir da página de referência (http://10.0.0.104:8080/).
 *
 * Uso:
 *   docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php
 *   docker compose run --rm wpcli eval-file /project/scripts/setup-procon.php sobrescrever
 *
 * Idempotente. Sem o argumento "sobrescrever", páginas editadas no Elementor e menus já
 * existentes são mantidos; com ele, conteúdo das páginas e menus são recriados.
 *
 * Fontes de dados:
 *   scripts/conteudo/paginas.json + scripts/conteudo/<pagina>/*.html  (páginas)
 *   scripts/dados/noticias.json                                        (notícias)
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

const PROCON_PROJECT = '/project';

$procon_sobrescrever = in_array( 'sobrescrever', (array) ( $args ?? array() ), true );

// ---------------------------------------------------------------------------
// Utilitários
// ---------------------------------------------------------------------------

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

/**
 * Contêiner do Elementor em largura total, sem respiro, com um único widget.
 */
function procon_secao_elementor( $widget_type, $settings ) {
	$zero = array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true );
	return array(
		'id'       => procon_eid(),
		'elType'   => 'container',
		'isInner'  => false,
		'settings' => array(
			'content_width' => 'full',
			'css_classes'   => 'procon-secao',
			'padding'       => $zero,
			'margin'        => $zero,
			'flex_gap'      => array( 'column' => '0', 'row' => '0', 'unit' => 'px', 'isLinked' => true ),
		),
		'elements' => array(
			array(
				'id'         => procon_eid(),
				'elType'     => 'widget',
				'widgetType' => $widget_type,
				'settings'   => $settings,
				'elements'   => array(),
			),
		),
	);
}

/**
 * Cria/atualiza uma página por caminho (slug, com pai opcional).
 */
function procon_pagina( $slug, $titulo, $resumo = '', $pai_id = 0 ) {
	$caminho = $pai_id ? get_page_uri( $pai_id ) . '/' . $slug : $slug;
	$pagina  = get_page_by_path( $caminho );
	$dados   = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $titulo,
		'post_name'    => $slug,
		'post_excerpt' => $resumo,
		'post_parent'  => $pai_id,
	);
	if ( $pagina ) {
		$dados['ID'] = $pagina->ID;
		wp_update_post( $dados );
		return $pagina->ID;
	}
	$id = wp_insert_post( $dados );
	WP_CLI::log( "Página criada: {$titulo} (#{$id})" );
	return $id;
}

// ---------------------------------------------------------------------------
// 1. Tema, identificação do site e links permanentes
// ---------------------------------------------------------------------------
if ( 'procon' !== get_stylesheet() ) {
	switch_theme( 'procon' );
	WP_CLI::log( 'Tema "PROCON Pernambuco" ativado.' );
}
update_option( 'blogname', 'PROCON Pernambuco' );
update_option( 'blogdescription', 'Proteção e Defesa do Consumidor' );

if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	WP_CLI::log( 'Links permanentes: /%postname%/' );
}

// ---------------------------------------------------------------------------
// 2. Mídia: logo, ícone do site e imagens das notícias
// ---------------------------------------------------------------------------
$logo_id = procon_import_media( PROCON_PROJECT . '/logo-procon.png', 'Logo PROCON Pernambuco' );
$icon_id = procon_import_media( PROCON_PROJECT . '/wp-content/themes/procon/assets/img/icone-procon.png', 'Brasão de Pernambuco' );
set_theme_mod( 'custom_logo', $logo_id );
update_option( 'site_icon', $icon_id );

$imagens = array(); // "photo-xxxx" => attachment id
foreach ( glob( PROCON_PROJECT . '/page-procon/imagens/carrossel-*.jpg' ) as $i => $file ) {
	$id = procon_import_media( $file, 'Imagem de destaque ' . ( $i + 1 ) );
	if ( preg_match( '/photo-[0-9a-f-]+/', basename( $file ), $m ) ) {
		$imagens[ $m[0] ] = $id;
	}
}

// ---------------------------------------------------------------------------
// 3. Kit do Elementor: só cores e tipografias globais (para os widgets nativos).
//    Estilos de body/títulos/links/botões ficam com o CSS de referência do tema.
// ---------------------------------------------------------------------------
$kit_id = (int) get_option( 'elementor_active_kit' );
if ( $kit_id ) {
	$kit  = (array) get_post_meta( $kit_id, '_elementor_page_settings', true );
	$font = function ( $weight ) {
		return array( 'typography_typography' => 'custom', 'typography_font_family' => 'Roboto', 'typography_font_weight' => (string) $weight );
	};
	foreach ( array_keys( $kit ) as $chave ) {
		if ( preg_match( '/^(body_|h[1-6]_|link_|button_)/', $chave ) ) {
			unset( $kit[ $chave ] );
		}
	}
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
			array_merge( array( '_id' => 'text', 'title' => 'Texto' ), $font( 400 ) ),
			array_merge( array( '_id' => 'accent', 'title' => 'Destaque (botões)' ), $font( 600 ) ),
		),
		'container_width'   => array( 'unit' => 'px', 'size' => 1280, 'sizes' => array() ),
		'site_name'         => get_bloginfo( 'name' ),
		'site_logo'         => array( 'id' => $logo_id, 'url' => wp_get_attachment_url( $logo_id ) ),
		'site_favicon'      => array( 'id' => $icon_id, 'url' => wp_get_attachment_url( $icon_id ) ),
	) );
	update_post_meta( $kit_id, '_elementor_page_settings', $kit );
	WP_CLI::log( "Kit do Elementor #{$kit_id}: cores e tipografias globais." );
}

// ---------------------------------------------------------------------------
// 4. Notícias: categorias (ordem da referência) e posts
// ---------------------------------------------------------------------------
$categorias = array( 'Campanhas', 'Pesquisa de Preços', 'Ranking', 'Notas Técnicas', 'Atendimento', 'Institucional', 'Notícias' );
foreach ( $categorias as $nome ) {
	if ( ! term_exists( $nome, 'category' ) ) {
		wp_insert_term( $nome, 'category' );
	}
}

// Post de exemplo da instalação ("Olá, mundo!") não faz parte do site: vai para a lixeira.
foreach ( array( 'ola-mundo', 'hello-world' ) as $slug ) {
	$exemplo = get_page_by_path( $slug, OBJECT, 'post' );
	if ( $exemplo ) {
		wp_trash_post( $exemplo->ID );
		WP_CLI::log( 'Post de exemplo movido para a lixeira: ' . $exemplo->post_title );
	}
}

$noticias = json_decode( file_get_contents( PROCON_PROJECT . '/scripts/dados/noticias.json' ), true );
foreach ( $noticias as $n ) {
	$existente = get_posts( array(
		'post_type'   => 'post',
		'post_status' => 'any',
		'meta_key'    => '_procon_noticia_id',
		'meta_value'  => (string) $n['id'],
		'fields'      => 'ids',
		'numberposts' => 1,
	) );
	if ( $existente ) {
		continue;
	}
	$data = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $n['publicado_em'] ) ) );
	$id   = wp_insert_post( array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'post_title'    => $n['titulo'],
		'post_excerpt'  => $n['resumo'],
		'post_content'  => $n['conteudo'],
		'post_date'     => $data,
		'post_category' => array( (int) get_cat_ID( $n['categoria'] ) ),
	) );
	update_post_meta( $id, '_procon_noticia_id', (string) $n['id'] );
	if ( ! empty( $imagens[ $n['imagem'] ] ) ) {
		set_post_thumbnail( $id, $imagens[ $n['imagem'] ] );
	}
	WP_CLI::log( "Notícia importada: {$n['titulo']} (#{$id})" );
}

// ---------------------------------------------------------------------------
// 5. Páginas (conteúdo em contêineres do Elementor: HTML / Shortcode)
// ---------------------------------------------------------------------------
$manifesto = json_decode( file_get_contents( PROCON_PROJECT . '/scripts/conteudo/paginas.json' ), true );
$site      = untrailingslashit( home_url() );
$ids       = array();

foreach ( $manifesto as $p ) {
	$pai_id             = $p['pai'] ? (int) $ids[ $p['pai'] ] : 0;
	$id                 = procon_pagina( $p['slug'], $p['titulo'], $p['resumo'], $pai_id );
	$ids[ $p['slug'] ] = $id;

	$elementos = array();
	foreach ( $p['secoes'] as $secao ) {
		if ( 'shortcode' === $secao['tipo'] ) {
			$elementos[] = procon_secao_elementor( 'shortcode', array( 'shortcode' => $secao['shortcode'] ) );
		} else {
			$html        = file_get_contents( PROCON_PROJECT . '/scripts/conteudo/' . $secao['arquivo'] );
			$elementos[] = procon_secao_elementor( 'html', array( 'html' => str_replace( '{{site}}', $site, $html ) ) );
		}
	}
	$json = wp_json_encode( $elementos );

	// Só regrava se a página ainda não tem conteúdo ou não foi editada desde a última geração.
	$atual   = (string) get_post_meta( $id, '_elementor_data', true );
	$gerado  = (string) get_post_meta( $id, '_procon_hash', true );
	$editada = $atual && md5( $atual ) !== $gerado;
	if ( $editada && ! $procon_sobrescrever ) {
		WP_CLI::warning( "Página \"{$p['titulo']}\" foi editada no Elementor — mantida (use 'sobrescrever' para recriar)." );
		continue;
	}
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
	update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $id, '_elementor_data', wp_slash( $json ) );
	update_post_meta( $id, '_procon_hash', md5( $json ) );
	delete_post_meta( $id, '_elementor_css' );
}

// Página de notícias (lista de posts, sem Elementor)
$ids['noticias'] = procon_pagina( 'noticias', 'Notícias' );
delete_post_meta( $ids['noticias'], '_elementor_data' );
delete_post_meta( $ids['noticias'], '_elementor_edit_mode' );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $ids['inicio'] );
update_option( 'page_for_posts', $ids['noticias'] );

// ---------------------------------------------------------------------------
// 6. Menus
// ---------------------------------------------------------------------------
$url = function ( $caminho ) use ( $site ) {
	return $site . '/' . ltrim( $caminho, '/' );
};

/**
 * Cria (ou recria) um menu. Itens: [ título, destino, filhos?, opções? ]
 * destino: slug de página em $ids ("pagina:slug"), URL absoluta ou caminho do site.
 */
$monta_menu = function ( $nome, $local, $itens ) use ( $ids, $url, $procon_sobrescrever ) {
	$menu    = wp_get_nav_menu_object( $nome );
	$menu_id = $menu ? $menu->term_id : wp_create_nav_menu( $nome );

	if ( wp_get_nav_menu_items( $menu_id ) && ! $procon_sobrescrever ) {
		WP_CLI::log( "Menu \"{$nome}\" já existe — mantido." );
	} else {
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		$adiciona = function ( $item, $pai = 0 ) use ( &$adiciona, $menu_id, $ids, $url ) {
			list( $titulo, $destino ) = $item;
			$filhos = $item[2] ?? array();
			$opcoes = $item[3] ?? array();
			$dados  = array(
				'menu-item-title'     => $titulo,
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $pai,
				'menu-item-classes'   => $opcoes['classe'] ?? '',
			);
			if ( 0 === strpos( $destino, 'pagina:' ) ) {
				$dados['menu-item-type']      = 'post_type';
				$dados['menu-item-object']    = 'page';
				$dados['menu-item-object-id'] = $ids[ substr( $destino, 7 ) ];
			} else {
				$externo                 = (bool) preg_match( '#^https?://#', $destino );
				$dados['menu-item-type'] = 'custom';
				$dados['menu-item-url']  = $externo || '#' === $destino ? $destino : $url( $destino );
				if ( $externo ) {
					$dados['menu-item-target'] = '_blank';
				}
			}
			$id = wp_update_nav_menu_item( $menu_id, 0, $dados );
			foreach ( $filhos as $filho ) {
				$adiciona( $filho, $id );
			}
		};
		foreach ( $itens as $item ) {
			$adiciona( $item );
		}
		WP_CLI::log( "Menu \"{$nome}\" criado." );
	}

	$locais           = get_theme_mod( 'nav_menu_locations', array() );
	$locais[ $local ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locais );
};

$divisor = array( '—', '#', array(), array( 'classe' => 'dropdown-divider' ) );

$monta_menu( 'Principal', 'procon-principal', array(
	array( 'Início', 'pagina:inicio' ),
	array( 'Institucional', 'pagina:institucional', array(
		array( 'Quem Somos', 'institucional/#quem-somos' ),
		array( 'Estrutura Organizacional', 'institucional/#organograma' ),
		array( 'Identidade Institucional', 'institucional/#identidade' ),
		array( 'Ética e Integridade', 'institucional/#etica' ),
		array( 'LGPD', 'institucional/#lgpd' ),
		array( 'Conselho', 'institucional/#conselho' ),
		$divisor,
		array( 'Competências e Legislação', 'pagina:legislacao' ),
	) ),
	array( 'Pesquisa de Preços', 'pagina:pesquisa-de-precos' ),
	array( 'Transparência', 'pagina:transparencia', array(
		array( 'Portal da Transparência', 'pagina:transparencia' ),
		$divisor,
		array( 'Recursos Humanos', 'transparencia/' ),
		array( 'Licitações e Contratos', 'transparencia/' ),
		array( 'Despesas', 'transparencia/' ),
		array( 'Responsabilidade Fiscal', 'transparencia/' ),
		array( 'SIC', 'transparencia/' ),
	) ),
	array( 'Ranking', 'pagina:ranking-de-reclamacoes' ),
	array( 'Notícias', 'pagina:noticias' ),
	array( 'Contato', 'pagina:contatos' ),
) );

$monta_menu( 'Barra do Governo', 'procon-gov-bar', array(
	array( 'PERNAMBUCO', 'https://www.pe.gov.br' ),
	array( 'SECRETARIAS', 'https://www.pe.gov.br/secretarias' ),
	array( 'PORTAL DO SERVIDOR', 'http://www.portaldoservidor.pe.gov.br' ),
	array( 'NOTÍCIAS DO GOV.', 'https://www.pe.gov.br/blog' ),
	array( 'SJDH', 'https://sjdh.pe.gov.br' ),
	array( 'OUVIDORIA', 'https://www.sjdh.pe.gov.br/ouvidoria-3__trashed/ouvdh/' ),
	array( 'TRANSPARÊNCIA', 'pagina:transparencia' ),
) );

$monta_menu( 'Rodapé — Serviços', 'procon-rodape-servicos', array(
	array( 'Pesquisa de Preços', 'pagina:pesquisa-de-precos' ),
	array( 'Ranking de Reclamações', 'pagina:ranking-de-reclamacoes' ),
	array( 'Últimas Notícias', 'pagina:noticias' ),
	array( 'Reclama Procon ↗', 'https://www.pe.gov.br/app/procon-reclamaprocon/reclamation' ),
	array( 'Denúncia Anônima ↗', 'https://www.pe.gov.br/app/procon-reclamaprocon/complaint/anonymous' ),
	array( 'SEI — Processos ↗', 'https://sei.pe.gov.br' ),
	array( 'Fale Conosco / Postos', 'pagina:contatos' ),
) );

$monta_menu( 'Rodapé — Inf. Institucionais', 'procon-rodape-inst', array(
	array( '» Gerente Geral', 'pagina:gerente-geral' ),
	array( '» Ética e Integridade', 'institucional/#etica' ),
	array( '» Lei de Proteção de Dados Pessoais - LGPD', 'institucional/#lgpd' ),
	array( '» Redes Sociais', 'pagina:institucional' ),
	array( '» Conselho', 'institucional/#conselho' ),
	array( '» Quem é Quem', 'institucional/#organograma' ),
	array( '» Estrutura Organizacional', 'institucional/#organograma' ),
	array( '» Ouvidoria ↗', 'https://www.sjdh.pe.gov.br/ouvidoria-3__trashed/ouvdh/' ),
	array( '» Secretário Executivo', 'pagina:secretario-executivo' ),
	array( '» Plano de Cargos e Carreiras', 'pagina:transparencia' ),
	array( '» Competências e legislação Aplicável', 'pagina:legislacao' ),
	array( '» Gerente Jurídico', 'pagina:gerente-juridico' ),
	array( '» Gerente de Fiscalização', 'pagina:gerente-fiscalizacao' ),
	array( '» Identidade Institucional', 'institucional/#identidade' ),
	array( '» Gerente de Atendimento', 'pagina:gerente-atendimento' ),
) );

$monta_menu( 'Rodapé — Órgãos Parceiros', 'procon-rodape-orgaos', array(
	array( 'Governo de PE ↗', 'https://www.pe.gov.br' ),
	array( 'SJDH Pernambuco ↗', 'https://www.sjdh.pe.gov.br' ),
	array( 'Consumidor.gov.br ↗', 'https://www.consumidor.gov.br' ),
	array( 'Senacon ↗', 'https://www.gov.br/mj/pt-br/assuntos/seus-direitos/consumidor' ),
	array( 'Anatel ↗', 'https://www.gov.br/anatel' ),
	array( 'Anvisa ↗', 'https://www.gov.br/anvisa' ),
	array( 'Mapa do Site', 'pagina:mapa-do-site' ),
	array( 'Acessibilidade', 'pagina:acessibilidade' ),
) );

$monta_menu( 'Rodapé — Links inferiores', 'procon-rodape-base', array(
	array( 'Acessibilidade', 'pagina:acessibilidade' ),
	array( 'Mapa do Site', 'pagina:mapa-do-site' ),
	array( 'Política de Privacidade', 'institucional/#lgpd' ),
) );

// ---------------------------------------------------------------------------
// 7. Contatos e redes (Personalizar > PROCON) — só preenche campos vazios
// ---------------------------------------------------------------------------
$contatos = array(
	'procon_descricao'          => 'O PROCON Pernambuco é o órgão estadual responsável pela proteção e defesa do consumidor no Estado de Pernambuco, vinculado à Secretaria de Justiça e Direitos Humanos — SJDH.',
	'procon_endereco'           => 'Rua Floriano Peixoto, 141, Santo Antônio — Recife/PE, CEP 50.020-065',
	'procon_telefone'           => '0800-282-1512',
	'procon_pabx'               => '3181-7000',
	'procon_pabx_tel'           => '+558131817000',
	'procon_whatsapp'           => 'https://wa.me/558131817000',
	'procon_email'              => 'denuncia@procon.pe.gov.br',
	'procon_consumidor_gov'     => 'https://www.consumidor.gov.br',
	'procon_social_instagram'   => 'https://www.instagram.com/proconpe',
	'procon_social_facebook'    => 'https://www.facebook.com/proconpe',
	'procon_social_youtube'     => 'https://www.youtube.com/@proconpernambucocanal',
	'procon_social_x_twitter'   => 'https://twitter.com/procon_pe',
);
foreach ( $contatos as $chave => $valor ) {
	if ( ! get_theme_mod( $chave ) ) {
		set_theme_mod( $chave, $valor );
	}
}

// ---------------------------------------------------------------------------
// 8. Regras de URL e cache de CSS do Elementor
// ---------------------------------------------------------------------------
// No CLI o WordPress não detecta o mod_rewrite do Apache (ativo na imagem wordpress:*-apache)
// e deixaria o bloco do .htaccess vazio.
add_filter( 'got_rewrite', '__return_true' );
flush_rewrite_rules( true );
\Elementor\Plugin::$instance->files_manager->clear_cache();

WP_CLI::success( 'Site do PROCON PE montado: ' . count( $manifesto ) . ' páginas, ' . count( $noticias ) . ' notícias, 6 menus.' );
