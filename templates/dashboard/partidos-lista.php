<?php
/**
 * Template: Dashboard – Consola de partidos
 * Ruta: /mi-panel/?scl_ruta=partidos
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_url = home_url( '/mi-panel/' );

// ── Filtros desde GET ──────────────────────────────────────────────────────
$torneo_filtro    = absint( $_GET['torneo_id']    ?? 0 );
$temporada_filtro = absint( $_GET['temporada_id'] ?? 0 );
$fase_filtro      = sanitize_key( $_GET['tipo_fase'] ?? '' );
$estado_filtro    = sanitize_key( $_GET['estado']    ?? '' );

// ── Torneos del organizador (para select de filtro y drawer) ───────────────
$mis_torneos = get_posts( [
	'post_type'      => 'scl_torneo',
	'author'         => get_current_user_id(),
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

$mis_torneo_ids = wp_list_pluck( $mis_torneos, 'ID' );

// ── Temporadas y jornadas globales (para selects) ──────────────────────────
$temporadas_all = get_terms( [
	'taxonomy'   => 'scl_temporada',
	'hide_empty' => false,
	'orderby'    => 'name',
] );
$temporadas_all = is_wp_error( $temporadas_all ) ? [] : $temporadas_all;

$jornadas_all = get_terms( [
	'taxonomy'   => 'scl_jornada',
	'hide_empty' => false,
	'orderby'    => 'name',
] );
$jornadas_all = is_wp_error( $jornadas_all ) ? [] : $jornadas_all;

// ── Equipos del organizador (para drawer) ─────────────────────────────────
$mis_equipos = get_posts( [
	'post_type'      => 'scl_equipo',
	'author'         => get_current_user_id(),
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

// ── Query principal de partidos ────────────────────────────────────────────
$partidos = [];
if ( ! empty( $mis_torneo_ids ) || $torneo_filtro ) {
	$meta_query = [
		[
			'key'     => 'scl_partido_torneo_id',
			'value'   => $torneo_filtro ?: $mis_torneo_ids,
			'compare' => $torneo_filtro ? '=' : 'IN',
			'type'    => 'NUMERIC',
		],
	];
	if ( $fase_filtro ) {
		$meta_query[] = [ 'key' => 'scl_partido_tipo_fase', 'value' => $fase_filtro ];
	}
	if ( $estado_filtro ) {
		$meta_query[] = [ 'key' => 'scl_partido_estado', 'value' => $estado_filtro ];
	}

	$tax_query = [];
	if ( $temporada_filtro ) {
		$tax_query[] = [
			'taxonomy' => 'scl_temporada',
			'field'    => 'term_id',
			'terms'    => $temporada_filtro,
		];
	}

	$partidos = get_posts( [
		'post_type'      => 'scl_partido',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => $meta_query,
		'tax_query'      => $tax_query,
		'orderby'        => 'meta_value',
		'meta_key'       => 'scl_partido_fecha',
		'order'          => 'ASC',
	] );
}

// ── Agrupar: tipo_fase → jornada → partidos ────────────────────────────────
$grupos_render = [];
foreach ( $partidos as $partido ) {
	$tipo    = get_post_meta( $partido->ID, 'scl_partido_tipo_fase', true ) ?: 'grupos';
	$jors    = wp_get_post_terms( $partido->ID, 'scl_jornada' );
	$jornada = ( ! is_wp_error( $jors ) && ! empty( $jors ) ) ? $jors[0]->name : 'Sin jornada';
	$grupos_render[ $tipo ][ $jornada ][] = $partido;
}

// ── Datos para el drawer (JSON inline para JS) ─────────────────────────────
$datos_drawer = [
	'torneos'    => array_map( fn( $t ) => [
		'ID'    => $t->ID,
		'title' => $t->post_title,
	], $mis_torneos ),
	'temporadas' => array_map( fn( $t ) => [
		'term_id' => $t->term_id,
		'name'    => $t->name,
	], $temporadas_all ),
	'jornadas'   => array_map( fn( $t ) => $t->name, $jornadas_all ),
	'equipos'    => array_map( fn( $e ) => [
		'ID'         => $e->ID,
		'nombre'     => $e->post_title,
		'escudo_url' => (string) ( wp_get_attachment_image_url(
			absint( get_post_meta( $e->ID, 'scl_equipo_escudo', true ) ),
			'thumbnail'
		) ?: '' ),
	], $mis_equipos ),
];
?>

<script>var scl_datos_drawer = <?php echo wp_json_encode( $datos_drawer ); ?>;</script>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php esc_html_e( 'Partidos', 'sportcriss-lite' ); ?></h1>
	<button type="button" class="scl-btn scl-btn--primary" id="scl_nuevo_partido_btn">
		+ <?php esc_html_e( 'Nuevo partido', 'sportcriss-lite' ); ?>
	</button>
</div>

<!-- Filtros -->
<div class="scl-filtros">
	<select id="scl_filtro_torneo" data-param="torneo_id">
		<option value="0"><?php esc_html_e( 'Todos los torneos', 'sportcriss-lite' ); ?></option>
		<?php foreach ( $mis_torneos as $t ) : ?>
			<option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $torneo_filtro, $t->ID ); ?>>
				<?php echo esc_html( $t->post_title ); ?>
			</option>
		<?php endforeach; ?>
	</select>

	<select id="scl_filtro_temporada" data-param="temporada_id">
		<option value="0"><?php esc_html_e( 'Todas las temporadas', 'sportcriss-lite' ); ?></option>
		<?php foreach ( $temporadas_all as $t ) : ?>
			<option value="<?php echo esc_attr( $t->term_id ); ?>" <?php selected( $temporada_filtro, $t->term_id ); ?>>
				<?php echo esc_html( $t->name ); ?>
			</option>
		<?php endforeach; ?>
	</select>

	<select id="scl_filtro_fase" data-param="tipo_fase">
		<option value=""><?php esc_html_e( 'Todas las fases', 'sportcriss-lite' ); ?></option>
		<option value="grupos"  <?php selected( $fase_filtro, 'grupos' ); ?>><?php esc_html_e( 'Grupos', 'sportcriss-lite' ); ?></option>
		<option value="playoff" <?php selected( $fase_filtro, 'playoff' ); ?>><?php esc_html_e( 'Playoff', 'sportcriss-lite' ); ?></option>
	</select>

	<select id="scl_filtro_estado" data-param="estado">
		<option value=""><?php esc_html_e( 'Todos los estados', 'sportcriss-lite' ); ?></option>
		<option value="pendiente"  <?php selected( $estado_filtro, 'pendiente' ); ?>><?php esc_html_e( 'Pendiente', 'sportcriss-lite' ); ?></option>
		<option value="finalizado" <?php selected( $estado_filtro, 'finalizado' ); ?>><?php esc_html_e( 'Finalizado', 'sportcriss-lite' ); ?></option>
	</select>
</div>

<?php if ( ! empty( $partidos ) ) :
	$orden_fases = [ 'grupos' => __( 'Fase de Grupos', 'sportcriss-lite' ), 'playoff' => __( 'Playoff', 'sportcriss-lite' ) ];
	foreach ( $orden_fases as $tipo_key => $tipo_label ) :
		if ( empty( $grupos_render[ $tipo_key ] ) ) continue;
		foreach ( $grupos_render[ $tipo_key ] as $jornada_nombre => $partidos_grupo ) :
?>
	<div class="scl-fase-grupo">
		<div class="scl-fase-grupo__titulo">
			<?php echo esc_html( $tipo_label ); ?>
			<?php if ( $jornada_nombre !== 'Sin jornada' ) : ?>
				&nbsp;&middot;&nbsp; <?php echo esc_html( $jornada_nombre ); ?>
			<?php endif; ?>
		</div>

		<?php foreach ( $partidos_grupo as $partido ) :
			$local_id    = absint( get_post_meta( $partido->ID, 'scl_partido_equipo_local_id',  true ) );
			$visita_id   = absint( get_post_meta( $partido->ID, 'scl_partido_equipo_visita_id', true ) );
			$goles_local = get_post_meta( $partido->ID, 'scl_partido_goles_local',  true );
			$goles_vis   = get_post_meta( $partido->ID, 'scl_partido_goles_visita', true );
			$estado      = get_post_meta( $partido->ID, 'scl_partido_estado',       true ) ?: 'pendiente';
			$tipo_fase   = get_post_meta( $partido->ID, 'scl_partido_tipo_fase',    true ) ?: 'grupos';
			$fecha_raw   = get_post_meta( $partido->ID, 'scl_partido_fecha',        true );
			$grupo_id    = absint( get_post_meta( $partido->ID, 'scl_partido_grupo_id',  true ) );
			$temp_terms  = wp_get_post_terms( $partido->ID, 'scl_temporada' );
			$temp_id     = ( ! is_wp_error( $temp_terms ) && ! empty( $temp_terms ) )
						   ? $temp_terms[0]->term_id : 0;

			$nombre_local  = get_the_title( $local_id )  ?: __( '(Equipo)', 'sportcriss-lite' );
			$nombre_visita = get_the_title( $visita_id ) ?: __( '(Equipo)', 'sportcriss-lite' );

			$esc_local_id    = absint( get_post_meta( $local_id,  'scl_equipo_escudo', true ) );
			$esc_visita_id   = absint( get_post_meta( $visita_id, 'scl_equipo_escudo', true ) );
			$esc_local_url   = $esc_local_id  ? (string) wp_get_attachment_image_url( $esc_local_id,  'thumbnail' ) : '';
			$esc_visita_url  = $esc_visita_id ? (string) wp_get_attachment_image_url( $esc_visita_id, 'thumbnail' ) : '';
			$esc_local_img   = $esc_local_id  ? wp_get_attachment_image( $esc_local_id,  [ 36, 36 ] ) : '';
			$esc_visita_img  = $esc_visita_id ? wp_get_attachment_image( $esc_visita_id, [ 36, 36 ] ) : '';

			$fecha_str  = $fecha_raw ? date_i18n( 'j M Y', strtotime( $fecha_raw ) ) : '';
			$grupo_nombre = $grupo_id ? get_the_title( $grupo_id ) : '';

			// Llave asociada
			$llave_id = absint( get_post_meta( $partido->ID, 'scl_partido_llave_id', true ) );

			// Datos para el drawer de edición completa
			$partido_data = wp_json_encode( [
				'id'               => $partido->ID,
				'torneo_id'        => absint( get_post_meta( $partido->ID, 'scl_partido_torneo_id', true ) ),
				'temporada_term_id'=> $temp_id,
				'tipo_fase'        => $tipo_fase,
				'grupo_id'         => $grupo_id,
				'grupo_nombre'     => $grupo_nombre,
				'jornada'          => $jornada_nombre !== 'Sin jornada' ? $jornada_nombre : '',
				'equipo_local_id'  => $local_id,
				'equipo_visita_id' => $visita_id,
				'fecha'            => $fecha_raw,
				'estado'           => $estado,
				'goles_local'      => $goles_local,
				'goles_visita'     => $goles_vis,
			] );
		?>
		<div class="scl-partido-card" id="scl-partido-<?php echo esc_attr( $partido->ID ); ?>">

			<div class="scl-partido-card__equipos">
				<!-- Equipo local -->
				<div class="scl-partido-card__equipo">
					<div class="scl-escudo-sm">
						<?php if ( $esc_local_img ) : echo $esc_local_img; else : ?>
							<?php echo esc_html( mb_strtoupper( mb_substr( $nombre_local, 0, 1 ) ) ); ?>
						<?php endif; ?>
					</div>
					<span class="scl-partido-card__equipo-nombre"><?php echo esc_html( $nombre_local ); ?></span>
				</div>

				<!-- Marcador -->
				<?php if ( 'finalizado' === $estado && $goles_local !== '' ) : ?>
					<div class="scl-partido-card__marcador">
						<?php echo esc_html( $goles_local ); ?> - <?php echo esc_html( $goles_vis ); ?>
					</div>
				<?php else : ?>
					<div class="scl-partido-card__marcador scl-partido-card__marcador--pendiente">— vs —</div>
				<?php endif; ?>

				<!-- Equipo visitante -->
				<div class="scl-partido-card__equipo scl-partido-card__equipo--visita">
					<div class="scl-escudo-sm">
						<?php if ( $esc_visita_img ) : echo $esc_visita_img; else : ?>
							<?php echo esc_html( mb_strtoupper( mb_substr( $nombre_visita, 0, 1 ) ) ); ?>
						<?php endif; ?>
					</div>
					<span class="scl-partido-card__equipo-nombre"><?php echo esc_html( $nombre_visita ); ?></span>
				</div>
			</div>

			<div class="scl-partido-card__meta">
				<?php if ( $fecha_str ) : ?>
					<?php echo esc_html( $fecha_str ); ?>
				<?php endif; ?>
				<?php if ( $grupo_nombre ) : ?>
					&nbsp;&middot;&nbsp; <?php echo esc_html( $grupo_nombre ); ?>
				<?php endif; ?>
				<br>
				<span class="scl-badge scl-badge--<?php echo esc_attr( $estado ); ?>">
					<?php echo 'finalizado' === $estado ? '✅' : '⏳'; ?>
					<?php echo esc_html( ucfirst( $estado ) ); ?>
				</span>
				<?php if ( 'playoff' === $tipo_fase ) : ?>
					<span class="scl-badge scl-badge--playoff">Playoff</span>
				<?php endif; ?>
			</div>

			<div class="scl-partido-card__actions">
				<?php if ( ! $llave_id ) : ?>
				<button type="button"
				        class="scl-btn scl-btn--primary scl-btn--sm scl-resultado-btn"
				        data-id="<?php echo esc_attr( $partido->ID ); ?>"
				        data-tipo-fase="<?php echo esc_attr( $tipo_fase ); ?>"
				        data-nombre-local="<?php echo esc_attr( $nombre_local ); ?>"
				        data-nombre-visita="<?php echo esc_attr( $nombre_visita ); ?>"
				        data-escudo-local="<?php echo esc_attr( $esc_local_url ); ?>"
				        data-escudo-visita="<?php echo esc_attr( $esc_visita_url ); ?>"
				        data-goles-local="<?php echo esc_attr( $goles_local ); ?>"
				        data-goles-visita="<?php echo esc_attr( $goles_vis ); ?>">
					<?php echo 'finalizado' === $estado
						? esc_html__( 'Editar resultado', 'sportcriss-lite' )
						: esc_html__( 'Ingresar resultado', 'sportcriss-lite' ); ?>
				</button>
				<?php elseif ( $llave_id ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'llaves', 'scl_id' => $llave_id ], $home_url ) ); ?>"
				   class="scl-btn scl-btn--outline scl-btn--sm">
					<?php esc_html_e( 'Ver llave', 'sportcriss-lite' ); ?>
				</a>
				<?php endif; ?>

				<button type="button"
				        class="scl-btn scl-btn--outline scl-btn--sm scl-partido-editar-btn"
				        data-partido="<?php echo esc_attr( $partido_data ); ?>">
					<?php esc_html_e( 'Editar', 'sportcriss-lite' ); ?>
				</button>

				<button type="button"
				        class="scl-btn scl-btn--danger scl-btn--sm scl-partido-eliminar-btn"
				        data-id="<?php echo esc_attr( $partido->ID ); ?>"
				        data-titulo="<?php echo esc_attr( $nombre_local . ' vs ' . $nombre_visita ); ?>">
					<?php esc_html_e( 'Eliminar', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>
		<?php endforeach; // partidos_grupo ?>
	</div>
<?php
	endforeach; // jornada
	endforeach; // fase
else : ?>
	<div class="scl-empty">
		<p><?php esc_html_e( 'No hay partidos que coincidan con los filtros seleccionados.', 'sportcriss-lite' ); ?></p>
		<?php if ( empty( $mis_torneos ) ) : ?>
			<p><?php esc_html_e( 'Crea un torneo primero para poder registrar partidos.', 'sportcriss-lite' ); ?></p>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php include SCL_PATH . 'templates/dashboard/partidos-form.php'; ?>
