<?php
/**
 * Motor de Cálculo de tabla de posiciones.
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
	 * Hook disparado al guardar un partido.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function recalcular_si_aplica( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$estado = get_post_meta( $post_id, 'scl_partido_estado', true );
		if ( 'finalizado' !== $estado ) {
			return;
		}

		// Verificar tipo de fase antes de recalcular
		$tipo_fase = get_post_meta( $post_id, 'scl_partido_tipo_fase', true );
		if ( $tipo_fase !== 'grupos' ) return; // playoff no afecta tabla

		$torneo_id = (int) get_post_meta( $post_id, 'scl_partido_torneo_id', true );
		$terms = wp_get_post_terms( $post_id, 'scl_temporada' );
		$temporada_term_id = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->term_id : 0;

		if ( $torneo_id && $temporada_term_id ) {
			$this->recalcular_tabla( $torneo_id, $temporada_term_id );
		}
	}

	/**
	 * Recalcula la tabla de posiciones de una temporada en un torneo.
	 *
	 * @param int $torneo_id
	 * @param int $temporada_term_id
	 */
	public function recalcular_tabla( int $torneo_id, int $temporada_term_id ): void {
		if ( ! $torneo_id || ! $temporada_term_id ) {
			return;
		}

		$pts_victoria = (int) get_post_meta( $torneo_id, 'scl_torneo_puntos_victoria', true ) ?: 3;
		$pts_empate   = (int) get_post_meta( $torneo_id, 'scl_torneo_puntos_empate',   true ) ?: 1;
		$pts_derrota  = (int) get_post_meta( $torneo_id, 'scl_torneo_puntos_derrota',  true ) ?: 0;
		$desempate_raw = get_post_meta( $torneo_id, 'scl_torneo_desempate_orden', true );
		$desempate = $desempate_raw ? json_decode( $desempate_raw, true ) : [];

		if ( ! is_array( $desempate ) ) {
			$desempate = [];
		}

		$args = [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'   => 'scl_partido_torneo_id',
					'value' => $torneo_id,
					'type'  => 'NUMERIC',
				],
				[
					'key'   => 'scl_partido_estado',
					'value' => 'finalizado',
				],
			],
			'tax_query' => [
				[
					'taxonomy' => 'scl_temporada',
					'field'    => 'term_id',
					'terms'    => $temporada_term_id,
				]
			]
		];

		$partidos_query = new WP_Query( $args );
		$partidos = $partidos_query->posts;

		$partidos_validos = array_filter( $partidos, function( $p ) {
			$tipo_fase = get_post_meta( $p->ID, 'scl_partido_tipo_fase', true );
			return $tipo_fase === 'grupos';
		});

		$stats = [];

		$init_equipo = function() {
			return [
				'PJ' => 0, 'PG' => 0, 'PE' => 0, 'PP' => 0,
				'GF' => 0, 'GC' => 0, 'DG' => 0, 'Pts' => 0
			];
		};

		foreach ( $partidos_validos as $p ) {
			$local_id  = (int) get_post_meta( $p->ID, 'scl_partido_equipo_local_id', true );
			$visita_id = (int) get_post_meta( $p->ID, 'scl_partido_equipo_visita_id', true );
			$gl        = (int) get_post_meta( $p->ID, 'scl_partido_goles_local', true );
			$gv        = (int) get_post_meta( $p->ID, 'scl_partido_goles_visita', true );
			
			if ( ! $local_id || ! $visita_id ) continue;

			$grupo_id  = (int) get_post_meta( $p->ID, 'scl_partido_grupo_id', true );

			$contextos = [ 'general' ];
			if ( $grupo_id ) {
				$contextos[] = $grupo_id;
			}

			foreach ( $contextos as $ctx ) {
				if ( ! isset( $stats[ $ctx ] ) ) {
					$stats[ $ctx ] = [];
				}
				if ( ! isset( $stats[ $ctx ][ $local_id ] ) ) {
					$stats[ $ctx ][ $local_id ] = $init_equipo();
				}
				if ( ! isset( $stats[ $ctx ][ $visita_id ] ) ) {
					$stats[ $ctx ][ $visita_id ] = $init_equipo();
				}

				if ( $gl > $gv ) {
					$stats[ $ctx ][ $local_id ]['PJ']++;
					$stats[ $ctx ][ $local_id ]['PG']++;
					$stats[ $ctx ][ $local_id ]['GF'] += $gl;
					$stats[ $ctx ][ $local_id ]['GC'] += $gv;
					$stats[ $ctx ][ $local_id ]['Pts'] += $pts_victoria;

					$stats[ $ctx ][ $visita_id ]['PJ']++;
					$stats[ $ctx ][ $visita_id ]['PP']++;
					$stats[ $ctx ][ $visita_id ]['GF'] += $gv;
					$stats[ $ctx ][ $visita_id ]['GC'] += $gl;
					$stats[ $ctx ][ $visita_id ]['Pts'] += $pts_derrota;
				} elseif ( $gl < $gv ) {
					$stats[ $ctx ][ $local_id ]['PJ']++;
					$stats[ $ctx ][ $local_id ]['PP']++;
					$stats[ $ctx ][ $local_id ]['GF'] += $gl;
					$stats[ $ctx ][ $local_id ]['GC'] += $gv;
					$stats[ $ctx ][ $local_id ]['Pts'] += $pts_derrota;

					$stats[ $ctx ][ $visita_id ]['PJ']++;
					$stats[ $ctx ][ $visita_id ]['PG']++;
					$stats[ $ctx ][ $visita_id ]['GF'] += $gv;
					$stats[ $ctx ][ $visita_id ]['GC'] += $gl;
					$stats[ $ctx ][ $visita_id ]['Pts'] += $pts_victoria;
				} else {
					$stats[ $ctx ][ $local_id ]['PJ']++;
					$stats[ $ctx ][ $local_id ]['PE']++;
					$stats[ $ctx ][ $local_id ]['GF'] += $gl;
					$stats[ $ctx ][ $local_id ]['GC'] += $gv;
					$stats[ $ctx ][ $local_id ]['Pts'] += $pts_empate;

					$stats[ $ctx ][ $visita_id ]['PJ']++;
					$stats[ $ctx ][ $visita_id ]['PE']++;
					$stats[ $ctx ][ $visita_id ]['GF'] += $gv;
					$stats[ $ctx ][ $visita_id ]['GC'] += $gl;
					$stats[ $ctx ][ $visita_id ]['Pts'] += $pts_empate;
				}
			}
		}

		// Calcular DG y preparar arrays para ordenamiento
		foreach ( $stats as $ctx => &$equipos ) {
			$equipos_lista = [];
			foreach ( $equipos as $eq_id => $data ) {
				$data['DG'] = $data['GF'] - $data['GC'];
				$data['equipo_id'] = $eq_id;
				$data['nombre'] = get_the_title( $eq_id );
				$escudo_id = get_post_meta( $eq_id, 'scl_equipo_escudo', true );
				$data['escudo_url'] = $escudo_id ? wp_get_attachment_image_url( $escudo_id, 'thumbnail' ) : '';
				$equipos_lista[] = $data;
			}
			$equipos = $equipos_lista;
		}
		unset($equipos);

		// Ordenar tablas
		foreach ( $stats as $ctx => &$equipos ) {
			usort( $equipos, function( $a, $b ) use ( $desempate, $partidos_validos, $pts_victoria, $pts_empate, $pts_derrota ) {
				if ( $a['Pts'] !== $b['Pts'] ) {
					return $b['Pts'] <=> $a['Pts']; // Pts descendente
				}

				foreach ( $desempate as $criterio ) {
					if ( 'diferencia_goles' === $criterio ) {
						if ( $a['DG'] !== $b['DG'] ) return $b['DG'] <=> $a['DG'];
					} elseif ( 'goles_favor' === $criterio ) {
						if ( $a['GF'] !== $b['GF'] ) return $b['GF'] <=> $a['GF'];
					} elseif ( 'goles_contra' === $criterio ) {
						if ( $a['GC'] !== $b['GC'] ) return $a['GC'] <=> $b['GC']; // Ascendente
					} elseif ( 'enfrentamiento_directo' === $criterio ) {
						// Puntos obtenidos solo en partidos entre estos dos equipos
						$pts_a = 0;
						$pts_b = 0;
						foreach ( $partidos_validos as $p ) {
							$lid = (int) get_post_meta( $p->ID, 'scl_partido_equipo_local_id', true );
							$vid = (int) get_post_meta( $p->ID, 'scl_partido_equipo_visita_id', true );
							$gl  = (int) get_post_meta( $p->ID, 'scl_partido_goles_local', true );
							$gv  = (int) get_post_meta( $p->ID, 'scl_partido_goles_visita', true );

							if ( $lid === $a['equipo_id'] && $vid === $b['equipo_id'] ) {
								if ( $gl > $gv ) { $pts_a += $pts_victoria; $pts_b += $pts_derrota; }
								elseif ( $gl < $gv ) { $pts_b += $pts_victoria; $pts_a += $pts_derrota; }
								else { $pts_a += $pts_empate; $pts_b += $pts_empate; }
							} elseif ( $lid === $b['equipo_id'] && $vid === $a['equipo_id'] ) {
								if ( $gl > $gv ) { $pts_b += $pts_victoria; $pts_a += $pts_derrota; }
								elseif ( $gl < $gv ) { $pts_a += $pts_victoria; $pts_b += $pts_derrota; }
								else { $pts_a += $pts_empate; $pts_b += $pts_empate; }
							}
						}
						if ( $pts_a !== $pts_b ) {
							return $pts_b <=> $pts_a;
						}
					}
				}

				return 0;
			});
		}
		unset($equipos);

		$cache = [
			'tablas'     => $stats,
			'updated_at' => current_time( 'c' ),
			'torneo_id'  => $torneo_id,
		];

		update_post_meta( $torneo_id, "scl_tabla_{$temporada_term_id}_cache", wp_json_encode( $cache ) );
		update_post_meta( $torneo_id, "scl_tabla_{$temporada_term_id}_updated_at", current_time( 'c' ) );

		// Invalidar transients de todas las vistas de esta tabla
		$grupos_ids = array_merge( [ 'general' ], wp_list_pluck( scl_get_grupos_por_torneo( $torneo_id ), 'ID' ) );
		foreach ( $grupos_ids as $gid ) {
			delete_transient( 'scl_tabla_' . $torneo_id . '_' . $temporada_term_id . '_' . $gid );
		}
	}

	/**
	 * Recalcula las tablas de todas las temporadas de un torneo.
	 *
	 * @param int $torneo_id
	 */
	public function recalcular_todas( int $torneo_id ): void {
		$terms = get_terms( [
			'taxonomy'   => 'scl_temporada',
			'hide_empty' => false,
			'meta_query' => [
				[
					'key'     => 'scl_temporada_torneo_id',
					'value'   => $torneo_id,
					'compare' => '=',
				]
			],
		] );

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $t ) {
				$this->recalcular_tabla( $torneo_id, $t->term_id );
			}
		}
	}
}

