<?php
/**
 * Template de registro de nuevos organizadores.
 * Variables: $error (string), $campos (array: nombre, apellido, email)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$portal_nombre = get_option( 'scl_portal_nombre', 'SportCriss Lite' );
$logo_id       = get_option( 'scl_portal_logo_id', 0 );
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Registro · <?php echo esc_html( $portal_nombre ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( SCL_URL . 'assets/css/auth.css' ); ?>">
</head>
<body class="scl-auth-body">

<div class="scl-auth-wrapper">
	<div class="scl-auth-card">

		<div class="scl-auth-header">
			<?php if ( $logo_id ) : ?>
				<img src="<?php echo esc_url( wp_get_attachment_image_url( $logo_id, 'medium' ) ); ?>"
				     alt="Logo" class="scl-auth-logo">
			<?php else : ?>
				<div class="scl-auth-logo-text">
					&#9917; <?php echo esc_html( $portal_nombre ); ?>
				</div>
			<?php endif; ?>
			<p class="scl-auth-subtitle">Crear cuenta de organizador</p>
		</div>

		<?php if ( $error ) : ?>
			<div class="scl-auth-alert scl-auth-alert--error">
				<?php echo esc_html( $error ); ?>
			</div>
		<?php endif; ?>

		<form class="scl-auth-form" method="post" novalidate>
			<?php wp_nonce_field( 'scl_registro', 'scl_registro_nonce' ); ?>

			<div class="scl-auth-field-row">
				<div class="scl-auth-field">
					<label for="scl_nombre">Nombre</label>
					<input type="text" id="scl_nombre" name="scl_nombre"
					       value="<?php echo esc_attr( $campos['nombre'] ?? '' ); ?>"
					       placeholder="Cristian"
					       autocomplete="given-name" required>
				</div>
				<div class="scl-auth-field">
					<label for="scl_apellido">Apellido</label>
					<input type="text" id="scl_apellido" name="scl_apellido"
					       value="<?php echo esc_attr( $campos['apellido'] ?? '' ); ?>"
					       placeholder="Ávila"
					       autocomplete="family-name" required>
				</div>
			</div>

			<div class="scl-auth-field">
				<label for="scl_email">Correo electrónico</label>
				<input type="email" id="scl_email" name="scl_email"
				       value="<?php echo esc_attr( sanitize_email( $campos['email'] ?? '' ) ); ?>"
				       placeholder="tucorreo@ejemplo.com"
				       autocomplete="email" required>
			</div>

			<div class="scl-auth-field">
				<label for="scl_password">Contraseña <span class="scl-auth-hint">(mín. 8 caracteres)</span></label>
				<div class="scl-auth-password-wrap">
					<input type="password" id="scl_password" name="scl_password"
					       placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
					       autocomplete="new-password" required>
					<button type="button" class="scl-auth-toggle-pass" data-target="scl_password" aria-label="Ver contraseña">
						&#128065;
					</button>
				</div>
			</div>

			<div class="scl-auth-field">
				<label for="scl_password2">Confirmar contraseña</label>
				<div class="scl-auth-password-wrap">
					<input type="password" id="scl_password2" name="scl_password2"
					       placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
					       autocomplete="new-password" required>
					<button type="button" class="scl-auth-toggle-pass" data-target="scl_password2" aria-label="Ver contraseña">
						&#128065;
					</button>
				</div>
			</div>

			<button type="submit" class="scl-auth-submit">
				Crear cuenta
			</button>
		</form>

	</div>

	<p class="scl-auth-footer-text">
		&#x2714; ¿Ya tienes cuenta?
		<a href="<?php echo esc_url( home_url( '/acceso/' ) ); ?>">Inicia sesión</a>
		&nbsp;&middot;&nbsp;
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Volver al sitio</a>
	</p>
</div>

<script>
document.querySelectorAll('.scl-auth-toggle-pass').forEach(function(btn) {
	btn.addEventListener('click', function() {
		var input = document.getElementById(this.dataset.target);
		input.type = input.type === 'password' ? 'text' : 'password';
		this.textContent = input.type === 'password' ? '\u{1F441}' : '\u{1F648}';
	});
});
</script>

</body>
</html>
