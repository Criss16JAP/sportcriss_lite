<?php
/**
 * Exportación visual de tabla de posiciones.
 *
 * Sirve una vista HTML limpia (sin chrome de WP) autenticada por token HMAC diario.
 * No requiere que el visitante esté logueado — el token es la autorización.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ---------------------------------------------------------------------------
// Funciones globales auxiliares
// ---------------------------------------------------------------------------

/**
 * Genera un token HMAC diario vinculado al torneo y su autor.
 * Expira al cambiar el día (UTC).
 */
function scl_generar_token_exportacion( int $torneo_id, int $user_id ): string {
	$secret = wp_salt( 'auth' );
	$token  = hash_hmac(
		'sha256',
		$torneo_id . '|' . $user_id . '|' . gmdate( 'Y-m-d' ),
		$secret
	);
	return substr( $token, 0, 32 );
}

/**
 * Verifica que el token corresponde al autor del torneo en el día de hoy.
 */
function scl_verificar_token_exportacion( string $token, int $torneo_id ): bool {
	if ( empty( $token ) || ! $torneo_id ) {
		return false;
	}
	$torneo = get_post( $torneo_id );
	if ( ! $torneo || 'scl_torneo' !== $torneo->post_type ) {
		return false;
	}
	$esperado = scl_generar_token_exportacion( $torneo_id, (int) $torneo->post_author );
	return hash_equals( $esperado, $token );
}

/**
 * Convierte un color hex a la cadena "R, G, B" usada en CSS var().
 */
function scl_hex_to_rgb( string $hex ): string {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	return "{$r}, {$g}, {$b}";
}

// ---------------------------------------------------------------------------
// Clase principal
// ---------------------------------------------------------------------------

/**
 * Class Scl_Export
 */
class Scl_Export {

