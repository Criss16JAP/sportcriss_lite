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
		$loader->add_action( 'wp_ajax_scl_eliminar_torneo', [ $this, 'ajax_eliminar_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_subir_imagen_torneo', [ $this, 'ajax_subir_imagen_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_crear_grupo', [ $this, 'ajax_crear_grupo' ] );
		$loader->add_action( 'wp_ajax_scl_eliminar_grupo', [ $this, 'ajax_eliminar_grupo' ] );
		$loader->add_action( 'wp_ajax_scl_crear_temporada', [ $this, 'ajax_crear_temporada' ] );
		$loader->add_action( 'wp_ajax_scl_confirmar_ganador_llave', [ $this, 'ajax_confirmar_ganador_llave' ] );
		// Sprint 5: Equipos
		$loader->add_action( 'wp_ajax_scl_crear_equipo',    [ $this, 'ajax_crear_equipo' ] );
		$loader->add_action( 'wp_ajax_scl_editar_equipo',   [ $this, 'ajax_editar_equipo' ] );
		$loader->add_action( 'wp_ajax_scl_eliminar_equipo', [ $this, 'ajax_eliminar_equipo' ] );
		$loader->add_action( 'wp_ajax_scl_subir_escudo',    [ $this, 'ajax_subir_escudo' ] );
		$loader->add_action( 'wp_ajax_scl_get_equipos',     [ $this, 'ajax_get_equipos' ] );
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



	private function verificar_permisos(): void {
		if ( ! check_ajax_referer( 'scl_dashboard_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce inválido' );
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'No autenticado' );
		}
		$usuario = wp_get_current_user();
		$roles_permitidos = [ 'scl_organizador', 'scl_colaborador', 'administrator' ];
		if ( empty( array_intersect( $roles_permitidos, (array) $usuario->roles ) ) ) {
			wp_send_json_error( 'Sin permisos' );
		}
	}

	public function ajax_crear_torneo() {
		$this->verificar_permisos();

		$titulo = sanitize_text_field( wp_unslash( $_POST['titulo'] ?? $_POST['nombre'] ?? '' ) );
		if ( ! $titulo ) wp_send_json_error( 'El nombre es requerido.' );

		$post_id = wp_insert_post( [
			'post_type'   => 'scl_torneo',
			'post_title'  => $titulo,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		] );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( 'Error al crear el torneo.' );
		}

		$this->guardar_metas_torneo( $post_id );

		wp_send_json_success( [
			'success'    => true,
			'torneo_id'  => $post_id,
			'torneo_url' => get_permalink( $post_id ),
		] );
	}

	public function ajax_editar_torneo() {
		$this->verificar_permisos();

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$post = get_post( $torneo_id );
		
		if ( ! $post || 'scl_torneo' !== $post->post_type ) {
			wp_send_json_error( 'Torneo no válido.' );
		}
		
		if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		$titulo = sanitize_text_field( wp_unslash( $_POST['titulo'] ?? $_POST['nombre'] ?? '' ) );
		if ( ! $titulo ) wp_send_json_error( 'El nombre es requerido.' );

		wp_update_post( [
			'ID'         => $torneo_id,
			'post_title' => $titulo,
		] );

		$this->guardar_metas_torneo( $torneo_id );

		wp_send_json_success( [ 'success' => true ] );
	}

	public function ajax_eliminar_torneo() {
		$this->verificar_permisos();

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$post = get_post( $torneo_id );
		
		if ( ! $post || 'scl_torneo' !== $post->post_type ) {
			wp_send_json_error( 'Torneo no válido.' );
		}
		
		if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		wp_trash_post( $torneo_id );
		wp_send_json_success( [ 'success' => true ] );
	}

	public function ajax_subir_imagen_torneo() {
		$this->verificar_permisos();

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( 'No se ha subido ningún archivo.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'file', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		}

		wp_send_json_success( [
			'success'       => true,
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
		] );
	}

	public function ajax_crear_grupo() {
		$this->verificar_permisos();

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$post = get_post( $torneo_id );
		if ( ! $post || 'scl_torneo' !== $post->post_type ) {
			wp_send_json_error( 'Torneo no válido.' );
		}
		
		if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		$nombre = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
		if ( ! $nombre ) wp_send_json_error( 'El nombre del grupo es requerido.' );

		$grupo_id = wp_insert_post( [
			'post_type'   => 'scl_grupo',
			'post_title'  => $nombre,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_parent' => $torneo_id,
		] );

		if ( is_wp_error( $grupo_id ) ) {
			wp_send_json_error( 'Error al crear el grupo.' );
		}

		$descripcion = sanitize_textarea_field( wp_unslash( $_POST['descripcion'] ?? '' ) );
		update_post_meta( $grupo_id, 'scl_grupo_descripcion', $descripcion );

		wp_send_json_success( [ 'success' => true, 'grupo_id' => $grupo_id ] );
	}

	public function ajax_eliminar_grupo() {
		$this->verificar_permisos();

		$grupo_id = absint( $_POST['grupo_id'] ?? 0 );
		$post = get_post( $grupo_id );
		
		if ( ! $post || 'scl_grupo' !== $post->post_type ) {
			wp_send_json_error( 'Grupo no válido.' );
		}
		
		if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		wp_trash_post( $grupo_id );
		wp_send_json_success( [ 'success' => true ] );
	}

	public function ajax_crear_temporada() {
		$this->verificar_permisos();

		$nombre = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
		if ( ! $nombre ) wp_send_json_error( 'El nombre de la temporada es requerido.' );

		$term = get_term_by( 'name', $nombre, 'scl_temporada' );
		$created = false;

		if ( $term ) {
			$term_id = $term->term_id;
		} else {
			$result = wp_insert_term( $nombre, 'scl_temporada' );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( 'Error al crear la temporada: ' . $result->get_error_message() );
			}
			$term_id = $result['term_id'];
			$created = true;
			
			$estado = in_array( $_POST['estado'] ?? '', [ 'activa', 'finalizada' ], true ) ? $_POST['estado'] : 'activa';
			$anio   = absint( $_POST['anio'] ?? date( 'Y' ) );
			update_term_meta( $term_id, 'scl_temporada_estado', $estado );
			update_term_meta( $term_id, 'scl_temporada_anio', $anio );
		}

		wp_send_json_success( [
			'success' => true,
			'term_id' => $term_id,
			'nombre'  => $nombre,
			'created' => $created,
		] );
	}

	private function guardar_metas_torneo( $torneo_id ) {
		update_post_meta( $torneo_id, 'scl_torneo_siglas', strtoupper( sanitize_text_field( wp_unslash( $_POST['siglas'] ?? '' ) ) ) );
		update_post_meta( $torneo_id, 'scl_torneo_puntos_victoria', absint( $_POST['puntos_victoria'] ?? 3 ) );
		update_post_meta( $torneo_id, 'scl_torneo_puntos_empate', absint( $_POST['puntos_empate'] ?? 1 ) );
		update_post_meta( $torneo_id, 'scl_torneo_puntos_derrota', absint( $_POST['puntos_derrota'] ?? 0 ) );
		update_post_meta( $torneo_id, 'scl_torneo_color_primario', sanitize_hex_color( wp_unslash( $_POST['color_primario'] ?? '#1a3a5c' ) ) );
		update_post_meta( $torneo_id, 'scl_torneo_color_secundario', sanitize_hex_color( wp_unslash( $_POST['color_secundario'] ?? '#f5a623' ) ) );

		// Desempate: validar que sea JSON array válido
		$desempate_raw = wp_unslash( $_POST['desempate_orden'] ?? '[]' );
		$desempate     = json_decode( $desempate_raw, true );
		$valores_validos = [ 'diferencia_goles', 'goles_favor', 'goles_contra', 'enfrentamiento_directo' ];
		if ( is_array( $desempate ) ) {
			$desempate = array_values( array_intersect( $desempate, $valores_validos ) );
			update_post_meta( $torneo_id, 'scl_torneo_desempate_orden', wp_json_encode( $desempate ) );
		}

		// Logo y fondo (attachment IDs)
		$logo_id = absint( $_POST['logo_id'] ?? 0 );
		if ( $logo_id ) {
			update_post_meta( $torneo_id, 'scl_torneo_logo', $logo_id );
		}
		$fondo_id = absint( $_POST['fondo_id'] ?? 0 );
		if ( $fondo_id ) {
			update_post_meta( $torneo_id, 'scl_torneo_fondo', $fondo_id );
		}
	}


	// -----------------------------------------------------------------------
	// Sprint 5: Handlers de Equipos
	// -----------------------------------------------------------------------

	public function ajax_crear_equipo(): void {
		$this->verificar_permisos();

		$nombre = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
		if ( empty( $nombre ) ) {
			wp_send_json_error( 'El nombre del equipo es obligatorio.' );
		}

		$existente = get_posts( [
			'post_type'      => 'scl_equipo',
			'author'         => get_current_user_id(),
			'post_status'    => 'publish',
			'title'          => $nombre,
			'posts_per_page' => 1,
		] );
		if ( ! empty( $existente ) ) {
			wp_send_json_error( 'Ya tienes un equipo con ese nombre.' );
		}

		$equipo_id = wp_insert_post( [
			'post_type'   => 'scl_equipo',
			'post_title'  => $nombre,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		] );

		if ( is_wp_error( $equipo_id ) ) {
			wp_send_json_error( 'Error al crear el equipo.' );
		}

		$zona = sanitize_text_field( wp_unslash( $_POST['zona'] ?? '' ) );
		update_post_meta( $equipo_id, 'scl_equipo_zona', $zona );

		$escudo_id = absint( $_POST['escudo_id'] ?? 0 );
		if ( $escudo_id ) {
			update_post_meta( $equipo_id, 'scl_equipo_escudo', $escudo_id );
			update_post_meta( $equipo_id, 'scl_equipo_incompleto', '0' );
		} else {
			update_post_meta( $equipo_id, 'scl_equipo_incompleto', '1' );
		}

		wp_send_json_success( [ 'equipo_id' => $equipo_id ] );
	}

	public function ajax_editar_equipo(): void {
		$this->verificar_permisos();

		$equipo_id = absint( $_POST['equipo_id'] ?? 0 );
		$post      = get_post( $equipo_id );

		if ( ! $post || 'scl_equipo' !== $post->post_type ) {
			wp_send_json_error( 'Equipo no válido.' );
		}
		if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		$nombre = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
		if ( empty( $nombre ) ) {
			wp_send_json_error( 'El nombre del equipo es obligatorio.' );
		}

		wp_update_post( [
			'ID'         => $equipo_id,
			'post_title' => $nombre,
		] );

		$zona = sanitize_text_field( wp_unslash( $_POST['zona'] ?? '' ) );
		update_post_meta( $equipo_id, 'scl_equipo_zona', $zona );

		$escudo_id = absint( $_POST['escudo_id'] ?? 0 );
		if ( $escudo_id ) {
			update_post_meta( $equipo_id, 'scl_equipo_escudo', $escudo_id );
			update_post_meta( $equipo_id, 'scl_equipo_incompleto', '0' );
		} else {
			// Sin escudo nuevo: mantener el existente o marcar incompleto si no hay ninguno
			$escudo_actual = absint( get_post_meta( $equipo_id, 'scl_equipo_escudo', true ) );
			if ( ! $escudo_actual ) {
				update_post_meta( $equipo_id, 'scl_equipo_incompleto', '1' );
			}
		}

		wp_send_json_success( [ 'success' => true ] );
	}

	public function ajax_eliminar_equipo(): void {
		$this->verificar_permisos();

		$equipo_id = absint( $_POST['equipo_id'] ?? 0 );
		$post      = get_post( $equipo_id );

		if ( ! $post || 'scl_equipo' !== $post->post_type ) {
			wp_send_json_error( 'Equipo no válido.' );
		}
		if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		$partidos = get_posts( [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'OR',
				[ 'key' => 'scl_partido_equipo_local_id',  'value' => $equipo_id, 'compare' => '=' ],
				[ 'key' => 'scl_partido_equipo_visita_id', 'value' => $equipo_id, 'compare' => '=' ],
			],
		] );

		if ( ! empty( $partidos ) ) {
			wp_send_json_error( 'No puedes eliminar un equipo que tiene partidos asignados.' );
		}

		wp_trash_post( $equipo_id );
		wp_send_json_success( [ 'success' => true ] );
	}

	public function ajax_subir_escudo(): void {
		$this->verificar_permisos();

		if ( empty( $_FILES['escudo'] ) || UPLOAD_ERR_OK !== (int) $_FILES['escudo']['error'] ) {
			wp_send_json_error( 'No se ha subido ningún archivo.' );
		}

		if ( (int) $_FILES['escudo']['size'] > 2 * 1024 * 1024 ) {
			wp_send_json_error( 'El escudo no puede superar 2MB.' );
		}

		$tipo       = wp_check_filetype( $_FILES['escudo']['name'] );
		$permitidos = [ 'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml' ];
		if ( ! in_array( $tipo['type'], $permitidos, true ) ) {
			wp_send_json_error( 'Formato no permitido. Usa JPG, PNG, WebP o SVG.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'escudo', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		}

		wp_send_json_success( [
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
		] );
	}

	public function ajax_get_equipos(): void {
		$this->verificar_permisos();

		$busqueda = sanitize_text_field( wp_unslash( $_POST['busqueda'] ?? '' ) );

		$args = [
			'post_type'      => 'scl_equipo',
			'author'         => get_current_user_id(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];
		if ( $busqueda ) {
			$args['s'] = $busqueda;
		}

		$equipos = get_posts( $args );

		$data = array_map( function( $equipo ) {
			$escudo_id = absint( get_post_meta( $equipo->ID, 'scl_equipo_escudo', true ) );
			return [
				'ID'         => $equipo->ID,
				'nombre'     => $equipo->post_title,
				'escudo_url' => $escudo_id ? wp_get_attachment_url( $escudo_id ) : '',
				'zona'       => (string) get_post_meta( $equipo->ID, 'scl_equipo_zona', true ),
				'incompleto' => get_post_meta( $equipo->ID, 'scl_equipo_incompleto', true ) === '1',
			];
		}, $equipos );

		wp_send_json_success( $data );
	}

	// -----------------------------------------------------------------------

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
