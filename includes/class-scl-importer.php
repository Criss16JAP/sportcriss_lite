<?php
/**
 * Procesador de importación masiva de partidos desde CSV.
 *
 * Implementado en Sprint 8.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Importer
 */
class Scl_Importer {

	/**
	 * Procesa el archivo CSV y crea los partidos correspondientes.
	 *
	 * @param string $filepath Ruta absoluta al archivo CSV temporal.
	 * @param int    $user_id  ID del Organizador que realiza la importación.
	 * @return array Resumen de la operación: creados, errores, equipos nuevos.
	 */
	public function procesar( $filepath, $user_id ) {
		// TODO Sprint 8
		return [];
	}
}
