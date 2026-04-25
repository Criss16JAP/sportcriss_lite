<?php
/**
 * Integración con el servidor de licencias CD License Server.
 *
 * Controla el acceso al dashboard: modo normal vs. modo solo lectura.
 * La verificación se cachea en user_meta por 24 horas.
 * WP Cron ejecuta una verificación diaria para todos los Organizadores activos.
 *
 * Implementado en Sprint 11.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_License
 */
class Scl_License {

	/**
	 * Verifica la licencia de un Organizador contra el servidor externo.
	 *
	 * @param int $user_id ID del usuario Organizador.
	 * @return bool True si la licencia es válida y activa.
	 */
	public function verificar_licencia( $user_id ) {
		// TODO Sprint 11
		return true;
	}

	/**
	 * Consulta el cache de licencia (user_meta) para un Organizador.
	 *
	 * @param int $user_id ID del usuario Organizador.
	 * @return bool True si la licencia cacheada está activa.
	 */
	public function licencia_activa( $user_id ) {
		// TODO Sprint 11
		return true;
	}

	/**
	 * Determina si el Organizador está en modo solo lectura.
	 *
	 * @param int $user_id ID del usuario Organizador.
	 * @return bool True si debe operar en modo lectura.
	 */
	public function modo_readonly( $user_id ) {
		// TODO Sprint 11
		return false;
	}

	/**
	 * Verifica las licencias de todos los Organizadores activos.
	 * Callback del hook 'scl_verificar_licencias_diario' (WP Cron).
	 */
	public function verificar_todas() {
		// TODO Sprint 11
	}
}
