<?php
/**
 * Template: Dashboard – Exportación Visual
 * Ruta: /mi-panel/?scl_ruta=exportar          → grid de torneos
 *       /mi-panel/?scl_ruta=exportar&scl_id=X → preview del torneo X
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( scl_es_colaborador() ) {
	include SCL_PATH . 'templates/dashboard/acceso-denegado.php';
	return;
}

$autor_ef  = scl_get_autor_efectivo();
$home_url  = home_url( '/mi-panel/' );
$torneo_id = (int) ( $_GET['scl_id'] ?? 0 );

/* ────────────────────────────────────────────────────────────────────────────
 * Sin torneo seleccionado → grid de torneos
 * ────────────────────────────────────────────────────────────────────────── */
if ( ! $torneo_id ) {
	$mis_torneos = get_posts( [
		'post_type'      => 'scl_torneo',
		'author'         => $autor_ef,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );
	?>
	<div class="scl-page-header">
		<h1 class="scl-page-title"><?php esc_html_e( 'Exportar Tabla de Posiciones', 'sportcriss-lite' ); ?></h1>
	</div>

	<div class="scl-form-section">
		<p class="scl-description">
			<?php esc_html_e( 'Selecciona el torneo que deseas exportar. Se abrirá una vista limpia lista para tomar captura de pantalla.', 'sportcriss-lite' ); ?>
		</p>

		<?php if ( empty( $mis_torneos ) ) : ?>
			<div class="scl-empty">
				<p><?php esc_html_e( 'No tienes torneos activos. Crea un torneo primero.', 'sportcriss-lite' ); ?></p>
			</div>
		<?php else : ?>
			<div class="scl-torneos-export-grid">
				<?php foreach ( $mis_torneos as $t ) :
					$logo_id  = (int) get_post_meta( $t->ID, 'scl_torneo_logo', true );
					$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
					$siglas   = get_post_meta( $t->ID, 'scl_torneo_siglas', true );
					$url      = add_query_arg( [ 'scl_ruta' => 'exportar', 'scl_id' => $t->ID ], $home_url );
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="scl-torneo-export-btn">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>"
						     alt="<?php echo esc_attr( $t->post_title ); ?>">
					<?php else : ?>
						<span class="scl-siglas-placeholder">
							<?php echo esc_html( $siglas ?: strtoupper( mb_substr( $t->post_title, 0, 3 ) ) ); ?>
						</span>
					<?php endif; ?>
					<span><?php echo esc_html( $t->post_title ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Con torneo seleccionado → preview + controles
 * ────────────────────────────────────────────────────────────────────────── */
$torneo = get_post( $torneo_id );
if ( ! $torneo || ( (int) $torneo->post_author !== $autor_ef && ! current_user_can( 'manage_options' ) ) ) {
	echo '<div class="scl-empty"><p>' . esc_html__( 'Torneo no encontrado.', 'sportcriss-lite' ) . '</p></div>';
	return;
}

// Temporadas usadas en partidos de este torneo
$partidos_ids = get_posts( [
	'post_type'      => 'scl_partido',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => [ [
		'key'   => 'scl_partido_torneo_id',
		'value' => $torneo_id,
		'type'  => 'NUMERIC',
	] ],
] );

$temporadas_usadas = [];
foreach ( $partidos_ids as $pid ) {
	$terms = wp_get_post_terms( $pid, 'scl_temporada', [ 'fields' => 'ids' ] );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $tid ) {
			$temporadas_usadas[ $tid ] = true;
		}
	}
}
$temporadas = array_filter(
	array_map( fn( $id ) => get_term( $id, 'scl_temporada' ), array_keys( $temporadas_usadas ) ),
	fn( $t ) => $t && ! is_wp_error( $t )
);

// Grupos del torneo
$grupos = scl_get_grupos_por_torneo( $torneo_id );

// Selección actual
$temp_sel  = (int) ( $_GET['scl_temporada'] ?? ( $temporadas ? reset( $temporadas )->term_id : 0 ) );
$grupo_sel = (int) ( $_GET['scl_grupo'] ?? 0 );

// URL de exportación limpia
$export_url = Scl_Export::get_url( $torneo_id, $temp_sel, $grupo_sel );

$logo_id = (int) get_post_meta( $torneo_id, 'scl_torneo_logo', true );
?>

<div class="scl-exportar-header">
	<div class="scl-exportar-header__info">
		<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'exportar', $home_url ) ); ?>"
		   class="scl-back-link">← <?php esc_html_e( 'Mis torneos', 'sportcriss-lite' ); ?></a>
		<h2>
			<?php if ( $logo_id ) : ?>
				<img src="<?php echo esc_url( wp_get_attachment_image_url( $logo_id, 'thumbnail' ) ); ?>"
				     alt=""
				     class="scl-export-mini-logo">
			<?php endif; ?>
			<?php echo esc_html( $torneo->post_title ); ?>
		</h2>
	</div>
	<a href="#"
	   target="_blank"
	   rel="noopener"
	   class="scl-btn scl-btn--primary"
	   id="scl_abrir_export">
		🖼 <?php esc_html_e( 'Abrir para pantallazo', 'sportcriss-lite' ); ?>
	</a>
