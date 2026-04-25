<?php
/**
 * Control de acceso: bloqueo de wp-admin para el rol Organizador.
 *
 * Los usuarios con rol `scl_organizador` no deben tener acceso al panel
 * nativo de WordPress. En lugar de usar capacidades de WordPress para esto
 * (que puede producir pantallas de error poco amigables), se intercepta
 * la petición en el hook `init` con prioridad alta y se redirige al
 * dashboard frontend antes de que WordPress cargue cualquier pantalla de admin.
 *
 * admin-ajax.php queda explícitamente excluido de la redirección porque
 * todas las peticiones AJAX del dashboard pasan por ese endpoint.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Access
 */
class Scl_Access {

	/**
	 * URL del dashboard frontend al que se redirige al Organizador.
	 * Se construye dinámicamente con home_url() para no hardcodear URLs.
	 *
	 * @return string
	 */
	private function url_dashboard() {
		return home_url( '/mi-panel/' );
	}

	// -----------------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------------

	/**
	 * Detecta si el usuario actual es Organizador e intenta acceder al wp-admin.
	 * Si es así, lo redirige al dashboard frontend.
	 *
	 * Registrado en: 'init' con prioridad 1 (antes de cualquier lógica de admin).
	 *
	 * La condición verifica tres cosas:
	 *   1. El usuario está logueado.
	 *   2. El usuario tiene el rol scl_organizador.
	 *   3. La URL actual contiene /wp-admin/ Y no es admin-ajax.php.
	 */
	public function bloquear_wp_admin() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! $this->es_organizador() ) {
			return;
		}

		if ( $this->intentando_acceder_wp_admin() ) {
			wp_safe_redirect( $this->url_dashboard() );
			exit;
		}
	}

	// -----------------------------------------------------------------------
	// Métodos internos
	// -----------------------------------------------------------------------

	/**
	 * Comprueba si el usuario actual tiene el rol Organizador.
	 *
	 * @return bool
	 */
	private function es_organizador() {
		$user = wp_get_current_user();
		return in_array( Scl_Roles::SLUG, (array) $user->roles, true );
	}

	/**
	 * Determina si la petición actual apunta al área de administración de WordPress,
	 * excluyendo admin-ajax.php para no romper las peticiones AJAX del dashboard.
	 *
	 * Se usa is_admin() combinado con la constante DOING_AJAX para una detección
	 * robusta compatible con distintos contextos (carga directa, Cron, REST…).
	 *
	 * @return bool
	 */
	private function intentando_acceder_wp_admin() {
		// is_admin() devuelve true tanto para pantallas admin como para ajax.
		// DOING_AJAX es true solo cuando la petición es AJAX.
		if ( ! is_admin() ) {
			return false;
		}

		$es_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		return ! $es_ajax;
	}
}
