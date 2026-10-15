<?php
/**
 * Rodapé: faixa de ajuda, identidade + contatos, colunas de links (menus) e rodapé inferior.
 * Markup idêntico ao index.html da página de referência; dados em Personalizar > PROCON.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$procon_telefone = get_theme_mod( 'procon_telefone' );
$procon_pabx     = get_theme_mod( 'procon_pabx' );
$procon_pabx_tel = get_theme_mod( 'procon_pabx_tel', $procon_pabx );
$procon_whatsapp = get_theme_mod( 'procon_whatsapp' );
$procon_email    = get_theme_mod( 'procon_email' );
$procon_cgov     = get_theme_mod( 'procon_consumidor_gov' );
$procon_endereco = get_theme_mod( 'procon_endereco' );

/**
 * Coluna de links do rodapé a partir de uma posição de menu.
 */
function procon_footer_coluna( $location, $titulo, $aria, $classe = 'footer-links' ) {
	if ( ! has_nav_menu( $location ) ) {
		return;
	}
	echo '<nav aria-label="' . esc_attr( $aria ) . '">';
	echo '<h2 class="footer-col-title">' . esc_html( $titulo ) . '</h2>';
	wp_nav_menu( array(
		'theme_location' => $location,
		'container'      => false,
		'menu_class'     => $classe,
		'depth'          => 1,
	) );
	echo '</nav>';
}
?>
</main>

<footer class="site-footer" role="contentinfo">
  <?php if ( $procon_telefone || $procon_whatsapp || $procon_email || $procon_cgov ) : ?>
  <div class="footer-top-bar">
    <div class="container">
      <p>📞 <strong>Precisa de ajuda?</strong> Ligue grátis ou nos escreva:</p>
      <div class="footer-top-bar-links">
        <?php if ( $procon_telefone ) : ?>
          <a href="<?php echo esc_attr( procon_tel( $procon_telefone ) ); ?>" aria-label="<?php echo esc_attr( 'Ligar ' . $procon_telefone ); ?>">📞 <?php echo esc_html( $procon_telefone ); ?></a>
        <?php endif; ?>
        <?php if ( $procon_whatsapp ) : ?>
          <a href="<?php echo esc_url( $procon_whatsapp ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp">💬 WhatsApp</a>
        <?php endif; ?>
        <?php if ( $procon_email ) : ?>
          <a href="mailto:<?php echo esc_attr( $procon_email ); ?>" aria-label="E-mail do PROCON PE">✉ E-mail</a>
        <?php endif; ?>
        <?php if ( $procon_cgov ) : ?>
          <a href="<?php echo esc_url( $procon_cgov ); ?>" target="_blank" rel="noopener" aria-label="Consumidor.gov.br">🌐 Consumidor.gov.br</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="footer-main">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a class="footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="PROCON Pernambuco">
            <div class="footer-logo-brasao" aria-hidden="true">
              <img src="<?php echo esc_url( procon_logo_url() ); ?>" alt="" class="brasao-img-footer" onerror="this.parentElement.style.display='none'">
            </div>
            <span class="footer-logo-text">PROCON Pernambuco</span>
          </a>

          <?php if ( get_theme_mod( 'procon_descricao' ) ) : ?>
            <p><?php echo esc_html( get_theme_mod( 'procon_descricao' ) ); ?></p>
          <?php endif; ?>

          <?php if ( $procon_endereco ) : ?>
          <div class="footer-contact-item">
            <?php echo procon_icon( 'location' ); // phpcs:ignore ?>
            <span><?php echo esc_html( $procon_endereco ); ?></span>
          </div>
          <?php endif; ?>

          <?php if ( $procon_telefone || $procon_pabx ) : ?>
          <div class="footer-contact-item">
            <?php echo procon_icon( 'phone' ); // phpcs:ignore ?>
            <span>
              <?php if ( $procon_telefone ) : ?><a href="<?php echo esc_attr( procon_tel( $procon_telefone ) ); ?>"><?php echo esc_html( $procon_telefone ); ?></a><?php endif; ?>
              <?php if ( $procon_telefone && $procon_pabx ) : ?> | <?php endif; ?>
              <?php if ( $procon_pabx ) : ?><a href="<?php echo esc_attr( procon_tel( $procon_pabx_tel ) ); ?>">PABX: <?php echo esc_html( $procon_pabx ); ?></a><?php endif; ?>
            </span>
          </div>
          <?php endif; ?>

          <?php if ( $procon_email ) : ?>
          <div class="footer-contact-item">
            <?php echo procon_icon( 'email' ); // phpcs:ignore ?>
            <a href="mailto:<?php echo esc_attr( $procon_email ); ?>"><?php echo esc_html( $procon_email ); ?></a>
          </div>
          <?php endif; ?>

          <div class="footer-social" aria-label="Redes sociais do PROCON PE">
            <?php
            foreach ( procon_social_networks() as $slug => $label ) :
                $url = procon_social_url( $slug );
                if ( ! $url ) {
                    continue;
                }
                ?>
              <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="footer-social-btn" aria-label="<?php echo esc_attr( $label . ' do PROCON PE' ); ?>" title="<?php echo esc_attr( $label ); ?>">
                <?php echo procon_icon( $slug, 'x-twitter' === $slug ? 14 : 16 ); // phpcs:ignore ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php
        procon_footer_coluna( 'procon-rodape-servicos', 'Serviços', 'Links de serviços' );
        procon_footer_coluna( 'procon-rodape-inst', 'Inf. Institucionais', 'Informações Institucionais', 'footer-links footer-links-dense' );
        procon_footer_coluna( 'procon-rodape-orgaos', 'Órgãos Parceiros', 'Links de órgãos parceiros' );
        ?>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <p class="footer-copyright">
        © <?php echo esc_html( wp_date( 'Y' ) ); ?> PROCON Pernambuco — Secretaria de Justiça, Direitos Humanos e Prevenção à Violência (SJDH/PE).
        Todos os direitos reservados.
      </p>
      <?php
      wp_nav_menu( array(
          'theme_location' => 'procon-rodape-base',
          'container'      => false,
          'menu_class'     => 'footer-bottom-links',
          'depth'          => 1,
          'fallback_cb'    => false,
      ) );
      ?>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
