<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Acceso · <?php echo esc_html( get_option('scl_portal_nombre', 'SportCriss Lite') ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( SCL_URL . 'assets/css/auth.css' ); ?>">
</head>
<body class="scl-auth-body">

<div class="scl-auth-wrapper">
	<div class="scl-auth-card">

		<div class="scl-auth-header">
			<?php
			$logo_id = get_option( 'scl_portal_logo_id', 0 );
			if ( $logo_id ) :
			?>
				<img src="<?php echo esc_url( wp_get_attachment_image_url($logo_id, 'medium') ); ?>"
				     alt="Logo" class="scl-auth-logo">
			<?php else : ?>
				<div class="scl-auth-logo-text">
					⚽ <?php echo esc_html( get_option('scl_portal_nombre', 'SportCriss Lite') ); ?>
				</div>
			<?php endif; ?>
			<p class="scl-auth-subtitle">Panel de gestión de torneos</p>
		</div>

		<?php if ( ! empty( $error ) ) : ?>
			<div class="scl-auth-alert scl-auth-alert--error">
				<?php echo esc_html( $error ); ?>
			</div>
		<?php endif; ?>

		<form class="scl-auth-form" method="post" novalidate>
			<?php wp_nonce_field( 'scl_login', 'scl_login_nonce' ); ?>

			<div class="scl-auth-field">
				<label for="scl_email">Correo electrónico</label>
				<input type="email" id="scl_email" name="scl_email"
				       value="<?php echo esc_attr( sanitize_email( wp_unslash( $_POST['scl_email'] ?? '' ) ) ); ?>"
				       placeholder="tucorreo@ejemplo.com"
				       autocomplete="email" required>
			</div>

			<div class="scl-auth-field">
				<label for="scl_password">Contraseña</label>
				<div class="scl-auth-password-wrap">
					<input type="password" id="scl_password" name="scl_password"
					       placeholder="••••••••"
					       autocomplete="current-password" required>
					<button type="button" class="scl-auth-toggle-pass" aria-label="Ver contraseña">
						👁
					</button>
				</div>
			</div>

			<div class="scl-auth-options">
				<label class="scl-auth-remember">
					<input type="checkbox" name="scl_remember" value="1">
					Mantener sesión iniciada
				</label>
				<a href="<?php echo esc_url( wp_lostpassword_url( home_url( '/acceso/' ) ) ); ?>"
				   class="scl-auth-forgot">
					¿Olvidaste tu contraseña?
				</a>
			</div>

			<button type="submit" class="scl-auth-submit">
				Ingresar al panel
			</button>
		</form>

	</div>

	<p class="scl-auth-footer-text">
		<?php echo esc_html( get_option('scl_portal_nombre', 'SportCriss Lite') ); ?> ·
		<a href="<?php echo esc_url( home_url('/') ); ?>">Volver al sitio</a>
	</p>
</div>

<script>
document.querySelector('.scl-auth-toggle-pass')?.addEventListener('click', function() {
	var input = document.getElementById('scl_password');
	input.type = input.type === 'password' ? 'text' : 'password';
	this.textContent = input.type === 'password' ? '👁' : '🙈';
});
</script>

</body>
</html>
