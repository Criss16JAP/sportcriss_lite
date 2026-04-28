<?php
/**
 * Módulo de autenticación frontend: página /acceso/ con shortcode [scl_login].
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Auth {

	public function init(): void {
		add_shortcode( 'scl_login', [ $this, 'shortcode_login' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets(): void {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'scl_login' ) ) {
			return;
		}
		wp_enqueue_style( 'scl-auth-css', SCL_URL . 'assets/css/auth.css', [], SCL_VERSION );
	}

	public function shortcode_login(): string {
		if ( is_user_logged_in() ) {
			$user             = wp_get_current_user();
			$roles_plugin     = [ 'scl_organizador', 'scl_colaborador' ];
			if ( array_intersect( $roles_plugin, (array) $user->roles ) ) {
				wp_safe_redirect( home_url( '/mi-panel/' ) );
				exit;
			}
		}

		$error = '';

		if ( isset( $_POST['scl_login_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_login_nonce'] ) ), 'scl_login' )
		) {
			$email    = sanitize_email( wp_unslash( $_POST['scl_email'] ?? '' ) );
			$password = $_POST['scl_password'] ?? '';

			if ( ! $email || ! $password ) {
				$error = 'Completa todos los campos.';
			} else {
				$user = get_user_by( 'email', $email );
				if ( ! $user ) {
					$error = 'No existe una cuenta con ese email.';
				} else {
					$result = wp_signon( [
						'user_login'    => $user->user_login,
						'user_password' => $password,
						'remember'      => isset( $_POST['scl_remember'] ),
					], is_ssl() );

					if ( is_wp_error( $result ) ) {
						$error = 'Email o contraseña incorrectos.';
					} else {
						wp_safe_redirect( home_url( '/mi-panel/' ) );
						exit;
					}
				}
			}
		}

		ob_start();
		include SCL_PATH . 'templates/auth/login.php';
		return ob_get_clean();
	}
}
