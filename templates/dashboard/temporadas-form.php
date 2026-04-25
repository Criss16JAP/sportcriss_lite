<?php
/**
 * Template: Dashboard – Formulario de creación/edición de temporada
 * Ruta: /mi-panel/torneos/{slug}/temporadas/nueva/
 *
 * Implementado en Sprint 4.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_url = home_url( '/mi-panel/' );
$es_edicion = ( 'editar' === $accion && $id > 0 );
$torneo_id = isset( $_GET['scl_torneo_id'] ) ? absint( $_GET['scl_torneo_id'] ) : 0;

if ( ! $torneo_id ) {
	echo '<p>Torneo no especificado.</p>';
	return;
}

$torneo = get_post( $torneo_id );
if ( ! $torneo || 'scl_torneo' !== $torneo->post_type || $torneo->post_author != get_current_user_id() ) {
	echo '<p>Torneo no encontrado o sin permisos.</p>';
	return;
}

$term = null;
if ( $es_edicion ) {
	$term = get_term( $id, 'scl_temporada' );
	if ( ! $term || is_wp_error( $term ) ) {
		echo '<p>Temporada no encontrada.</p>';
		return;
	}
	// Verificar pertenencia al torneo
	$term_torneo_id = (int) get_term_meta( $id, 'scl_temporada_torneo_id', true );
	if ( $term_torneo_id !== $torneo_id ) {
		echo '<p>Temporada no pertenece a este torneo.</p>';
		return;
	}
}

$titulo = $es_edicion ? $term->name : '';
$estado = $es_edicion ? get_term_meta( $id, 'scl_temporada_estado', true ) : 'activa';
$anio   = $es_edicion ? get_term_meta( $id, 'scl_temporada_anio', true ) : date('Y');

$redirect_url = add_query_arg( [ 'scl_ruta' => 'temporadas', 'scl_torneo_id' => $torneo_id ], '' );
?>
<div class="scl-page-header">
	<h1 class="scl-page-title"><?php echo $es_edicion ? esc_html__( 'Editar Temporada', 'sportcriss-lite' ) : esc_html__( 'Nueva Temporada', 'sportcriss-lite' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'temporadas', 'scl_torneo_id' => $torneo_id ], $home_url ) ); ?>" class="scl-btn scl-btn--outline">
		&larr; <?php esc_html_e( 'Volver', 'sportcriss-lite' ); ?>
	</a>
</div>

<form class="scl-form" id="scl-temporada-form" data-accion="<?php echo $es_edicion ? 'scl_editar_temporada' : 'scl_crear_temporada'; ?>" data-redirect="<?php echo esc_attr( $redirect_url ); ?>">
	<input type="hidden" name="torneo_id" value="<?php echo esc_attr( $torneo_id ); ?>">
	<?php if ( $es_edicion ) : ?>
		<input type="hidden" name="temporada_id" value="<?php echo esc_attr( $id ); ?>">
	<?php endif; ?>
	
	<div class="scl-form-group">
		<label for="titulo"><?php esc_html_e( 'Nombre de la Temporada', 'sportcriss-lite' ); ?> *</label>
		<input type="text" id="titulo" name="titulo" value="<?php echo esc_attr( $titulo ); ?>" required>
	</div>
	
	<div class="scl-form-group">
		<label for="estado"><?php esc_html_e( 'Estado', 'sportcriss-lite' ); ?></label>
		<select id="estado" name="estado">
			<option value="activa" <?php selected( $estado, 'activa' ); ?>><?php esc_html_e( 'Activa', 'sportcriss-lite' ); ?></option>
			<option value="finalizada" <?php selected( $estado, 'finalizada' ); ?>><?php esc_html_e( 'Finalizada', 'sportcriss-lite' ); ?></option>
		</select>
	</div>

	<div class="scl-form-group">
		<label for="anio"><?php esc_html_e( 'Año', 'sportcriss-lite' ); ?></label>
		<input type="number" id="anio" name="anio" value="<?php echo esc_attr( $anio ); ?>">
	</div>
	
	<div class="scl-form-actions">
		<button type="submit" class="scl-btn scl-btn--primary">
			<?php esc_html_e( 'Guardar temporada', 'sportcriss-lite' ); ?>
		</button>
	</div>
	
	<div id="scl-form-feedback" style="display:none;" class="scl-alert"></div>
</form>
