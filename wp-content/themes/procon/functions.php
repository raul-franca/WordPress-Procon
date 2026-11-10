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

define( 'PROCON_THEME_VERSION', '2.1.0' );

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
		'procon-principal'       => 'Menu principal (cabeçalho)',
		'procon-gov-bar'         => 'Barra do governo (topo)',
		'procon-rodape-servicos' => 'Rodapé — Serviços',
		'procon-rodape-inst'     => 'Rodapé — Inf. Institucionais',
		'procon-rodape-orgaos'   => 'Rodapé — Órgãos Parceiros',
		'procon-rodape-base'     => 'Rodapé — Links inferiores',
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
	wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', array(), '1.11.3' );

	// Mesma ordem do <head> da página de referência.
	$deps = array( 'procon-fontes', 'bootstrap-icons' );
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

/* ==========================================================================
   ACERVO DE PESQUISAS DE PREÇOS EM PDF (2023 ATÉ HOJE) — PAINEL WP-ADMIN
   ========================================================================== */

/**
 * Lista oficial das 15 categorias da página de Pesquisa de Preços.
 */
function procon_categorias_pesquisa() {
	return array(
		'black-friday'     => 'Black Friday',
		'material-escolar' => 'Material Escolar',
		'cesta-basica'     => 'Cesta Básica',
		'gas-cozinha'      => 'Gás de Cozinha',
		'medicamentos'     => 'Medicamentos',
		'pascoa'           => 'Páscoa',
		'milho-junino'     => 'Milho e Artigos Juninos',
		'peixe-crustaceo'  => 'Peixe e Crustáceo',
		'dia-maes'         => 'Dia das Mães',
		'dia-namorados'    => 'Dia dos Namorados',
		'dia-pais'         => 'Dia dos Pais',
		'dia-criancas'     => 'Dia das Crianças',
		'cesta-natalina'   => 'Cesta Natalina',
		'carnes-queijos'   => 'Carnes, Queijos e Presuntos',
		'outras'           => 'Outras Pesquisas',
	);
}

/**
 * Registra o menu "Pesquisas PDF" no painel administrativo do WordPress.
 */
