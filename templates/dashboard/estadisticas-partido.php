<?php
/**
 * Ingreso de estadísticas individuales por partido.
 *
 * URL: ?scl_ruta=estadisticas_partido&scl_id={partido_id}
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$autor_ef   = scl_get_autor_efectivo();
$partido_id = $id;

if ( $partido_id <= 0 ) {
	echo '<div class="scl-flash scl-flash--error">' . esc_html__( 'No se especificó un partido.', 'sportcriss-lite' ) . '</div>';
	return;
}

$partido = get_post( $partido_id );

if ( ! $partido || 'scl_partido' !== $partido->post_type
	|| ( (int) $partido->post_author !== $autor_ef && ! current_user_can( 'manage_options' ) )
) {
	echo '<div class="scl-flash scl-flash--error">' . esc_html__( 'Partido no válido o sin permisos.', 'sportcriss-lite' ) . '</div>';
	return;
}

if ( 'finalizado' !== get_post_meta( $partido_id, 'scl_partido_estado', true ) ) {
	echo '<div class="scl-flash scl-flash--error">' . esc_html__( 'Solo se pueden ingresar estadísticas de partidos finalizados.', 'sportcriss-lite' ) . '</div>';
	return;
}

$local_id    = (int) get_post_meta( $partido_id, 'scl_partido_equipo_local_id',  true );
$visita_id   = (int) get_post_meta( $partido_id, 'scl_partido_equipo_visita_id', true );
$goles_local = (int) get_post_meta( $partido_id, 'scl_partido_goles_local',  true );
$goles_vis   = (int) get_post_meta( $partido_id, 'scl_partido_goles_visita', true );
$torneo_id   = (int) get_post_meta( $partido_id, 'scl_partido_torneo_id',    true );

$local_nombre  = $local_id  ? get_the_title( $local_id )  : '—';
$visita_nombre = $visita_id ? get_the_title( $visita_id ) : '—';

// Temporada vía taxonomía
$temporadas_del_partido = wp_get_post_terms( $partido_id, 'scl_temporada' );
$temporada_term_id      = ! empty( $temporadas_del_partido ) ? (int) $temporadas_del_partido[0]->term_id : 0;

// Jugadores inscritos en cada equipo para este torneo/temporada
$jugadores_local  = Scl_Stats::get_jugadores_inscritos( $local_id,  $torneo_id, $temporada_term_id );
$jugadores_visita = Scl_Stats::get_jugadores_inscritos( $visita_id, $torneo_id, $temporada_term_id );

// Estadísticas ya ingresadas para este partido (si existen)
global $wpdb;
$stats_existentes = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}scl_estadisticas WHERE partido_id = %d",
	$partido_id
), OBJECT_K /* keyed by 'id', but we need jugador_id as key */ );

// Re-index by jugador_id for easy lookup
$stats_por_jugador = [];
foreach ( $stats_existentes as $s ) {
	$stats_por_jugador[ (int) $s->jugador_id ] = $s;
}

// Validación de goles guardados
$val_local  = Scl_Stats::validar_goles_partido( $partido_id, $local_id );
$val_visita = Scl_Stats::validar_goles_partido( $partido_id, $visita_id );

// ¿Hay llave con penales?
$llave_id  = (int) get_post_meta( $partido_id, 'scl_partido_llave_id', true );
$es_vuelta = (bool) get_post_meta( $partido_id, 'scl_partido_es_vuelta', true );
$llave_es_doble = $llave_id ? (bool) get_post_meta( $llave_id, 'scl_llave_es_doble', true ) : false;
$tiene_penales  = $llave_id && ( ! $llave_es_doble || $es_vuelta );

$home_url = home_url( '/mi-panel/' );
?>
<div class="scl-dashboard-header">
	<h2>
		<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'partidos', $home_url ) ); ?>"
		   class="scl-btn scl-btn--ghost" style="padding:0;margin-right:10px;">&larr;</a>
		<?php esc_html_e( 'Estadísticas del partido', 'sportcriss-lite' ); ?>
	</h2>
</div>

<!-- Marcador -->
<div class="scl-marcador-header" style="text-align:center;margin-bottom:2rem;padding:1rem;background:var(--scl-surface);border-radius:var(--scl-radius-md)">
	<span style="font-size:1.2rem;font-weight:600"><?php echo esc_html( $local_nombre ); ?></span>
	<span style="font-size:2rem;font-weight:700;margin:0 1.5rem;color:var(--scl-primary)">
		<?php echo esc_html( $goles_local ); ?> &ndash; <?php echo esc_html( $goles_vis ); ?>
	</span>
	<span style="font-size:1.2rem;font-weight:600"><?php echo esc_html( $visita_nombre ); ?></span>
</div>

