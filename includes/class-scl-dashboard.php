<?php
/**
 * Controlador del dashboard frontend privado.
 *
 * Gestiona el routing interno por query vars, verifica sesión y licencia,
 * y despacha el template correcto según la ruta activa.
 *
 * Implementado en Sprint 3.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Dashboard
 */
class Scl_Dashboard {

	/**
	 * Registra las query vars personalizadas para el routing interno.
	 * Callback del hook 'init'.
	 */
	public function registrar_query_vars() {
		// TODO Sprint 3
	}

	/**
	 * Determina la ruta activa y despacha el template correspondiente.
	 * Callback del hook 'template_redirect'.
	 */
	public function despachar() {
		// TODO Sprint 3
	}

	/**
	 * Encola los assets del dashboard solo en la página del panel.
	 * Callback del hook 'wp_enqueue_scripts'.
	 */
	public function encolar_assets() {
		// TODO Sprint 3
	}
}
