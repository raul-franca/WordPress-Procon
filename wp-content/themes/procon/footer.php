<?php
/**
 * Rodapé: logo, contato (Personalizar > PROCON) e redes sociais.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$procon_endereco = get_theme_mod( 'procon_endereco' );
$procon_telefone = get_theme_mod( 'procon_telefone' );
$procon_email    = get_theme_mod( 'procon_email' );
?>
<footer class="site-footer footer-full-width">
	<div class="container site-footer__inner">
		<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo esc_url( procon_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="173" height="64" loading="lazy">
		</a>

		<ul class="site-footer__contact">
			<?php if ( $procon_endereco ) : ?>
				<li><?php echo procon_icon( 'location' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( $procon_endereco ) ); ?></span></li>
			<?php endif; ?>
			<?php if ( $procon_telefone ) : ?>
				<li><?php echo procon_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $procon_telefone ) ); ?>"><?php echo esc_html( $procon_telefone ); ?></a></li>
			<?php endif; ?>
			<?php if ( $procon_email ) : ?>
				<li><?php echo procon_icon( 'email' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( $procon_email ); ?>"><?php echo esc_html( $procon_email ); ?></a></li>
			<?php endif; ?>
		</ul>

		<ul class="site-footer__social">
			<?php
			foreach ( procon_social_networks() as $slug => $label ) :
				$url = get_theme_mod( 'procon_social_' . str_replace( '-', '_', $slug ) );
				if ( ! $url ) {
					continue;
				}
				?>
				<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $label ); ?>"><?php echo procon_icon( $slug ); // phpcs:ignore ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<div class="site-footer__bottom">
		<div class="container">© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?> — Secretaria de Justiça, Direitos Humanos e Prevenção à Violência de Pernambuco</div>
	</div>
</footer>

<?php if ( get_theme_mod( 'procon_vlibras', true ) ) : ?>
<div vw class="enabled">
	<div vw-access-button class="active"></div>
	<div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
