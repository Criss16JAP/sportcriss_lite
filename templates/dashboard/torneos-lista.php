<?php
/**
 * Template: Dashboard – Lista de torneos del Organizador
 * Ruta: /mi-panel/torneos/
 *
 * Implementado en Sprint 4.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_url = home_url( '/mi-panel/' );
$torneos = new WP_Query([
    'post_type' => 'scl_torneo',
    'author' => get_current_user_id(),
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);
?>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php esc_html_e( 'Mis Torneos', 'sportcriss-lite' ); ?></h1>
	<?php if ( $licencia_activa ) : ?>
		<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'torneos', 'scl_accion' => 'nuevo' ], $home_url ) ); ?>" class="scl-btn scl-btn--primary">
			+ <?php esc_html_e( 'Crear torneo', 'sportcriss-lite' ); ?>
		</a>
	<?php endif; ?>
</div>

<?php if ( $torneos->have_posts() ) : ?>
	<div class="scl-table-responsive">
		<table class="scl-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Nombre', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Temporadas', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Acciones', 'sportcriss-lite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php while ( $torneos->have_posts() ) : $torneos->the_post(); 
					$logo_id = get_post_meta( get_the_ID(), 'scl_torneo_logo', true );
					$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
					
					$temporadas = get_terms([
						'taxonomy' => 'scl_temporada',
						'hide_empty' => false,
						'meta_query' => [
							[
								'key' => 'scl_temporada_torneo_id',
								'value' => get_the_ID()
							]
						]
					]);
					$num_temporadas = is_wp_error($temporadas) ? 0 : count($temporadas);
				?>
					<tr>
						<td>
							<div class="scl-torneo-name-cell">
								<?php if ( $logo_url ) : ?>
									<img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" class="scl-avatar">
								<?php endif; ?>
								<strong><?php the_title(); ?></strong>
							</div>
						</td>
						<td><?php echo esc_html( $num_temporadas ); ?></td>
						<td>
							<div class="scl-actions-group">
								<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'temporadas', 'scl_torneo_id' => get_the_ID() ], $home_url ) ); ?>" class="scl-btn scl-btn--sm scl-btn--secondary">
									<?php esc_html_e( 'Temporadas', 'sportcriss-lite' ); ?>
								</a>
								<?php if ( $licencia_activa ) : ?>
								<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'torneos', 'scl_accion' => 'editar', 'scl_id' => get_the_ID() ], $home_url ) ); ?>" class="scl-btn scl-btn--sm scl-btn--outline">
									<?php esc_html_e( 'Editar', 'sportcriss-lite' ); ?>
								</a>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				<?php endwhile; wp_reset_postdata(); ?>
			</tbody>
		</table>
	</div>
<?php else : ?>
	<div class="scl-empty-state">
		<p><?php esc_html_e( 'No has creado ningún torneo aún.', 'sportcriss-lite' ); ?></p>
	</div>
<?php endif; ?>