/**
 * Función global auxiliar para leer la tabla cacheada
 *
 * @param int    $torneo_id
 * @param int    $temporada_term_id
 * @param string $grupo_id 'general' o term_id del grupo
 * @return array
 */
function scl_get_tabla( int $torneo_id, int $temporada_term_id, $grupo_id = 'general' ): array {
	$transient_key = 'scl_tabla_' . $torneo_id . '_' . $temporada_term_id . '_' . $grupo_id;
	$cached = get_transient( $transient_key );
	if ( false !== $cached ) return $cached;

	$raw = get_post_meta( $torneo_id, "scl_tabla_{$temporada_term_id}_cache", true );
	if ( ! $raw ) return [];
	$cache = json_decode( $raw, true );
	if ( ! is_array( $cache ) || ! isset( $cache['tablas'] ) ) return [];
	$result = $cache['tablas'][ $grupo_id ] ?? [];

	$ttl = (int) get_option( 'scl_tabla_transient_ttl', 5 );
	set_transient( $transient_key, $result, $ttl * MINUTE_IN_SECONDS );

	return $result;
}

/**
 * Función global auxiliar para obtener grupos por torneo
 *
 * @param int $torneo_id
 * @return WP_Post[]
 */
function scl_get_grupos_por_torneo( int $torneo_id ): array {
	return get_posts( [
		'post_type'      => 'scl_grupo',
		'post_parent'    => $torneo_id,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );
}
