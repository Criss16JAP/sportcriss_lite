<?php
/**
 * Vistas públicas: archivo de torneos y singles de torneo/equipo.
 *
 * Implementado en Sprint 10.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Public
 */
class Scl_Public {

	/**
	 * Reemplaza el template de WordPress con las vistas propias del plugin.
	 * Callback del filter 'template_include'.
	 *
	 * @param string $template Ruta al template activo.
	 * @return string Ruta al template a usar.
	 */
	public function filtrar_template( $template ) {
		// TODO Sprint 10
		return $template;
	}

	/**
	 * Encola los assets de vistas públicas.
	 * Callback del hook 'wp_enqueue_scripts'.
	 */
	public function encolar_assets() {
		// TODO Sprint 10
	}
}
