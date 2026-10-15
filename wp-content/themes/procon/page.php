<?php
/**
 * Páginas comuns (sem Elementor): breadcrumb + faixa de título + conteúdo.
 * Páginas do Elementor usam o modelo "Elementor Cabeçalho e Rodapé" (ver functions.php).
 */

get_header();

while ( have_posts() ) :
	the_post();
	procon_page_header();
	?>
<section class="section">
  <div class="container">
    <div class="procon-conteudo">
      <?php the_content(); ?>
    </div>
  </div>
</section>
	<?php
endwhile;

get_footer();
