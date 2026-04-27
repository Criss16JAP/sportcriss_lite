<?php
/**
 * Template: Pantalla de acceso restringido para colaboradores.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="scl-empty" style="max-width: 480px; margin: 4rem auto;">
	<p style="font-size: 2.5rem; margin-bottom: 0.5rem;">&#128274;</p>
	<h3 style="margin: 0 0 0.75rem; color: var(--scl-primary);">
		<?php esc_html_e( 'Acceso restringido', 'sportcriss-lite' ); ?>
	</h3>
	<p style="color: #666; margin-bottom: 1.5rem;">
		<?php esc_html_e( 'Tu cuenta de colaborador solo tiene acceso a la consola de resultados.', 'sportcriss-lite' ); ?>
	</p>
	<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'partidos', home_url( '/mi-panel/' ) ) ); ?>"
	   class="scl-btn scl-btn--primary">
		<?php esc_html_e( 'Ir a resultados', 'sportcriss-lite' ); ?>
	</a>
</div>
