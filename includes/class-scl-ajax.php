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
		$loader->add_action( 'wp_ajax_scl_crear_torneo',          [ $this, 'ajax_crear_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_editar_torneo',         [ $this, 'ajax_editar_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_eliminar_torneo',       [ $this, 'ajax_eliminar_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_subir_imagen_torneo',   [ $this, 'ajax_subir_imagen_torneo' ] );
		$loader->add_action( 'wp_ajax_scl_crear_grupo',           [ $this, 'ajax_crear_grupo' ] );
		$loader->add_action( 'wp_ajax_scl_eliminar_grupo',        [ $this, 'ajax_eliminar_grupo' ] );
		$loader->add_action( 'wp_ajax_scl_crear_temporada',       [ $this, 'ajax_crear_temporada' ] );
		// Sprint 5: Equipos
		$loader->add_action( 'wp_ajax_scl_crear_equipo',    [ $this, 'ajax_crear_equipo' ] );
		$loader->add_action( 'wp_ajax_scl_editar_equipo',   [ $this, 'ajax_editar_equipo' ] );
		$loader->add_action( 'wp_ajax_scl_eliminar_equipo', [ $this, 'ajax_eliminar_equipo' ] );
		$loader->add_action( 'wp_ajax_scl_subir_escudo',    [ $this, 'ajax_subir_escudo' ] );
		$loader->add_action( 'wp_ajax_scl_get_equipos',     [ $this, 'ajax_get_equipos' ] );
		// Sprint 6: Partidos
		$loader->add_action( 'wp_ajax_scl_crear_partido',    [ $this, 'ajax_crear_partido' ] );
		$loader->add_action( 'wp_ajax_scl_guardar_resultado',[ $this, 'ajax_guardar_resultado' ] );
		$loader->add_action( 'wp_ajax_scl_editar_partido',   [ $this, 'ajax_editar_partido' ] );
		$loader->add_action( 'wp_ajax_scl_eliminar_partido', [ $this, 'ajax_eliminar_partido' ] );
		$loader->add_action( 'wp_ajax_scl_get_partidos',     [ $this, 'ajax_get_partidos' ] );
		// Sprint 6.5: Colaboradores
		$loader->add_action( 'wp_ajax_scl_asignar_colaborador',    [ $this, 'ajax_asignar_colaborador' ] );
		$loader->add_action( 'wp_ajax_scl_revocar_colaborador',    [ $this, 'ajax_revocar_colaborador' ] );
		$loader->add_action( 'wp_ajax_scl_get_mis_colaboradores',  [ $this, 'ajax_get_mis_colaboradores' ] );
		// Sprint 7: Llaves
		$loader->add_action( 'wp_ajax_scl_crear_llave',            [ $this, 'ajax_crear_llave' ] );
		$loader->add_action( 'wp_ajax_scl_confirmar_ganador_llave',[ $this, 'ajax_confirmar_ganador_llave' ] );
		$loader->add_action( 'wp_ajax_scl_eliminar_llave',         [ $this, 'ajax_eliminar_llave' ] );
		// Sprint 8: Importador CSV
		$loader->add_action( 'wp_ajax_scl_validar_csv',           [ $this, 'ajax_validar_csv' ] );
		$loader->add_action( 'wp_ajax_scl_procesar_importacion',  [ $this, 'ajax_procesar_importacion' ] );
		$loader->add_action( 'wp_ajax_scl_descargar_plantilla',   [ $this, 'ajax_descargar_plantilla' ] );
		// Sprint 9: Exportación Visual
		$loader->add_action( 'wp_ajax_scl_get_export_url', [ $this, 'ajax_get_export_url' ] );
		// Sprint 10B: Tiers de anunciantes
		$loader->add_action( 'wp_ajax_scl_get_tier_anunciante', [ $this, 'get_tier_anunciante' ] );
	}

	// -----------------------------------------------------------------------
	// Sprint 10B: Tier de anunciante (para meta box dinámico del anuncio)
	// -----------------------------------------------------------------------

	public function get_tier_anunciante(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
		if ( ! check_ajax_referer( 'scl_get_tier', 'nonce', false ) ) wp_send_json_error();

		$anunciante_id = absint( $_POST['anunciante_id'] ?? 0 );
		if ( ! $anunciante_id ) wp_send_json_error( 'anunciante_id requerido.' );

		$tier = Scl_Ads::get_tier_anunciante( $anunciante_id );

		wp_send_json_success( [
			'tier'          => $tier,
			'multiplicador' => Scl_Ads::get_multiplicador( $tier ),
			'ubicaciones'   => Scl_Ads::TIERS[ $tier ]['ubicaciones'] ?? [],
		] );
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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

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
			'author'         => scl_get_autor_efectivo(),
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
	// Sprint 6.5: Colaboradores
	// -----------------------------------------------------------------------

	public function ajax_asignar_colaborador(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Sin permisos.' );

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( 'Email inválido.' );
		}

		$usuario = get_user_by( 'email', $email );
		if ( ! $usuario ) {
			wp_send_json_error( 'No existe un usuario con ese email en el sistema.' );
		}
		if ( $usuario->ID === get_current_user_id() ) {
			wp_send_json_error( 'No puedes asignarte a ti mismo.' );
		}

		$org_existente = (int) get_user_meta( $usuario->ID, 'scl_colaborador_organizador_id', true );
		if ( $org_existente && $org_existente !== get_current_user_id() ) {
			wp_send_json_error( 'Este usuario ya es colaborador de otro organizador.' );
		}

		$usuario->set_role( 'scl_colaborador' );
		update_user_meta( $usuario->ID, 'scl_colaborador_organizador_id', get_current_user_id() );

		wp_send_json_success( [
			'user_id'      => $usuario->ID,
			'display_name' => $usuario->display_name,
			'email'        => $usuario->user_email,
		] );
	}

	public function ajax_revocar_colaborador(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Sin permisos.' );

		$colaborador_id = absint( $_POST['colaborador_id'] ?? 0 );
		$org_id         = (int) get_user_meta( $colaborador_id, 'scl_colaborador_organizador_id', true );

		if ( $org_id !== get_current_user_id() ) {
			wp_send_json_error( 'No puedes revocar este colaborador.' );
		}

		$usuario = get_userdata( $colaborador_id );
		if ( $usuario ) {
			$usuario->set_role( 'subscriber' );
			delete_user_meta( $colaborador_id, 'scl_colaborador_organizador_id' );
		}

		wp_send_json_success();
	}

	public function ajax_get_mis_colaboradores(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Sin permisos.' );

		$colaboradores = get_users( [
			'role'       => 'scl_colaborador',
			'meta_key'   => 'scl_colaborador_organizador_id',
			'meta_value' => get_current_user_id(),
		] );

		$data = array_map( function( $u ) {
			return [
				'user_id'      => $u->ID,
				'display_name' => $u->display_name,
				'email'        => $u->user_email,
			];
		}, $colaboradores );

		wp_send_json_success( $data );
	}

	// -----------------------------------------------------------------------
	// Sprint 7: Llaves Playoff
	// -----------------------------------------------------------------------

	public function ajax_crear_llave(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

		$torneo_id         = absint( $_POST['torneo_id']         ?? 0 );
		$temporada_term_id = absint( $_POST['temporada_term_id'] ?? 0 );
		$equipo_a_id       = absint( $_POST['equipo_a_id']       ?? 0 );
		$equipo_b_id       = absint( $_POST['equipo_b_id']       ?? 0 );
		$nombre_fase       = sanitize_text_field( wp_unslash( $_POST['nombre_fase'] ?? 'Playoff' ) );
		$es_doble          = ! empty( $_POST['es_doble'] ) && '1' === $_POST['es_doble'];

		if ( ! $torneo_id || ! $equipo_a_id || ! $equipo_b_id ) {
			wp_send_json_error( 'Torneo y ambos equipos son obligatorios.' );
		}
		if ( $equipo_a_id === $equipo_b_id ) {
			wp_send_json_error( 'Los dos equipos deben ser diferentes.' );
		}

		$torneo = get_post( $torneo_id );
		if ( ! $torneo || 'scl_torneo' !== $torneo->post_type
			|| ( (int) $torneo->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( 'Torneo no válido.' );
		}

		$nombre_llave = $nombre_fase . ': ' . get_the_title( $equipo_a_id )
			. ' vs ' . get_the_title( $equipo_b_id );

		$llave_id = wp_insert_post( [
			'post_type'   => 'scl_llave',
			'post_title'  => $nombre_llave,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		] );
		if ( is_wp_error( $llave_id ) ) {
			wp_send_json_error( 'Error al crear la llave.' );
		}

		update_post_meta( $llave_id, 'scl_llave_torneo_id',         $torneo_id );
		update_post_meta( $llave_id, 'scl_llave_temporada_term_id', $temporada_term_id );
		update_post_meta( $llave_id, 'scl_llave_equipo_a_id',       $equipo_a_id );
		update_post_meta( $llave_id, 'scl_llave_equipo_b_id',       $equipo_b_id );
		update_post_meta( $llave_id, 'scl_llave_nombre_fase',        $nombre_fase );
		update_post_meta( $llave_id, 'scl_llave_es_doble',          $es_doble ? '1' : '0' );
		update_post_meta( $llave_id, 'scl_llave_estado',            'en_curso' );

		// Partido de ida (o único)
		$partido_ida_id = $this->crear_partido_de_llave(
			$llave_id, $torneo_id, $temporada_term_id,
			$equipo_a_id, $equipo_b_id, $nombre_fase, false
		);
		update_post_meta( $llave_id, 'scl_llave_partido_ida_id', $partido_ida_id );

		if ( $es_doble ) {
			$partido_vuelta_id = $this->crear_partido_de_llave(
				$llave_id, $torneo_id, $temporada_term_id,
				$equipo_b_id, $equipo_a_id, $nombre_fase, true
			);
			update_post_meta( $llave_id, 'scl_llave_partido_vuelta_id', $partido_vuelta_id );
		}

		wp_send_json_success( [ 'llave_id' => $llave_id ] );
	}

	private function crear_partido_de_llave(
		int $llave_id, int $torneo_id, int $temporada_term_id,
		int $local_id, int $visita_id, string $nombre_fase, bool $es_vuelta
	): int {
		$sufijo     = $es_vuelta ? ' (Vuelta)' : ' (Ida)';
		$titulo     = get_the_title( $local_id ) . ' vs ' . get_the_title( $visita_id ) . $sufijo;

		// Insertar como draft para establecer todas las metas antes de publicar
		$partido_id = wp_insert_post( [
			'post_type'   => 'scl_partido',
			'post_title'  => $titulo,
			'post_status' => 'draft',
			'post_author' => get_current_user_id(),
		] );

		if ( is_wp_error( $partido_id ) ) {
			return 0;
		}

		update_post_meta( $partido_id, 'scl_partido_torneo_id',        $torneo_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_local_id',  $local_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_visita_id', $visita_id );
		update_post_meta( $partido_id, 'scl_partido_tipo_fase',        'playoff' );
		update_post_meta( $partido_id, 'scl_partido_estado',           'pendiente' );
		update_post_meta( $partido_id, 'scl_partido_llave_id',         $llave_id );
		update_post_meta( $partido_id, 'scl_partido_es_vuelta',        $es_vuelta ? '1' : '0' );
		update_post_meta( $partido_id, 'scl_partido_goles_local',      '' );
		update_post_meta( $partido_id, 'scl_partido_goles_visita',     '' );

		if ( $temporada_term_id ) {
			wp_set_post_terms( $partido_id, [ $temporada_term_id ], 'scl_temporada' );
		}

		// Jornada = nombre de la fase (ej: "Semifinal")
		$term = get_term_by( 'name', $nombre_fase, 'scl_jornada' );
		if ( ! $term ) {
			$result  = wp_insert_term( $nombre_fase, 'scl_jornada' );
			$term_id = is_wp_error( $result ) ? 0 : $result['term_id'];
		} else {
			$term_id = $term->term_id;
		}
		if ( $term_id ) {
			wp_set_post_terms( $partido_id, [ $term_id ], 'scl_jornada' );
		}

		// Publicar — dispara save_post_scl_partido; la llave aún no tiene vuelta asignada
		// así que el evaluador retornará temprano sin efectos secundarios
		wp_update_post( [ 'ID' => $partido_id, 'post_status' => 'publish' ] );

		return $partido_id;
	}

	/**
	 * Confirma el ganador de una llave.
	 */
	public function ajax_confirmar_ganador_llave(): void {
		$this->verificar_permisos();

		$llave_id = absint( $_POST['llave_id'] ?? 0 );
		if ( ! $llave_id ) {
			wp_send_json_error( 'Falta llave_id.' );
		}

		$llave = get_post( $llave_id );
		if ( ! $llave || 'scl_llave' !== $llave->post_type ) {
			wp_send_json_error( 'Llave no válida.' );
		}
		if ( (int) $llave->post_author !== scl_get_autor_efectivo()
			&& ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permisos insuficientes.' );
		}

		$ganador_id          = absint( $_POST['ganador_id']  ?? 0 );
		$equipo_a_id         = (int) get_post_meta( $llave_id, 'scl_llave_equipo_a_id', true );
		$equipo_b_id         = (int) get_post_meta( $llave_id, 'scl_llave_equipo_b_id', true );
		$estado_llave        = get_post_meta( $llave_id, 'scl_llave_estado', true );
		$ganador_provisional = (int) get_post_meta( $llave_id, 'scl_llave_ganador_provisional_id', true );

		// Si no se envió ganador pero hay uno provisional, usarlo
		if ( ! $ganador_id && $ganador_provisional ) {
			$ganador_id = $ganador_provisional;
		}

		if ( ! $ganador_id ) {
			wp_send_json_error( 'No se proporcionó ganador.' );
		}

		// Validar que el ganador sea uno de los dos equipos de la llave
		if ( $equipo_a_id && $equipo_b_id
			&& ! in_array( $ganador_id, [ $equipo_a_id, $equipo_b_id ], true ) ) {
			wp_send_json_error( 'El ganador debe ser uno de los dos equipos de la llave.' );
		}

		if ( 'requiere_penales' === $estado_llave ) {
			$pen_a = isset( $_POST['penales_a'] ) ? absint( $_POST['penales_a'] ) : ( isset( $_POST['penales_local'] ) ? absint( $_POST['penales_local'] ) : null );
			$pen_b = isset( $_POST['penales_b'] ) ? absint( $_POST['penales_b'] ) : ( isset( $_POST['penales_visita'] ) ? absint( $_POST['penales_visita'] ) : null );
			if ( null === $pen_a || null === $pen_b ) {
				wp_send_json_error( 'Los penales son obligatorios para esta llave.' );
			}
			if ( $pen_a === $pen_b ) {
				wp_send_json_error( 'Los penales no pueden terminar en empate.' );
			}
			update_post_meta( $llave_id, 'scl_llave_penales_a', $pen_a );
			update_post_meta( $llave_id, 'scl_llave_penales_b', $pen_b );
		}

		update_post_meta( $llave_id, 'scl_llave_ganador_id', $ganador_id );
		update_post_meta( $llave_id, 'scl_llave_estado',     'resuelta' );

		wp_send_json_success( [
			'ganador'    => get_the_title( $ganador_id ),
			'ganador_id' => $ganador_id,
		] );
	}

	public function ajax_eliminar_llave(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

		$llave_id = absint( $_POST['llave_id'] ?? 0 );
		$llave    = get_post( $llave_id );

		if ( ! $llave || 'scl_llave' !== $llave->post_type ) {
			wp_send_json_error( 'Llave no válida.' );
		}
		if ( (int) $llave->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos sobre esta llave.' );
		}

		$estado = get_post_meta( $llave_id, 'scl_llave_estado', true );
		if ( 'resuelta' === $estado ) {
			wp_send_json_error( 'No se puede eliminar una llave ya resuelta.' );
		}

		// Papelera de los partidos asociados
		$ida_id    = absint( get_post_meta( $llave_id, 'scl_llave_partido_ida_id',    true ) );
		$vuelta_id = absint( get_post_meta( $llave_id, 'scl_llave_partido_vuelta_id', true ) );
		if ( $ida_id )    wp_trash_post( $ida_id );
		if ( $vuelta_id ) wp_trash_post( $vuelta_id );

		wp_trash_post( $llave_id );
		wp_send_json_success();
	}

	// -----------------------------------------------------------------------
	// Sprint 9: Exportación Visual
	// -----------------------------------------------------------------------

	public function ajax_get_export_url(): void {
		check_ajax_referer( 'scl_dashboard_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) wp_send_json_error( 'No autenticado.' );
		if ( scl_es_colaborador() ) wp_send_json_error( 'Sin permisos.' );

		$torneo_id         = absint( $_GET['torneo_id'] ?? 0 );
		$temporada_term_id = absint( $_GET['temporada']  ?? 0 );
		$grupo_id          = absint( $_GET['grupo']       ?? 0 );

		if ( ! $torneo_id ) {
			wp_send_json_error( 'Falta torneo_id.' );
		}

		$torneo = get_post( $torneo_id );
		if ( ! $torneo || 'scl_torneo' !== $torneo->post_type
			|| ( (int) $torneo->post_author !== scl_get_autor_efectivo() && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( 'Torneo no válido.' );
		}

		$url = Scl_Export::get_url( $torneo_id, $temporada_term_id, $grupo_id );
		wp_send_json_success( [ 'url' => $url ] );
	}

	// -----------------------------------------------------------------------
	// Sprint 6: Handlers de Partidos
	// -----------------------------------------------------------------------

	public function ajax_crear_partido(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$local_id  = absint( $_POST['equipo_local_id'] ?? 0 );
		$visita_id = absint( $_POST['equipo_visita_id'] ?? 0 );

		if ( ! $torneo_id || ! $local_id || ! $visita_id ) {
			wp_send_json_error( 'Torneo y ambos equipos son obligatorios.' );
		}
		if ( $local_id === $visita_id ) {
			wp_send_json_error( 'El equipo local y visitante no pueden ser el mismo.' );
		}

		$torneo = get_post( $torneo_id );
		if ( ! $torneo || 'scl_torneo' !== $torneo->post_type
			|| ( (int) $torneo->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( 'Torneo no válido.' );
		}

		$estado    = in_array( $_POST['estado'] ?? '', [ 'pendiente', 'finalizado' ], true )
					 ? $_POST['estado'] : 'pendiente';
		$tipo_fase = in_array( $_POST['tipo_fase'] ?? '', [ 'grupos', 'playoff' ], true )
					 ? $_POST['tipo_fase'] : 'grupos';

		// Insertar como draft para que el motor no dispare antes de tener las metas
		$partido_id = wp_insert_post( [
			'post_type'   => 'scl_partido',
			'post_title'  => 'Partido',
			'post_status' => 'draft',
			'post_author' => get_current_user_id(),
		] );

		if ( is_wp_error( $partido_id ) ) {
			wp_send_json_error( 'Error al crear el partido.' );
		}

		update_post_meta( $partido_id, 'scl_partido_torneo_id',        $torneo_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_local_id',  $local_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_visita_id', $visita_id );
		update_post_meta( $partido_id, 'scl_partido_tipo_fase',        $tipo_fase );
		update_post_meta( $partido_id, 'scl_partido_estado',           $estado );
		update_post_meta( $partido_id, 'scl_partido_fecha',
			sanitize_text_field( wp_unslash( $_POST['fecha'] ?? '' ) ) );

		if ( 'finalizado' === $estado ) {
			update_post_meta( $partido_id, 'scl_partido_goles_local',
				absint( $_POST['goles_local'] ?? 0 ) );
			update_post_meta( $partido_id, 'scl_partido_goles_visita',
				absint( $_POST['goles_visita'] ?? 0 ) );
		} else {
			update_post_meta( $partido_id, 'scl_partido_goles_local',  '' );
			update_post_meta( $partido_id, 'scl_partido_goles_visita', '' );
		}

		// Taxonomía temporada
		$temporada_term_id = absint( $_POST['temporada_term_id'] ?? 0 );
		if ( $temporada_term_id ) {
			wp_set_post_terms( $partido_id, [ $temporada_term_id ], 'scl_temporada' );
		}

		// Jornada: buscar o crear el término
		$jornada_nombre = sanitize_text_field( wp_unslash( $_POST['jornada'] ?? '' ) );
		if ( $jornada_nombre ) {
			$term    = get_term_by( 'name', $jornada_nombre, 'scl_jornada' );
			$term_id = $term ? $term->term_id : 0;
			if ( ! $term_id ) {
				$result  = wp_insert_term( $jornada_nombre, 'scl_jornada' );
				$term_id = is_wp_error( $result ) ? 0 : $result['term_id'];
			}
			if ( $term_id ) {
				wp_set_post_terms( $partido_id, [ $term_id ], 'scl_jornada' );
			}
		}

		// Grupo
		$grupo_id = absint( $_POST['grupo_id'] ?? 0 );
		if ( $grupo_id ) {
			update_post_meta( $partido_id, 'scl_partido_grupo_id', $grupo_id );
		}

		// Publicar con título auto-generado — dispara save_post_scl_partido con metas ya guardadas
		$titulo = get_the_title( $local_id ) . ' vs ' . get_the_title( $visita_id );
		wp_update_post( [
			'ID'          => $partido_id,
			'post_title'  => $titulo,
			'post_status' => 'publish',
		] );

		wp_send_json_success( [ 'partido_id' => $partido_id ] );
	}

	public function ajax_guardar_resultado(): void {
		$this->verificar_permisos();

		$partido_id = absint( $_POST['partido_id'] ?? 0 );
		$post       = get_post( $partido_id );

		if ( ! $post || 'scl_partido' !== $post->post_type ) {
			wp_send_json_error( 'Partido no válido.' );
		}

		// Verificar propiedad: organizador propio o colaborador vinculado al autor del partido
		$autor_partido      = (int) $post->post_author;
		$es_propietario     = ( $autor_partido === get_current_user_id() );
		$es_colaborador_del = ( scl_es_colaborador()
			&& scl_get_organizador_de_colaborador( get_current_user_id() ) === $autor_partido );
		if ( ! $es_propietario && ! $es_colaborador_del && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		$estado = in_array( $_POST['estado'] ?? '', [ 'pendiente', 'finalizado' ], true )
				  ? $_POST['estado'] : 'finalizado';

		update_post_meta( $partido_id, 'scl_partido_estado', $estado );

		if ( 'finalizado' === $estado ) {
			$goles_local  = absint( $_POST['goles_local']  ?? 0 );
			$goles_visita = absint( $_POST['goles_visita'] ?? 0 );
			update_post_meta( $partido_id, 'scl_partido_goles_local',  $goles_local );
			update_post_meta( $partido_id, 'scl_partido_goles_visita', $goles_visita );

			// Penales para playoff con empate (provisional hasta Sprint 7 / llaves)
			$tipo_fase = get_post_meta( $partido_id, 'scl_partido_tipo_fase', true );
			if ( 'playoff' === $tipo_fase && $goles_local === $goles_visita ) {
				if ( isset( $_POST['penales_local'] ) ) {
					update_post_meta( $partido_id, 'scl_partido_penales_local',
						absint( $_POST['penales_local'] ) );
				}
				if ( isset( $_POST['penales_visita'] ) ) {
					update_post_meta( $partido_id, 'scl_partido_penales_visita',
						absint( $_POST['penales_visita'] ) );
				}
			}
		} else {
			update_post_meta( $partido_id, 'scl_partido_goles_local',  '' );
			update_post_meta( $partido_id, 'scl_partido_goles_visita', '' );
		}

		// Disparar motor de cálculo y evaluador de llaves
		do_action( 'save_post_scl_partido', $partido_id, get_post( $partido_id ), false );

		$local_id     = absint( get_post_meta( $partido_id, 'scl_partido_equipo_local_id',  true ) );
		$visita_id    = absint( get_post_meta( $partido_id, 'scl_partido_equipo_visita_id', true ) );
		$gl           = get_post_meta( $partido_id, 'scl_partido_goles_local',  true );
		$gv           = get_post_meta( $partido_id, 'scl_partido_goles_visita', true );
		$marcador     = ( $gl !== '' && $gv !== '' ) ? "{$gl} - {$gv}" : '— vs —';

		wp_send_json_success( [
			'marcador' => $marcador,
			'estado'   => $estado,
			'titulo'   => get_the_title( $local_id ) . ' vs ' . get_the_title( $visita_id ),
		] );
	}

	public function ajax_editar_partido(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

		$partido_id = absint( $_POST['partido_id'] ?? 0 );
		$post       = get_post( $partido_id );

		if ( ! $post || 'scl_partido' !== $post->post_type ) {
			wp_send_json_error( 'Partido no válido.' );
		}
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		$torneo_id = absint( $_POST['torneo_id'] ?? 0 );
		$local_id  = absint( $_POST['equipo_local_id'] ?? 0 );
		$visita_id = absint( $_POST['equipo_visita_id'] ?? 0 );

		if ( ! $torneo_id || ! $local_id || ! $visita_id ) {
			wp_send_json_error( 'Torneo y ambos equipos son obligatorios.' );
		}
		if ( $local_id === $visita_id ) {
			wp_send_json_error( 'El equipo local y visitante no pueden ser el mismo.' );
		}

		$estado    = in_array( $_POST['estado'] ?? '', [ 'pendiente', 'finalizado' ], true )
					 ? $_POST['estado'] : 'pendiente';
		$tipo_fase = in_array( $_POST['tipo_fase'] ?? '', [ 'grupos', 'playoff' ], true )
					 ? $_POST['tipo_fase'] : 'grupos';

		update_post_meta( $partido_id, 'scl_partido_torneo_id',        $torneo_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_local_id',  $local_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_visita_id', $visita_id );
		update_post_meta( $partido_id, 'scl_partido_tipo_fase',        $tipo_fase );
		update_post_meta( $partido_id, 'scl_partido_estado',           $estado );
		update_post_meta( $partido_id, 'scl_partido_fecha',
			sanitize_text_field( wp_unslash( $_POST['fecha'] ?? '' ) ) );

		if ( 'finalizado' === $estado ) {
			update_post_meta( $partido_id, 'scl_partido_goles_local',
				absint( $_POST['goles_local'] ?? 0 ) );
			update_post_meta( $partido_id, 'scl_partido_goles_visita',
				absint( $_POST['goles_visita'] ?? 0 ) );
		} else {
			update_post_meta( $partido_id, 'scl_partido_goles_local',  '' );
			update_post_meta( $partido_id, 'scl_partido_goles_visita', '' );
		}

		// Temporada
		$temporada_term_id = absint( $_POST['temporada_term_id'] ?? 0 );
		wp_set_post_terms( $partido_id,
			$temporada_term_id ? [ $temporada_term_id ] : [],
			'scl_temporada' );

		// Jornada
		$jornada_nombre = sanitize_text_field( wp_unslash( $_POST['jornada'] ?? '' ) );
		if ( $jornada_nombre ) {
			$term    = get_term_by( 'name', $jornada_nombre, 'scl_jornada' );
			$term_id = $term ? $term->term_id : 0;
			if ( ! $term_id ) {
				$result  = wp_insert_term( $jornada_nombre, 'scl_jornada' );
				$term_id = is_wp_error( $result ) ? 0 : $result['term_id'];
			}
			wp_set_post_terms( $partido_id, $term_id ? [ $term_id ] : [], 'scl_jornada' );
		} else {
			wp_set_post_terms( $partido_id, [], 'scl_jornada' );
		}

		// Grupo
		update_post_meta( $partido_id, 'scl_partido_grupo_id', absint( $_POST['grupo_id'] ?? 0 ) );

		// Actualizar título y disparar motor
		$titulo = get_the_title( $local_id ) . ' vs ' . get_the_title( $visita_id );
		wp_update_post( [ 'ID' => $partido_id, 'post_title' => $titulo ] );

		wp_send_json_success( [ 'success' => true ] );
	}

	public function ajax_eliminar_partido(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden realizar esta acción.' );

		$partido_id = absint( $_POST['partido_id'] ?? 0 );
		$post       = get_post( $partido_id );

		if ( ! $post || 'scl_partido' !== $post->post_type ) {
			wp_send_json_error( 'Partido no válido.' );
		}
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}

		$llave_id = absint( get_post_meta( $partido_id, 'scl_partido_llave_id', true ) );
		if ( $llave_id ) {
			wp_send_json_error( 'Este partido pertenece a una llave. Elimina la llave primero.' );
		}

		// Capturar datos antes de eliminar para recalcular si aplica
		$estado            = get_post_meta( $partido_id, 'scl_partido_estado',    true );
		$tipo_fase         = get_post_meta( $partido_id, 'scl_partido_tipo_fase', true );
		$torneo_id         = absint( get_post_meta( $partido_id, 'scl_partido_torneo_id', true ) );
		$terms             = wp_get_post_terms( $partido_id, 'scl_temporada' );
		$temporada_term_id = ( ! is_wp_error( $terms ) && ! empty( $terms ) )
							 ? (int) $terms[0]->term_id : 0;

		wp_trash_post( $partido_id );

		// Recalcular tabla si el partido eliminado afectaba la posición
		if ( 'finalizado' === $estado && 'grupos' === $tipo_fase
			&& $torneo_id && $temporada_term_id ) {
			( new Scl_Engine() )->recalcular_tabla( $torneo_id, $temporada_term_id );
		}

		wp_send_json_success( [ 'success' => true ] );
	}

	// -----------------------------------------------------------------------
	// Sprint 8: Importador CSV
	// -----------------------------------------------------------------------

	public function ajax_validar_csv(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden importar.' );

		if ( empty( $_FILES['csv_file'] ) || UPLOAD_ERR_OK !== (int) $_FILES['csv_file']['error'] ) {
			wp_send_json_error( 'No se ha recibido ningún archivo.' );
		}

		if ( (int) $_FILES['csv_file']['size'] > 5 * 1024 * 1024 ) {
			wp_send_json_error( 'El archivo no puede superar 5 MB.' );
		}

		$ext = strtolower( pathinfo( sanitize_file_name( $_FILES['csv_file']['name'] ), PATHINFO_EXTENSION ) );
		if ( 'csv' !== $ext ) {
			wp_send_json_error( 'Solo se aceptan archivos .csv.' );
		}

		$tmp = $_FILES['csv_file']['tmp_name'];
		$importer = new Scl_Importer();
		$filas    = $importer->parsear_y_guardar( $tmp, get_current_user_id() );

		if ( is_wp_error( $filas ) ) {
			wp_send_json_error( $filas->get_error_message() );
		}

		$resultado = $importer->validar( get_current_user_id() );
		if ( isset( $resultado['error'] ) ) {
			wp_send_json_error( $resultado['error'] );
		}

		wp_send_json_success( $resultado );
	}

	public function ajax_procesar_importacion(): void {
		$this->verificar_permisos();
		if ( scl_es_colaborador() ) wp_send_json_error( 'Los colaboradores no pueden importar.' );

		$importer  = new Scl_Importer();
		$resultado = $importer->importar( get_current_user_id() );

		if ( isset( $resultado['error'] ) ) {
			wp_send_json_error( $resultado['error'] );
		}

		wp_send_json_success( $resultado );
	}

	public function ajax_descargar_plantilla(): void {
		if ( ! check_ajax_referer( 'scl_dashboard_nonce', 'nonce', false ) ) {
			wp_die( 'Nonce inválido.' );
		}
		if ( ! is_user_logged_in() ) {
			wp_die( 'No autenticado.' );
		}

		$csv = Scl_Importer::plantilla_csv();
		$bom = "\xEF\xBB\xBF"; // BOM para compatibilidad con Excel

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="plantilla-partidos.csv"' );
		header( 'Content-Length: ' . ( strlen( $bom ) + strlen( $csv ) ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $bom . $csv;
		exit;
	}

	public function ajax_get_partidos(): void {
		$this->verificar_permisos();

		$torneo_id         = absint( $_POST['torneo_id'] ?? 0 );
		$temporada_term_id = absint( $_POST['temporada_term_id'] ?? 0 );
		$tipo_fase         = sanitize_key( $_POST['tipo_fase'] ?? '' );
		$estado_f          = sanitize_key( $_POST['estado'] ?? '' );

		$mis_torneo_ids = get_posts( [
			'post_type'      => 'scl_torneo',
			'author'         => scl_get_autor_efectivo(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		if ( empty( $mis_torneo_ids ) && ! $torneo_id ) {
			wp_send_json_success( [] );
		}

		$meta_query = [
			[
				'key'     => 'scl_partido_torneo_id',
				'value'   => $torneo_id ?: $mis_torneo_ids,
				'compare' => $torneo_id ? '=' : 'IN',
				'type'    => 'NUMERIC',
			],
		];
		if ( $tipo_fase ) {
			$meta_query[] = [ 'key' => 'scl_partido_tipo_fase', 'value' => $tipo_fase ];
		}
		if ( $estado_f ) {
			$meta_query[] = [ 'key' => 'scl_partido_estado', 'value' => $estado_f ];
		}

		$tax_query = [];
		if ( $temporada_term_id ) {
			$tax_query[] = [
				'taxonomy' => 'scl_temporada',
				'field'    => 'term_id',
				'terms'    => $temporada_term_id,
			];
		}

		$partidos = get_posts( [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
			'tax_query'      => $tax_query,
			'orderby'        => 'meta_value',
			'meta_key'       => 'scl_partido_fecha',
			'order'          => 'ASC',
		] );

		$data = array_map( function( $p ) {
			$local_id  = absint( get_post_meta( $p->ID, 'scl_partido_equipo_local_id',  true ) );
			$visita_id = absint( get_post_meta( $p->ID, 'scl_partido_equipo_visita_id', true ) );
			return [
				'ID'            => $p->ID,
				'titulo'        => $p->post_title,
				'torneo_id'     => absint( get_post_meta( $p->ID, 'scl_partido_torneo_id', true ) ),
				'local_id'      => $local_id,
				'visita_id'     => $visita_id,
				'local_nombre'  => get_the_title( $local_id ),
				'visita_nombre' => get_the_title( $visita_id ),
				'goles_local'   => get_post_meta( $p->ID, 'scl_partido_goles_local',  true ),
				'goles_visita'  => get_post_meta( $p->ID, 'scl_partido_goles_visita', true ),
				'estado'        => get_post_meta( $p->ID, 'scl_partido_estado',       true ),
				'tipo_fase'     => get_post_meta( $p->ID, 'scl_partido_tipo_fase',    true ),
				'fecha'         => get_post_meta( $p->ID, 'scl_partido_fecha',        true ),
			];
		}, $partidos );

		wp_send_json_success( $data );
	}
}
