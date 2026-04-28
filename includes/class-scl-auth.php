<?php
/**
 * Módulo de autenticación frontend: [scl_login] y [scl_registro].
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Auth {

	private static string $login_error     = '';
	private static string $registro_error  = '';
	private static array  $registro_campos = [];

	public function init(): void {
		add_shortcode( 'scl_login',    [ $this, 'shortcode_login'    ] );
		add_shortcode( 'scl_registro', [ $this, 'shortcode_registro' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'template_redirect',  [ $this, 'procesar_auth'  ], 1 );
	}

	public function enqueue_assets(): void {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) return;
		if ( has_shortcode( $post->post_content, 'scl_login' )
			|| has_shortcode( $post->post_content, 'scl_registro' ) ) {
			wp_enqueue_style( 'scl-auth-css', SCL_URL . 'assets/css/auth.css', [], SCL_VERSION );
		}
	}

	private function es_contexto_editor(): bool {
		if ( is_admin() || wp_is_json_request() ) return true;
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['et_fb'] ) ) return true;
		if ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) return true;
		return false;
	}

	/**
	 * Punto de entrada único para redirects y procesamiento de formularios.
	 * Se ejecuta en template_redirect, antes de que el tema emita HTML.
	 */
	public function procesar_auth(): void {
		if ( $this->es_contexto_editor() ) return;

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) return;

		$tiene_login    = has_shortcode( $post->post_content, 'scl_login' );
		$tiene_registro = has_shortcode( $post->post_content, 'scl_registro' );

		if ( ! $tiene_login && ! $tiene_registro ) return;

		// Usuario ya autenticado: redirigir al panel sin mostrar el formulario
		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$roles = [ 'scl_organizador', 'scl_colaborador' ];
			if ( array_intersect( $roles, (array) $user->roles ) ) {
				wp_safe_redirect( home_url( '/mi-panel/' ) );
				exit;
			}
			return;
		}

		// ------------------------------------------------------------------
		// Procesar formulario de login
		// ------------------------------------------------------------------
		if ( $tiene_login
			&& isset( $_POST['scl_login_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_login_nonce'] ) ), 'scl_login' )
		) {
			$email    = sanitize_email( wp_unslash( $_POST['scl_email'] ?? '' ) );
			$password = $_POST['scl_password'] ?? '';

			if ( ! $email || ! $password ) {
				self::$login_error = 'Completa todos los campos.';
				return;
			}

			$user = get_user_by( 'email', $email );
			if ( ! $user ) {
				self::$login_error = 'No existe una cuenta con ese email.';
				return;
			}

			$result = wp_signon( [
				'user_login'    => $user->user_login,
				'user_password' => $password,
				'remember'      => isset( $_POST['scl_remember'] ),
			], is_ssl() );

			if ( is_wp_error( $result ) ) {
				self::$login_error = 'Email o contraseña incorrectos.';
			} else {
				wp_safe_redirect( home_url( '/mi-panel/' ) );
				exit;
			}
		}

		// ------------------------------------------------------------------
		// Procesar formulario de registro
		// ------------------------------------------------------------------
		if ( $tiene_registro
			&& isset( $_POST['scl_registro_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_registro_nonce'] ) ), 'scl_registro' )
		) {
			$ip        = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
			$transient = 'scl_reg_ip_' . md5( $ip );
			$intentos  = (int) get_transient( $transient );

			if ( $intentos >= 3 ) {
				self::$registro_error = 'Demasiados intentos. Espera una hora antes de volver a intentarlo.';
				return;
			}

			set_transient( $transient, $intentos + 1, HOUR_IN_SECONDS );

			$nombre   = sanitize_text_field( wp_unslash( $_POST['scl_nombre']   ?? '' ) );
			$apellido = sanitize_text_field( wp_unslash( $_POST['scl_apellido'] ?? '' ) );
			$email    = sanitize_email( wp_unslash( $_POST['scl_email']          ?? '' ) );
			$pass1    = wp_unslash( $_POST['scl_password']                        ?? '' );
			$pass2    = wp_unslash( $_POST['scl_password2']                       ?? '' );

			self::$registro_campos = compact( 'nombre', 'apellido', 'email' );

			if ( ! $nombre || ! $apellido || ! $email || ! $pass1 || ! $pass2 ) {
				self::$registro_error = 'Completa todos los campos.';
				return;
			}
			if ( ! is_email( $email ) ) {
				self::$registro_error = 'El correo electrónico no es válido.';
				return;
			}
			if ( strlen( $pass1 ) < 8 ) {
				self::$registro_error = 'La contraseña debe tener al menos 8 caracteres.';
				return;
			}
			if ( $pass1 !== $pass2 ) {
				self::$registro_error = 'Las contraseñas no coinciden.';
				return;
			}
			if ( email_exists( $email ) ) {
				self::$registro_error = 'Ya existe una cuenta con ese correo electrónico.';
				return;
			}

			$base     = sanitize_user( remove_accents( strtolower( $nombre . '.' . $apellido ) ), true );
			$username = $base;
			$i        = 1;
			while ( username_exists( $username ) ) {
				$username = $base . $i;
				$i++;
			}

			$user_id = wp_create_user( $username, $pass1, $email );
			if ( is_wp_error( $user_id ) ) {
				self::$registro_error = 'Error al crear la cuenta: ' . $user_id->get_error_message();
				return;
			}

			wp_update_user( [
				'ID'           => $user_id,
				'first_name'   => $nombre,
				'last_name'    => $apellido,
				'display_name' => $nombre . ' ' . $apellido,
				'role'         => 'scl_organizador',
			] );

			Scl_Emails::bienvenida_organizador( $user_id );

			wp_signon( [
				'user_login'    => $username,
				'user_password' => $pass1,
				'remember'      => true,
			], is_ssl() );

			wp_safe_redirect( home_url( '/mi-panel/' ) );
			exit;
		}
	}

	// -----------------------------------------------------------------------
	// Shortcodes — solo renderizan el formulario con el estado de error
	// -----------------------------------------------------------------------

	public function shortcode_login(): string {
		if ( $this->es_contexto_editor() ) return '';
		$error = self::$login_error;
		ob_start();
		include SCL_PATH . 'templates/auth/login.php';
		return ob_get_clean();
	}

	public function shortcode_registro(): string {
		if ( $this->es_contexto_editor() ) return '';
		$error  = self::$registro_error;
		$campos = self::$registro_campos;
		ob_start();
		include SCL_PATH . 'templates/auth/registro.php';
		return ob_get_clean();
	}
}
