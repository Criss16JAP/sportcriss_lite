<?php
/**
 * Template: Home del Dashboard
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$autor_ef = scl_get_autor_efectivo();

$torneos_activos = new WP_Query( [
	'post_type'      => 'scl_torneo',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
] );

$partidos_pendientes = new WP_Query( [
	'post_type'      => 'scl_partido',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'meta_query'     => [ [ 'key' => 'scl_partido_estado', 'value' => 'pendiente' ] ],
	'posts_per_page' => -1,
] );

// Temporadas activas
$temporadas_activas = get_terms( [
	'taxonomy'   => 'scl_temporada',
	'hide_empty' => false,
	'meta_query' => [
		[
			'key'   => 'scl_temporada_estado',
			'value' => 'activa'
		]
	]
] );
$num_temporadas_activas = is_wp_error( $temporadas_activas ) ? 0 : count( $temporadas_activas );

$home_url = home_url( '/mi-panel/' );
?>

<div class="scl-dashboard-home">
	<h1 class="scl-page-title"><?php esc_html_e( 'Resumen', 'sportcriss-lite' ); ?></h1>

	<div class="scl-cards-row">
		<div class="scl-card">
			<div class="scl-card__value"><?php echo esc_html( $torneos_activos->found_posts ); ?></div>
			<div class="scl-card__label"><?php esc_html_e( 'Torneos activos', 'sportcriss-lite' ); ?></div>
		</div>
		<div class="scl-card">
			<div class="scl-card__value"><?php echo esc_html( $partidos_pendientes->found_posts ); ?></div>
			<div class="scl-card__label"><?php esc_html_e( 'Partidos pendientes', 'sportcriss-lite' ); ?></div>
		</div>
		<div class="scl-card">
			<div class="scl-card__value"><?php echo esc_html( $num_temporadas_activas ); ?></div>
			<div class="scl-card__label"><?php esc_html_e( 'Temporadas activas', 'sportcriss-lite' ); ?></div>
		</div>
	</div>

	<div class="scl-quick-actions">
		<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'torneos', 'scl_accion' => 'nuevo' ], $home_url ) ); ?>" class="scl-btn scl-btn--primary">
			+ <?php esc_html_e( 'Crear torneo', 'sportcriss-lite' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'equipos', 'scl_accion' => 'nuevo' ], $home_url ) ); ?>" class="scl-btn scl-btn--secondary">
			+ <?php esc_html_e( 'Crear equipo', 'sportcriss-lite' ); ?>
		</a>
		<?php if ( ! scl_es_colaborador() ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'importar', $home_url ) ); ?>" class="scl-btn scl-btn--outline">
			↑ <?php esc_html_e( 'Importar CSV', 'sportcriss-lite' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'exportar', $home_url ) ); ?>" class="scl-btn scl-btn--outline">
			↗ <?php esc_html_e( 'Exportar tabla', 'sportcriss-lite' ); ?>
		</a>
		<?php endif; ?>
	</div>

	<h2 class="scl-section-title"><?php esc_html_e( 'Mis Torneos Activos', 'sportcriss-lite' ); ?></h2>
	
	<?php if ( $torneos_activos->have_posts() ) : ?>
		<div class="scl-torneos-list">
			<?php while ( $torneos_activos->have_posts() ) : $torneos_activos->the_post(); ?>
				<div class="scl-torneo-item">
					<h3><?php the_title(); ?></h3>
					<div class="scl-torneo-item__actions">
						<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'partidos', 'scl_torneo_id' => get_the_ID() ], $home_url ) ); ?>" class="scl-btn scl-btn--outline">
							<?php esc_html_e( 'Ver partidos', 'sportcriss-lite' ); ?>
						</a>
						<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'exportar', 'scl_id' => get_the_ID() ], $home_url ) ); ?>" class="scl-btn scl-btn--outline">
							<?php esc_html_e( 'Exportar tabla', 'sportcriss-lite' ); ?>
						</a>
					</div>
				</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No tienes torneos activos.', 'sportcriss-lite' ); ?></p>
	<?php endif; ?>
</div>
