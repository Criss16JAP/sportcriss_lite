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
if ( ! $torneo || (int) $torneo->post_author !== $autor_ef ) {
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
	<a href="<?php echo esc_url( $export_url ); ?>"
	   target="_blank"
	   rel="noopener"
	   class="scl-btn scl-btn--primary"
	   id="scl_abrir_export">
		🖼 <?php esc_html_e( 'Abrir para pantallazo', 'sportcriss-lite' ); ?>
	</a>
</div>

<!-- Controles de filtro -->
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

<!-- Preview embebida -->
<div class="scl-exportar-preview">
	<div class="scl-exportar-preview__label">
		<?php esc_html_e( 'Vista previa — La imagen final puede variar levemente según el navegador', 'sportcriss-lite' ); ?>
	</div>
	<div class="scl-exportar-preview__frame-wrap">
		<iframe id="scl_export_frame"
		        src="<?php echo esc_url( $export_url ); ?>"
		        class="scl-exportar-iframe"
		        scrolling="no"></iframe>
	</div>
	<div class="scl-exportar-preview__instrucciones">
		<p>
			💡 <strong><?php esc_html_e( '¿Cómo tomar el pantallazo?', 'sportcriss-lite' ); ?></strong>
			<?php esc_html_e( 'Haz clic en "Abrir para pantallazo" → se abre en una pestaña limpia →', 'sportcriss-lite' ); ?>
			<?php esc_html_e( 'usa', 'sportcriss-lite' ); ?> <kbd>Cmd+Shift+4</kbd> (Mac) <?php esc_html_e( 'o', 'sportcriss-lite' ); ?> <kbd>Win+Shift+S</kbd> (Windows)
			<?php esc_html_e( 'para capturar la tabla.', 'sportcriss-lite' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'También puedes usar la extensión', 'sportcriss-lite' ); ?>
			<strong>GoFullPage</strong>
			<?php esc_html_e( 'en Chrome para capturar la página completa automáticamente.', 'sportcriss-lite' ); ?>
		</p>
	</div>
</div>

<script>
/* Datos para el JS de exportación */
var scl_export_torneo_id = <?php echo (int) $torneo_id; ?>;
</script>
