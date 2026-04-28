<?php
/**
 * Sistema de emails transaccionales del plugin.
 *
 * Todos los emails se envían vía wp_mail() — compatible con cualquier plugin SMTP.
 * El sitio usa SMTP Pro que intercepta wp_mail() automáticamente.
 * NO se configura ningún servidor SMTP dentro del plugin.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Emails {

	/**
	 * Envía email de bienvenida al nuevo organizador.
	 * Llamar desde wp-admin al crear/activar un usuario con rol scl_organizador.
	 */
	public static function bienvenida_organizador( int $user_id ): bool {
		$user          = get_userdata( $user_id );
		$portal_nombre = get_option( 'scl_portal_nombre', 'SportCriss Lite' );
		$panel_url     = home_url( '/mi-panel/' );
		$acceso_url    = home_url( '/acceso/' );

		$asunto = '¡Bienvenido a ' . $portal_nombre . '!';
		$cuerpo = self::render_template( 'bienvenida-organizador', [
			'nombre'        => $user->first_name ?: $user->display_name,
			'email'         => $user->user_email,
			'portal_nombre' => $portal_nombre,
			'panel_url'     => $panel_url,
			'acceso_url'    => $acceso_url,
		] );

		return self::enviar( $user->user_email, $asunto, $cuerpo );
	}

	/**
	 * Envía credenciales al nuevo colaborador.
	 */
	public static function credenciales_colaborador(
		int $user_id, string $email, string $nombre,
		string $apellido, string $password
	): bool {
		$portal_nombre = get_option( 'scl_portal_nombre', 'SportCriss Lite' );
		$acceso_url    = home_url( '/acceso/' );
		$organizador   = get_userdata(
			(int) get_user_meta( $user_id, 'scl_colaborador_organizador_id', true )
		);
		$nombre_org = $organizador ? $organizador->display_name : $portal_nombre;

		$asunto = 'Tu acceso como colaborador en ' . $portal_nombre;
		$cuerpo = self::render_template( 'credenciales-colaborador', [
			'nombre'        => $nombre,
			'apellido'      => $apellido,
			'email'         => $email,
			'password'      => $password,
			'portal_nombre' => $portal_nombre,
			'acceso_url'    => $acceso_url,
			'nombre_org'    => $nombre_org,
		] );

		return self::enviar( $email, $asunto, $cuerpo );
	}

	/**
	 * Render del template de email.
	 */
	private static function render_template( string $template, array $vars ): string {
		$path = SCL_PATH . 'templates/emails/' . $template . '.php';
		if ( ! file_exists( $path ) ) return '';
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract
		ob_start();
		include $path;
		return ob_get_clean();
	}

	/**
	 * Envía el email usando wp_mail (compatible con SMTP Pro).
	 */
	private static function enviar( string $to, string $subject, string $body ): bool {
		add_filter( 'wp_mail_content_type', fn() => 'text/html' );
		$result = wp_mail( $to, $subject, $body );
		remove_filter( 'wp_mail_content_type', fn() => 'text/html' );
		return $result;
	}
}

/**
 * Función global para llamar desde el handler AJAX.
 */
function scl_enviar_email_colaborador(
	int $user_id, string $email, string $nombre,
	string $apellido, string $password
): void {
	Scl_Emails::credenciales_colaborador( $user_id, $email, $nombre, $apellido, $password );
}
