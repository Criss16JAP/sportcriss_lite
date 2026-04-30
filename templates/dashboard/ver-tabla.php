<?php
/**
 * Template: Dashboard – Ver tabla de posiciones (vista inline)
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

// Temporadas disponibles para este torneo
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

$temp_sel  = (int) ( $_GET['scl_temporada'] ?? ( $temporadas ? reset( $temporadas )->term_id : 0 ) );
$grupo_sel = (int) ( $_GET['scl_grupo'] ?? 0 );

$grupos = scl_get_grupos_por_torneo( $torneo_id );
$tabla  = Scl_Export::get_tabla_render( $torneo_id, $temp_sel, $grupo_sel );

$updated_at = get_post_meta( $torneo_id, "scl_tabla_{$temp_sel}_updated_at", true );
$fecha_act  = $updated_at ? date_i18n( 'j M Y · H:i', strtotime( $updated_at ) ) : '';

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
		<?php echo esc_html( $torneo->post_title ); ?> — <?php esc_html_e( 'Tabla de Posiciones', 'sportcriss-lite' ); ?>
	</h1>
</div>

<!-- Filtros -->
<div class="scl-form-section">
	<form method="get" class="scl-field-row" style="align-items:flex-end;">
		<input type="hidden" name="scl_ruta" value="ver_tabla">
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

		<?php if ( ! empty( $grupos ) ) : ?>
		<div class="scl-field">
			<label><?php esc_html_e( 'Grupo', 'sportcriss-lite' ); ?></label>
			<select name="scl_grupo" onchange="this.form.submit()">
				<option value="0"><?php esc_html_e( 'General', 'sportcriss-lite' ); ?></option>
				<?php foreach ( $grupos as $g ) : ?>
					<option value="<?php echo esc_attr( $g->ID ); ?>" <?php selected( $grupo_sel, $g->ID ); ?>>
						<?php echo esc_html( $g->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php endif; ?>

		<div class="scl-field scl-field--auto">
			<label>&nbsp;</label>
			<a href="<?php echo esc_url( Scl_Export::get_url( $torneo_id, $temp_sel, $grupo_sel ) ); ?>"
			   target="_blank" rel="noopener"
			   class="scl-btn scl-btn--primary">
				🖼 <?php esc_html_e( 'Abrir para pantallazo', 'sportcriss-lite' ); ?>
			</a>
		</div>
	</form>
</div>

<!-- Tabla -->
<div class="scl-form-section">
	<?php if ( $fecha_act ) : ?>
		<p class="scl-description" style="margin-bottom:12px;">
			<?php esc_html_e( 'Actualizado:', 'sportcriss-lite' ); ?> <?php echo esc_html( $fecha_act ); ?>
		</p>
	<?php endif; ?>

	<?php if ( empty( $tabla ) ) : ?>
		<div class="scl-empty">
			<p><?php esc_html_e( 'No hay datos de tabla disponibles. Ingresa resultados de partidos finalizados primero.', 'sportcriss-lite' ); ?></p>
		</div>
	<?php else : ?>
		<div class="scl-table-wrap">
			<table class="scl-table">
				<thead>
					<tr>
						<th class="scl-col-pos">#</th>
						<th class="scl-col-escudo"></th>
						<th class="scl-col-equipo"><?php esc_html_e( 'Equipo', 'sportcriss-lite' ); ?></th>
						<th class="scl-col-num">PJ</th>
						<th class="scl-col-num">PG</th>
						<th class="scl-col-num">PE</th>
						<th class="scl-col-num">PP</th>
						<th class="scl-col-num">GF</th>
						<th class="scl-col-num">GC</th>
						<th class="scl-col-num">DG</th>
						<th class="scl-col-pts">Pts</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tabla as $pos => $equipo ) :
						$dg = ( (int) ( $equipo['goles_favor'] ?? 0 ) ) - ( (int) ( $equipo['goles_contra'] ?? 0 ) );
					?>
					<tr>
						<td><?php echo esc_html( $pos + 1 ); ?></td>
						<td>
							<?php if ( ! empty( $equipo['escudo_url'] ) ) : ?>
								<img src="<?php echo esc_url( $equipo['escudo_url'] ); ?>"
								     alt="" style="width:24px;height:24px;object-fit:contain;">
							<?php endif; ?>
						</td>
						<td class="scl-col-equipo"><?php echo esc_html( $equipo['nombre'] ?? '—' ); ?></td>
						<td><?php echo esc_html( $equipo['pj'] ?? 0 ); ?></td>
						<td><?php echo esc_html( $equipo['pg'] ?? 0 ); ?></td>
						<td><?php echo esc_html( $equipo['pe'] ?? 0 ); ?></td>
						<td><?php echo esc_html( $equipo['pp'] ?? 0 ); ?></td>
						<td><?php echo esc_html( $equipo['goles_favor'] ?? 0 ); ?></td>
						<td><?php echo esc_html( $equipo['goles_contra'] ?? 0 ); ?></td>
						<td class="<?php echo $dg > 0 ? 'scl-dg-pos' : ( $dg < 0 ? 'scl-dg-neg' : '' ); ?>">
							<?php echo esc_html( ( $dg > 0 ? '+' : '' ) . $dg ); ?>
						</td>
						<td class="scl-col-pts-val"><?php echo esc_html( $equipo['puntos'] ?? 0 ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
