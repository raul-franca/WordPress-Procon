<?php
/**
 * Shortcodes usados nas páginas do Elementor (widget "Shortcode").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [procon_noticias_carrossel quantidade="8"]
 * Seção "Últimas Notícias" da página inicial, com as notícias mais recentes.
 */
add_shortcode( 'procon_noticias_carrossel', function ( $atts ) {
	$atts     = shortcode_atts( array( 'quantidade' => 8 ), $atts, 'procon_noticias_carrossel' );
	$noticias = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => (int) $atts['quantidade'],
		'post_status'    => 'publish',
	) );

	ob_start();
	?>
<section class="carousel-section" aria-labelledby="noticias-title" id="ultimas-noticias">
  <div class="container">
    <div class="carousel-header">
      <div>
        <h2 class="section-title" id="noticias-title">Últimas Notícias</h2>
        <p class="section-subtitle" style="margin-bottom:0">Fique por dentro de tudo que o PROCON PE está fazendo por você</p>
      </div>
      <a href="<?php echo esc_url( procon_noticias_url() ); ?>" class="btn btn-outline" aria-label="Ver todas as notícias">
        Ver todas
        <?php echo procon_icon( 'chevron-right', 16 ); // phpcs:ignore ?>
      </a>
    </div>

	<?php if ( ! $noticias ) : ?>
    <div class="text-center" style="padding: 2rem 0">
      <p class="text-muted">Nenhuma notícia disponível no momento.</p>
    </div>
	<?php else : ?>
    <div class="carousel-wrapper" role="region" aria-label="Carrossel de últimas notícias" aria-roledescription="carrossel">
      <div class="carousel-track-outer">
        <div class="carousel-track" role="list" aria-live="off">
		<?php
		foreach ( $noticias as $noticia ) :
			$titulo = get_the_title( $noticia );
			$link   = get_permalink( $noticia );
			$data   = procon_data( $noticia );
			$cat    = procon_categoria( $noticia );
			?>
          <article class="carousel-card" role="listitem" aria-label="<?php echo esc_attr( 'Notícia: ' . $titulo ); ?>">
            <div class="carousel-card-img-wrapper">
			<?php if ( has_post_thumbnail( $noticia ) ) : ?>
				<?php echo get_the_post_thumbnail( $noticia, 'medium_large', array( 'class' => 'carousel-card-img', 'alt' => 'Imagem da notícia: ' . $titulo, 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
              <div class="carousel-card-img-placeholder" aria-hidden="true"><?php echo procon_icon( 'image', 48 ); // phpcs:ignore ?></div>
			<?php endif; ?>
			<?php if ( $cat ) : ?>
              <span class="carousel-card-category" aria-label="<?php echo esc_attr( 'Categoria: ' . $cat ); ?>"><?php echo esc_html( $cat ); ?></span>
			<?php endif; ?>
            </div>
            <div class="carousel-card-body">
              <p class="carousel-card-date" aria-label="<?php echo esc_attr( 'Publicado em ' . $data ); ?>">
                <?php echo procon_icon( 'calendar' ); // phpcs:ignore ?>
                <?php echo esc_html( $data ); ?>
              </p>
              <h3 class="carousel-card-title"><?php echo esc_html( $titulo ); ?></h3>
              <p class="carousel-card-summary"><?php echo esc_html( get_the_excerpt( $noticia ) ); ?></p>
              <a class="carousel-card-link" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( 'Ler a notícia: ' . $titulo ); ?>">
                Leia mais
                <?php echo procon_icon( 'chevron-right' ); // phpcs:ignore ?>
              </a>
            </div>
          </article>
		<?php endforeach; ?>
        </div>
      </div>

      <div class="carousel-controls" role="group" aria-label="Controles do carrossel">
        <button class="carousel-btn carousel-btn-prev" type="button" aria-label="Notícia anterior"><?php echo procon_icon( 'chevron-left' ); // phpcs:ignore ?></button>
        <ul class="carousel-dots" role="group" aria-label="Indicadores de posição"></ul>
        <button class="carousel-btn carousel-btn-next" type="button" aria-label="Próxima notícia"><?php echo procon_icon( 'chevron-left' ); // phpcs:ignore ?></button>
      </div>

      <div style="text-align:center; margin-top: 0.5rem">
        <button class="carousel-pause-btn" type="button" aria-label="Pausar rotação automática">⏸ Pausar</button>
      </div>
    </div>
	<?php endif; ?>
  </div>
</section>
	<?php
	return ob_get_clean();
} );
