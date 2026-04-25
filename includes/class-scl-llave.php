<?php
/**
 * Motor de Llaves de Playoff.
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
	 * Evalúa si una llave está completa tras guardar un partido y calcula el ganador provisional.
	 * Se engancha en save_post_scl_partido con prioridad 30 (después de Scl_Engine).
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function evaluar_llave( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$llave_id = get_post_meta( $post_id, 'scl_partido_llave_id', true );
		if ( empty( $llave_id ) ) {
			return;
		}

		$llave = get_post( $llave_id );
		if ( ! $llave || 'scl_llave' !== $llave->post_type ) {
			return;
		}

		$es_doble = (bool) get_post_meta( $llave_id, 'scl_llave_es_doble', true );
		
		if ( ! $es_doble ) {
			// Partido único
			$estado = get_post_meta( $post_id, 'scl_partido_estado', true );
			if ( 'finalizado' === $estado ) {
				$gl = get_post_meta( $post_id, 'scl_partido_goles_local', true );
				$gv = get_post_meta( $post_id, 'scl_partido_goles_visita', true );
				$local_id = (int) get_post_meta( $post_id, 'scl_partido_equipo_local_id', true );
				$visita_id = (int) get_post_meta( $post_id, 'scl_partido_equipo_visita_id', true );
				
				if ( $gl === $gv ) {
					update_post_meta( $llave_id, 'scl_llave_estado', 'requiere_penales' );
					delete_post_meta( $llave_id, 'scl_llave_ganador_provisional_id' );
				} else {
					$ganador_provisional = ( $gl > $gv ) ? $local_id : $visita_id;
					update_post_meta( $llave_id, 'scl_llave_ganador_provisional_id', $ganador_provisional );
					update_post_meta( $llave_id, 'scl_llave_estado', 'pendiente_confirmacion' );
				}
			}
		} else {
			// Ida y vuelta
			$ida_id = get_post_meta( $llave_id, 'scl_llave_partido_ida_id', true );
			$vuelta_id = get_post_meta( $llave_id, 'scl_llave_partido_vuelta_id', true );

			if ( ! $ida_id || ! $vuelta_id ) {
				return;
			}

			$estado_ida = get_post_meta( $ida_id, 'scl_partido_estado', true );
			$estado_vuelta = get_post_meta( $vuelta_id, 'scl_partido_estado', true );

			if ( 'finalizado' !== $estado_ida || 'finalizado' !== $estado_vuelta ) {
				return;
			}

			// Ambos partidos finalizados, calcular agregado
			$equipo_a_id = (int) get_post_meta( $ida_id, 'scl_partido_equipo_local_id', true );
			$equipo_b_id = (int) get_post_meta( $ida_id, 'scl_partido_equipo_visita_id', true );

			$gl_ida = (int) get_post_meta( $ida_id, 'scl_partido_goles_local', true );
			$gv_ida = (int) get_post_meta( $ida_id, 'scl_partido_goles_visita', true );

			$gl_vuelta = (int) get_post_meta( $vuelta_id, 'scl_partido_goles_local', true );
			$gv_vuelta = (int) get_post_meta( $vuelta_id, 'scl_partido_goles_visita', true );

			// agregado_A = goles local ida + goles visita vuelta
			$agregado_a = $gl_ida + $gv_vuelta;
			// agregado_B = goles visita ida + goles local vuelta
			$agregado_b = $gv_ida + $gl_vuelta;

			if ( $agregado_a > $agregado_b ) {
				update_post_meta( $llave_id, 'scl_llave_ganador_provisional_id', $equipo_a_id );
				update_post_meta( $llave_id, 'scl_llave_estado', 'pendiente_confirmacion' );
			} elseif ( $agregado_b > $agregado_a ) {
				update_post_meta( $llave_id, 'scl_llave_ganador_provisional_id', $equipo_b_id );
				update_post_meta( $llave_id, 'scl_llave_estado', 'pendiente_confirmacion' );
			} else {
				update_post_meta( $llave_id, 'scl_llave_estado', 'requiere_penales' );
				delete_post_meta( $llave_id, 'scl_llave_ganador_provisional_id' );
			}
		}
	}
}
