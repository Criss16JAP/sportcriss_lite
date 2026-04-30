<?php
/**
 * Template: Dashboard – Importador CSV
 * Ruta: /mi-panel/?scl_ruta=importar
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( scl_es_colaborador() ) {
	include SCL_PATH . 'templates/dashboard/acceso-denegado.php';
	return;
}

$home_url      = home_url( '/mi-panel/' );
$plantilla_url = add_query_arg( [
	'action' => 'scl_descargar_plantilla',
	'nonce'  => wp_create_nonce( 'scl_dashboard_nonce' ),
], admin_url( 'admin-ajax.php' ) );
$plantilla_jugadores_url = add_query_arg( [
	'action' => 'scl_descargar_plantilla_jugadores',
	'nonce'  => wp_create_nonce( 'scl_dashboard_nonce' ),
], admin_url( 'admin-ajax.php' ) );
$max_filas = Scl_Importer::MAX_FILAS;
?>

<!-- Selector de pestaña -->
<div class="scl-page-header" style="margin-bottom:0">
	<h1 class="scl-page-title"><?php esc_html_e( 'Importar desde CSV', 'sportcriss-lite' ); ?></h1>
</div>

<div class="scl-tabs" id="scl-importer-tabs" style="margin-bottom:1.5rem;">
	<button type="button" class="scl-tab scl-tab--active" data-tab="partidos">
		<?php esc_html_e( 'Partidos', 'sportcriss-lite' ); ?>
	</button>
	<button type="button" class="scl-tab" data-tab="jugadores">
		<?php esc_html_e( 'Jugadores', 'sportcriss-lite' ); ?>
	</button>
</div>

<!-- ═══════════════════════════ PESTAÑA: PARTIDOS ═══════════════════════════ -->
<div id="scl-tab-partidos">

<!-- Paso 1: Subir archivo -->
<div id="scl-importer-step-1">
	<div class="scl-form-section">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
			<p class="scl-description" style="margin:0">
				<?php esc_html_e( 'Sube un archivo CSV con tus partidos. El torneo debe existir previamente.', 'sportcriss-lite' ); ?>
			</p>
			<a href="<?php echo esc_url( $plantilla_url ); ?>" class="scl-btn scl-btn--secondary">
				<?php esc_html_e( '↓ Plantilla', 'sportcriss-lite' ); ?>
			</a>
		</div>

		<div id="scl-csv-dropzone" class="scl-dropzone" role="button" tabindex="0">
			<div class="scl-dropzone__icon">📂</div>
			<p class="scl-dropzone__text">
				<?php esc_html_e( 'Arrastra tu archivo CSV aquí o', 'sportcriss-lite' ); ?>
				<span class="scl-link"><?php esc_html_e( 'haz clic para seleccionar', 'sportcriss-lite' ); ?></span>
			</p>
			<p class="scl-dropzone__hint">
				<?php printf( esc_html__( 'Máximo %d filas · Solo archivos .csv', 'sportcriss-lite' ), (int) $max_filas ); ?>
			</p>
		</div>
		<input type="file" id="scl-csv-file" accept=".csv,text/csv" style="display:none;">
	</div>
</div>

<!-- Paso 2: Validación -->
<div id="scl-importer-step-2" style="display:none;">
	<div class="scl-form-section">
		<div class="scl-cards-row scl-cards-row--compact">
			<div class="scl-card"><div class="scl-card__value" id="scl-val-total">0</div><div class="scl-card__label"><?php esc_html_e( 'Total de filas', 'sportcriss-lite' ); ?></div></div>
			<div class="scl-card scl-card--ok"><div class="scl-card__value" id="scl-val-validas">0</div><div class="scl-card__label"><?php esc_html_e( 'Filas válidas', 'sportcriss-lite' ); ?></div></div>
			<div class="scl-card scl-card--warn"><div class="scl-card__value" id="scl-val-errores">0</div><div class="scl-card__label"><?php esc_html_e( 'Filas con error', 'sportcriss-lite' ); ?></div></div>
		</div>
		<div class="scl-info-box scl-info-box--visible" style="margin-top:1rem;">
			<strong><?php esc_html_e( 'Entidades a crear automáticamente:', 'sportcriss-lite' ); ?></strong>
			<ul id="scl-nuevos-lista" style="margin:0.5rem 0 0;padding-left:1.25rem;"></ul>
		</div>
		<div id="scl-errores-bloque" class="scl-alert scl-alert--danger" style="display:none;margin-top:1rem;">
			<strong><?php esc_html_e( 'Errores encontrados — las filas con error serán omitidas:', 'sportcriss-lite' ); ?></strong>
			<ul id="scl-errores-lista" style="margin:0.5rem 0 0;padding-left:1.25rem;max-height:200px;overflow-y:auto;"></ul>
		</div>
	</div>
	<div class="scl-form-section">
		<h3><?php esc_html_e( 'Vista previa — primeras 5 filas', 'sportcriss-lite' ); ?></h3>
		<div class="scl-table-scroll">
			<table class="scl-table scl-table--sm">
				<thead><tr>
					<th><?php esc_html_e( 'Torneo',    'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Fase',      'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Jornada',   'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Grupo',     'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Fecha',     'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Local',     'sportcriss-lite' ); ?></th>
					<th>GL</th><th>GV</th>
					<th><?php esc_html_e( 'Visitante', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Estado',    'sportcriss-lite' ); ?></th>
				</tr></thead>
				<tbody id="scl-preview-tbody"></tbody>
			</table>
		</div>
		<div class="scl-form-actions" style="margin-top:1.5rem;">
			<button type="button" id="scl-cambiar-archivo" class="scl-btn scl-btn--outline">← <?php esc_html_e( 'Cambiar archivo', 'sportcriss-lite' ); ?></button>
			<button type="button" id="scl-importar-btn" class="scl-btn scl-btn--primary"><?php esc_html_e( 'Importar partidos', 'sportcriss-lite' ); ?></button>
		</div>
	</div>
</div>

<!-- Paso 3: Resultado -->
<div id="scl-importer-step-3" style="display:none;">
	<div class="scl-form-section scl-importer-resultado">
		<div class="scl-success-icon">✓</div>
		<div class="scl-cards-row scl-cards-row--compact">
			<div class="scl-card scl-card--ok"><div class="scl-card__value" id="scl-res-creados">0</div><div class="scl-card__label"><?php esc_html_e( 'Partidos creados', 'sportcriss-lite' ); ?></div></div>
			<div class="scl-card"><div class="scl-card__value" id="scl-res-omitidos">0</div><div class="scl-card__label"><?php esc_html_e( 'Filas omitidas', 'sportcriss-lite' ); ?></div></div>
		</div>
		<div id="scl-res-errores-bloque" style="display:none;margin-top:1rem;">
			<strong><?php esc_html_e( 'Filas no importadas:', 'sportcriss-lite' ); ?></strong>
			<ul id="scl-res-errores-lista" style="padding-left:1.25rem;margin:0.5rem 0 0;"></ul>
		</div>
		<div class="scl-form-actions" style="margin-top:1.5rem;">
			<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'partidos', $home_url ) ); ?>" class="scl-btn scl-btn--primary"><?php esc_html_e( 'Ver partidos', 'sportcriss-lite' ); ?></a>
			<button type="button" id="scl-otro-csv" class="scl-btn scl-btn--outline"><?php esc_html_e( 'Importar otro CSV', 'sportcriss-lite' ); ?></button>
		</div>
	</div>
</div>

</div><!-- /scl-tab-partidos -->

<!-- ═══════════════════════════ PESTAÑA: JUGADORES ══════════════════════════ -->
<div id="scl-tab-jugadores" style="display:none;">

<!-- Paso 1J: Subir archivo jugadores -->
<div id="scl-jug-step-1">
	<div class="scl-form-section">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
			<p class="scl-description" style="margin:0">
				<?php esc_html_e( 'Columnas requeridas: nombre, apellido, posicion, equipo, torneo, temporada. Si torneo y temporada están vacíos, solo se crea el jugador sin inscripción.', 'sportcriss-lite' ); ?>
			</p>
			<a href="<?php echo esc_url( $plantilla_jugadores_url ); ?>" class="scl-btn scl-btn--secondary">
				<?php esc_html_e( '↓ Plantilla', 'sportcriss-lite' ); ?>
			</a>
		</div>

		<div id="scl-jug-dropzone" class="scl-dropzone" role="button" tabindex="0">
			<div class="scl-dropzone__icon">👤</div>
			<p class="scl-dropzone__text">
				<?php esc_html_e( 'Arrastra tu CSV de jugadores aquí o', 'sportcriss-lite' ); ?>
				<span class="scl-link"><?php esc_html_e( 'haz clic para seleccionar', 'sportcriss-lite' ); ?></span>
			</p>
			<p class="scl-dropzone__hint">
				<?php printf( esc_html__( 'Máximo %d filas · Solo archivos .csv', 'sportcriss-lite' ), (int) $max_filas ); ?>
			</p>
		</div>
		<input type="file" id="scl-jug-file" accept=".csv,text/csv" style="display:none;">
	</div>
</div>

<!-- Paso 2J: Validación jugadores -->
<div id="scl-jug-step-2" style="display:none;">
	<div class="scl-form-section">
		<div class="scl-cards-row scl-cards-row--compact">
			<div class="scl-card"><div class="scl-card__value" id="scl-jug-val-total">0</div><div class="scl-card__label"><?php esc_html_e( 'Total de filas', 'sportcriss-lite' ); ?></div></div>
			<div class="scl-card scl-card--ok"><div class="scl-card__value" id="scl-jug-val-validas">0</div><div class="scl-card__label"><?php esc_html_e( 'Filas válidas', 'sportcriss-lite' ); ?></div></div>
			<div class="scl-card scl-card--warn"><div class="scl-card__value" id="scl-jug-val-errores">0</div><div class="scl-card__label"><?php esc_html_e( 'Filas con error', 'sportcriss-lite' ); ?></div></div>
		</div>
		<div class="scl-info-box scl-info-box--visible" style="margin-top:1rem;">
			<strong><?php esc_html_e( 'Resumen de operaciones:', 'sportcriss-lite' ); ?></strong>
			<ul id="scl-jug-nuevos-lista" style="margin:0.5rem 0 0;padding-left:1.25rem;"></ul>
		</div>
		<div id="scl-jug-errores-bloque" class="scl-alert scl-alert--danger" style="display:none;margin-top:1rem;">
			<strong><?php esc_html_e( 'Errores — las filas con error serán omitidas:', 'sportcriss-lite' ); ?></strong>
			<ul id="scl-jug-errores-lista" style="margin:0.5rem 0 0;padding-left:1.25rem;max-height:200px;overflow-y:auto;"></ul>
		</div>
	</div>
	<div class="scl-form-section">
		<h3><?php esc_html_e( 'Vista previa — primeras 5 filas', 'sportcriss-lite' ); ?></h3>
		<div class="scl-table-scroll">
			<table class="scl-table scl-table--sm">
				<thead><tr>
					<th><?php esc_html_e( 'Nombre',   'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Apellido', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Posición', 'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Equipo',   'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Torneo',   'sportcriss-lite' ); ?></th>
					<th><?php esc_html_e( 'Temporada','sportcriss-lite' ); ?></th>
				</tr></thead>
				<tbody id="scl-jug-preview-tbody"></tbody>
			</table>
		</div>
		<div class="scl-form-actions" style="margin-top:1.5rem;">
			<button type="button" id="scl-jug-cambiar-archivo" class="scl-btn scl-btn--outline">← <?php esc_html_e( 'Cambiar archivo', 'sportcriss-lite' ); ?></button>
			<button type="button" id="scl-jug-importar-btn" class="scl-btn scl-btn--primary"><?php esc_html_e( 'Importar jugadores', 'sportcriss-lite' ); ?></button>
		</div>
	</div>
</div>

<!-- Paso 3J: Resultado jugadores -->
<div id="scl-jug-step-3" style="display:none;">
	<div class="scl-form-section scl-importer-resultado">
		<div class="scl-success-icon">✓</div>
		<div class="scl-cards-row scl-cards-row--compact">
			<div class="scl-card scl-card--ok"><div class="scl-card__value" id="scl-jug-res-creados">0</div><div class="scl-card__label"><?php esc_html_e( 'Jugadores procesados', 'sportcriss-lite' ); ?></div></div>
			<div class="scl-card"><div class="scl-card__value" id="scl-jug-res-inscripciones">0</div><div class="scl-card__label"><?php esc_html_e( 'Inscripciones creadas', 'sportcriss-lite' ); ?></div></div>
			<div class="scl-card"><div class="scl-card__value" id="scl-jug-res-omitidos">0</div><div class="scl-card__label"><?php esc_html_e( 'Filas omitidas', 'sportcriss-lite' ); ?></div></div>
		</div>
		<div id="scl-jug-res-errores-bloque" style="display:none;margin-top:1rem;">
			<strong><?php esc_html_e( 'Filas no importadas:', 'sportcriss-lite' ); ?></strong>
			<ul id="scl-jug-res-errores-lista" style="padding-left:1.25rem;margin:0.5rem 0 0;"></ul>
		</div>
		<div class="scl-form-actions" style="margin-top:1.5rem;">
			<a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'jugadores', $home_url ) ); ?>" class="scl-btn scl-btn--primary"><?php esc_html_e( 'Ver jugadores', 'sportcriss-lite' ); ?></a>
			<button type="button" id="scl-jug-otro-csv" class="scl-btn scl-btn--outline"><?php esc_html_e( 'Importar otro CSV', 'sportcriss-lite' ); ?></button>
		</div>
	</div>
</div>

</div><!-- /scl-tab-jugadores -->

<script>
(function() {
	var tabs = document.querySelectorAll('#scl-importer-tabs .scl-tab');
	tabs.forEach(function(btn) {
		btn.addEventListener('click', function() {
			tabs.forEach(function(t) { t.classList.remove('scl-tab--active'); });
			btn.classList.add('scl-tab--active');
			document.getElementById('scl-tab-partidos').style.display  = btn.dataset.tab === 'partidos'  ? '' : 'none';
			document.getElementById('scl-tab-jugadores').style.display = btn.dataset.tab === 'jugadores' ? '' : 'none';
		});
	});
})();
</script>