	/**
	 * Registra los hooks del módulo de exportación.
	 * Llamado desde scl_run() en lugar de inyectarse vía Loader.
	 */
	public function init(): void {
		add_filter( 'query_vars',        [ $this, 'registrar_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'interceptar_exportacion' ] );
		add_action( 'init',              [ $this, 'registrar_rewrite' ] );
	}

	/** @internal También llamado desde scl_activar_plugin() */
	public function registrar_rewrite(): void {
		add_rewrite_rule(
			'^scl-exportar/?$',
			'index.php?scl_exportar=1',
			'top'
		);
	}

	public function registrar_query_vars( array $vars ): array {
		$vars[] = 'scl_exportar';
		$vars[] = 'scl_torneo';
		$vars[] = 'scl_temporada';
		$vars[] = 'scl_grupo';
		$vars[] = 'scl_token';
		$vars[] = 'scl_tipo';
		$vars[] = 'scl_stats_tipo';
		$vars[] = 'scl_stats_limite';
		return $vars;
	}

	/**
	 * Intercepta peticiones con ?scl_exportar=1, valida token y sirve la vista limpia.
	 */
	public function interceptar_exportacion(): void {
		if ( ! get_query_var( 'scl_exportar' ) ) {
			return;
		}

		$token     = sanitize_key( get_query_var( 'scl_token' ) );
		$torneo_id = (int) get_query_var( 'scl_torneo' );

		if ( ! scl_verificar_token_exportacion( $token, $torneo_id ) ) {
			wp_die(
				esc_html__( 'Enlace de exportación inválido o expirado.', 'sportcriss-lite' ),
				esc_html__( 'Error', 'sportcriss-lite' ),
				[ 'response' => 403 ]
			);
		}

		$tipo              = sanitize_key( get_query_var( 'scl_tipo' ) ?: 'tabla' );
		$temporada_term_id = (int) get_query_var( 'scl_temporada' );
		$grupo_id          = (int) get_query_var( 'scl_grupo' );

		// Datos de torneo comunes a todos los tipos
		$torneo        = get_post( $torneo_id );
		$temporada_obj = $temporada_term_id ? get_term( $temporada_term_id, 'scl_temporada' ) : null;

		$logo_id   = (int) get_post_meta( $torneo_id, 'scl_torneo_logo',            true );
		$fondo_id  = (int) get_post_meta( $torneo_id, 'scl_torneo_fondo',           true );
		$color_1   = get_post_meta( $torneo_id, 'scl_torneo_color_primario',    true ) ?: '#1a3a5c';
		$color_2   = get_post_meta( $torneo_id, 'scl_torneo_color_secundario',  true ) ?: '#f5a623';
		$siglas    = get_post_meta( $torneo_id, 'scl_torneo_siglas',            true ) ?: '';

		$logo_url  = $logo_id  ? (string) wp_get_attachment_image_url( $logo_id,  [ 120, 120 ] ) : '';
		$fondo_url = $fondo_id ? (string) wp_get_attachment_image_url( $fondo_id, 'large' )       : '';

		$titulo_torneo = $torneo ? $torneo->post_title : '';
		$titulo_temp   = ( $temporada_obj && ! is_wp_error( $temporada_obj ) ) ? $temporada_obj->name : '';

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=UTF-8' );

		if ( 'stats' === $tipo ) {
			$stats_tipo   = sanitize_key( get_query_var( 'scl_stats_tipo' ) ?: 'goleadores' );
			$stats_limite = max( 5, min( 20, (int) ( get_query_var( 'scl_stats_limite' ) ?: 10 ) ) );
			$stats_data   = self::get_stats_render( $torneo_id, $temporada_term_id, $stats_tipo, $stats_limite );
			include SCL_PATH . 'templates/public/export-stats.php';
		} elseif ( 'partidos' === $tipo ) {
			$partidos_data = self::get_partidos_render( $torneo_id, $temporada_term_id );
			include SCL_PATH . 'templates/public/export-partidos.php';
		} else {
			$grupo_obj  = $grupo_id ? get_post( $grupo_id ) : null;
			$titulo_grupo = ( $grupo_obj && 'scl_grupo' === $grupo_obj->post_type ) ? $grupo_obj->post_title : '';
			$tabla      = self::get_tabla_render( $torneo_id, $temporada_term_id, $grupo_id );
			$updated_at = get_post_meta( $torneo_id, "scl_tabla_{$temporada_term_id}_updated_at", true );
			$fecha_act  = $updated_at ? date_i18n( 'j M Y · H:i', strtotime( $updated_at ) ) : '';
			include SCL_PATH . 'templates/public/export-tabla.php';
		}
		exit;
	}

	// ---------------------------------------------------------------------------
	// Métodos estáticos de utilidad
	// ---------------------------------------------------------------------------

	/**
	 * Construye la URL de exportación limpia para un torneo/temporada/grupo.
	 */
	public static function get_url(
		int $torneo_id,
		int $temporada_term_id = 0,
		int $grupo_id = 0,
		int $autor_id = 0
	): string {
		$autor_id = $autor_id ?: (int) get_post_field( 'post_author', $torneo_id );
		$token    = scl_generar_token_exportacion( $torneo_id, $autor_id );

		return add_query_arg( [
			'scl_exportar'  => '1',
			'scl_torneo'    => $torneo_id,
			'scl_temporada' => $temporada_term_id,
			'scl_grupo'     => $grupo_id,
			'scl_token'     => $token,
		], home_url( '/' ) );
	}

	/**
	 * Lee la tabla desde el cache y la devuelve lista para renderizar.
	 * El cache ya incluye equipo_id, nombre y escudo_url.
	 * Si escudo_url está vacío, intenta re-obtenerlo.
	 */
	public static function get_tabla_render(
		int $torneo_id,
		int $temporada_term_id,
		int $grupo_id = 0
	): array {
		$tabla = scl_get_tabla( $torneo_id, $temporada_term_id, $grupo_id ?: 'general' );
		if ( empty( $tabla ) ) {
			return [];
		}

		return array_map( function ( array $equipo ) {
			// Si el cache no tiene escudo_url o está vacío, reintentarlo
			if ( empty( $equipo['escudo_url'] ) && ! empty( $equipo['equipo_id'] ) ) {
				$escudo_id = (int) get_post_meta( $equipo['equipo_id'], 'scl_equipo_escudo', true );
				if ( $escudo_id ) {
					$equipo['escudo_url'] = (string) wp_get_attachment_image_url( $escudo_id, [ 60, 60 ] );
				}
			}
			return $equipo;
		}, $tabla );
	}

	/**
	 * Construye la URL de exportación de estadísticas individuales.
	 */
	public static function get_url_stats(
		int $torneo_id,
		int $temporada_term_id = 0,
		string $stats_tipo = 'goleadores',
		int $limite = 10,
		int $autor_id = 0
	): string {
		$autor_id = $autor_id ?: (int) get_post_field( 'post_author', $torneo_id );
		$token    = scl_generar_token_exportacion( $torneo_id, $autor_id );

		return add_query_arg( [
			'scl_exportar'      => '1',
			'scl_tipo'          => 'stats',
			'scl_torneo'        => $torneo_id,
			'scl_temporada'     => $temporada_term_id,
			'scl_stats_tipo'    => $stats_tipo,
			'scl_stats_limite'  => $limite,
			'scl_token'         => $token,
		], home_url( '/' ) );
	}

	/**
	 * Construye la URL de exportación de partidos/resultados.
	 */
	public static function get_url_partidos(
		int $torneo_id,
		int $temporada_term_id = 0,
		int $autor_id = 0
	): string {
		$autor_id = $autor_id ?: (int) get_post_field( 'post_author', $torneo_id );
		$token    = scl_generar_token_exportacion( $torneo_id, $autor_id );

		return add_query_arg( [
			'scl_exportar'  => '1',
			'scl_tipo'      => 'partidos',
			'scl_torneo'    => $torneo_id,
			'scl_temporada' => $temporada_term_id,
			'scl_token'     => $token,
		], home_url( '/' ) );
	}

	/**
	 * Obtiene los datos de estadísticas para renderizar en el template.
	 */
	public static function get_stats_render( int $torneo_id, int $temporada_term_id, string $tipo, int $limite ): array {
		switch ( $tipo ) {
			case 'asistencias':
				return Scl_Stats::get_asistencias( $torneo_id, $temporada_term_id, $limite );
			case 'tarjetas_amarillas':
				return Scl_Stats::get_tarjetas( $torneo_id, $temporada_term_id, 'amarilla', $limite );
			case 'tarjetas_rojas':
				return Scl_Stats::get_tarjetas( $torneo_id, $temporada_term_id, 'roja', $limite );
			case 'calificaciones':
				return Scl_Stats::get_calificaciones( $torneo_id, $temporada_term_id, 1, $limite );
			default:
				return Scl_Stats::get_goleadores( $torneo_id, $temporada_term_id, $limite );
		}
	}

	/**
	 * Obtiene partidos agrupados por jornada para renderizar.
	 * Devuelve array: [ [ 'jornada' => term, 'partidos' => [...] ], ... ]
	 */
	public static function get_partidos_render( int $torneo_id, int $temporada_term_id ): array {
		$args = [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => 'scl_partido_fecha',
			'order'          => 'ASC',
			'meta_query'     => [ [
				'key'   => 'scl_partido_torneo_id',
				'value' => $torneo_id,
				'type'  => 'NUMERIC',
			] ],
		];

		if ( $temporada_term_id ) {
			$args['tax_query'] = [ [
				'taxonomy' => 'scl_temporada',
				'field'    => 'term_id',
				'terms'    => $temporada_term_id,
			] ];
		}

		$partidos = get_posts( $args );
		if ( empty( $partidos ) ) {
			return [];
		}

		// Precarga metas y escudos
		$ids = wp_list_pluck( $partidos, 'ID' );
		update_meta_cache( 'post', $ids );

		// Agrupar por jornada
		$grupos = [];
		$sin_jornada = [];

		foreach ( $partidos as $p ) {
			$jornadas = wp_get_post_terms( $p->ID, 'scl_jornada' );
			$jornada  = ( ! is_wp_error( $jornadas ) && ! empty( $jornadas ) ) ? $jornadas[0] : null;

			$local_id   = (int) get_post_meta( $p->ID, 'scl_partido_equipo_local_id',  true );
			$visita_id  = (int) get_post_meta( $p->ID, 'scl_partido_equipo_visita_id', true );
			$goles_l    = get_post_meta( $p->ID, 'scl_partido_goles_local',  true );
			$goles_v    = get_post_meta( $p->ID, 'scl_partido_goles_visita', true );
			$estado     = get_post_meta( $p->ID, 'scl_partido_estado',       true );
			$fecha      = get_post_meta( $p->ID, 'scl_partido_fecha',        true );

			$local_post  = get_post( $local_id );
			$visita_post = get_post( $visita_id );

			$escudo_l_id = (int) get_post_meta( $local_id,  'scl_equipo_escudo', true );
			$escudo_v_id = (int) get_post_meta( $visita_id, 'scl_equipo_escudo', true );

			$partido_data = [
				'id'           => $p->ID,
				'local'        => $local_post  ? $local_post->post_title  : '—',
				'visita'       => $visita_post ? $visita_post->post_title : '—',
				'escudo_local' => $escudo_l_id ? (string) wp_get_attachment_image_url( $escudo_l_id, [ 50, 50 ] ) : '',
				'escudo_visita'=> $escudo_v_id ? (string) wp_get_attachment_image_url( $escudo_v_id, [ 50, 50 ] ) : '',
				'goles_local'  => $goles_l !== '' && $goles_l !== null && $estado === 'finalizado' ? (int) $goles_l : null,
				'goles_visita' => $goles_v !== '' && $goles_v !== null && $estado === 'finalizado' ? (int) $goles_v : null,
				'estado'       => $estado ?: 'pendiente',
				'fecha'        => $fecha ? date_i18n( 'j M', strtotime( $fecha ) ) : '',
			];

			if ( $jornada ) {
				$grupos[ $jornada->term_id ]['jornada'] = $jornada;
				$grupos[ $jornada->term_id ]['partidos'][] = $partido_data;
			} else {
				$sin_jornada[] = $partido_data;
			}
		}

		$resultado = array_values( $grupos );
		if ( ! empty( $sin_jornada ) ) {
			$resultado[] = [ 'jornada' => null, 'partidos' => $sin_jornada ];
		}

		return $resultado;
	}

	/**
	 * Compatibilidad: mantiene el método anterior como alias durante transición.
	 * @deprecated Usar interceptar_exportacion() vía init()
	 */
	public function servir_vista_limpia(): void {
		$this->interceptar_exportacion();
	}
}
