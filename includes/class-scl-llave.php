<?php
/**
 * Motor de llaves de playoff (ida y vuelta / partido único).
 *
 * Evalúa el agregado cuando ambos partidos de una llave están finalizados
 * y calcula el ganador provisional. El Organizador debe confirmar el ganador.
 *
 * Implementado en Sprint 2.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Llave
 */
class Scl_Llave {

	/**
	 * Evalúa si el partido guardado pertenece a una llave y calcula el agregado.
	 * Callback del hook 'save_post_scl_partido' (prioridad 20, después del motor).
	 *
	 * @param int     $post_id ID del partido.
	 * @param WP_Post $post    Objeto post.
	 */
	public function evaluar_llave( $post_id, $post ) {
		// TODO Sprint 2
	}
}
