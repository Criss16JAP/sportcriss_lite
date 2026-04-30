<?php
/**
 * Template: Dashboard – Ver estadísticas individuales (vista inline)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$autor_ef  = scl_get_autor_efectivo();
$home_url  = home_url( '/mi-panel/' );
$torneo_id = (int) ( $_GET['scl_id'] ?? 0 );

$torneo = $torneo_id ? get_post( $torneo_id ) : null;
if ( ! $torneo || ( (int) $torneo->post_author !== $autor_ef && ! current_user_can( 'manage_options' ) ) ) {
	echo '<div class="scl-empty"><p>' . esc_html__( 'Torneo no encontrado.', 'sportcriss-lite' ) . '</p></div>';
	return;
}

// Temporadas disponibles
$partidos_ids = get_posts( [
	'post_type'      => 'scl_partido',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => 'scl_partido_torneo_id', 'value' => $torneo_id, 'type' => 'NUMERIC' ] ],
] );

$temporadas_usadas = [];
foreach ( $partidos_ids as $pid ) {
	$terms = wp_get_post_terms( $pid, 'scl_temporada', [ 'fields' => 'ids' ] );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $tid ) $temporadas_usadas[ $tid ] = true;
	}
}
$temporadas = array_filter(
	array_map( fn( $id ) => get_term( $id, 'scl_temporada' ), array_keys( $temporadas_usadas ) ),
	fn( $t ) => $t && ! is_wp_error( $t )
);

$temp_sel     = (int) ( $_GET['scl_temporada'] ?? ( $temporadas ? reset( $temporadas )->term_id : 0 ) );
$stats_tipo   = sanitize_key( $_GET['scl_tipo'] ?? 'goleadores' );
$stats_limite = max( 5, min( 20, (int) ( $_GET['scl_limite'] ?? 10 ) ) );

$stats_tipos_validos = [ 'goleadores', 'asistencias', 'tarjetas_amarillas', 'tarjetas_rojas', 'calificaciones' ];
if ( ! in_array( $stats_tipo, $stats_tipos_validos, true ) ) $stats_tipo = 'goleadores';

$stats_data = Scl_Export::get_stats_render( $torneo_id, $temp_sel, $stats_tipo, $stats_limite );

$tipo_labels = [
	'goleadores'         => __( 'Goleadores', 'sportcriss-lite' ),
	'asistencias'        => __( 'Asistencias', 'sportcriss-lite' ),
	'tarjetas_amarillas' => __( 'Tarjetas Amarillas', 'sportcriss-lite' ),
	'tarjetas_rojas'     => __( 'Tarjetas Rojas', 'sportcriss-lite' ),
	'calificaciones'     => __( 'Calificaciones', 'sportcriss-lite' ),
];

$logo_id = (int) get_post_meta( $torneo_id, 'scl_torneo_logo', true );
?>

<div class="scl-page-header">
	<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'torneos', $home_url ) ); ?>"
	   class="scl-back-link">← <?php esc_html_e( 'Mis torneos', 'sportcriss-lite' ); ?></a>
	<h1 class="scl-page-title">
		<?php if ( $logo_id ) : ?>
			<img src="<?php echo esc_url( wp_get_attachment_image_url( $logo_id, 'thumbnail' ) ); ?>"
			     alt="" class="scl-export-mini-logo">
		<?php endif; ?>
		<?php echo esc_html( $torneo->post_title ); ?> — <?php esc_html_e( 'Estadísticas', 'sportcriss-lite' ); ?>
	</h1>
</div>

<!-- Filtros -->
<div class="scl-form-section">
	<form method="get" class="scl-field-row" style="align-items:flex-end;flex-wrap:wrap;gap:12px;">
		<input type="hidden" name="scl_ruta" value="ver_stats">
		<input type="hidden" name="scl_id" value="<?php echo esc_attr( $torneo_id ); ?>">

		<div class="scl-field">
			<label><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></label>
			<select name="scl_temporada" onchange="this.form.submit()">
				<option value="0"><?php esc_html_e( '— Todas —', 'sportcriss-lite' ); ?></option>
				<?php foreach ( $temporadas as $t ) : ?>
					<option value="<?php echo esc_attr( $t->term_id ); ?>" <?php selected( $temp_sel, $t->term_id ); ?>>
						<?php echo esc_html( $t->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="scl-field">
			<label><?php esc_html_e( 'Estadística', 'sportcriss-lite' ); ?></label>
			<select name="scl_tipo" onchange="this.form.submit()">
				<?php foreach ( $tipo_labels as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $stats_tipo, $val ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="scl-field" style="max-width:100px;">
			<label><?php esc_html_e( 'Límite', 'sportcriss-lite' ); ?></label>
			<select name="scl_limite" onchange="this.form.submit()">
				<?php foreach ( [ 5, 10, 15, 20 ] as $lim ) : ?>
					<option value="<?php echo esc_attr( $lim ); ?>" <?php selected( $stats_limite, $lim ); ?>>
						<?php echo esc_html( $lim ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="scl-field scl-field--auto">
			<label>&nbsp;</label>
			<a href="<?php echo esc_url( Scl_Export::get_url_stats( $torneo_id, $temp_sel, $stats_tipo, $stats_limite ) ); ?>"
			   target="_blank" rel="noopener"
			   class="scl-btn scl-btn--primary">
				🖼 <?php esc_html_e( 'Abrir para pantallazo', 'sportcriss-lite' ); ?>
			</a>
		</div>
	</form>
</div>

<!-- Tabla de stats -->
<div class="scl-form-section">
	<h2 class="scl-section-title"><?php echo esc_html( $tipo_labels[ $stats_tipo ] ); ?></h2>

	<?php if ( empty( $stats_data ) ) : ?>
		<div class="scl-empty">
			<p><?php esc_html_e( 'No hay estadísticas disponibles aún.', 'sportcriss-lite' ); ?></p>
		</div>
	<?php else : ?>
		<div class="scl-table-wrap">
			<table class="scl-table">
				<thead>
					<tr>
						<th class="scl-col-pos">#</th>
						<th><?php esc_html_e( 'Jugador', 'sportcriss-lite' ); ?></th>
						<th><?php esc_html_e( 'Equipo', 'sportcriss-lite' ); ?></th>
						<?php if ( in_array( $stats_tipo, [ 'tarjetas_amarillas', 'tarjetas_rojas' ], true ) ) : ?>
							<th class="scl-col-num">🟨</th>
							<th class="scl-col-num">🟥</th>
						<?php elseif ( 'calificaciones' === $stats_tipo ) : ?>
							<th class="scl-col-num">PJ</th>
							<th class="scl-col-num"><?php esc_html_e( 'Prom.', 'sportcriss-lite' ); ?></th>
						<?php elseif ( 'asistencias' === $stats_tipo ) : ?>
							<th class="scl-col-num"><?php esc_html_e( 'Asist.', 'sportcriss-lite' ); ?></th>
						<?php else : ?>
							<th class="scl-col-num"><?php esc_html_e( 'Goles', 'sportcriss-lite' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $stats_data as $i => $row ) : ?>
					<tr>
						<td><?php echo esc_html( $i + 1 ); ?></td>
						<td><strong><?php echo esc_html( $row->jugador_nombre ?? '—' ); ?></strong></td>
						<td><?php echo esc_html( $row->equipo_nombre ?? '' ); ?></td>
						<?php if ( in_array( $stats_tipo, [ 'tarjetas_amarillas', 'tarjetas_rojas' ], true ) ) : ?>
							<td class="scl-col-num"><?php echo esc_html( (int) ( $row->amarillas ?? 0 ) ); ?></td>
							<td class="scl-col-num"><?php echo esc_html( (int) ( $row->rojas ?? 0 ) ); ?></td>
						<?php elseif ( 'calificaciones' === $stats_tipo ) : ?>
							<td class="scl-col-num"><?php echo esc_html( (int) ( $row->partidos ?? 0 ) ); ?></td>
							<td class="scl-col-num scl-col-pts-val"><?php echo esc_html( number_format( (float) ( $row->promedio ?? 0 ), 1 ) ); ?></td>
						<?php elseif ( 'asistencias' === $stats_tipo ) : ?>
							<td class="scl-col-num scl-col-pts-val"><?php echo esc_html( (int) ( $row->asistencias ?? 0 ) ); ?></td>
						<?php else : ?>
							<td class="scl-col-num scl-col-pts-val"><?php echo esc_html( (int) ( $row->goles ?? 0 ) ); ?></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
