<?php
/**
 * Exportación visual de tabla de posiciones.
 *
 * Genera una vista limpia (sin chrome del dashboard) con CSS variables
 * inyectadas desde la configuración del torneo, lista para pantallazo.
 *
 * Implementado en Sprint 9.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Export
 */
class Scl_Export {

	/**
	 * Sirve la vista de exportación limpia si la URL corresponde.
	 * Callback del hook 'template_redirect'.
	 */
	public function servir_vista_limpia() {
		// TODO Sprint 9
	}
}
