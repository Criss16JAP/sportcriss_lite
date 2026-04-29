<?php
/**
 * Motor de estadísticas individuales de jugadores.
 *
 * Todas las queries excluyen es_penales = 1 por defecto para que los
 * goles de penaltis en definición de llaves no inflen las estadísticas.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Stats {

	// -----------------------------------------------------------------------
	// Goleadores
	// -----------------------------------------------------------------------

	/**
	 * Devuelve los goleadores de un torneo/temporada, ordenados por goles DESC.
	 *
	 * @param int $torneo_id
	 * @param int $temporada_term_id  0 = todas las temporadas del torneo
	 * @param int $limite             0 = sin límite
	 * @return array  [ { jugador_id, jugador_nombre, equipo_id, equipo_nombre, goles } ]
	 */
	public static function get_goleadores( int $torneo_id, int $temporada_term_id = 0, int $limite = 10 ): array {
		global $wpdb;

		$where_temporada = $temporada_term_id > 0
			? $wpdb->prepare( 'AND e.temporada_term_id = %d', $temporada_term_id )
			: '';

		$limit_sql = $limite > 0 ? $wpdb->prepare( 'LIMIT %d', $limite ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				e.jugador_id,
				j.post_title        AS jugador_nombre,
				e.equipo_id,
				eq.post_title       AS equipo_nombre,
				SUM(e.goles)        AS goles
			FROM {$wpdb->prefix}scl_estadisticas e
			LEFT JOIN {$wpdb->posts} j  ON j.ID  = e.jugador_id  AND j.post_type = 'scl_jugador'
			LEFT JOIN {$wpdb->posts} eq ON eq.ID = e.equipo_id   AND eq.post_type = 'scl_equipo'
			WHERE e.torneo_id = %d
			  AND e.es_penales = 0
			  $where_temporada
			GROUP BY e.jugador_id, e.equipo_id
			ORDER BY goles DESC
			$limit_sql",
			$torneo_id
		) );
		// phpcs:enable

		return $rows ?: [];
	}

	// -----------------------------------------------------------------------
	// Asistencias
	// -----------------------------------------------------------------------

	/**
	 * @param int $torneo_id
	 * @param int $temporada_term_id
	 * @param int $limite
	 * @return array  [ { jugador_id, jugador_nombre, equipo_id, equipo_nombre, asistencias } ]
	 */
	public static function get_asistencias( int $torneo_id, int $temporada_term_id = 0, int $limite = 10 ): array {
		global $wpdb;

		$where_temporada = $temporada_term_id > 0
			? $wpdb->prepare( 'AND e.temporada_term_id = %d', $temporada_term_id )
			: '';

		$limit_sql = $limite > 0 ? $wpdb->prepare( 'LIMIT %d', $limite ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				e.jugador_id,
				j.post_title          AS jugador_nombre,
				e.equipo_id,
				eq.post_title         AS equipo_nombre,
				SUM(e.asistencias)    AS asistencias
			FROM {$wpdb->prefix}scl_estadisticas e
			LEFT JOIN {$wpdb->posts} j  ON j.ID  = e.jugador_id  AND j.post_type = 'scl_jugador'
			LEFT JOIN {$wpdb->posts} eq ON eq.ID = e.equipo_id   AND eq.post_type = 'scl_equipo'
			WHERE e.torneo_id = %d
			  AND e.es_penales = 0
			  $where_temporada
			GROUP BY e.jugador_id, e.equipo_id
			ORDER BY asistencias DESC
			$limit_sql",
			$torneo_id
		) );
		// phpcs:enable

		return $rows ?: [];
	}

	// -----------------------------------------------------------------------
	// Tarjetas
	// -----------------------------------------------------------------------

	/**
	 * @param int    $torneo_id
	 * @param int    $temporada_term_id
	 * @param string $tipo  'amarilla' | 'roja' | 'ambas'
	 * @param int    $limite
	 * @return array  [ { jugador_id, jugador_nombre, equipo_id, equipo_nombre, amarillas, rojas } ]
	 */
	public static function get_tarjetas( int $torneo_id, int $temporada_term_id = 0, string $tipo = 'ambas', int $limite = 10 ): array {
		global $wpdb;

		$where_temporada = $temporada_term_id > 0
			? $wpdb->prepare( 'AND e.temporada_term_id = %d', $temporada_term_id )
			: '';

		$having = match ( $tipo ) {
			'amarilla' => 'HAVING amarillas > 0',
			'roja'     => 'HAVING rojas > 0',
			default    => 'HAVING (amarillas > 0 OR rojas > 0)',
		};

		$order = $tipo === 'roja' ? 'rojas DESC' : 'amarillas DESC';
		$limit_sql = $limite > 0 ? $wpdb->prepare( 'LIMIT %d', $limite ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				e.jugador_id,
				j.post_title               AS jugador_nombre,
				e.equipo_id,
				eq.post_title              AS equipo_nombre,
				SUM(e.tarjeta_amarilla)    AS amarillas,
				SUM(e.tarjeta_roja)        AS rojas
			FROM {$wpdb->prefix}scl_estadisticas e
			LEFT JOIN {$wpdb->posts} j  ON j.ID  = e.jugador_id  AND j.post_type = 'scl_jugador'
			LEFT JOIN {$wpdb->posts} eq ON eq.ID = e.equipo_id   AND eq.post_type = 'scl_equipo'
			WHERE e.torneo_id = %d
			  $where_temporada
			GROUP BY e.jugador_id, e.equipo_id
			$having
			ORDER BY $order
			$limit_sql",
			$torneo_id
		) );
		// phpcs:enable

		return $rows ?: [];
	}

	// -----------------------------------------------------------------------
	// Calificaciones promedio
	// -----------------------------------------------------------------------

	/**
	 * @param int $torneo_id
	 * @param int $temporada_term_id
	 * @param int $minimo_partidos   Mínimo de partidos jugados para aparecer
	 * @param int $limite
	 * @return array  [ { jugador_id, jugador_nombre, equipo_id, equipo_nombre, promedio, partidos } ]
	 */
	public static function get_calificaciones( int $torneo_id, int $temporada_term_id = 0, int $minimo_partidos = 3, int $limite = 10 ): array {
		global $wpdb;

		$where_temporada = $temporada_term_id > 0
			? $wpdb->prepare( 'AND e.temporada_term_id = %d', $temporada_term_id )
			: '';

		$limit_sql = $limite > 0 ? $wpdb->prepare( 'LIMIT %d', $limite ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				e.jugador_id,
				j.post_title              AS jugador_nombre,
				e.equipo_id,
				eq.post_title             AS equipo_nombre,
				ROUND(AVG(e.calificacion), 2) AS promedio,
				COUNT(e.id)               AS partidos
			FROM {$wpdb->prefix}scl_estadisticas e
			LEFT JOIN {$wpdb->posts} j  ON j.ID  = e.jugador_id  AND j.post_type = 'scl_jugador'
			LEFT JOIN {$wpdb->posts} eq ON eq.ID = e.equipo_id   AND eq.post_type = 'scl_equipo'
			WHERE e.torneo_id = %d
			  AND e.calificacion IS NOT NULL
			  $where_temporada
			GROUP BY e.jugador_id, e.equipo_id
			HAVING partidos >= %d
			ORDER BY promedio DESC
			$limit_sql",
			$torneo_id,
			$minimo_partidos
		) );
		// phpcs:enable

		return $rows ?: [];
	}

	// -----------------------------------------------------------------------
	// Valla menos vencida
	// -----------------------------------------------------------------------

	/**
	 * Goles en contra de cada equipo (como local + visitante) en partidos finalizados.
	 * Menos goles recibidos = mejor valla.
	 *
	 * @param int $torneo_id
	 * @param int $temporada_term_id
	 * @param int $limite
	 * @return array  [ { equipo_id, equipo_nombre, goles_en_contra, partidos } ]
	 */
	public static function get_valla_menos_vencida( int $torneo_id, int $temporada_term_id = 0, int $limite = 10 ): array {
		global $wpdb;

		$meta_torneo     = $wpdb->prepare( '%s', 'scl_partido_torneo_id' );
		$meta_local_id   = $wpdb->prepare( '%s', 'scl_partido_equipo_local_id' );
		$meta_visita_id  = $wpdb->prepare( '%s', 'scl_partido_equipo_visita_id' );
		$meta_goles_loc  = $wpdb->prepare( '%s', 'scl_partido_goles_local' );
		$meta_goles_vis  = $wpdb->prepare( '%s', 'scl_partido_goles_visita' );
		$meta_estado     = $wpdb->prepare( '%s', 'scl_partido_estado' );
		$meta_temporada  = $wpdb->prepare( '%s', 'scl_temporada' );

		$tax_join  = '';
		$tax_where = '';

		if ( $temporada_term_id > 0 ) {
			$tax_join  = "INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			              INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			                         AND tt.taxonomy = 'scl_temporada'
			                         AND tt.term_id = " . absint( $temporada_term_id );
		}

		$limit_sql = $limite > 0 ? $wpdb->prepare( 'LIMIT %d', $limite ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				equipo_id,
				eq.post_title AS equipo_nombre,
				SUM(goles_en_contra) AS goles_en_contra,
				COUNT(*) AS partidos
			FROM (
				/* Como local: goles en contra = goles visitante */
				SELECT
					pm_local.meta_value AS equipo_id,
					CAST(pm_gv.meta_value AS UNSIGNED) AS goles_en_contra
				FROM {$wpdb->posts} p
				$tax_join
				INNER JOIN {$wpdb->postmeta} pm_t      ON pm_t.post_id = p.ID      AND pm_t.meta_key = 'scl_partido_torneo_id'   AND pm_t.meta_value = %d
				INNER JOIN {$wpdb->postmeta} pm_estado  ON pm_estado.post_id = p.ID AND pm_estado.meta_key = 'scl_partido_estado'   AND pm_estado.meta_value = 'finalizado'
				INNER JOIN {$wpdb->postmeta} pm_local   ON pm_local.post_id = p.ID  AND pm_local.meta_key = 'scl_partido_equipo_local_id'
				INNER JOIN {$wpdb->postmeta} pm_gv      ON pm_gv.post_id = p.ID     AND pm_gv.meta_key = 'scl_partido_goles_visita'
				WHERE p.post_type = 'scl_partido' AND p.post_status = 'publish'

				UNION ALL

				/* Como visitante: goles en contra = goles local */
				SELECT
					pm_visita.meta_value AS equipo_id,
					CAST(pm_gl.meta_value AS UNSIGNED) AS goles_en_contra
				FROM {$wpdb->posts} p
				$tax_join
				INNER JOIN {$wpdb->postmeta} pm_t      ON pm_t.post_id = p.ID       AND pm_t.meta_key = 'scl_partido_torneo_id'    AND pm_t.meta_value = %d
				INNER JOIN {$wpdb->postmeta} pm_estado  ON pm_estado.post_id = p.ID  AND pm_estado.meta_key = 'scl_partido_estado'   AND pm_estado.meta_value = 'finalizado'
				INNER JOIN {$wpdb->postmeta} pm_visita  ON pm_visita.post_id = p.ID  AND pm_visita.meta_key = 'scl_partido_equipo_visita_id'
				INNER JOIN {$wpdb->postmeta} pm_gl      ON pm_gl.post_id = p.ID      AND pm_gl.meta_key = 'scl_partido_goles_local'
				WHERE p.post_type = 'scl_partido' AND p.post_status = 'publish'
			) sub
			LEFT JOIN {$wpdb->posts} eq ON eq.ID = sub.equipo_id AND eq.post_type = 'scl_equipo'
			GROUP BY equipo_id
			ORDER BY goles_en_contra ASC
			$limit_sql",
			$torneo_id,
			$torneo_id
		) );
		// phpcs:enable

		return $rows ?: [];
	}

	// -----------------------------------------------------------------------
	// Perfil completo de un jugador
	// -----------------------------------------------------------------------

	/**
	 * Estadísticas acumuladas de un jugador en un torneo/temporada.
	 *
	 * @param int $jugador_id
	 * @param int $torneo_id          0 = todos los torneos
	 * @param int $temporada_term_id  0 = todas las temporadas
	 * @return object|null
	 */
	public static function get_stats_jugador( int $jugador_id, int $torneo_id = 0, int $temporada_term_id = 0 ): ?object {
		global $wpdb;

		$where_torneo    = $torneo_id > 0
			? $wpdb->prepare( 'AND torneo_id = %d', $torneo_id )
			: '';
		$where_temporada = $temporada_term_id > 0
			? $wpdb->prepare( 'AND temporada_term_id = %d', $temporada_term_id )
			: '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				SUM(goles)             AS goles,
				SUM(asistencias)       AS asistencias,
				SUM(tarjeta_amarilla)  AS amarillas,
				SUM(tarjeta_roja)      AS rojas,
				ROUND(AVG(CASE WHEN calificacion IS NOT NULL THEN calificacion END), 2) AS promedio_calificacion,
				COUNT(id)              AS partidos
			FROM {$wpdb->prefix}scl_estadisticas
			WHERE jugador_id = %d
			  AND es_penales = 0
			  $where_torneo
			  $where_temporada",
			$jugador_id
		) );
		// phpcs:enable

		return $row ?: null;
	}

	// -----------------------------------------------------------------------
	// Validación de consistencia
	// -----------------------------------------------------------------------

	/**
	 * Verifica que los goles ingresados para un partido en estadísticas
	 * sumen exactamente los goles del marcador.
	 *
	 * @param int $partido_id
	 * @param int $equipo_id
	 * @return array  [ 'ok' => bool, 'marcador' => int, 'suma_jugadores' => int ]
	 */
	public static function validar_goles_partido( int $partido_id, int $equipo_id ): array {
		global $wpdb;

		$es_local   = (int) get_post_meta( $partido_id, 'scl_partido_equipo_local_id',  true ) === $equipo_id;
		$meta_goles = $es_local ? 'scl_partido_goles_local' : 'scl_partido_goles_visita';
		$marcador   = (int) get_post_meta( $partido_id, $meta_goles, true );

		$suma = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(goles)
			 FROM {$wpdb->prefix}scl_estadisticas
			 WHERE partido_id = %d AND equipo_id = %d AND es_penales = 0",
			$partido_id,
			$equipo_id
		) );

		return [
			'ok'              => $suma === $marcador,
			'marcador'        => $marcador,
			'suma_jugadores'  => $suma,
		];
	}

	// -----------------------------------------------------------------------
	// Helpers de inscripciones
	// -----------------------------------------------------------------------

	/**
	 * Devuelve los jugadores inscritos en un equipo para un torneo/temporada.
	 *
	 * @param int $equipo_id
	 * @param int $torneo_id
	 * @param int $temporada_term_id
	 * @return array  [ { id, jugador_id, jugador_nombre, jugador_foto_id } ]
	 */
	public static function get_jugadores_inscritos( int $equipo_id, int $torneo_id, int $temporada_term_id = 0 ): array {
		global $wpdb;

		$where_temporada = $temporada_term_id > 0
			? $wpdb->prepare( 'AND i.temporada_term_id = %d', $temporada_term_id )
			: '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				i.id,
				i.jugador_id,
				j.post_title  AS jugador_nombre,
				pm.meta_value AS jugador_foto_id
			FROM {$wpdb->prefix}scl_inscripciones i
			INNER JOIN {$wpdb->posts} j ON j.ID = i.jugador_id AND j.post_type = 'scl_jugador' AND j.post_status = 'publish'
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = j.ID AND pm.meta_key = 'scl_jugador_foto'
			WHERE i.equipo_id = %d
			  AND i.torneo_id = %d
			  AND i.activo    = 1
			  $where_temporada
			ORDER BY j.post_title ASC",
			$equipo_id,
			$torneo_id
		) );
		// phpcs:enable

		return $rows ?: [];
	}
}
