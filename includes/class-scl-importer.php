<?php
/**
 * Procesador de importación masiva de partidos desde CSV.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Importer
 */
class Scl_Importer {

	const MAX_FILAS = 500;

	const COLUMNAS = [
		'torneo', 'temporada', 'tipo_fase', 'jornada', 'grupo',
		'fecha', 'equipo_local', 'goles_local', 'goles_visitante',
		'equipo_visitante', 'estado',
	];

	/**
	 * Parsea el CSV subido y almacena las filas en un transient.
	 *
	 * @param string $filepath Ruta absoluta al archivo CSV temporal.
	 * @param int    $user_id  ID del Organizador.
	 * @return array|WP_Error Filas parseadas o error.
	 */
	public function parsear_y_guardar( string $filepath, int $user_id ) {
		if ( ! file_exists( $filepath ) ) {
			return new WP_Error( 'file_not_found', 'Archivo no encontrado.' );
		}

		$handle = fopen( $filepath, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'file_open_error', 'No se pudo abrir el archivo.' );
		}

		// Detectar y saltar BOM UTF-8
		$bom = fread( $handle, 3 );
		if ( "\xef\xbb\xbf" !== $bom ) {
			fseek( $handle, 0 );
		}

		$headers = fgetcsv( $handle );
		if ( ! $headers ) {
			fclose( $handle );
			return new WP_Error( 'empty_csv', 'El archivo CSV está vacío.' );
		}

		$headers = array_map( fn( $h ) => strtolower( trim( $h ) ), $headers );

		$faltantes = array_diff( self::COLUMNAS, $headers );
		if ( ! empty( $faltantes ) ) {
			fclose( $handle );
			return new WP_Error(
				'columnas_faltantes',
				'Columnas faltantes: ' . implode( ', ', $faltantes )
			);
		}