add_action( 'init', function () {
	register_post_type( 'procon_pesquisa_pdf', array(
		'labels' => array(
			'name'               => 'Pesquisas PDF',
			'singular_name'      => 'Pesquisa PDF',
			'menu_name'          => 'Pesquisas PDF',
			'add_new'            => 'Adicionar PDF',
			'add_new_item'       => 'Adicionar Novo PDF de Pesquisa',
			'edit_item'          => 'Editar PDF de Pesquisa',
			'new_item'           => 'Novo PDF de Pesquisa',
			'view_item'          => 'Ver PDF',
			'search_items'       => 'Buscar Pesquisas PDF',
			'not_found'          => 'Nenhum PDF cadastrado ainda.',
			'not_found_in_trash' => 'Nenhum PDF na lixeira.',
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-media-document',
		'menu_position'=> 21,
		'supports'     => array( 'title' ),
	) );
} );

/**
 * Caixa de campos (Categoria, Ano, Mês e Upload de PDF) no painel Admin.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'procon_pesquisa_pdf_dados',
		'Dados do Relatório em PDF (2023 até Hoje)',
		function ( $post ) {
			wp_nonce_field( 'procon_salvar_pesquisa_pdf', 'procon_pesquisa_pdf_nonce' );
			$categoria = get_post_meta( $post->ID, '_procon_categoria', true ) ?: 'cesta-basica';
			$ano       = get_post_meta( $post->ID, '_procon_ano', true ) ?: wp_date( 'Y' );
			$mes       = get_post_meta( $post->ID, '_procon_mes', true ) ?: 'Outubro';
			$pdf_url   = get_post_meta( $post->ID, '_procon_pdf_url', true );
			$cats      = procon_categorias_pesquisa();
			$meses     = array( 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro' );
			?>
			<style>
				.procon-meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
				.procon-meta-field label { display: block; font-weight: 600; margin-bottom: 6px; color: #002752; }
				.procon-meta-field select, .procon-meta-field input { width: 100%; padding: 6px 10px; }
				@media (max-width: 782px) { .procon-meta-grid { grid-template-columns: 1fr; } }
			</style>
			<div class="procon-meta-grid">
				<div class="procon-meta-field">
					<label for="procon_categoria">1. Categoria da Pesquisa:</label>
					<select name="procon_categoria" id="procon_categoria">
						<?php foreach ( $cats as $slug => $nome ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $categoria, $slug ); ?>>
								<?php echo esc_html( $nome ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="procon-meta-field">
					<label for="procon_ano">2. Ano de Referência:</label>
					<input type="number" name="procon_ano" id="procon_ano" min="2020" max="2035" value="<?php echo esc_attr( $ano ); ?>">
				</div>
				<div class="procon-meta-field">
					<label for="procon_mes">3. Mês / Edição:</label>
					<input type="text" name="procon_mes" id="procon_mes" list="procon_lista_meses" value="<?php echo esc_attr( $mes ); ?>" placeholder="Ex: Outubro">
					<datalist id="procon_lista_meses">
						<?php foreach ( $meses as $m ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>">
						<?php endforeach; ?>
					</datalist>
				</div>
			</div>
			<div class="procon-meta-field">
				<label for="procon_pdf_url">4. Arquivo PDF (Selecione na Biblioteca de Mídia ou cole o link):</label>
				<div style="display:flex; gap:8px; align-items:center;">
					<input type="url" name="procon_pdf_url" id="procon_pdf_url" value="<?php echo esc_attr( $pdf_url ); ?>" placeholder="https://.../wp-content/uploads/.../pesquisa.pdf">
					<button type="button" class="button button-primary" id="procon_btn_upload_pdf">Selecionar PDF na Mídia</button>
				</div>
			</div>
			<script>
			jQuery(function($){
				var frame;
				$('#procon_btn_upload_pdf').on('click', function(e){
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: 'Selecionar Relatório em PDF',
						button: { text: 'Usar este PDF' },
						library: { type: 'application/pdf' },
						multiple: false
					});
					frame.on('select', function(){
						var attachment = frame.state().get('selection').first().toJSON();
						$('#procon_pdf_url').val(attachment.url);
					});
					frame.open();
				});
			});
			</script>
			<?php
		},
		'procon_pesquisa_pdf',
		'normal',
		'high'
	);
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		$pt = get_post_type();
		if ( in_array( $pt, array( 'procon_pesquisa_pdf', 'post' ), true ) ) {
			wp_enqueue_media();
		}
	}
} );

add_action( 'save_post_procon_pesquisa_pdf', function ( $post_id ) {
	if ( ! isset( $_POST['procon_pesquisa_pdf_nonce'] ) || ! wp_verify_nonce( $_POST['procon_pesquisa_pdf_nonce'], 'procon_salvar_pesquisa_pdf' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['procon_categoria'] ) ) {
		update_post_meta( $post_id, '_procon_categoria', sanitize_key( $_POST['procon_categoria'] ) );
	}
	if ( isset( $_POST['procon_ano'] ) ) {
		update_post_meta( $post_id, '_procon_ano', sanitize_text_field( $_POST['procon_ano'] ) );
	}
	if ( isset( $_POST['procon_mes'] ) ) {
		update_post_meta( $post_id, '_procon_mes', sanitize_text_field( $_POST['procon_mes'] ) );
	}
	if ( isset( $_POST['procon_pdf_url'] ) ) {
		update_post_meta( $post_id, '_procon_pdf_url', esc_url_raw( $_POST['procon_pdf_url'] ) );
	}
} );

/**
 * Disponibiliza os PDFs cadastrados no painel Admin para a página de Pesquisa de Preços.
 */
add_action( 'wp_footer', function () {
	$posts = get_posts( array(
		'post_type'      => 'procon_pesquisa_pdf',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	$acervo = array();
	foreach ( $posts as $p ) {
		$cat = get_post_meta( $p->ID, '_procon_categoria', true );
		$ano = get_post_meta( $p->ID, '_procon_ano', true );
		$mes = get_post_meta( $p->ID, '_procon_mes', true );
		$url = get_post_meta( $p->ID, '_procon_pdf_url', true );
		if ( ! $cat || ! $ano || ! $url ) {
			continue;
		}
		if ( ! isset( $acervo[ $cat ] ) ) {
			$acervo[ $cat ] = array();
		}
		if ( ! isset( $acervo[ $cat ][ $ano ] ) ) {
			$acervo[ $cat ][ $ano ] = array();
		}
		$acervo[ $cat ][ $ano ][] = array(
			'mes'    => $mes ?: $p->post_title,
			'titulo' => $p->post_title,
			'url'    => $url,
		);
	}
	echo '<script>window.PROCON_PESQUISAS_ACERVO = ' . wp_json_encode( $acervo ) . ";</script>\n";
}, 5 );

/* ==========================================================================
   GESTÃO DE NOTÍCIAS PARA A EQUIPE DE IMPRENSA (WP-ADMIN)
   ========================================================================== */

/**
 * 1. Renomeia "Posts" para "Notícias" no menu do painel WordPress (sem emojis).
 */
add_action( 'init', function () {
	global $wp_post_types;
	if ( isset( $wp_post_types['post'] ) ) {
		$labels = &$wp_post_types['post']->labels;
		$labels->name               = 'Notícias';
		$labels->singular_name      = 'Notícia';
		$labels->add_new            = 'Adicionar Notícia';
		$labels->add_new_item       = 'Adicionar Nova Notícia';
		$labels->edit_item          = 'Editar Notícia';
		$labels->new_item           = 'Nova Notícia';
		$labels->view_item          = 'Ver Notícia';
		$labels->search_items       = 'Buscar Notícias';
		$labels->not_found          = 'Nenhuma notícia encontrada';
		$labels->not_found_in_trash = 'Nenhuma notícia na lixeira';
		$labels->all_items          = 'Todas as Notícias';
		$labels->menu_name          = 'Notícias';
		$labels->name_admin_bar     = 'Notícia';
	}
} );

add_action( 'admin_menu', function () {
	global $menu, $submenu;
	foreach ( $menu as $key => $item ) {
		if ( isset( $item[2] ) && 'edit.php' === $item[2] ) {
			$menu[ $key ][0] = 'Notícias';
			break;
		}
	}
	if ( isset( $submenu['edit.php'] ) ) {
		foreach ( $submenu['edit.php'] as $s_key => $s_item ) {
			if ( 'edit.php' === $s_item[2] ) {
				$submenu['edit.php'][ $s_key ][0] = 'Todas as Notícias';
			} elseif ( 'post-new.php' === $s_item[2] ) {
				$submenu['edit.php'][ $s_key ][0] = 'Adicionar Notícia';
			}
		}
	}
} );

/**
 * 2. Suporte garantido ao Resumo (Linha Fina) e remoção de poluição (comentários/trackbacks).
 */
add_action( 'init', function () {
	add_post_type_support( 'post', 'excerpt' );
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'post', 'trackbacks' );
} );

add_action( 'admin_menu', function () {
	remove_meta_box( 'commentstatusdiv', 'post', 'normal' );
	remove_meta_box( 'commentsdiv', 'post', 'normal' );
	remove_meta_box( 'trackbacksdiv', 'post', 'normal' );
} );

/**
 * 3. Categorias oficiais pré-cadastradas para a equipe de imprensa (sem emojis).
 * Executa apenas na primeira vez para criar a base; no futuro, a imprensa pode
 * renomear, excluir ou criar novas livremente em Notícias > Categorias sem que o código interfira.
 */
add_action( 'admin_init', function () {
	if ( get_option( 'procon_categorias_iniciais_instaladas' ) ) {
		return;
	}
	$categorias_oficiais = array(
		'Fiscalização e Operações',
		'Campanhas Educativas',
		'Orientações ao Consumidor',
		'Pesquisas de Preços',
		'Decisões e Jurídico',
		'Comunicados Oficiais',
	);
	foreach ( $categorias_oficiais as $cat_nome ) {
		if ( ! term_exists( $cat_nome, 'category' ) ) {
			wp_insert_term( $cat_nome, 'category' );
		}
	}
	update_option( 'procon_categorias_iniciais_instaladas', '1' );
} );

/**
 * 4. Caixa de controle de Destaque no Carrossel da Página Inicial e Documento Anexo (Nota Técnica).
 */
add_action( 'add_meta_boxes', function () {
	// Destaque no Carrossel (Coluna lateral)
	add_meta_box(
		'procon_destaque_carrossel_box',
		'Carrossel da Página Inicial',
		function ( $post ) {
			wp_nonce_field( 'procon_salvar_noticia_metas', 'procon_noticia_meta_nonce' );
			$destaque = get_post_meta( $post->ID, '_procon_destaque_home', true );
			?>
			<label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer; font-size:13px; line-height:1.4;">
				<input type="checkbox" name="procon_destaque_home" value="1" <?php checked( $destaque, '1' ); ?> style="margin-top:2px;">
				<span>
					<strong>Destacar na Página Inicial</strong><br>
					<span style="color:#64748b; font-size:12px;">Marque para incluir esta notícia na rotação principal do carrossel da Home.</span>
				</span>
			</label>
			<?php
		},
		'post',
		'side',
		'high'
	);

	// Documento Oficial Anexo / Nota Técnica em PDF (Abaixo do editor)
	add_meta_box(
		'procon_anexo_documento_box',
		'Documento Oficial Anexo (Nota Técnica / Portaria / Parecer em PDF)',
		function ( $post ) {
			$pdf_url    = get_post_meta( $post->ID, '_procon_anexo_pdf_url', true );
			$pdf_titulo = get_post_meta( $post->ID, '_procon_anexo_pdf_titulo', true );
			?>
			<div style="margin-bottom:12px;">
				<label for="procon_anexo_pdf_titulo" style="display:block; font-weight:600; margin-bottom:4px; font-size:13px; color:#002752;">
					Nome de Exibição do Documento (Opcional):
				</label>
				<input type="text" name="procon_anexo_pdf_titulo" id="procon_anexo_pdf_titulo" value="<?php echo esc_attr( $pdf_titulo ); ?>" placeholder="Ex: Nota Técnica nº 01/2026 — Orientação sobre Precificação" style="width:100%; padding:6px 10px;">
			</div>
			<div>
				<label for="procon_anexo_pdf_url" style="display:block; font-weight:600; margin-bottom:4px; font-size:13px; color:#002752;">
					Arquivo PDF Oficial:
				</label>
				<div style="display:flex; gap:8px; align-items:center;">
					<input type="url" name="procon_anexo_pdf_url" id="procon_anexo_pdf_url" value="<?php echo esc_attr( $pdf_url ); ?>" placeholder="https://.../wp-content/uploads/.../documento.pdf" style="flex:1; padding:6px 10px;">
					<button type="button" class="button button-primary" id="procon_btn_upload_anexo">Selecionar PDF na Mídia</button>
				</div>
				<p style="font-size:12px; color:#64748b; margin-top:6px; margin-bottom:0;">
					Preencha apenas quando a matéria for acompanhada de um documento oficial (Nota Técnica, Portaria ou Parecer). Se for uma notícia comum sem PDF anexo, deixe em branco.
				</p>
			</div>
			<script>
			jQuery(function($){
				var frameAnexo;
				$('#procon_btn_upload_anexo').on('click', function(e){
					e.preventDefault();
					if (frameAnexo) { frameAnexo.open(); return; }
					frameAnexo = wp.media({
						title: 'Selecionar Documento Oficial em PDF',
						button: { text: 'Usar este PDF' },
						library: { type: 'application/pdf' },
						multiple: false
					});
					frameAnexo.on('select', function(){
						var attachment = frameAnexo.state().get('selection').first().toJSON();
						$('#procon_anexo_pdf_url').val(attachment.url);
						if (!$('#procon_anexo_pdf_titulo').val()) {
							$('#procon_anexo_pdf_titulo').val(attachment.title || attachment.filename);
						}
					});
					frameAnexo.open();
				});
			});
			</script>
			<?php
		},
		'post',
		'normal',
		'high'
	);
} );

add_action( 'save_post_post', function ( $post_id ) {
	if ( ! isset( $_POST['procon_noticia_meta_nonce'] ) || ! wp_verify_nonce( $_POST['procon_noticia_meta_nonce'], 'procon_salvar_noticia_metas' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// Salva destaque no carrossel
	$destaque = isset( $_POST['procon_destaque_home'] ) ? '1' : '0';
	update_post_meta( $post_id, '_procon_destaque_home', $destaque );

	// Salva anexo oficial / nota técnica
	if ( isset( $_POST['procon_anexo_pdf_url'] ) ) {
		update_post_meta( $post_id, '_procon_anexo_pdf_url', esc_url_raw( $_POST['procon_anexo_pdf_url'] ) );
	}
	if ( isset( $_POST['procon_anexo_pdf_titulo'] ) ) {
		update_post_meta( $post_id, '_procon_anexo_pdf_titulo', sanitize_text_field( $_POST['procon_anexo_pdf_titulo'] ) );
	}
} );

/**
 * 5. Exibe o quadro oficial de Documento Anexo / Nota Técnica ao final da matéria.
 */
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$pdf_url = get_post_meta( get_the_ID(), '_procon_anexo_pdf_url', true );
	if ( empty( $pdf_url ) ) {
		return $content;
	}
	$pdf_titulo = get_post_meta( get_the_ID(), '_procon_anexo_pdf_titulo', true ) ?: 'Nota Técnica Oficial';

	ob_start();
	?>
	<div class="procon-documento-anexo">
		<div class="anexo-info">
			<span class="anexo-rotulo">Documento Oficial Anexo</span>
			<strong class="anexo-titulo"><?php echo esc_html( $pdf_titulo ); ?></strong>
		</div>
		<div class="anexo-acoes">
			<a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline">Visualizar Documento</a>
			<a href="<?php echo esc_url( $pdf_url ); ?>" download class="btn btn-primary">Baixar PDF</a>
		</div>
	</div>
	<?php
	$anexo_html = ob_get_clean();

	return $content . $anexo_html;
} );

/* ==========================================================================
   GERENCIADOR DO RANKING DE RECLAMAÇÕES (TOP 10 MENSAL E ANUAL)
   ========================================================================== */

/**
 * Retorna os dados padrão do Ranking (Top 10) caso ainda não tenham sido configurados.
 */
function procon_ranking_dados_padrao() {
	return array(
		'mensal' => array(
			'periodo'       => 'Outubro 2026',
			'atualizado_em' => '10 de Novembro de 2026',
			'itens'         => array(
				array( 'posicao' => 1,  'empresa' => 'Claro S.A.',               'segmento' => 'Telecomunicações',   'reclamacoes' => 1842, 'resolucao_pct' => 62, 'variacao' => 'up' ),
				array( 'posicao' => 2,  'empresa' => 'Vivo / Telefônica Brasil', 'segmento' => 'Telecomunicações',   'reclamacoes' => 1615, 'resolucao_pct' => 68, 'variacao' => 'down' ),
				array( 'posicao' => 3,  'empresa' => 'Mercado Livre',            'segmento' => 'E-commerce',         'reclamacoes' => 1394, 'resolucao_pct' => 71, 'variacao' => 'up' ),
				array( 'posicao' => 4,  'empresa' => 'OI S.A.',                  'segmento' => 'Telecomunicações',   'reclamacoes' => 1187, 'resolucao_pct' => 55, 'variacao' => 'stable' ),
				array( 'posicao' => 5,  'empresa' => 'Amazon Brasil',            'segmento' => 'E-commerce',         'reclamacoes' => 1056, 'resolucao_pct' => 78, 'variacao' => 'up' ),
				array( 'posicao' => 6,  'empresa' => 'Bradesco',                 'segmento' => 'Bancos e Finanças',  'reclamacoes' => 982,  'resolucao_pct' => 74, 'variacao' => 'down' ),
				array( 'posicao' => 7,  'empresa' => 'Itaú Unibanco',            'segmento' => 'Bancos e Finanças',  'reclamacoes' => 934,  'resolucao_pct' => 76, 'variacao' => 'stable' ),
				array( 'posicao' => 8,  'empresa' => 'Magazine Luiza (Magalu)',  'segmento' => 'E-commerce',         'reclamacoes' => 887,  'resolucao_pct' => 69, 'variacao' => 'down' ),
				array( 'posicao' => 9,  'empresa' => 'Nubank',                   'segmento' => 'Fintechs',           'reclamacoes' => 743,  'resolucao_pct' => 82, 'variacao' => 'up' ),
				array( 'posicao' => 10, 'empresa' => 'Caixa Econômica Federal',  'segmento' => 'Bancos e Finanças',  'reclamacoes' => 698,  'resolucao_pct' => 61, 'variacao' => 'stable' ),
			),
		),
		'anual'  => array(
			'periodo'       => 'Acumulado 2026 (12 Meses)',
			'atualizado_em' => '10 de Novembro de 2026',
			'itens'         => array(
				array( 'posicao' => 1,  'empresa' => 'Claro S.A.',               'segmento' => 'Telecomunicações',   'reclamacoes' => 19420, 'resolucao_pct' => 65, 'variacao' => 'stable' ),
				array( 'posicao' => 2,  'empresa' => 'Vivo / Telefônica Brasil', 'segmento' => 'Telecomunicações',   'reclamacoes' => 17850, 'resolucao_pct' => 69, 'variacao' => 'stable' ),
				array( 'posicao' => 3,  'empresa' => 'Mercado Livre',            'segmento' => 'E-commerce',         'reclamacoes' => 14900, 'resolucao_pct' => 73, 'variacao' => 'up' ),
				array( 'posicao' => 4,  'empresa' => 'OI S.A.',                  'segmento' => 'Telecomunicações',   'reclamacoes' => 12300, 'resolucao_pct' => 56, 'variacao' => 'down' ),
				array( 'posicao' => 5,  'empresa' => 'Bradesco',                 'segmento' => 'Bancos e Finanças',  'reclamacoes' => 11200, 'resolucao_pct' => 75, 'variacao' => 'stable' ),
				array( 'posicao' => 6,  'empresa' => 'Amazon Brasil',            'segmento' => 'E-commerce',         'reclamacoes' => 10800, 'resolucao_pct' => 80, 'variacao' => 'up' ),
				array( 'posicao' => 7,  'empresa' => 'Itaú Unibanco',            'segmento' => 'Bancos e Finanças',  'reclamacoes' => 9950,  'resolucao_pct' => 77, 'variacao' => 'stable' ),
				array( 'posicao' => 8,  'empresa' => 'Magazine Luiza (Magalu)',  'segmento' => 'E-commerce',         'reclamacoes' => 9200,  'resolucao_pct' => 71, 'variacao' => 'down' ),
				array( 'posicao' => 9,  'empresa' => 'Caixa Econômica Federal',  'segmento' => 'Bancos e Finanças',  'reclamacoes' => 7800,  'resolucao_pct' => 63, 'variacao' => 'stable' ),
				array( 'posicao' => 10, 'empresa' => 'Nubank',                   'segmento' => 'Fintechs',           'reclamacoes' => 7650,  'resolucao_pct' => 84, 'variacao' => 'up' ),
			),
		),
	);
}

/**
 * Adiciona a tela de gerenciamento do Ranking no menu lateral do WordPress.
 */
add_action( 'admin_menu', function () {
	add_menu_page(
		'Ranking de Reclamações',
		'Ranking',
		'edit_posts',
		'procon-ranking',
		'procon_render_ranking_admin_page',
		'dashicons-chart-bar',
		26
	);
} );

/**
 * Salva os dados do Ranking enviados pelo formulário do WP-Admin.
 */
add_action( 'admin_init', function () {
	if ( ! isset( $_POST['procon_salvar_ranking_nonce'] ) || ! wp_verify_nonce( $_POST['procon_salvar_ranking_nonce'], 'procon_salvar_ranking_action' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$dados = array(
		'mensal' => array(
			'periodo'       => sanitize_text_field( $_POST['mensal_periodo'] ?? '' ),
			'atualizado_em' => sanitize_text_field( $_POST['mensal_atualizado_em'] ?? '' ),
			'itens'         => array(),
		),
		'anual'  => array(
			'periodo'       => sanitize_text_field( $_POST['anual_periodo'] ?? '' ),
			'atualizado_em' => sanitize_text_field( $_POST['anual_atualizado_em'] ?? '' ),
			'itens'         => array(),
		),
	);

	foreach ( array( 'mensal', 'anual' ) as $tipo ) {
		if ( isset( $_POST[ $tipo . '_empresa' ] ) && is_array( $_POST[ $tipo . '_empresa' ] ) ) {
			for ( $i = 0; $i < 10; $i++ ) {
				$empresa = sanitize_text_field( $_POST[ $tipo . '_empresa' ][ $i ] ?? '' );
				if ( empty( $empresa ) ) {
					continue;
				}
				$segmento       = sanitize_text_field( $_POST[ $tipo . '_segmento' ][ $i ] ?? '' );
				$reclamacoes    = absint( $_POST[ $tipo . '_reclamacoes' ][ $i ] ?? 0 );
				$resolucao_pct  = min( 100, max( 0, absint( $_POST[ $tipo . '_resolucao_pct' ][ $i ] ?? 0 ) ) );
				$variacao       = sanitize_key( $_POST[ $tipo . '_variacao' ][ $i ] ?? 'stable' );
				if ( ! in_array( $variacao, array( 'up', 'down', 'stable' ), true ) ) {
					$variacao = 'stable';
				}

				$dados[ $tipo ]['itens'][] = array(
					'posicao'       => count( $dados[ $tipo ]['itens'] ) + 1,
					'empresa'       => $empresa,
					'segmento'      => $segmento,
					'reclamacoes'   => $reclamacoes,
					'resolucao_pct' => $resolucao_pct,
					'variacao'      => $variacao,
				);
			}
		}
	}

	update_option( 'procon_ranking_dados', $dados );
	wp_safe_redirect( add_query_arg( array( 'page' => 'procon-ranking', 'salvo' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
} );

/**
 * Renderiza a tela de administração do Ranking no WP-Admin.
 */
function procon_render_ranking_admin_page() {
	$dados = get_option( 'procon_ranking_dados' );
	if ( empty( $dados ) || ! is_array( $dados ) ) {
		$dados = procon_ranking_dados_padrao();
	}
	$mensal = $dados['mensal'] ?? array();
	$anual  = $dados['anual'] ?? array();
	?>
	<div class="wrap">
		<h1 style="color:#002752; font-weight:700; margin-bottom:12px;">Ranking de Reclamações — Top 10</h1>
		<p style="color:#64748b; font-size:14px; margin-bottom:20px;">
			Gerenciamento das 10 empresas mais reclamadas no PROCON PE. Altere os valores abaixo e clique em Salvar para atualizar a página pública automaticamente.
		</p>

		<?php if ( isset( $_GET['salvo'] ) ) : ?>
			<div class="notice notice-success is-dismissible" style="border-left-color:#059669;">
				<p><strong>Sucesso:</strong> Dados do Ranking atualizados com sucesso!</p>
			</div>
		<?php endif; ?>

		<style>
			.procon-ranking-tabs { display:flex; gap:8px; border-bottom:2px solid #cbd5e1; margin-bottom:20px; }
			.procon-ranking-tab-btn { background:#f1f5f9; border:1px solid #cbd5e1; border-bottom:none; padding:10px 20px; font-weight:700; font-size:14px; color:#475569; cursor:pointer; border-radius:6px 6px 0 0; }
			.procon-ranking-tab-btn.active { background:#ffffff; color:#002752; border-color:#002752; margin-bottom:-2px; border-bottom:2px solid #ffffff; }
			.procon-tab-content { display:none; background:#ffffff; border:1px solid #cbd5e1; border-radius:0 0 6px 6px; padding:20px; }
			.procon-tab-content.active { display:block; }
			.procon-table-form { width:100%; border-collapse:collapse; margin-top:16px; }
			.procon-table-form th { background:#002752; color:#ffffff; padding:10px 12px; text-align:left; font-size:13px; font-weight:600; }
			.procon-table-form td { padding:8px 10px; border-bottom:1px solid #e2e8f0; vertical-align:middle; }
			.procon-table-form tr:nth-child(even) { background:#f8fafc; }
			.procon-table-form input, .procon-table-form select { width:100%; padding:6px 8px; font-size:13px; }
			.procon-header-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
			.procon-header-grid label { display:block; font-weight:600; font-size:13px; color:#002752; margin-bottom:4px; }
			.procon-header-grid input { width:100%; padding:8px 12px; font-size:14px; }
		</style>

		<datalist id="segmentos_sugestoes">
			<option value="Telecomunicações">
			<option value="E-commerce">
			<option value="Bancos e Finanças">
			<option value="Fintechs">
			<option value="Energia Elétrica">
			<option value="Energia e Gás">
			<option value="Saúde">
			<option value="Varejo">
			<option value="Turismo e Transportes">
		</datalist>

		<form method="post" action="">
			<?php wp_nonce_field( 'procon_salvar_ranking_action', 'procon_salvar_ranking_nonce' ); ?>

			<div class="procon-ranking-tabs">
				<button type="button" class="procon-ranking-tab-btn active" onclick="alternarTabRanking('mensal')">Ranking Mensal (Top 10)</button>
				<button type="button" class="procon-ranking-tab-btn" onclick="alternarTabRanking('anual')">Ranking Anual (Top 10)</button>
			</div>

			<!-- TAB MENSAL -->
			<div class="procon-tab-content active" id="tab-ranking-mensal">
				<div class="procon-header-grid">
					<div>
						<label for="mensal_periodo">Mês de Referência:</label>
						<input type="text" id="mensal_periodo" name="mensal_periodo" value="<?php echo esc_attr( $mensal['periodo'] ?? 'Outubro 2026' ); ?>" placeholder="Ex: Outubro 2026">
					</div>
					<div>
						<label for="mensal_atualizado_em">Data da Última Atualização:</label>
						<input type="text" id="mensal_atualizado_em" name="mensal_atualizado_em" value="<?php echo esc_attr( $mensal['atualizado_em'] ?? '10 de Novembro de 2026' ); ?>" placeholder="Ex: 10 de Novembro de 2026">
					</div>
				</div>

				<table class="procon-table-form">
					<thead>
						<tr>
							<th style="width:50px; text-align:center;">#</th>
							<th style="width:30%;">Empresa</th>
							<th style="width:25%;">Segmento</th>
							<th style="width:15%;">Reclamações</th>
							<th style="width:15%;">Resolução %</th>
							<th style="width:15%;">Variação</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$itens_mensal = $mensal['itens'] ?? array();
						for ( $i = 0; $i < 10; $i++ ) :
							$it = $itens_mensal[ $i ] ?? array();
							?>
							<tr>
								<td style="text-align:center; font-weight:700; color:#002752;"><?php echo ( $i + 1 ); ?>º</td>
								<td><input type="text" name="mensal_empresa[]" value="<?php echo esc_attr( $it['empresa'] ?? '' ); ?>" placeholder="Nome da Empresa" required></td>
								<td><input type="text" name="mensal_segmento[]" list="segmentos_sugestoes" value="<?php echo esc_attr( $it['segmento'] ?? '' ); ?>" placeholder="Segmento"></td>
								<td><input type="number" name="mensal_reclamacoes[]" value="<?php echo esc_attr( $it['reclamacoes'] ?? '' ); ?>" min="0"></td>
								<td><input type="number" name="mensal_resolucao_pct[]" value="<?php echo esc_attr( $it['resolucao_pct'] ?? '' ); ?>" min="0" max="100"></td>
								<td>
									<select name="mensal_variacao[]">
										<option value="up" <?php selected( $it['variacao'] ?? '', 'up' ); ?>>▲ Subiu</option>
										<option value="stable" <?php selected( $it['variacao'] ?? '', 'stable' ); ?>>— Manteve</option>
										<option value="down" <?php selected( $it['variacao'] ?? '', 'down' ); ?>>▼ Desceu</option>
									</select>
								</td>
							</tr>
						<?php endfor; ?>
					</tbody>
				</table>
			</div>

			<!-- TAB ANUAL -->
			<div class="procon-tab-content" id="tab-ranking-anual">
				<div class="procon-header-grid">
					<div>
						<label for="anual_periodo">Ano de Referência / Acumulado:</label>
						<input type="text" id="anual_periodo" name="anual_periodo" value="<?php echo esc_attr( $anual['periodo'] ?? 'Acumulado 2026 (12 Meses)' ); ?>" placeholder="Ex: Acumulado 2026">
					</div>
					<div>
						<label for="anual_atualizado_em">Data da Última Atualização:</label>
						<input type="text" id="anual_atualizado_em" name="anual_atualizado_em" value="<?php echo esc_attr( $anual['atualizado_em'] ?? '10 de Novembro de 2026' ); ?>" placeholder="Ex: 10 de Novembro de 2026">
					</div>
				</div>

				<table class="procon-table-form">
					<thead>
						<tr>
							<th style="width:50px; text-align:center;">#</th>
							<th style="width:30%;">Empresa</th>
							<th style="width:25%;">Segmento</th>
							<th style="width:15%;">Reclamações</th>
							<th style="width:15%;">Resolução %</th>
							<th style="width:15%;">Variação</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$itens_anual = $anual['itens'] ?? array();
						for ( $i = 0; $i < 10; $i++ ) :
							$it = $itens_anual[ $i ] ?? array();
							?>
							<tr>
								<td style="text-align:center; font-weight:700; color:#002752;"><?php echo ( $i + 1 ); ?>º</td>
								<td><input type="text" name="anual_empresa[]" value="<?php echo esc_attr( $it['empresa'] ?? '' ); ?>" placeholder="Nome da Empresa"></td>
								<td><input type="text" name="anual_segmento[]" list="segmentos_sugestoes" value="<?php echo esc_attr( $it['segmento'] ?? '' ); ?>" placeholder="Segmento"></td>
								<td><input type="number" name="anual_reclamacoes[]" value="<?php echo esc_attr( $it['reclamacoes'] ?? '' ); ?>" min="0"></td>
								<td><input type="number" name="anual_resolucao_pct[]" value="<?php echo esc_attr( $it['resolucao_pct'] ?? '' ); ?>" min="0" max="100"></td>
								<td>
									<select name="anual_variacao[]">
										<option value="up" <?php selected( $it['variacao'] ?? '', 'up' ); ?>>▲ Subiu</option>
										<option value="stable" <?php selected( $it['variacao'] ?? '', 'stable' ); ?>>— Manteve</option>
										<option value="down" <?php selected( $it['variacao'] ?? '', 'down' ); ?>>▼ Desceu</option>
									</select>
								</td>
							</tr>
						<?php endfor; ?>
					</tbody>
				</table>
			</div>

			<p style="margin-top:20px;">
				<button type="submit" class="button button-primary button-hero" style="background:#002752; border-color:#002752;">
					Salvar Dados do Ranking
				</button>
			</p>
		</form>

		<script>
		function alternarTabRanking(tipo) {
			document.querySelectorAll('.procon-ranking-tab-btn').forEach(function(b, idx) {
				b.classList.toggle('active', (tipo === 'mensal' && idx === 0) || (tipo === 'anual' && idx === 1));
			});
			document.getElementById('tab-ranking-mensal').classList.toggle('active', tipo === 'mensal');
			document.getElementById('tab-ranking-anual').classList.toggle('active', tipo === 'anual');
		}
		</script>
	</div>
	<?php
}

/**
 * Permite que widgets do Elementor (inclusive o widget de Código HTML) executem shortcodes.
 */
add_filter( 'elementor/widget/render_content', function ( $content ) {
	return do_shortcode( $content );
} );

/**
 * Shortcode [procon_ranking] para exibir o Top 10 na página pública do site.
 */
add_shortcode( 'procon_ranking', function () {
	$dados = get_option( 'procon_ranking_dados' );
	if ( empty( $dados ) || ! is_array( $dados ) ) {
		$dados = procon_ranking_dados_padrao();
	}

	$mensal = $dados['mensal'] ?? array();
	$anual  = $dados['anual'] ?? array();

	// Coleta segmentos únicos para o filtro
	$segmentos = array();
	foreach ( array( 'mensal', 'anual' ) as $t ) {
		foreach ( ( $dados[ $t ]['itens'] ?? array() ) as $item ) {
			if ( ! empty( $item['segmento'] ) && ! in_array( $item['segmento'], $segmentos, true ) ) {
				$segmentos[] = $item['segmento'];
			}
		}
	}
	sort( $segmentos );

	ob_start();
	?>
	<style>
		.resolucao-bar {
			display: flex !important;
			align-items: center !important;
			gap: 8px !important;
			min-width: 140px !important;
		}
		.resolucao-track {
			flex: 1 1 auto !important;
			height: 8px !important;
			background-color: #e2e8f0 !important;
			border-radius: 9999px !important;
			overflow: hidden !important;
			position: relative !important;
			display: block !important;
		}
		.resolucao-track .resolucao-fill,
		.resolucao-fill {
			height: 100% !important;
			border-radius: 9999px !important;
			display: block !important;
			min-width: 4px !important;
		}
		.resolucao-fill.resolucao-alta,
		.resolucao-fill.resolucao-success {
			background-color: #15803d !important;
		}
		.resolucao-fill.resolucao-media,
		.resolucao-fill.resolucao-warning {
			background-color: #d97706 !important;
		}
		.resolucao-fill.resolucao-baixa,
		.resolucao-fill.resolucao-danger {
			background-color: #dc2626 !important;
		}
		.resolucao-pct {
			font-size: 0.82rem !important;
			font-weight: 700 !important;
			white-space: nowrap !important;
			min-width: 38px !important;
			color: #1e293b !important;
		}
		.rank-top3 {
			background-color: #fffbeb !important;
		}
		.var-up { color: #dc2626 !important; }
		.var-down { color: #15803d !important; }
		.var-stable { color: #94a3b8 !important; }
	</style>
	<section class="section">
		<div class="container">

			<!-- Período e Filtros de Busca -->
			<div class="ranking-header-info">
				<div>
					<h2 class="section-title">As 10 Empresas Mais Reclamadas</h2>
					<p class="text-muted">
						Período de referência: <strong data-ranking="periodo-rotulo"><?php echo esc_html( $mensal['periodo'] ?? 'Mensal' ); ?></strong>
						— Última atualização: <span><?php echo esc_html( $mensal['atualizado_em'] ?? '' ); ?></span>
					</p>
				</div>
				<div class="d-flex gap-md align-center" style="flex-wrap:wrap">

					<!-- Alternador de Periodicidade -->
					<div class="ranking-periodo-toggle" role="group" aria-label="Filtrar por periodicidade">
						<button type="button" class="filtro-btn active" data-ranking-periodo="mensal"
								data-rotulo-mensal="<?php echo esc_attr( $mensal['periodo'] ?? 'Mensal' ); ?>"
								data-rotulo-anual="<?php echo esc_attr( $anual['periodo'] ?? 'Anual' ); ?>">
							Mensal
						</button>
						<button type="button" class="filtro-btn" data-ranking-periodo="anual"
								data-rotulo-mensal="<?php echo esc_attr( $mensal['periodo'] ?? 'Mensal' ); ?>"
								data-rotulo-anual="<?php echo esc_attr( $anual['periodo'] ?? 'Anual' ); ?>">
							Anual
						</button>
					</div>

					<!-- Busca por Empresa -->
					<div class="search-form" style="width:200px; border-radius:var(--radius-md);">
						<input class="search-input" type="search" data-ranking="busca" placeholder="Buscar empresa..." aria-label="Buscar empresa no ranking" style="padding:.5rem .75rem; font-size:var(--font-size-sm);">
					</div>

					<!-- Filtro por Segmento -->
					<select data-ranking="segmento" aria-label="Filtrar por segmento" style="padding:.5rem .75rem; border:1px solid var(--color-border); border-radius:var(--radius-md); font-family:var(--font-primary); font-size:var(--font-size-sm); color:var(--color-text);">
						<option value="">Todos os segmentos</option>
						<?php foreach ( $segmentos as $seg ) : ?>
							<option value="<?php echo esc_attr( $seg ); ?>"><?php echo esc_html( $seg ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<!-- Legenda de Resolução -->
			<div class="ranking-legenda" role="note" aria-label="Legenda de resolução">
				<span class="legenda-item legenda-success">● Alta resolução (≥75%)</span>
				<span class="legenda-item legenda-warning">● Média resolução (60-74%)</span>
				<span class="legenda-item legenda-danger">● Baixa resolução (&lt;60%)</span>
			</div>

			<!-- Tabela Responsiva do Top 10 -->
			<div class="table-responsive" role="region" aria-label="Tabela de ranking de reclamações" tabindex="0">
				<table class="ranking-table" aria-label="Ranking de reclamações por empresa">
					<thead>
						<tr>
							<th scope="col" aria-label="Posição">#</th>
							<th scope="col">Empresa</th>
							<th scope="col">Segmento</th>
							<th scope="col" aria-label="Número de reclamações">Reclamações</th>
							<th scope="col" aria-label="Percentual de resolução">Resolução %</th>
							<th scope="col" aria-label="Variação">Variação</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( array( 'mensal', 'anual' ) as $tipo ) :
							$itens = $dados[ $tipo ]['itens'] ?? array();
							foreach ( $itens as $item ) :
								$pct = $item['resolucao_pct'] ?? 0;
								$cor_classe = $pct >= 75 ? 'alta' : ( $pct >= 60 ? 'media' : 'baixa' );
								$var = $item['variacao'] ?? 'stable';
								$var_simbolo = 'up' === $var ? '▲' : ( 'down' === $var ? '▼' : '—' );
								$var_classe  = 'up' === $var ? 'var-up' : ( 'down' === $var ? 'var-down' : 'var-stable' );
								$pos = $item['posicao'] ?? 1;
								?>
								<tr data-periodo="<?php echo esc_attr( $tipo ); ?>"
									data-empresa="<?php echo esc_attr( $item['empresa'] ?? '' ); ?>"
									data-segmento="<?php echo esc_attr( $item['segmento'] ?? '' ); ?>"
									class="<?php echo $pos <= 3 ? 'rank-top3' : ''; ?>">
									<td class="rank-pos"><strong><?php echo esc_html( $pos ); ?>º</strong></td>
									<td class="rank-empresa"><strong><?php echo esc_html( $item['empresa'] ?? '' ); ?></strong></td>
									<td><span class="tag tag-light"><?php echo esc_html( $item['segmento'] ?? '' ); ?></span></td>
									<td class="rank-num"><strong><?php echo number_format_i18n( $item['reclamacoes'] ?? 0 ); ?></strong></td>
									<td>
										<div class="resolucao-bar" aria-label="Taxa de resolução: <?php echo esc_attr( $pct ); ?>%">
											<div class="resolucao-track">
												<div class="resolucao-fill resolucao-<?php echo esc_attr( $cor_classe ); ?>" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
											</div>
											<span class="resolucao-pct"><?php echo esc_html( $pct ); ?>%</span>
										</div>
									</td>
									<td class="rank-variacao">
										<span class="<?php echo esc_attr( $var_classe ); ?>"><?php echo esc_html( $var_simbolo ); ?></span>
									</td>
								</tr>
								<?php
							endforeach;
						endforeach;
						?>
					</tbody>
				</table>

				<p data-ranking="vazio" class="text-center text-muted" style="padding:2rem;" hidden>
					Nenhuma empresa encontrada com os filtros selecionados.
				</p>
			</div>

			<div class="ranking-nota" style="margin-top:var(--space-xl)">
				<p class="text-muted" style="font-size:var(--font-size-sm)">
					<strong>Nota:</strong> Os dados apresentados referem-se às reclamações registradas diretamente no PROCON Pernambuco.
					O índice de resolução indica o percentual de casos resolvidos pelas empresas dentro do prazo estabelecido.
					Dados atualizados periodicamente pela equipe técnica.
				</p>
			</div>

		</div>
	</section>
	<?php
	return ob_get_clean();
} );


