<?php
/**
 * Motor de cálculo de tabla de posiciones.
 *
 * Recalcula la tabla al guardar un partido finalizado en una fase
 * que suma puntos. Serializa el resultado en scl_temporada_tabla_cache.
 *
 * Implementado en Sprint 2.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Engine
 */
class Scl_Engine {

	/**
	 * Evalúa si debe recalcular la tabla al guardar un partido.
	 * Callback del hook 'save_post_scl_partido'.
	 *
	 * @param int     $post_id ID del partido.
	 * @param WP_Post $post    Objeto post.
	 */
	public function recalcular_si_aplica( $post_id, $post ) {
		// TODO Sprint 2
	}

	/**
	 * Recalcula la tabla de posiciones de una temporada completa.
	 * Puede invocarse externamente para recalculados manuales forzados.
	 *
	 * @param int $temporada_id ID del post scl_temporada.
	 */
	public function recalcular_tabla( $temporada_id ) {
		// TODO Sprint 2
	}
}
