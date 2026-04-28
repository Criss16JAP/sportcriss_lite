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
	 * URL del dashboard frontend.
	 *
	 * @return string
	 */
	private function url_dashboard(): string {
		return home_url( '/mi-panel/' );
	}

	/**
	 * URL de la página de login propia del plugin.
	 *
	 * @return string
	 */
	private function url_login(): string {
		return home_url( '/acceso/' );
	}

	// -----------------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------------

	/**
	 * Intercepta wp-login.php cuando la acción es 'login' (GET sin credenciales)
	 * y redirige a la página propia de acceso del plugin.
	 * Se registra en el hook 'login_init'.
	 */
	public function interceptar_wp_login(): void {
		$action = sanitize_key( $_REQUEST['action'] ?? 'login' );
		// Solo redirigir la acción de login, no logout/register/resetpass/etc.
		if ( in_array( $action, [ 'login', '' ], true ) && ! isset( $_POST['log'] ) ) {
			wp_safe_redirect( $this->url_login() );
			exit;
		}
	}

	/**
	 * Redirige a /mi-panel/ después de un login de wp-admin nativo
	 * si el usuario es Organizador o Colaborador.
	 * Se registra en el filter 'login_redirect'.
	 */
	public function redirigir_tras_login( string $redirect_to, string $requested, $user ): string {
		if ( is_wp_error( $user ) ) {
			return $redirect_to;
		}
		$roles_plugin = [ 'scl_organizador', 'scl_colaborador' ];
		if ( array_intersect( $roles_plugin, (array) $user->roles ) ) {
			return $this->url_dashboard();
		}
		return $redirect_to;
	}

	/**
	 * Detecta si el usuario actual es Organizador e intenta acceder al wp-admin.
	 * Si es así, lo redirige al dashboard frontend.
	 *
	 * Registrado en: 'init' con prioridad 1 (antes de cualquier lógica de admin).
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

	/**
	 * Oculta la barra de administración de WordPress al Organizador.
	 *
	 * Se llama en 'after_setup_theme' para que show_admin_bar() esté disponible.
	 * También inyecta CSS inline como garantía secundaria contra márgenes residuales
	 * que algunos temas añaden al body cuando el admin bar está activo.
	 *
	 * Registrado en: 'after_setup_theme'.
	 */
	public function ocultar_admin_bar() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! $this->es_organizador() ) {
			return;
		}

		show_admin_bar( false );
	}

	/**
	 * Inyecta CSS inline para eliminar márgenes residuales del admin bar.
	 * Solo se ejecuta si el usuario es Organizador.
	 *
	 * Registrado en: 'wp_enqueue_scripts'.
	 */
	public function eliminar_margen_admin_bar() {
		if ( ! is_user_logged_in() || ! $this->es_organizador() ) {
			return;
		}

		$css = 'html { margin-top: 0 !important; } * html body { margin-top: 0 !important; }';
		wp_add_inline_style( 'wp-block-library', $css );
	}

	// -----------------------------------------------------------------------
	// Métodos internos
	// -----------------------------------------------------------------------

	/**
	 * Comprueba si el usuario actual tiene un rol que debe ser bloqueado del wp-admin
	 * (Organizador o Colaborador).
	 *
	 * @return bool
	 */
	private function es_organizador() {
		$user  = wp_get_current_user();
		$roles = (array) $user->roles;
		return in_array( Scl_Roles::SLUG, $roles, true )
			|| in_array( Scl_Roles::SLUG_COLABORADOR, $roles, true );
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
