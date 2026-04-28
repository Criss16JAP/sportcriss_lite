<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $portal_nombre ); ?></title>
	<style>
		body { margin:0; padding:0; background:#f4f6f8; font-family: Arial, sans-serif; }
		.wrap { max-width:580px; margin:40px auto; background:#fff;
		        border-radius:12px; overflow:hidden;
		        box-shadow:0 4px 20px rgba(0,0,0,0.08); }
		.header { background:#1a3a5c; padding:32px; text-align:center; }
		.header h1 { color:#fff; margin:0; font-size:22px; }
		.header p  { color:rgba(255,255,255,0.7); margin:8px 0 0; font-size:14px; }
		.body { padding:32px; }
		.body h2 { color:#1a3a5c; font-size:20px; margin-top:0; }
		.body p  { color:#555; line-height:1.6; }
		.btn { display:block; width:fit-content; margin:24px auto;
		       background:#f5a623; color:#1a3a5c; text-decoration:none;
		       padding:14px 32px; border-radius:8px;
		       font-weight:700; font-size:15px; }
		.footer { background:#f4f6f8; padding:20px 32px;
		          text-align:center; font-size:12px; color:#aaa; }
		.divider { border:none; border-top:1px solid #eee; margin:24px 0; }
	</style>
</head>
<body>
<div class="wrap">
	<div class="header">
		<h1>⚽ <?php echo esc_html( $portal_nombre ); ?></h1>
		<p>Panel de gestión de torneos deportivos</p>
	</div>
	<div class="body">
		<h2>¡Bienvenido, <?php echo esc_html( $nombre ); ?>!</h2>
		<p>
			Tu cuenta de <strong>organizador</strong> ha sido activada exitosamente en
			<strong><?php echo esc_html( $portal_nombre ); ?></strong>.
			Ya puedes crear y gestionar tus torneos, equipos, partidos y más.
		</p>
		<a href="<?php echo esc_url( $acceso_url ); ?>" class="btn">
			Ingresar a mi panel →
		</a>
		<hr class="divider">
		<p style="font-size:13px;color:#888">
			Ingresa con tu correo: <strong><?php echo esc_html( $email ); ?></strong><br>
			Si olvidaste tu contraseña, puedes recuperarla desde la pantalla de acceso.
		</p>
	</div>
	<div class="footer">
		Este correo fue enviado por <?php echo esc_html( $portal_nombre ); ?>.
		Si no esperabas recibirlo, ignóralo.
	</div>
</div>
</body>
</html>
