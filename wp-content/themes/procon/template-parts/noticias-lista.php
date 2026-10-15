<?php
/**
 * Lista de notícias com busca, filtro por categoria e paginação (pages/noticias.html).
 * Usada pela página de notícias, pelas categorias e pela busca.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

$procon_url_noticias = procon_noticias_url();
$procon_cat_atual    = is_category() ? get_queried_object_id() : 0;
$procon_busca        = get_search_query();

if ( is_category() ) {
	procon_breadcrumb( array( array( 'Notícias', $procon_url_noticias ), array( single_cat_title( '', false ) ) ) );
	procon_page_hero( single_cat_title( '', false ), 'Notícias da categoria ' . single_cat_title( '', false ) );
} elseif ( is_search() ) {
	procon_breadcrumb( array( array( 'Notícias', $procon_url_noticias ), array( 'Busca' ) ) );
	procon_page_hero( 'Resultados da busca', $procon_busca ? 'Notícias encontradas para "' . $procon_busca . '"' : '' );
} else {
	procon_breadcrumb( array( array( 'Notícias' ) ) );
	procon_page_hero( 'Últimas Notícias', 'Acompanhe as ações, campanhas e informações do PROCON Pernambuco' );
}

// Categorias na ordem de criação (a mesma da referência), sem a categoria padrão.
$procon_categorias = get_categories( array(
	'hide_empty' => false,
	'orderby'    => 'term_id',
	'exclude'    => array( (int) get_option( 'default_category' ) ),
) );

$procon_total = (int) $wp_query->found_posts;
?>
<section class="section">
  <div class="container">
    <div class="noticias-toolbar" role="search" aria-label="Filtrar notícias">
      <form class="search-form noticias-search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input class="search-input" type="search" name="s" value="<?php echo esc_attr( $procon_busca ); ?>" placeholder="Buscar notícias..." aria-label="Buscar notícias">
        <button class="search-btn" type="submit" aria-label="Buscar">
          <?php echo procon_icon( 'search', 18 ); // phpcs:ignore ?>
          Buscar
        </button>
      </form>
      <div class="noticias-filtros" role="group" aria-label="Filtrar por categoria">
        <a class="filtro-btn<?php echo $procon_cat_atual ? '' : ' active'; ?>" href="<?php echo esc_url( $procon_url_noticias ); ?>" aria-pressed="<?php echo $procon_cat_atual ? 'false' : 'true'; ?>">Todas</a>
        <?php foreach ( $procon_categorias as $procon_cat ) : ?>
          <a class="filtro-btn<?php echo $procon_cat_atual === $procon_cat->term_id ? ' active' : ''; ?>" href="<?php echo esc_url( get_category_link( $procon_cat ) ); ?>" aria-pressed="<?php echo $procon_cat_atual === $procon_cat->term_id ? 'true' : 'false'; ?>"><?php echo esc_html( $procon_cat->name ); ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <p class="noticias-count" aria-live="polite" aria-atomic="true">
      <?php if ( $procon_total > 0 ) : ?>
        <?php echo esc_html( sprintf( _n( '%d notícia encontrada', '%d notícias encontradas', $procon_total, 'procon' ), $procon_total ) ); ?>
      <?php else : ?>
        Nenhuma notícia encontrada para os filtros selecionados.
      <?php endif; ?>
    </p>

    <?php if ( have_posts() ) : ?>
    <div class="noticias-grid" role="list" aria-label="Lista de notícias">
      <?php
      while ( have_posts() ) :
          the_post();
          $procon_titulo = get_the_title();
          ?>
      <article class="card noticias-card" role="listitem">
        <div class="card-img-wrapper">
          <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'medium_large', array( 'class' => 'card-img', 'alt' => 'Imagem da notícia ' . $procon_titulo, 'loading' => 'lazy' ) ); ?>
          <?php else : ?>
            <div class="card-img-placeholder" aria-hidden="true"><?php echo procon_icon( 'image', 40 ); // phpcs:ignore ?></div>
          <?php endif; ?>
          <?php if ( procon_categoria() ) : ?>
            <span class="card-category tag tag-secondary"><?php echo esc_html( procon_categoria() ); ?></span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <p class="card-date">
            <?php echo procon_icon( 'calendar', 14 ); // phpcs:ignore ?>
            <?php echo esc_html( procon_data() ); ?>
          </p>
          <h2 class="card-title">
            <a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( 'Leia a notícia: ' . $procon_titulo ); ?>"><?php echo esc_html( $procon_titulo ); ?></a>
          </h2>
          <p class="card-text"><?php echo esc_html( get_the_excerpt() ); ?></p>
          <a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm" aria-label="<?php echo esc_attr( 'Ler mais sobre: ' . $procon_titulo ); ?>">Leia mais →</a>
        </div>
      </article>
      <?php endwhile; ?>
    </div>

      <?php
      $procon_paginas = (int) $wp_query->max_num_pages;
      if ( $procon_paginas > 1 ) :
          $procon_atual = max( 1, (int) get_query_var( 'paged' ) );
          ?>
    <nav class="pagination" aria-label="Navegação de páginas">
      <?php if ( $procon_atual > 1 ) : ?>
        <a class="page-btn" href="<?php echo esc_url( get_pagenum_link( $procon_atual - 1 ) ); ?>" aria-label="Página anterior">‹</a>
      <?php else : ?>
        <button class="page-btn" type="button" disabled aria-label="Página anterior">‹</button>
      <?php endif; ?>
      <?php for ( $procon_p = 1; $procon_p <= $procon_paginas; $procon_p++ ) : ?>
        <a class="page-btn<?php echo $procon_p === $procon_atual ? ' active' : ''; ?>" href="<?php echo esc_url( get_pagenum_link( $procon_p ) ); ?>" aria-label="<?php echo esc_attr( 'Ir para página ' . $procon_p ); ?>"<?php echo $procon_p === $procon_atual ? ' aria-current="page"' : ''; ?>><?php echo (int) $procon_p; ?></a>
      <?php endfor; ?>
      <?php if ( $procon_atual < $procon_paginas ) : ?>
        <a class="page-btn" href="<?php echo esc_url( get_pagenum_link( $procon_atual + 1 ) ); ?>" aria-label="Próxima página">›</a>
      <?php else : ?>
        <button class="page-btn" type="button" disabled aria-label="Próxima página">›</button>
      <?php endif; ?>
    </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
