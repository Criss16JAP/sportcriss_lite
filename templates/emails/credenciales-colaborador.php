<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Tus credenciales de acceso</title>
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
		.credentials { background:#f4f6f8; border-radius:8px;
		               padding:20px; margin:20px 0; }
		.credentials p { margin:6px 0; font-size:14px; }
		.credentials strong { color:#1a3a5c; }
		.cred-value { font-family:monospace; background:#e8ecef;
		              padding:4px 10px; border-radius:4px;
		              font-size:15px; letter-spacing:0.05em; }
		.btn { display:block; width:fit-content; margin:24px auto;
		       background:#f5a623; color:#1a3a5c; text-decoration:none;
		       padding:14px 32px; border-radius:8px;
		       font-weight:700; font-size:15px; }
		.footer { background:#f4f6f8; padding:20px 32px;
		          text-align:center; font-size:12px; color:#aaa; }
		.warning { background:#fff3cd; border-left:4px solid #ffc107;
		           padding:12px 16px; border-radius:4px;
		           font-size:13px; color:#856404; margin-top:16px; }
	</style>
</head>
<body>
<div class="wrap">
	<div class="header">
		<h1>⚽ <?php echo esc_html( $portal_nombre ); ?></h1>
		<p>Acceso de colaborador</p>
	</div>
	<div class="body">
		<h2>Hola, <?php echo esc_html( $nombre ); ?>!</h2>
		<p>
			<strong><?php echo esc_html( $nombre_org ); ?></strong> te ha agregado como
			colaborador en <strong><?php echo esc_html( $portal_nombre ); ?></strong>.
			Como colaborador puedes ingresar resultados de partidos desde tu panel personal.
		</p>

		<div class="credentials">
			<p><strong>Tus credenciales de acceso:</strong></p>
			<p>📧 Correo: <span class="cred-value"><?php echo esc_html( $email ); ?></span></p>
			<p>🔑 Contraseña: <span class="cred-value"><?php echo esc_html( $password ); ?></span></p>
		</div>

		<div class="warning">
			⚠ <strong>Importante:</strong> Cambia tu contraseña después de tu primer ingreso.
			Esta contraseña fue generada automáticamente.
		</div>

		<a href="<?php echo esc_url( $acceso_url ); ?>" class="btn">
			Ingresar al panel →
		</a>
	</div>
	<div class="footer">
		Este correo fue enviado por <?php echo esc_html( $portal_nombre ); ?>.
		Si no esperabas recibirlo, contacta a <?php echo esc_html( $nombre_org ); ?>.
	</div>
</div>
</body>
</html>
