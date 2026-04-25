<?php
/**
 * Template: Dashboard – Formulario de creación/edición de torneo
 * Ruta: /mi-panel/torneos/nuevo/ y /mi-panel/torneos/{slug}/editar/
 *
 * Implementado en Sprint 4.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_url = home_url( '/mi-panel/' );
$es_edicion = ( 'editar' === $accion && $id > 0 );
$torneo_post = null;

if ( $es_edicion ) {
	$torneo_post = get_post( $id );
	if ( ! $torneo_post || 'scl_torneo' !== $torneo_post->post_type || $torneo_post->post_author != get_current_user_id() ) {
		echo '<p>' . esc_html__( 'Torneo no encontrado o no tienes permiso para editarlo.', 'sportcriss-lite' ) . '</p>';
		return;
	}
}

// Variables iniciales
$titulo           = $es_edicion ? $torneo_post->post_title : '';
$puntos_victoria  = $es_edicion ? get_post_meta( $id, 'scl_torneo_puntos_victoria', true ) : 3;
$puntos_empate    = $es_edicion ? get_post_meta( $id, 'scl_torneo_puntos_empate', true ) : 1;
$puntos_derrota   = $es_edicion ? get_post_meta( $id, 'scl_torneo_puntos_derrota', true ) : 0;
$color_primario   = $es_edicion ? get_post_meta( $id, 'scl_torneo_color_primario', true ) : '#1a2b3c';
$color_secundario = $es_edicion ? get_post_meta( $id, 'scl_torneo_color_secundario', true ) : '#ffffff';

?>
<div class="scl-page-header">
	<h1 class="scl-page-title"><?php echo $es_edicion ? esc_html__( 'Editar Torneo', 'sportcriss-lite' ) : esc_html__( 'Nuevo Torneo', 'sportcriss-lite' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'torneos' ], $home_url ) ); ?>" class="scl-btn scl-btn--outline">
		&larr; <?php esc_html_e( 'Volver', 'sportcriss-lite' ); ?>
	</a>
</div>

<form class="scl-form" id="scl-torneo-form" data-accion="<?php echo $es_edicion ? 'scl_editar_torneo' : 'scl_crear_torneo'; ?>">
	<?php if ( $es_edicion ) : ?>
		<input type="hidden" name="torneo_id" value="<?php echo esc_attr( $id ); ?>">
	<?php endif; ?>
	
	<div class="scl-form-group">
		<label for="titulo"><?php esc_html_e( 'Nombre del Torneo', 'sportcriss-lite' ); ?> *</label>
		<input type="text" id="titulo" name="titulo" value="<?php echo esc_attr( $titulo ); ?>" required>
	</div>
	
	<div class="scl-form-group">
		<label for="puntos_victoria"><?php esc_html_e( 'Puntos por victoria', 'sportcriss-lite' ); ?></label>
		<input type="number" id="puntos_victoria" name="puntos_victoria" value="<?php echo esc_attr( $puntos_victoria ); ?>">
	</div>
	
	<div class="scl-form-group">
		<label for="puntos_empate"><?php esc_html_e( 'Puntos por empate', 'sportcriss-lite' ); ?></label>
		<input type="number" id="puntos_empate" name="puntos_empate" value="<?php echo esc_attr( $puntos_empate ); ?>">
	</div>
	
	<div class="scl-form-group">
		<label for="puntos_derrota"><?php esc_html_e( 'Puntos por derrota', 'sportcriss-lite' ); ?></label>
		<input type="number" id="puntos_derrota" name="puntos_derrota" value="<?php echo esc_attr( $puntos_derrota ); ?>">
	</div>
	
	<div class="scl-form-group">
		<label for="color_primario"><?php esc_html_e( 'Color principal', 'sportcriss-lite' ); ?></label>
		<input type="color" id="color_primario" name="color_primario" value="<?php echo esc_attr( $color_primario ); ?>">
	</div>

	<div class="scl-form-group">
		<label for="color_secundario"><?php esc_html_e( 'Color secundario', 'sportcriss-lite' ); ?></label>
		<input type="color" id="color_secundario" name="color_secundario" value="<?php echo esc_attr( $color_secundario ); ?>">
	</div>
	
	<div class="scl-form-actions">
		<button type="submit" class="scl-btn scl-btn--primary">
			<?php esc_html_e( 'Guardar torneo', 'sportcriss-lite' ); ?>
		</button>
	</div>
	
	<div id="scl-form-feedback" style="display:none;" class="scl-alert"></div>
</form>