</div>

<?php
// URLs base para cada tipo de exportación
$export_url_tabla    = Scl_Export::get_url( $torneo_id, $temp_sel, $grupo_sel );
$export_url_stats    = Scl_Export::get_url_stats( $torneo_id, $temp_sel, 'goleadores', 10 );
$export_url_partidos = Scl_Export::get_url_partidos( $torneo_id, $temp_sel );
?>

<!-- Tabs de tipo de exportación -->
<div class="scl-tabs scl-exportar-tabs">
	<button type="button" class="scl-tab scl-tab--active" data-tab="tabla">
		<?php esc_html_e( 'Tabla de posiciones', 'sportcriss-lite' ); ?>
	</button>
	<button type="button" class="scl-tab" data-tab="stats">
		<?php esc_html_e( 'Estadísticas', 'sportcriss-lite' ); ?>
	</button>
	<button type="button" class="scl-tab" data-tab="partidos">
		<?php esc_html_e( 'Resultados', 'sportcriss-lite' ); ?>
	</button>
</div>

<!-- ── TAB: TABLA ─────────────────────────────────────────────── -->
<div class="scl-exportar-tab-panel" id="scl_exp_panel_tabla">
	<div class="scl-exportar-controles scl-form-section">
		<div class="scl-field-row">
			<div class="scl-field">
				<label><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></label>
				<select id="scl_export_temporada">
					<option value="0"><?php esc_html_e( '— Todas —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $temporadas as $t ) : ?>
						<option value="<?php echo esc_attr( $t->term_id ); ?>"
						        <?php selected( $temp_sel, $t->term_id ); ?>>
							<?php echo esc_html( $t->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( ! empty( $grupos ) ) : ?>
			<div class="scl-field">
				<label><?php esc_html_e( 'Grupo', 'sportcriss-lite' ); ?></label>
				<select id="scl_export_grupo">
					<option value="0"><?php esc_html_e( 'General (todos los grupos)', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $grupos as $g ) : ?>
						<option value="<?php echo esc_attr( $g->ID ); ?>"
						        <?php selected( $grupo_sel, $g->ID ); ?>>
							<?php echo esc_html( $g->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>

			<div class="scl-field scl-field--auto">
				<label>&nbsp;</label>
				<button type="button" class="scl-btn scl-btn--outline" id="scl_export_actualizar">
					<?php esc_html_e( 'Actualizar vista', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="scl-exportar-preview">
		<div class="scl-exportar-preview__label">
			<?php esc_html_e( 'Vista previa — La imagen final puede variar levemente según el navegador', 'sportcriss-lite' ); ?>
		</div>
		<div class="scl-exportar-preview__frame-wrap">
			<iframe id="scl_export_frame"
			        src="<?php echo esc_url( $export_url_tabla ); ?>"
			        class="scl-exportar-iframe"
			        scrolling="no"></iframe>
		</div>
		<div class="scl-exportar-preview__instrucciones">
			<p>💡 <strong><?php esc_html_e( '¿Cómo tomar el pantallazo?', 'sportcriss-lite' ); ?></strong>
			<?php esc_html_e( 'Haz clic en "Abrir para pantallazo" → pestaña limpia → Cmd+Shift+4 (Mac) o Win+Shift+S (Windows).', 'sportcriss-lite' ); ?></p>
		</div>
	</div>
</div>

<!-- ── TAB: ESTADÍSTICAS ─────────────────────────────────────── -->
<div class="scl-exportar-tab-panel" id="scl_exp_panel_stats" style="display:none;">
	<div class="scl-exportar-controles scl-form-section">
		<div class="scl-field-row">
			<div class="scl-field">
				<label><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></label>
				<select id="scl_stats_temporada">
					<option value="0"><?php esc_html_e( '— Todas —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $temporadas as $t ) : ?>
						<option value="<?php echo esc_attr( $t->term_id ); ?>"
						        <?php selected( $temp_sel, $t->term_id ); ?>>
							<?php echo esc_html( $t->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="scl-field">
				<label><?php esc_html_e( 'Estadística', 'sportcriss-lite' ); ?></label>
				<select id="scl_stats_tipo">
					<option value="goleadores"><?php esc_html_e( 'Goleadores', 'sportcriss-lite' ); ?></option>
					<option value="asistencias"><?php esc_html_e( 'Asistencias', 'sportcriss-lite' ); ?></option>
					<option value="tarjetas_amarillas"><?php esc_html_e( 'Tarjetas Amarillas', 'sportcriss-lite' ); ?></option>
					<option value="tarjetas_rojas"><?php esc_html_e( 'Tarjetas Rojas', 'sportcriss-lite' ); ?></option>
					<option value="calificaciones"><?php esc_html_e( 'Calificaciones', 'sportcriss-lite' ); ?></option>
				</select>
			</div>
			<div class="scl-field" style="max-width:100px;">
				<label><?php esc_html_e( 'Límite', 'sportcriss-lite' ); ?></label>
				<select id="scl_stats_limite">
					<option value="5">5</option>
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="20">20</option>
				</select>
			</div>
			<div class="scl-field scl-field--auto">
				<label>&nbsp;</label>
				<button type="button" class="scl-btn scl-btn--outline" id="scl_stats_actualizar">
					<?php esc_html_e( 'Actualizar vista', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="scl-exportar-preview">
		<div class="scl-exportar-preview__label">
			<?php esc_html_e( 'Vista previa de estadísticas', 'sportcriss-lite' ); ?>
		</div>
		<div class="scl-exportar-preview__frame-wrap">
			<iframe id="scl_stats_frame"
			        src="<?php echo esc_url( $export_url_stats ); ?>"
			        class="scl-exportar-iframe"
			        scrolling="no"></iframe>
		</div>
	</div>
</div>

<!-- ── TAB: RESULTADOS/PARTIDOS ──────────────────────────────── -->
<div class="scl-exportar-tab-panel" id="scl_exp_panel_partidos" style="display:none;">
	<div class="scl-exportar-controles scl-form-section">
		<div class="scl-field-row">
			<div class="scl-field">
				<label><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></label>
				<select id="scl_partidos_temporada">
					<option value="0"><?php esc_html_e( '— Todas —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $temporadas as $t ) : ?>
						<option value="<?php echo esc_attr( $t->term_id ); ?>"
						        <?php selected( $temp_sel, $t->term_id ); ?>>
							<?php echo esc_html( $t->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="scl-field scl-field--auto">
				<label>&nbsp;</label>
				<button type="button" class="scl-btn scl-btn--outline" id="scl_partidos_actualizar">
					<?php esc_html_e( 'Actualizar vista', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="scl-exportar-preview">
		<div class="scl-exportar-preview__label">
			<?php esc_html_e( 'Vista previa de resultados', 'sportcriss-lite' ); ?>
		</div>
		<div class="scl-exportar-preview__frame-wrap">
			<iframe id="scl_partidos_frame"
			        src="<?php echo esc_url( $export_url_partidos ); ?>"
			        class="scl-exportar-iframe"
			        scrolling="no"></iframe>
		</div>
	</div>
</div>

<script>
var scl_export_torneo_id = <?php echo (int) $torneo_id; ?>;
var scl_export_base_urls = {
	tabla:    <?php echo wp_json_encode( $export_url_tabla ); ?>,
	stats:    <?php echo wp_json_encode( $export_url_stats ); ?>,
	partidos: <?php echo wp_json_encode( $export_url_partidos ); ?>,
};
</script>
