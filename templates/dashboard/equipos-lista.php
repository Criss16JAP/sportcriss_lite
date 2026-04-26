<?php
/**
 * Template: Dashboard – Lista de equipos del organizador
 * Ruta: /mi-panel/?scl_ruta=equipos
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_url = home_url( '/mi-panel/' );
$filtro   = sanitize_key( $_GET['filtro'] ?? 'todos' );

$args = [
	'post_type'      => 'scl_equipo',
	'author'         => get_current_user_id(),
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
];

if ( 'incompletos' === $filtro ) {
	$args['meta_query'] = [ [ 'key' => 'scl_equipo_incompleto', 'value' => '1' ] ];
} elseif ( 'completos' === $filtro ) {
	$args['meta_query'] = [ [ 'key' => 'scl_equipo_incompleto', 'value' => '0' ] ];
}

$equipos = get_posts( $args );

// Contar incompletos siempre, para el banner, independiente del filtro activo
$num_incompletos = count( get_posts( [
	'post_type'      => 'scl_equipo',
	'author'         => get_current_user_id(),
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => 'scl_equipo_incompleto', 'value' => '1' ] ],
] ) );

// $accion está disponible desde el scope de dispatch() en Scl_Dashboard
$auto_nuevo = isset( $accion ) && 'nuevo' === $accion;
?>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php esc_html_e( 'Mis Equipos', 'sportcriss-lite' ); ?></h1>
	<button type="button" class="scl-btn scl-btn--primary" id="scl_nuevo_equipo_btn">
		+ <?php esc_html_e( 'Nuevo equipo', 'sportcriss-lite' ); ?>
	</button>
</div>

<?php if ( $num_incompletos > 0 ) : ?>
<div class="scl-alert scl-alert--warning">
	⚠ <?php
	printf(
		esc_html( _n(
			'%d equipo creado por el importador necesita ser completado.',
			'%d equipos creados por el importador necesitan ser completados.',
			$num_incompletos,
			'sportcriss-lite'
		) ),
		$num_incompletos
	);
	?>
</div>
<?php endif; ?>

<div class="scl-search-bar">
	<input type="text" id="scl_buscar_equipo"
	       placeholder="<?php esc_attr_e( 'Buscar equipo...', 'sportcriss-lite' ); ?>">
	<select id="scl_filtro_equipos">
		<option value="todos"       <?php selected( $filtro, 'todos' ); ?>>
			<?php esc_html_e( 'Todos', 'sportcriss-lite' ); ?>
		</option>
		<option value="completos"   <?php selected( $filtro, 'completos' ); ?>>
			<?php esc_html_e( 'Completos', 'sportcriss-lite' ); ?>
		</option>
		<option value="incompletos" <?php selected( $filtro, 'incompletos' ); ?>>
			<?php esc_html_e( 'Incompletos', 'sportcriss-lite' ); ?>
		</option>
	</select>
</div>

<?php if ( ! empty( $equipos ) ) : ?>
	<div class="scl-equipos-list">
		<?php foreach ( $equipos as $equipo ) :
			$escudo_id  = absint( get_post_meta( $equipo->ID, 'scl_equipo_escudo', true ) );
			$zona       = (string) get_post_meta( $equipo->ID, 'scl_equipo_zona', true );
			$incompleto = get_post_meta( $equipo->ID, 'scl_equipo_incompleto', true ) === '1';
			$escudo_url = $escudo_id ? (string) wp_get_attachment_url( $escudo_id ) : '';
			$escudo_img = $escudo_id ? wp_get_attachment_image( $escudo_id, [ 60, 60 ] ) : '';
			$inicial    = mb_strtoupper( mb_substr( $equipo->post_title, 0, 1 ) );

			// Torneos únicos donde este equipo aparece en partidos
			$partido_ids = get_posts( [
				'post_type'      => 'scl_partido',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					'relation' => 'OR',
					[ 'key' => 'scl_partido_equipo_local_id',  'value' => $equipo->ID, 'compare' => '=' ],
					[ 'key' => 'scl_partido_equipo_visita_id', 'value' => $equipo->ID, 'compare' => '=' ],
				],
			] );
			$torneo_ids  = array_unique( array_filter( array_map(
				fn( $pid ) => absint( get_post_meta( $pid, 'scl_partido_torneo_id', true ) ),
				$partido_ids
			) ) );
			$num_torneos = count( $torneo_ids );
		?>
		<div class="scl-equipo-card"
		     id="scl-equipo-<?php echo esc_attr( $equipo->ID ); ?>"
		     data-nombre="<?php echo esc_attr( mb_strtolower( $equipo->post_title ) ); ?>">

			<div class="scl-equipo-card__escudo">
				<?php if ( $escudo_img ) : ?>
					<?php echo $escudo_img; // wp_get_attachment_image output is safe ?>
				<?php else : ?>
					<?php echo esc_html( $inicial ); ?>
				<?php endif; ?>
			</div>

			<div class="scl-equipo-card__info">
				<div class="scl-equipo-card__nombre">
					<?php echo esc_html( $equipo->post_title ); ?>
					<?php if ( $incompleto ) : ?>
						<span class="scl-badge scl-badge--warning">
							⚠ <?php esc_html_e( 'Incompleto', 'sportcriss-lite' ); ?>
						</span>
					<?php endif; ?>
				</div>
				<div class="scl-equipo-card__meta">
					<?php if ( $zona ) : ?>
						<?php esc_html_e( 'Zona:', 'sportcriss-lite' ); ?> <?php echo esc_html( $zona ); ?> &nbsp;&middot;&nbsp;
					<?php else : ?>
						<em><?php esc_html_e( 'Sin zona', 'sportcriss-lite' ); ?></em> &nbsp;&middot;&nbsp;
					<?php endif; ?>
					<?php if ( $incompleto ) : ?>
						<?php esc_html_e( 'Creado por importador', 'sportcriss-lite' ); ?>
					<?php else : ?>
						<?php printf(
							esc_html( _n(
								'Usado en %d torneo',
								'Usado en %d torneos',
								$num_torneos,
								'sportcriss-lite'
							) ),
							$num_torneos
						); ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="scl-equipo-card__actions">
				<button type="button"
				        class="scl-btn scl-btn--outline scl-btn--sm scl-equipo-editar-btn"
				        data-id="<?php echo esc_attr( $equipo->ID ); ?>"
				        data-nombre="<?php echo esc_attr( $equipo->post_title ); ?>"
				        data-zona="<?php echo esc_attr( $zona ); ?>"
				        data-escudo-url="<?php echo esc_attr( $escudo_url ); ?>"
				        data-escudo-id="<?php echo esc_attr( $escudo_id ); ?>">
					<?php echo $incompleto
						? esc_html__( 'Completar', 'sportcriss-lite' )
						: esc_html__( 'Editar', 'sportcriss-lite' ); ?>
				</button>
				<button type="button"
				        class="scl-btn scl-btn--danger scl-btn--sm scl-equipo-eliminar-btn"
				        data-id="<?php echo esc_attr( $equipo->ID ); ?>"
				        data-nombre="<?php echo esc_attr( $equipo->post_title ); ?>">
					<?php esc_html_e( 'Eliminar', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<div class="scl-empty" id="scl-empty-equipos">
		<p><?php esc_html_e( 'Aún no tienes equipos registrados.', 'sportcriss-lite' ); ?></p>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_nuevo_equipo_btn_empty">
			<?php esc_html_e( 'Registrar primer equipo', 'sportcriss-lite' ); ?>
		</button>
	</div>
<?php endif; ?>

<?php include SCL_PATH . 'templates/dashboard/equipos-form.php'; ?>

<?php if ( $auto_nuevo ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
	if (typeof scl_equipo_nuevo === 'function') { scl_equipo_nuevo(); }
});
</script>
<?php endif; ?>