<?php if ( ! $val_local['ok'] || ! $val_visita['ok'] ) : ?>
<div class="scl-flash scl-flash--warning" id="scl_stats_advertencia">
	<?php
	if ( ! $val_local['ok'] ) {
		printf(
			esc_html__( '%s: el marcador dice %d goles pero los jugadores suman %d.', 'sportcriss-lite' ),
			esc_html( $local_nombre ),
			(int) $val_local['marcador'],
			(int) $val_local['suma_jugadores']
		);
		echo '<br>';
	}
	if ( ! $val_visita['ok'] ) {
		printf(
			esc_html__( '%s: el marcador dice %d goles pero los jugadores suman %d.', 'sportcriss-lite' ),
			esc_html( $visita_nombre ),
			(int) $val_visita['marcador'],
			(int) $val_visita['suma_jugadores']
		);
	}
	?>
</div>
<?php endif; ?>

<input type="hidden" id="scl_stats_partido_id" value="<?php echo esc_attr( $partido_id ); ?>">
<input type="hidden" id="scl_stats_torneo_id"  value="<?php echo esc_attr( $torneo_id ); ?>">
<input type="hidden" id="scl_stats_local_id"   value="<?php echo esc_attr( $local_id ); ?>">
<input type="hidden" id="scl_stats_visita_id"  value="<?php echo esc_attr( $visita_id ); ?>">
<input type="hidden" id="scl_stats_goles_local_marcador"  value="<?php echo esc_attr( $goles_local ); ?>">
<input type="hidden" id="scl_stats_goles_visita_marcador" value="<?php echo esc_attr( $goles_vis ); ?>">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">

	<!-- Panel equipo local -->
	<div class="scl-section">
		<h3 style="margin-top:0"><?php echo esc_html( $local_nombre ); ?></h3>

		<?php if ( empty( $jugadores_local ) ) : ?>
			<p style="color:#888;font-size:0.9rem">
				<?php esc_html_e( 'Sin jugadores inscritos.', 'sportcriss-lite' ); ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'inscripciones', 'scl_id' => $torneo_id ], $home_url ) ); ?>">
					<?php esc_html_e( 'Inscribir', 'sportcriss-lite' ); ?>
				</a>
			</p>
		<?php else : ?>
			<div class="scl-stats-grid">
				<!-- Cabecera -->
				<div class="scl-stats-header">
					<span><?php esc_html_e( 'Jugador', 'sportcriss-lite' ); ?></span>
					<span title="<?php esc_attr_e( 'Goles', 'sportcriss-lite' ); ?>">&#9917;</span>
					<span title="<?php esc_attr_e( 'Asistencias', 'sportcriss-lite' ); ?>">&#128203;</span>
					<span title="<?php esc_attr_e( 'Tarjeta amarilla', 'sportcriss-lite' ); ?>">&#129000;</span>
					<span title="<?php esc_attr_e( 'Tarjeta roja', 'sportcriss-lite' ); ?>">&#128998;</span>
					<span title="<?php esc_attr_e( 'Calificación', 'sportcriss-lite' ); ?>">&#11088;</span>
				</div>
				<?php foreach ( $jugadores_local as $jug ) :
					$s = $stats_por_jugador[ (int) $jug->jugador_id ] ?? null;
				?>
					<div class="scl-stats-row" data-jugador-id="<?php echo esc_attr( $jug->jugador_id ); ?>" data-equipo-id="<?php echo esc_attr( $local_id ); ?>" data-inscripcion-id="<?php echo esc_attr( $jug->id ); ?>">
						<span class="scl-stats-nombre"><?php echo esc_html( $jug->jugador_nombre ); ?></span>
						<input type="number" class="scl-stats-input scl-stats-goles" min="0" max="20" value="<?php echo esc_attr( $s ? $s->goles : 0 ); ?>" style="width:50px">
						<input type="number" class="scl-stats-input scl-stats-asistencias" min="0" max="20" value="<?php echo esc_attr( $s ? $s->asistencias : 0 ); ?>" style="width:50px">
						<input type="checkbox" class="scl-stats-input scl-stats-amarilla" <?php checked( $s && $s->tarjeta_amarilla ); ?>>
						<input type="checkbox" class="scl-stats-input scl-stats-roja" <?php checked( $s && $s->tarjeta_roja ); ?>>
						<input type="number" class="scl-stats-input scl-stats-calificacion" min="0" max="10" step="0.1" placeholder="—" value="<?php echo esc_attr( $s && $s->calificacion !== null ? $s->calificacion : '' ); ?>" style="width:55px">
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Totalizador en tiempo real -->
			<p class="scl-stats-total" id="scl_total_goles_local" style="font-size:0.85rem;color:#666;margin-top:0.5rem">
				<?php
				printf(
					esc_html__( 'Goles ingresados: %s / %s del marcador', 'sportcriss-lite' ),
					'<strong id="scl_sum_goles_local">0</strong>',
					'<strong>' . esc_html( $goles_local ) . '</strong>'
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<!-- Panel equipo visitante -->
	<div class="scl-section">
		<h3 style="margin-top:0"><?php echo esc_html( $visita_nombre ); ?></h3>

		<?php if ( empty( $jugadores_visita ) ) : ?>
			<p style="color:#888;font-size:0.9rem">
				<?php esc_html_e( 'Sin jugadores inscritos.', 'sportcriss-lite' ); ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'inscripciones', 'scl_id' => $torneo_id ], $home_url ) ); ?>">
					<?php esc_html_e( 'Inscribir', 'sportcriss-lite' ); ?>
				</a>
			</p>
		<?php else : ?>
			<div class="scl-stats-grid">
				<div class="scl-stats-header">
					<span><?php esc_html_e( 'Jugador', 'sportcriss-lite' ); ?></span>
					<span title="<?php esc_attr_e( 'Goles', 'sportcriss-lite' ); ?>">&#9917;</span>
					<span title="<?php esc_attr_e( 'Asistencias', 'sportcriss-lite' ); ?>">&#128203;</span>
					<span title="<?php esc_attr_e( 'Tarjeta amarilla', 'sportcriss-lite' ); ?>">&#129000;</span>
					<span title="<?php esc_attr_e( 'Tarjeta roja', 'sportcriss-lite' ); ?>">&#128998;</span>
					<span title="<?php esc_attr_e( 'Calificación', 'sportcriss-lite' ); ?>">&#11088;</span>
				</div>
				<?php foreach ( $jugadores_visita as $jug ) :
					$s = $stats_por_jugador[ (int) $jug->jugador_id ] ?? null;
				?>
					<div class="scl-stats-row" data-jugador-id="<?php echo esc_attr( $jug->jugador_id ); ?>" data-equipo-id="<?php echo esc_attr( $visita_id ); ?>" data-inscripcion-id="<?php echo esc_attr( $jug->id ); ?>">
						<span class="scl-stats-nombre"><?php echo esc_html( $jug->jugador_nombre ); ?></span>
						<input type="number" class="scl-stats-input scl-stats-goles" min="0" max="20" value="<?php echo esc_attr( $s ? $s->goles : 0 ); ?>" style="width:50px">
						<input type="number" class="scl-stats-input scl-stats-asistencias" min="0" max="20" value="<?php echo esc_attr( $s ? $s->asistencias : 0 ); ?>" style="width:50px">
						<input type="checkbox" class="scl-stats-input scl-stats-amarilla" <?php checked( $s && $s->tarjeta_amarilla ); ?>>
						<input type="checkbox" class="scl-stats-input scl-stats-roja" <?php checked( $s && $s->tarjeta_roja ); ?>>
						<input type="number" class="scl-stats-input scl-stats-calificacion" min="0" max="10" step="0.1" placeholder="—" value="<?php echo esc_attr( $s && $s->calificacion !== null ? $s->calificacion : '' ); ?>" style="width:55px">
					</div>
				<?php endforeach; ?>
			</div>

			<p class="scl-stats-total" id="scl_total_goles_visita" style="font-size:0.85rem;color:#666;margin-top:0.5rem">
				<?php
				printf(
					esc_html__( 'Goles ingresados: %s / %s del marcador', 'sportcriss-lite' ),
					'<strong id="scl_sum_goles_visita">0</strong>',
					'<strong>' . esc_html( $goles_vis ) . '</strong>'
				);
				?>
			</p>
		<?php endif; ?>
	</div>

