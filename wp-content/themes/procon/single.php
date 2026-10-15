<?php
/**
 * Detalhe da notícia (pages/noticia-detalhe.html).
 */

get_header();

while ( have_posts() ) :
	the_post();
	$procon_titulo = get_the_title();
	$procon_link   = get_permalink();
	$procon_resumo = wp_html_excerpt( $procon_titulo, 50, '...' );

	procon_breadcrumb( array( array( 'Notícias', procon_noticias_url() ), array( $procon_resumo ) ) );
	?>
<div class="page-hero" style="padding-bottom:var(--space-2xl)">
  <div class="container">
    <?php if ( procon_categoria() ) : ?>
      <span class="tag tag-secondary" style="margin-bottom:var(--space-md);display:inline-block"><?php echo esc_html( procon_categoria() ); ?></span>
    <?php endif; ?>
    <h1 style="max-width:800px"><?php echo esc_html( $procon_titulo ); ?></h1>
    <p style="margin-top:var(--space-md);opacity:.8;display:flex;align-items:center;gap:.5rem">
      <?php echo procon_icon( 'calendar', 16 ); // phpcs:ignore ?>
      <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( procon_data() ); ?></time>
    </p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="noticia-layout">
      <article class="noticia-content" aria-label="Conteúdo da notícia">
        <?php if ( has_post_thumbnail() ) : ?>
          <?php the_post_thumbnail( 'large', array( 'class' => 'noticia-img', 'alt' => 'Imagem da notícia ' . $procon_titulo, 'style' => 'width:100%;border-radius:var(--radius-md);margin-bottom:var(--space-xl)' ) ); ?>
        <?php endif; ?>

        <div class="noticia-body"><?php the_content(); ?></div>

        <div class="noticia-actions" style="margin-top:var(--space-xl);padding-top:var(--space-lg);border-top:1px solid var(--color-border)">
          <a class="btn btn-outline" href="<?php echo esc_url( procon_noticias_url() ); ?>">← Voltar às Notícias</a>
          <div class="share-buttons" aria-label="Compartilhar notícia">
            <span style="color:var(--color-text-muted);font-size:var(--font-size-sm)">Compartilhar:</span>
            <a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $procon_link ) ); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline" aria-label="Compartilhar no Facebook">Facebook</a>
            <a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( 'Confira esta notícia do PROCON PE: ' . $procon_link ) ); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline" aria-label="Compartilhar no WhatsApp">WhatsApp</a>
          </div>
        </div>
      </article>

      <aside class="noticia-sidebar" aria-label="Outras notícias">
        <h2 class="section-title" style="font-size:1.1rem">Outras Notícias</h2>
        <?php
        $procon_outras = get_posts( array(
            'post_type'      => 'post',
            'posts_per_page' => 3,
            'post__not_in'   => array( get_the_ID() ),
        ) );
        foreach ( $procon_outras as $procon_n ) :
            ?>
          <div>
            <a href="<?php echo esc_url( get_permalink( $procon_n ) ); ?>" class="news-sidebar-item" aria-label="<?php echo esc_attr( 'Ler notícia: ' . get_the_title( $procon_n ) ); ?>">
              <div class="news-sidebar-img" style="width:80px;height:60px;background:linear-gradient(135deg,var(--color-primary),var(--color-secondary));border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?php echo procon_icon( 'image', 24, 'rgba(255,255,255,.5)' ); // phpcs:ignore ?>
              </div>
              <div class="news-sidebar-content">
                <p class="news-sidebar-category"><?php echo esc_html( procon_categoria( $procon_n ) ); ?></p>
                <p class="news-sidebar-title"><?php echo esc_html( get_the_title( $procon_n ) ); ?></p>
                <p class="news-sidebar-date"><?php echo esc_html( procon_data( $procon_n ) ); ?></p>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </aside>
    </div>
  </div>
</section>
	<?php
endwhile;

get_footer();
