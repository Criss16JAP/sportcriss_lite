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
		$loader->add_action( 'wp_ajax_scl_get_grupos_por_torneo', [ $this, 'get_grupos_por_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_crear_torneo', [ $this, 'ajax_crear_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_editar_torneo', [ $this, 'ajax_editar_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_crear_temporada', [ $this, 'ajax_crear_temporada' ] );
		$loader->add_action( 'wp_ajax_scl_editar_temporada', [ $this, 'ajax_editar_temporada' ] );
		$loader->add_action( 'wp_ajax_scl_confirmar_ganador_llave', [ $this, 'ajax_confirmar_ganador_llave' ] );
	}

	public function get_grupos_por_torneo(): void {
		check_ajax_referer( 'scl_dashboard_nonce', 'nonce' );

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		if ( ! $torneo_id ) {
			wp_send_json_error( 'torneo_id requerido' );
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



	public function ajax_crear_torneo() {
		check_ajax_referer( 'scl_dashboard_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Sin permisos' );

		$titulo = sanitize_text_field( $_POST['titulo'] ?? '' );
		if ( ! $titulo ) wp_send_json_error( 'El título es requerido' );

		$post_id = wp_insert_post( [
			'post_type'   => 'scl_torneo',
			'post_title'  => $titulo,
			'post_status' => 'publish',
			'post_author' => get_current_user_id()
		] );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( 'Error al crear el torneo' );
		}

		$this->guardar_metas_torneo( $post_id );

		wp_send_json_success( [ 'id' => $post_id, 'mensaje' => 'Torneo creado con éxito' ] );
	}

	public function ajax_editar_torneo() {
		check_ajax_referer( 'scl_dashboard_nonce', 'nonce' );

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$post = get_post( $torneo_id );
		
		if ( ! $post || 'scl_torneo' !== $post->post_type || $post->post_author != get_current_user_id() ) {
			wp_send_json_error( 'Torneo no válido' );
		}

		$titulo = sanitize_text_field( $_POST['titulo'] ?? '' );
		if ( ! $titulo ) wp_send_json_error( 'El título es requerido' );

		wp_update_post( [
			'ID'         => $torneo_id,
			'post_title' => $titulo
		] );

		$this->guardar_metas_torneo( $torneo_id );

		wp_send_json_success( [ 'id' => $torneo_id, 'mensaje' => 'Torneo actualizado con éxito' ] );
	}

	private function guardar_metas_torneo( $post_id ) {
		update_post_meta( $post_id, 'scl_torneo_puntos_victoria', absint( $_POST['puntos_victoria'] ?? 3 ) );
		update_post_meta( $post_id, 'scl_torneo_puntos_empate', absint( $_POST['puntos_empate'] ?? 1 ) );
		update_post_meta( $post_id, 'scl_torneo_puntos_derrota', absint( $_POST['puntos_derrota'] ?? 0 ) );
		
		$color_primario = sanitize_hex_color( $_POST['color_primario'] ?? '#1a2b3c' );
		if ( $color_primario ) update_post_meta( $post_id, 'scl_torneo_color_primario', $color_primario );
		
		$color_secundario = sanitize_hex_color( $_POST['color_secundario'] ?? '#ffffff' );
		if ( $color_secundario ) update_post_meta( $post_id, 'scl_torneo_color_secundario', $color_secundario );
	}

	public function ajax_crear_temporada() {
		check_ajax_referer( 'scl_dashboard_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Sin permisos' );

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$torneo = get_post( $torneo_id );
		if ( ! $torneo || 'scl_torneo' !== $torneo->post_type || $torneo->post_author != get_current_user_id() ) {
			wp_send_json_error( 'Torneo no válido' );
		}

		$titulo = sanitize_text_field( $_POST['titulo'] ?? '' );
		if ( ! $titulo ) wp_send_json_error( 'El título es requerido' );

		$term = wp_insert_term( $titulo, 'scl_temporada' );
		if ( is_wp_error( $term ) ) {
			wp_send_json_error( 'Error al crear la temporada: ' . $term->get_error_message() );
		}

		$term_id = $term['term_id'];

		$this->guardar_metas_temporada( $term_id, $torneo_id );

		wp_send_json_success( [ 'id' => $term_id, 'mensaje' => 'Temporada creada con éxito' ] );
	}

	public function ajax_editar_temporada() {
		check_ajax_referer( 'scl_dashboard_nonce', 'nonce' );

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$torneo = get_post( $torneo_id );
		if ( ! $torneo || 'scl_torneo' !== $torneo->post_type || $torneo->post_author != get_current_user_id() ) {
			wp_send_json_error( 'Torneo no válido' );
		}

		$term_id = absint( $_POST['temporada_id'] ?? 0 );
		$term = get_term( $term_id, 'scl_temporada' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( 'Temporada no válida' );
		}

		$term_torneo_id = (int) get_term_meta( $term_id, 'scl_temporada_torneo_id', true );
		if ( $term_torneo_id !== $torneo_id ) {
			wp_send_json_error( 'La temporada no pertenece al torneo' );
		}

		$titulo = sanitize_text_field( $_POST['titulo'] ?? '' );
		if ( ! $titulo ) wp_send_json_error( 'El título es requerido' );

		wp_update_term( $term_id, 'scl_temporada', [
			'name' => $titulo
		] );

		$this->guardar_metas_temporada( $term_id, $torneo_id );

		wp_send_json_success( [ 'id' => $term_id, 'mensaje' => 'Temporada actualizada con éxito' ] );
	}

	private function guardar_metas_temporada( $term_id, $torneo_id ) {
		update_term_meta( $term_id, 'scl_temporada_torneo_id', $torneo_id );

		$estados_validos = [ 'activa', 'finalizada' ];
		$estado = isset( $_POST['estado'] ) ? sanitize_key( wp_unslash( $_POST['estado'] ) ) : 'activa';
		if ( ! in_array( $estado, $estados_validos, true ) ) {
			$estado = 'activa';
		}
		update_term_meta( $term_id, 'scl_temporada_estado', $estado );

		$anio = isset( $_POST['anio'] ) ? absint( $_POST['anio'] ) : absint( date( 'Y' ) );
		update_term_meta( $term_id, 'scl_temporada_anio', $anio );
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
