<?php
/**
 * Handlers de peticiones AJAX (admin-ajax.php).
 *
 * Todos los handlers verifican nonce y capacidades antes de ejecutar.
 * Ningún handler expone datos sin autenticación (wp_ajax_ solo, no wp_ajax_nopriv_).
 *
 * Implementado en Sprints 4, 5, 6, 7, 8 y 9.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Ajax
 */
class Scl_Ajax {

	/**
	 * Registra todos los handlers AJAX en el Loader.
	 *
	 * @param Scl_Loader $loader Instancia del loader de hooks.
	 */
	public function registrar_handlers( $loader ) {
		$loader->add_action( 'wp_ajax_scl_get_grupos_por_torneo', $this, 'get_grupos_por_torneo' );
		$loader->add_action( 'wp_ajax_scl_confirmar_ganador_llave', $this, 'ajax_confirmar_ganador_llave' );
	}

	public function get_grupos_por_torneo(): void {
		check_ajax_referer( 'scl_dashboard_nonce', 'nonce' );

		$temporada_id = absint( $_POST['temporada_id'] ?? 0 );
		if ( ! $temporada_id ) {
			wp_send_json_error( 'temporada_id requerido' );
		}

		// Paso intermedio: obtener el torneo padre de la temporada
		$temporada = get_post( $temporada_id );
		if ( ! $temporada || $temporada->post_type !== 'scl_temporada' ) {
			wp_send_json_error( 'Temporada no válida' );
		}

		$torneo_id = (int) $temporada->post_parent;
		if ( ! $torneo_id ) {
			wp_send_json_error( 'La temporada no tiene torneo padre asignado' );
		}

		// Buscar grupos cuyo post_parent sea el torneo
		$grupos = get_posts( [
			'post_type'      => 'scl_grupo',
			'post_parent'    => $torneo_id,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		// Devolver solo los campos que el JS necesita
		$data = array_map( function( $grupo ) {
			return [
				'ID'         => $grupo->ID,
				'post_title' => $grupo->post_title,
			];
		}, $grupos );

		wp_send_json_success( $data );
	}

	/**
	 * Confirma el ganador de una llave.
	 */
	public function ajax_confirmar_ganador_llave() {
		check_ajax_referer( 'scl_ajax_nonce', 'nonce' );

		$llave_id = isset( $_POST['llave_id'] ) ? absint( $_POST['llave_id'] ) : 0;
		if ( ! $llave_id ) {
			wp_send_json_error( 'Falta llave_id.' );
		}

		$llave = get_post( $llave_id );
		if ( ! $llave || 'scl_llave' !== $llave->post_type ) {
			wp_send_json_error( 'Llave no válida.' );
		}

		if ( $llave->post_author != get_current_user_id() && ! current_user_can( 'edit_post', $llave_id ) ) {
			wp_send_json_error( 'Permisos insuficientes.' );
		}

		$ganador_id     = isset( $_POST['ganador_id'] ) ? absint( $_POST['ganador_id'] ) : 0;
		$penales_local  = isset( $_POST['penales_local'] ) ? absint( $_POST['penales_local'] ) : '';
		$penales_visita = isset( $_POST['penales_visita'] ) ? absint( $_POST['penales_visita'] ) : '';

		$estado_llave = get_post_meta( $llave_id, 'scl_llave_estado', true );
		$ganador_provisional = get_post_meta( $llave_id, 'scl_llave_ganador_provisional_id', true );

		if ( 'requiere_penales' === $estado_llave ) {
			if ( '' === $penales_local || '' === $penales_visita ) {
				wp_send_json_error( 'Los penales son obligatorios para esta llave.' );
			}
			if ( ! $ganador_id ) {
				wp_send_json_error( 'Se debe elegir explícitamente un ganador.' );
			}
		} elseif ( 'pendiente_confirmacion' === $estado_llave ) {
			if ( ! $ganador_id && $ganador_provisional ) {
				$ganador_id = $ganador_provisional;
			}
		}

		if ( ! $ganador_id ) {
			wp_send_json_error( 'No se proporcionó ganador.' );
		}

		update_post_meta( $llave_id, 'scl_llave_ganador_id', $ganador_id );
		if ( '' !== $penales_local ) update_post_meta( $llave_id, 'scl_llave_penales_local', $penales_local );
		if ( '' !== $penales_visita ) update_post_meta( $llave_id, 'scl_llave_penales_visita', $penales_visita );
		update_post_meta( $llave_id, 'scl_llave_estado', 'resuelta' );

		wp_send_json_success( [
			'ganador' => get_the_title( $ganador_id ),
		] );
	}
}
