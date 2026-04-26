<?php
/**
 * Template: Drawer de creación/edición de equipo.
 * Incluido por equipos-lista.php. Se muestra/oculta vía JS sin recargar la página.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="scl-drawer" id="scl_equipo_drawer">
	<div class="scl-drawer__overlay"></div>
	<div class="scl-drawer__panel">

		<div class="scl-drawer__header">
			<h3 id="scl_equipo_drawer_titulo">
				<?php esc_html_e( 'Nuevo Equipo', 'sportcriss-lite' ); ?>
			</h3>
			<button type="button" class="scl-drawer__close" id="scl_equipo_cerrar" aria-label="<?php esc_attr_e( 'Cerrar', 'sportcriss-lite' ); ?>">&#x2715;</button>
		</div>

		<div class="scl-drawer__body">
			<input type="hidden" id="scl_equipo_id" value="0">

			<!-- Escudo -->
			<div class="scl-field scl-field--centered">
				<div class="scl-escudo-preview" id="scl_escudo_preview">
					<div class="scl-escudo-placeholder" id="scl_escudo_placeholder">?</div>
					<img id="scl_escudo_img" src="" alt="" style="display:none">
				</div>
				<input type="file" id="scl_escudo_file"
				       accept="image/jpeg,image/png,image/webp,image/svg+xml"
				       style="display:none">
				<input type="hidden" id="scl_escudo_id" value="0">
				<button type="button" class="scl-btn scl-btn--outline scl-btn--sm" id="scl_escudo_btn">
					&#128247; <?php esc_html_e( 'Subir escudo', 'sportcriss-lite' ); ?>
				</button>
				<p class="scl-description" style="margin-top:0.5rem;font-size:0.8rem">
					<?php esc_html_e( 'JPG, PNG, WebP o SVG. Máximo 2MB.', 'sportcriss-lite' ); ?>
				</p>
			</div>

			<!-- Nombre -->
			<div class="scl-field">
				<label for="scl_equipo_nombre">
					<?php esc_html_e( 'Nombre del equipo', 'sportcriss-lite' ); ?> <span aria-hidden="true">*</span>
				</label>
				<input type="text" id="scl_equipo_nombre" maxlength="100"
				       placeholder="<?php esc_attr_e( 'Ej: San Pedro FC', 'sportcriss-lite' ); ?>">
			</div>

			<!-- Zona -->
			<div class="scl-field">
				<label for="scl_equipo_zona">
					<?php esc_html_e( 'Zona / Región', 'sportcriss-lite' ); ?>
				</label>
				<input type="text" id="scl_equipo_zona" maxlength="100"
				       placeholder="<?php esc_attr_e( 'Ej: Norte, Barranquilla, Zona Centro', 'sportcriss-lite' ); ?>">
			</div>
		</div>

		<div class="scl-drawer__footer">
			<button type="button" class="scl-btn scl-btn--ghost" id="scl_equipo_cancelar">
				<?php esc_html_e( 'Cancelar', 'sportcriss-lite' ); ?>
			</button>
			<button type="button" class="scl-btn scl-btn--primary" id="scl_equipo_guardar">
				<?php esc_html_e( 'Guardar equipo', 'sportcriss-lite' ); ?>
			</button>
		</div>

	</div>
</div>
