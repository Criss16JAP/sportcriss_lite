<?php
/**
 * Gestión de Temporadas — vista global con estado por torneo (Fix 3)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$autor_ef = scl_get_autor_efectivo();

$temporadas = get_terms( [
	'taxonomy'   => 'scl_temporada',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );

$mis_torneos = get_posts( [
	'post_type'      => 'scl_torneo',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );
$mis_torneo_ids = wp_list_pluck( $mis_torneos, 'ID' );

// Build map: temporada_term_id → [torneo_id, ...] (via a single DB query)
$mapa_temp_torneos = []; // term_id → array of torneo_ids
if ( ! empty( $mis_torneo_ids ) ) {
	global $wpdb;
	$ids_in    = implode( ',', array_map( 'absint', $mis_torneo_ids ) );
	$resultados = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT pm.meta_value AS torneo_id, tt.term_id
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'scl_partido_torneo_id'
			 JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
			 JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			 WHERE p.post_type   = 'scl_partido'
			   AND p.post_status = 'publish'
			   AND p.post_author = %d
			   AND tt.taxonomy   = 'scl_temporada'
			   AND CAST(pm.meta_value AS UNSIGNED) IN ($ids_in)",
			$autor_ef
		)
	);
	foreach ( $resultados as $row ) {
		$mapa_temp_torneos[ $row->term_id ][] = (int) $row->torneo_id;
	}
}

// Index torneos by ID for quick lookup
$torneos_idx = [];
foreach ( $mis_torneos as $t ) {
	$torneos_idx[ $t->ID ] = $t;
}

$home_url = home_url( '/mi-panel/' );
?>
<div class="scl-dashboard-header">
	<h2><?php esc_html_e( 'Temporadas', 'sportcriss-lite' ); ?></h2>
	<button type="button" class="scl-btn scl-btn--primary" id="scl_btn_nueva_temporada">+ <?php esc_html_e( 'Nueva temporada', 'sportcriss-lite' ); ?></button>
</div>

<div class="scl-flash scl-flash--warning" style="margin-bottom: 20px;">
	<?php esc_html_e( 'Las temporadas son compartidas entre torneos. El estado se gestiona por torneo.', 'sportcriss-lite' ); ?>
</div>

<div class="scl-inline-form" id="scl_temporada_form" style="display:none">
	<div class="scl-field-row">
		<div class="scl-field">
			<label><?php esc_html_e( 'Nombre *', 'sportcriss-lite' ); ?></label>
			<input type="text" id="scl_temp_nombre" placeholder="Ej: Apertura 2025">
		</div>
		<div class="scl-field">
			<label><?php esc_html_e( 'Año', 'sportcriss-lite' ); ?></label>
			<input type="number" id="scl_temp_anio" value="<?php echo esc_attr( date( 'Y' ) ); ?>">
		</div>
	</div>
	<p class="scl-description">
		<?php esc_html_e( 'Si la temporada ya existe no se duplicará — se reutilizará la existente.', 'sportcriss-lite' ); ?>
	</p>
	<div class="scl-form-actions">
		<button type="button" class="scl-btn scl-btn--ghost" id="scl_temp_cancelar"><?php esc_html_e( 'Cancelar', 'sportcriss-lite' ); ?></button>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_temp_guardar"><?php esc_html_e( 'Guardar temporada', 'sportcriss-lite' ); ?></button>
	</div>
</div>

<?php if ( is_wp_error( $temporadas ) || empty( $temporadas ) ) : ?>
	<div class="scl-empty">
		<p><?php esc_html_e( 'No hay temporadas registradas.', 'sportcriss-lite' ); ?></p>
	</div>
<?php else : ?>
	<table class="scl-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></th>
				<th><?php esc_html_e( 'Año',       'sportcriss-lite' ); ?></th>
				<th><?php esc_html_e( 'Torneo',    'sportcriss-lite' ); ?></th>
				<th><?php esc_html_e( 'Estado',    'sportcriss-lite' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $temporadas as $temp ) :
				$anio         = get_term_meta( $temp->term_id, 'scl_temporada_anio', true );
				$torneo_ids   = $mapa_temp_torneos[ $temp->term_id ] ?? [];

				if ( empty( $torneo_ids ) ) :
			?>
				<tr>
					<td><strong><?php echo esc_html( $temp->name ); ?></strong></td>
					<td><?php echo esc_html( $anio ); ?></td>
					<td colspan="2"><em><?php esc_html_e( 'Sin partidos registrados', 'sportcriss-lite' ); ?></em></td>
				</tr>
			<?php else :
				foreach ( $torneo_ids as $tid ) :
					if ( ! isset( $torneos_idx[ $tid ] ) ) continue;
					$torneo = $torneos_idx[ $tid ];
					$estado = scl_get_estado_temporada( $tid, $temp->term_id );
					$next   = 'activa' === $estado ? 'finalizada' : 'activa';
			?>
				<tr>
					<td><strong><?php echo esc_html( $temp->name ); ?></strong></td>
					<td><?php echo esc_html( $anio ); ?></td>
					<td><?php echo esc_html( $torneo->post_title ); ?></td>
					<td>
						<button type="button"
						        class="scl-btn scl-btn--sm <?php echo 'activa' === $estado ? 'scl-btn--outline' : 'scl-btn--ghost'; ?>"
						        onclick="scl_cambiar_estado_temporada(<?php echo esc_attr( $temp->term_id ); ?>, '<?php echo esc_attr( $next ); ?>', <?php echo esc_attr( $tid ); ?>)">
							<?php echo 'activa' === $estado
								? esc_html__( '→ Marcar finalizada', 'sportcriss-lite' )
								: esc_html__( '→ Reactivar',        'sportcriss-lite' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; endif; endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
