<?php
/**
 * Página não encontrada (mesmo padrão do "não encontrado" da referência).
 */

get_header();
?>
<div class="section text-center" role="alert">
  <div class="container">
    <p style="font-size:4rem" aria-hidden="true">🔍</p>
    <h1 class="text-primary">Página não encontrada</h1>
    <p class="text-muted">A página que você procura não existe ou foi removida.</p>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary" style="margin-top:1rem">← Voltar ao Início</a>
  </div>
</div>
<?php
get_footer();