</div><!-- /grid -->

<?php if ( $tiene_penales ) : ?>
<!-- Sección de penales (solo en partido donde aplica) -->
<div class="scl-section" style="margin-top:2rem;padding:1.5rem;background:var(--scl-surface);border-radius:var(--scl-radius-md)">
	<h4 style="margin-top:0"><?php esc_html_e( 'Goles de penales (no suman a estadísticas)', 'sportcriss-lite' ); ?></h4>
	<div style="display:flex;gap:2rem;align-items:center">
		<div class="scl-field" style="margin:0">
			<label><?php echo esc_html( $local_nombre ); ?></label>
			<input type="number" id="scl_penales_local" min="0" max="20"
				value="<?php echo esc_attr( (int) get_post_meta( $llave_id, 'scl_llave_penales_local', true ) ); ?>"
				style="width:70px">
		</div>
		<div class="scl-field" style="margin:0">
			<label><?php echo esc_html( $visita_nombre ); ?></label>
			<input type="number" id="scl_penales_visita" min="0" max="20"
				value="<?php echo esc_attr( (int) get_post_meta( $llave_id, 'scl_llave_penales_visita', true ) ); ?>"
				style="width:70px">
		</div>
	</div>
	<input type="hidden" id="scl_stats_llave_id" value="<?php echo esc_attr( $llave_id ); ?>">
</div>
<?php endif; ?>

<!-- Botón guardar -->
<div class="scl-form-actions" style="margin-top:2rem">
	<div id="scl_stats_msg"></div>
	<button type="button" class="scl-btn scl-btn--primary" id="scl_stats_guardar">
		<?php esc_html_e( 'Guardar estadísticas', 'sportcriss-lite' ); ?>
	</button>
</div>

<style>
.scl-stats-grid {
	display: grid;
	grid-template-columns: 1fr repeat(5, auto);
	gap: 0.4rem 0.75rem;
	align-items: center;
}
.scl-stats-header {
	display: contents;
	font-weight: 600;
	font-size: 0.85rem;
	color: #666;
}
.scl-stats-header > span { text-align: center; }
.scl-stats-row { display: contents; }
.scl-stats-nombre { font-size: 0.9rem; padding: 0.2rem 0; }
.scl-stats-input { text-align: center; }
</style>
