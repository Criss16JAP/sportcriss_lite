<?php
/**
 * Template: Dashboard – Lista de temporadas de un torneo
 * Ruta: /mi-panel/torneos/{slug}/temporadas/
 *
 * Implementado en Sprint 4.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_url = home_url( '/mi-panel/' );
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

$temporadas = get_terms([
	'taxonomy' => 'scl_temporada',
	'hide_empty' => false,
	'meta_query' => [
		[
			'key' => 'scl_temporada_torneo_id',
			'value' => $torneo_id
		]
	]
]);
?>

<div class="scl-page-header">
	<h1 class="scl-page-title">
		<?php printf( esc_html__( 'Temporadas: %s', 'sportcriss-lite' ), esc_html( $torneo->post_title ) ); ?>
	</h1>
	<div class="scl-actions-group">
		<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'torneos' ], $home_url ) ); ?>" class="scl-btn scl-btn--outline">
			&larr; <?php esc_html_e( 'Volver a Torneos', 'sportcriss-lite' ); ?>
		</a>
		<?php if ( $licencia_activa ) : ?>
			<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'temporadas', 'scl_accion' => 'nuevo', 'scl_torneo_id' => $torneo_id ], $home_url ) ); ?>" class="scl-btn scl-btn--primary">
				+ <?php esc_html_e( 'Crear temporada', 'sportcriss-lite' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>

<?php if ( ! is_wp_error( $temporadas ) && ! empty( $temporadas ) ) : ?>
	<div class="scl-table-responsive">
		<table class="scl-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Nombre', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Año', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Estado', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Acciones', 'sportcriss-lite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $temporadas as $t ) : 
					$anio = get_term_meta( $t->term_id, 'scl_temporada_anio', true );
					$estado = get_term_meta( $t->term_id, 'scl_temporada_estado', true );
				?>
					<tr>
						<td><strong><?php echo esc_html( $t->name ); ?></strong></td>
						<td><?php echo esc_html( $anio ); ?></td>
						<td>
							<span class="scl-badge scl-badge--<?php echo esc_attr( $estado ); ?>">
								<?php echo esc_html( ucfirst( $estado ) ); ?>
							</span>
						</td>
						<td>
							<div class="scl-actions-group">
								<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'partidos', 'scl_torneo_id' => $torneo_id, 'scl_temporada_term_id' => $t->term_id ], $home_url ) ); ?>" class="scl-btn scl-btn--sm scl-btn--secondary">
									<?php esc_html_e( 'Partidos', 'sportcriss-lite' ); ?>
								</a>
								<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'llaves', 'scl_torneo_id' => $torneo_id, 'scl_temporada_term_id' => $t->term_id ], $home_url ) ); ?>" class="scl-btn scl-btn--sm scl-btn--secondary">
									<?php esc_html_e( 'Llaves', 'sportcriss-lite' ); ?>
								</a>
								<?php if ( $licencia_activa ) : ?>
								<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'temporadas', 'scl_accion' => 'editar', 'scl_id' => $t->term_id, 'scl_torneo_id' => $torneo_id ], $home_url ) ); ?>" class="scl-btn scl-btn--sm scl-btn--outline">
									<?php esc_html_e( 'Editar', 'sportcriss-lite' ); ?>
								</a>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="scl-empty-state">
		<p><?php esc_html_e( 'No hay temporadas creadas en este torneo.', 'sportcriss-lite' ); ?></p>
	</div>
<?php endif; ?>
