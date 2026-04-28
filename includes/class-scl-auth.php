<?php
/**
 * Módulo de autenticación frontend: [scl_login] y [scl_registro].
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Auth {

	public function init(): void {
		add_shortcode( 'scl_login',    [ $this, 'shortcode_login'    ] );
		add_shortcode( 'scl_registro', [ $this, 'shortcode_registro' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets(): void {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) return;
		if ( has_shortcode( $post->post_content, 'scl_login' )
			|| has_shortcode( $post->post_content, 'scl_registro' ) ) {
			wp_enqueue_style( 'scl-auth-css', SCL_URL . 'assets/css/auth.css', [], SCL_VERSION );
		}
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

	// -----------------------------------------------------------------------
	// Shortcode [scl_registro]
	// -----------------------------------------------------------------------

	public function shortcode_registro(): string {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/mi-panel/' ) );
			exit;
		}

		$error  = '';
		$campos = [];

		if ( isset( $_POST['scl_registro_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_registro_nonce'] ) ), 'scl_registro' )
		) {
			// Rate limiting: máx. 3 intentos por IP por hora
			$ip          = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
			$transient   = 'scl_reg_ip_' . md5( $ip );
			$intentos    = (int) get_transient( $transient );

			if ( $intentos >= 3 ) {
				$error = 'Demasiados intentos. Espera una hora antes de volver a intentarlo.';
			} else {
				// Incrementar contador antes de validar (cuenta el intento)
				set_transient( $transient, $intentos + 1, HOUR_IN_SECONDS );

				$nombre   = sanitize_text_field( wp_unslash( $_POST['scl_nombre']   ?? '' ) );
				$apellido = sanitize_text_field( wp_unslash( $_POST['scl_apellido'] ?? '' ) );
				$email    = sanitize_email( wp_unslash( $_POST['scl_email']          ?? '' ) );
				$pass1    = wp_unslash( $_POST['scl_password']                        ?? '' );
				$pass2    = wp_unslash( $_POST['scl_password2']                       ?? '' );

				// Conservar campos para repoblar el form en caso de error
				$campos = compact( 'nombre', 'apellido', 'email' );

				if ( ! $nombre || ! $apellido || ! $email || ! $pass1 || ! $pass2 ) {
					$error = 'Completa todos los campos.';
				} elseif ( ! is_email( $email ) ) {
					$error = 'El correo electrónico no es válido.';
				} elseif ( strlen( $pass1 ) < 8 ) {
					$error = 'La contraseña debe tener al menos 8 caracteres.';
				} elseif ( $pass1 !== $pass2 ) {
					$error = 'Las contraseñas no coinciden.';
				} elseif ( email_exists( $email ) ) {
					$error = 'Ya existe una cuenta con ese correo electrónico.';
				} else {
					// Generar username único: nombre.apellido
					$base    = sanitize_user( remove_accents( strtolower( $nombre . '.' . $apellido ) ), true );
					$username = $base;
					$i = 1;
					while ( username_exists( $username ) ) {
						$username = $base . $i;
						$i++;
					}

					$user_id = wp_create_user( $username, $pass1, $email );
					if ( is_wp_error( $user_id ) ) {
						$error = 'Error al crear la cuenta: ' . $user_id->get_error_message();
					} else {
						wp_update_user( [
							'ID'           => $user_id,
							'first_name'   => $nombre,
							'last_name'    => $apellido,
							'display_name' => $nombre . ' ' . $apellido,
							'role'         => 'scl_organizador',
						] );

						Scl_Emails::bienvenida_organizador( $user_id );

						// Login automático
						wp_signon( [
							'user_login'    => $username,
							'user_password' => $pass1,
							'remember'      => true,
						], is_ssl() );

						wp_safe_redirect( home_url( '/mi-panel/' ) );
						exit;
					}
				}
			}
		}

		ob_start();
		include SCL_PATH . 'templates/auth/registro.php';
		return ob_get_clean();
	}
}