		$filas = [];
		$count = 0;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( $count >= self::MAX_FILAS ) {
				fclose( $handle );
				return new WP_Error(
					'max_filas',
					sprintf( 'El CSV supera el límite de %d filas.', self::MAX_FILAS )
				);
			}
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}
			$fila = [];
			foreach ( $headers as $idx => $col ) {
				$fila[ $col ] = isset( $row[ $idx ] ) ? trim( $row[ $idx ] ) : '';
			}
			$filas[] = $fila;
			$count++;
		}
		fclose( $handle );

		if ( empty( $filas ) ) {
			return new WP_Error( 'sin_datos', 'El CSV no contiene filas de datos.' );
		}

		set_transient( 'scl_csv_' . $user_id, $filas, 30 * MINUTE_IN_SECONDS );
		return $filas;
	}

	/**
	 * Valida las filas almacenadas en el transient y devuelve el resumen.
	 *
	 * @param int $user_id ID del Organizador.
	 * @return array Resumen de validación.
	 */
	public function validar( int $user_id ): array {
		$filas = $this->obtener_filas_transient( $user_id );
		if ( is_wp_error( $filas ) ) {
			return [ 'error' => $filas->get_error_message() ];
		}

		$filas_validas     = 0;
		$filas_con_error   = 0;
		$equipos_nuevos    = [];
		$temporadas_nuevas = [];
		$errores           = [];
		$preview           = [];

		foreach ( $filas as $i => $fila ) {
			$num         = $i + 2;
			$fila_errores = $this->validar_fila( $fila, $num, $user_id );

			if ( empty( $fila_errores ) ) {
				$filas_validas++;

				$temporada_nombre = trim( $fila['temporada'] ?? '' );
				if ( $temporada_nombre && ! get_term_by( 'name', $temporada_nombre, 'scl_temporada' ) ) {
					$temporadas_nuevas[ $temporada_nombre ] = true;
				}

				foreach ( [ 'equipo_local', 'equipo_visitante' ] as $campo ) {
					$nombre_eq = trim( $fila[ $campo ] ?? '' );
					if ( $nombre_eq ) {
						$existe = get_posts( [
							'post_type'      => 'scl_equipo',
							'author'         => $user_id,
							'post_status'    => 'publish',
							'title'          => $nombre_eq,
							'posts_per_page' => 1,
							'fields'         => 'ids',
						] );
						if ( empty( $existe ) ) {
							$equipos_nuevos[ $nombre_eq ] = true;
						}
					}
				}
			} else {
				$filas_con_error++;
				foreach ( $fila_errores as $err ) {
					$errores[] = "Fila {$num}: {$err}";
				}
			}

			if ( $i < 5 ) {
				$preview[] = $fila;
			}
		}

		return [
			'total'             => count( $filas ),
			'filas_validas'     => $filas_validas,
			'filas_con_error'   => $filas_con_error,
			'equipos_nuevos'    => array_keys( $equipos_nuevos ),
			'temporadas_nuevas' => array_keys( $temporadas_nuevas ),
			'errores'           => $errores,
			'preview'           => $preview,
		];
	}

	/**
	 * Importa todas las filas válidas del transient y crea los partidos.
	 *
	 * @param int $user_id ID del Organizador.
	 * @return array Resumen: creados, omitidos, errores.
	 */
	public function importar( int $user_id ): array {
		$filas = $this->obtener_filas_transient( $user_id );
		if ( is_wp_error( $filas ) ) {
			return [ 'error' => $filas->get_error_message() ];
		}

		$creados    = 0;
		$omitidos   = 0;
		$errores    = [];
		$recalcular = [];

		foreach ( $filas as $i => $fila ) {
			$num          = $i + 2;
			$fila_errores = $this->validar_fila( $fila, $num, $user_id );

			if ( ! empty( $fila_errores ) ) {
				$omitidos++;
				continue;
			}

			$result = $this->importar_fila( $fila, $user_id );
			if ( is_wp_error( $result ) ) {
				$errores[] = "Fila {$num}: " . $result->get_error_message();
				$omitidos++;
			} else {
				$creados++;
				[ , $torneo_id, $temporada_term_id, $tipo_fase ] = $result;
				if ( 'grupos' === $tipo_fase && $torneo_id && $temporada_term_id ) {
					$key                = "{$torneo_id}:{$temporada_term_id}";
					$recalcular[ $key ] = [ $torneo_id, $temporada_term_id ];
				}
			}
		}

		// Recálculo en batch — garantiza tabla correcta con todos los partidos importados
		foreach ( $recalcular as [ $torneo_id, $term_id ] ) {
			( new Scl_Engine() )->recalcular_tabla( $torneo_id, $term_id );
		}

		delete_transient( 'scl_csv_' . $user_id );

		return [
			'creados'  => $creados,
			'omitidos' => $omitidos,
			'errores'  => $errores,
		];
	}

	/**
	 * Devuelve el contenido CSV de la plantilla de ejemplo.
	 */
	public static function plantilla_csv(): string {
		$lineas = [
			implode( ',', self::COLUMNAS ),
			'Torneo Las Colinas,Apertura 2025,grupos,Fecha 1,Grupo A,2025-03-15,San Pedro FC,2,1,Unicolinas,finalizado',
			'Torneo Las Colinas,Apertura 2025,grupos,Fecha 1,Grupo B,2025-03-16,Los Pinos,,,El Molino,pendiente',
			'Torneo Las Colinas,Apertura 2025,playoff,Semifinal,,2025-04-05,San Pedro FC,1,1,Los Pinos,finalizado',
		];
		return implode( "\r\n", $lineas );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function obtener_filas_transient( int $user_id ) {
		$filas = get_transient( 'scl_csv_' . $user_id );
		if ( false === $filas ) {
			return new WP_Error( 'no_csv', 'No hay ningún CSV cargado. Sube el archivo primero.' );
		}
		return $filas;
	}

	private function validar_fila( array $fila, int $num_fila, int $user_id ): array {
		$errores = [];

		$torneo_nombre = trim( $fila['torneo'] ?? '' );
		if ( ! $torneo_nombre ) {
			$errores[] = 'El campo "torneo" es obligatorio.';
		} else {
			$torneo = get_posts( [
				'post_type'      => 'scl_torneo',
				'author'         => $user_id,
				'post_status'    => 'publish',
				'title'          => $torneo_nombre,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			] );
			if ( empty( $torneo ) ) {
				$errores[] = sprintf( 'El torneo "%s" no existe. Créalo primero.', $torneo_nombre );
			}
		}

		if ( empty( trim( $fila['temporada'] ?? '' ) ) ) {
			$errores[] = 'El campo "temporada" es obligatorio.';
		}

		$tipo_fase = strtolower( trim( $fila['tipo_fase'] ?? '' ) );
		if ( ! in_array( $tipo_fase, [ 'grupos', 'playoff' ], true ) ) {
			$errores[] = 'El campo "tipo_fase" debe ser "grupos" o "playoff".';
		}

		$equipo_local     = trim( $fila['equipo_local']     ?? '' );
		$equipo_visitante = trim( $fila['equipo_visitante'] ?? '' );

		if ( ! $equipo_local ) {
			$errores[] = 'El campo "equipo_local" es obligatorio.';
		}
		if ( ! $equipo_visitante ) {
			$errores[] = 'El campo "equipo_visitante" es obligatorio.';
		}
		if ( $equipo_local && $equipo_visitante && $equipo_local === $equipo_visitante ) {
			$errores[] = 'El equipo local y visitante no pueden ser el mismo.';
		}

		$estado = strtolower( trim( $fila['estado'] ?? '' ) );
		if ( ! in_array( $estado, [ 'pendiente', 'finalizado' ], true ) ) {
			$errores[] = 'El campo "estado" debe ser "pendiente" o "finalizado".';
		}

		if ( 'finalizado' === $estado ) {
			$gl = $fila['goles_local']     ?? '';
			$gv = $fila['goles_visitante'] ?? '';
			if ( '' === $gl || ! is_numeric( $gl ) || (int) $gl < 0 ) {
				$errores[] = '"goles_local" debe ser un entero ≥ 0 cuando el estado es "finalizado".';
			}
			if ( '' === $gv || ! is_numeric( $gv ) || (int) $gv < 0 ) {
				$errores[] = '"goles_visitante" debe ser un entero ≥ 0 cuando el estado es "finalizado".';
			}
		}

		$fecha = trim( $fila['fecha'] ?? '' );
		if ( $fecha && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) ) {
			$errores[] = 'La fecha debe tener el formato AAAA-MM-DD.';
		}

		return $errores;
	}

	/**
	 * Crea un partido a partir de una fila válida.
	 *
	 * @return array|WP_Error  [partido_id, torneo_id, temporada_term_id, tipo_fase] o WP_Error.
	 */
	private function importar_fila( array $fila, int $user_id ) {
		// Torneo
		$torneos = get_posts( [
			'post_type'      => 'scl_torneo',
			'author'         => $user_id,
			'post_status'    => 'publish',
			'title'          => trim( $fila['torneo'] ),
			'posts_per_page' => 1,
		] );
		if ( empty( $torneos ) ) {
			return new WP_Error( 'torneo_not_found', 'Torneo no encontrado.' );
		}
		$torneo_id = $torneos[0]->ID;

		// Temporada: buscar o crear
		$temporada_nombre = trim( $fila['temporada'] );
		$term             = get_term_by( 'name', $temporada_nombre, 'scl_temporada' );
		if ( $term ) {
			$temporada_term_id = $term->term_id;
		} else {
			$result = wp_insert_term( $temporada_nombre, 'scl_temporada' );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$temporada_term_id = $result['term_id'];
			update_term_meta( $temporada_term_id, 'scl_temporada_estado', 'activa' );
			update_term_meta( $temporada_term_id, 'scl_temporada_anio', (string) gmdate( 'Y' ) );
		}

		// Jornada: buscar o crear
		$jornada_term_id = 0;
		$jornada_nombre  = trim( $fila['jornada'] ?? '' );
		if ( $jornada_nombre ) {
			$term = get_term_by( 'name', $jornada_nombre, 'scl_jornada' );
			if ( $term ) {
				$jornada_term_id = $term->term_id;
			} else {
				$result          = wp_insert_term( $jornada_nombre, 'scl_jornada' );
				$jornada_term_id = is_wp_error( $result ) ? 0 : $result['term_id'];
			}
		}

		// Equipos: buscar o crear con flag incompleto
		$equipo_local_id = $this->find_or_create_equipo( trim( $fila['equipo_local'] ), $user_id );
		if ( is_wp_error( $equipo_local_id ) ) {
			return $equipo_local_id;
		}

		$equipo_visita_id = $this->find_or_create_equipo( trim( $fila['equipo_visitante'] ), $user_id );
		if ( is_wp_error( $equipo_visita_id ) ) {
			return $equipo_visita_id;
		}

		$tipo_fase = strtolower( trim( $fila['tipo_fase'] ) );
		$estado    = strtolower( trim( $fila['estado'] ) );
		$fecha     = sanitize_text_field( trim( $fila['fecha'] ?? '' ) );

		// Crear como draft — metas antes de publicar para que el motor dispare correctamente
		$titulo     = get_the_title( $equipo_local_id ) . ' vs ' . get_the_title( $equipo_visita_id );
		$partido_id = wp_insert_post( [
			'post_type'   => 'scl_partido',
			'post_title'  => $titulo,
			'post_status' => 'draft',
			'post_author' => $user_id,
		] );
		if ( is_wp_error( $partido_id ) ) {
			return $partido_id;
		}

		update_post_meta( $partido_id, 'scl_partido_torneo_id',        $torneo_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_local_id',  $equipo_local_id );
		update_post_meta( $partido_id, 'scl_partido_equipo_visita_id', $equipo_visita_id );
		update_post_meta( $partido_id, 'scl_partido_tipo_fase',        $tipo_fase );
		update_post_meta( $partido_id, 'scl_partido_estado',           $estado );
		update_post_meta( $partido_id, 'scl_partido_fecha',            $fecha );

		if ( 'finalizado' === $estado ) {
			update_post_meta( $partido_id, 'scl_partido_goles_local',  absint( $fila['goles_local'] ) );
			update_post_meta( $partido_id, 'scl_partido_goles_visita', absint( $fila['goles_visitante'] ) );
		} else {
			update_post_meta( $partido_id, 'scl_partido_goles_local',  '' );
			update_post_meta( $partido_id, 'scl_partido_goles_visita', '' );
		}

		// Grupo: buscar en el torneo o crear
		$grupo_nombre = trim( $fila['grupo'] ?? '' );
		if ( $grupo_nombre ) {
			$grupos = get_posts( [
				'post_type'      => 'scl_grupo',
				'post_parent'    => $torneo_id,
				'post_status'    => 'publish',
				'title'          => $grupo_nombre,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			] );
			if ( ! empty( $grupos ) ) {
				$grupo_id = $grupos[0];
			} else {
				$grupo_id = wp_insert_post( [
					'post_type'   => 'scl_grupo',
					'post_title'  => $grupo_nombre,
					'post_status' => 'publish',
					'post_author' => $user_id,
					'post_parent' => $torneo_id,
				] );
				if ( is_wp_error( $grupo_id ) ) {
					$grupo_id = 0;
				}
			}
			if ( $grupo_id ) {
				update_post_meta( $partido_id, 'scl_partido_grupo_id', $grupo_id );
			}
		}

		wp_set_post_terms( $partido_id, [ $temporada_term_id ], 'scl_temporada' );
		if ( $jornada_term_id ) {
			wp_set_post_terms( $partido_id, [ $jornada_term_id ], 'scl_jornada' );
		}

		// Publicar — dispara save_post_scl_partido con todas las metas ya guardadas
		wp_update_post( [ 'ID' => $partido_id, 'post_status' => 'publish' ] );

		return [ $partido_id, $torneo_id, $temporada_term_id, $tipo_fase ];
	}

	/**
	 * Busca un equipo por nombre y autor; si no existe, lo crea con flag incompleto.
	 *
	 * @return int|WP_Error ID del equipo o error.
	 */
	private function find_or_create_equipo( string $nombre, int $user_id ) {
		$existentes = get_posts( [
			'post_type'      => 'scl_equipo',
			'author'         => $user_id,
			'post_status'    => 'publish',
			'title'          => $nombre,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		] );
		if ( ! empty( $existentes ) ) {
			return $existentes[0];
		}

		$equipo_id = wp_insert_post( [
			'post_type'   => 'scl_equipo',
			'post_title'  => $nombre,
			'post_status' => 'publish',
			'post_author' => $user_id,
		] );
		if ( is_wp_error( $equipo_id ) ) {
			return new WP_Error(
				'equipo_error',
				sprintf( 'No se pudo crear el equipo "%s".', $nombre )
			);
		}

		update_post_meta( $equipo_id, 'scl_equipo_incompleto', '1' );
		return $equipo_id;
	}
}
