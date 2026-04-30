<?php
/**
 * Template: Dashboard – Perfil y configuración de usuario
 * Ruta: /mi-panel/?scl_ruta=perfil
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$user = wp_get_current_user();
?>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php esc_html_e( 'Mi Perfil', 'sportcriss-lite' ); ?></h1>
</div>

<!-- ── Sección 1: Datos personales ──────────────────────────────────────── -->
<div class="scl-form-section">
	<h2 class="scl-section-title"><?php esc_html_e( 'Datos personales', 'sportcriss-lite' ); ?></h2>

	<div class="scl-form-container">
		<div class="scl-field-row">
			<div class="scl-field">
				<label for="scl_perfil_nombre"><?php esc_html_e( 'Nombre', 'sportcriss-lite' ); ?></label>
				<input type="text" id="scl_perfil_nombre" value="<?php echo esc_attr( $user->first_name ); ?>" maxlength="60">
			</div>
			<div class="scl-field">
				<label for="scl_perfil_apellido"><?php esc_html_e( 'Apellido', 'sportcriss-lite' ); ?></label>
				<input type="text" id="scl_perfil_apellido" value="<?php echo esc_attr( $user->last_name ); ?>" maxlength="60">
			</div>
		</div>

		<div class="scl-field" style="max-width:280px;">
			<label><?php esc_html_e( 'Foto de perfil', 'sportcriss-lite' ); ?></label>
			<div class="scl-file-uploader" id="scl_perfil_foto_uploader" style="height:120px;">
				<div id="scl_perfil_foto_preview">
					<?php
					$foto_id = (int) get_user_meta( $user->ID, 'scl_perfil_foto_id', true );
					if ( $foto_id ) :
						echo '<img src="' . esc_url( (string) wp_get_attachment_image_url( $foto_id, 'thumbnail' ) ) . '" style="max-height:80px;border-radius:50%;" alt="">';
					else :
					?>
					<span class="scl-upload-icon">&#128247;</span>
					<p><?php esc_html_e( 'Click para subir', 'sportcriss-lite' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<input type="file" id="scl_perfil_foto_file" accept="image/*" style="display:none;">
			<input type="hidden" id="scl_perfil_foto_id" value="<?php echo esc_attr( $foto_id ); ?>">
		</div>

		<div class="scl-form-actions">
			<button type="button" class="scl-btn scl-btn--primary" id="scl_perfil_guardar">
				<?php esc_html_e( 'Guardar datos', 'sportcriss-lite' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- ── Sección 2: Cambio de email ───────────────────────────────────────── -->
<div class="scl-form-section">
	<h2 class="scl-section-title"><?php esc_html_e( 'Cambiar email', 'sportcriss-lite' ); ?></h2>

	<div class="scl-form-container">
		<p class="scl-description">
			<?php printf(
				/* translators: %s: current email */
				esc_html__( 'Email actual: %s', 'sportcriss-lite' ),
				'<strong>' . esc_html( $user->user_email ) . '</strong>'
			); ?>
		</p>

		<div id="scl_email_paso1">
			<div class="scl-field">
				<label for="scl_perfil_nuevo_email"><?php esc_html_e( 'Nuevo email', 'sportcriss-lite' ); ?></label>
				<input type="email" id="scl_perfil_nuevo_email" placeholder="nuevo@ejemplo.com" style="max-width:340px;">
			</div>
			<div class="scl-form-actions">
				<button type="button" class="scl-btn scl-btn--primary" id="scl_enviar_codigo_email">
					<?php esc_html_e( 'Enviar código de verificación', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>

		<div id="scl_email_paso2" style="display:none;">
			<p class="scl-description"><?php esc_html_e( 'Ingresa el código de 6 dígitos enviado al nuevo email.', 'sportcriss-lite' ); ?></p>
			<div class="scl-field">
				<label for="scl_perfil_codigo"><?php esc_html_e( 'Código de verificación', 'sportcriss-lite' ); ?></label>
				<input type="text" id="scl_perfil_codigo" placeholder="123456" maxlength="6" inputmode="numeric" style="max-width:160px;letter-spacing:0.3em;font-size:1.2rem;">
			</div>
			<div class="scl-form-actions">
				<button type="button" class="scl-btn scl-btn--ghost" id="scl_email_reintentar">
					<?php esc_html_e( '← Cambiar email', 'sportcriss-lite' ); ?>
				</button>
				<button type="button" class="scl-btn scl-btn--primary" id="scl_verificar_codigo_email">
					<?php esc_html_e( 'Verificar y actualizar', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ── Sección 3: Cambio de contraseña ─────────────────────────────────── -->
<div class="scl-form-section">
	<h2 class="scl-section-title"><?php esc_html_e( 'Cambiar contraseña', 'sportcriss-lite' ); ?></h2>

	<div class="scl-form-container">
		<div class="scl-field">
			<label for="scl_pwd_actual"><?php esc_html_e( 'Contraseña actual', 'sportcriss-lite' ); ?></label>
			<input type="password" id="scl_pwd_actual" autocomplete="current-password" style="max-width:340px;">
		</div>
		<div class="scl-field">
			<label for="scl_pwd_nueva"><?php esc_html_e( 'Nueva contraseña', 'sportcriss-lite' ); ?></label>
			<input type="password" id="scl_pwd_nueva" autocomplete="new-password" style="max-width:340px;">
			<small class="scl-hint"><?php esc_html_e( 'Mínimo 8 caracteres.', 'sportcriss-lite' ); ?></small>
		</div>
		<div class="scl-field">
			<label for="scl_pwd_confirmar"><?php esc_html_e( 'Confirmar nueva contraseña', 'sportcriss-lite' ); ?></label>
			<input type="password" id="scl_pwd_confirmar" autocomplete="new-password" style="max-width:340px;">
		</div>
		<div class="scl-form-actions">
			<button type="button" class="scl-btn scl-btn--primary" id="scl_cambiar_contrasena">
				<?php esc_html_e( 'Cambiar contraseña', 'sportcriss-lite' ); ?>
			</button>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {

	// ── Foto de perfil ────────────────────────────────────────────
	$('#scl_perfil_foto_uploader').on('click', function() {
		$('#scl_perfil_foto_file').trigger('click');
	});

	$('#scl_perfil_foto_file').on('change', function() {
		var file = this.files[0];
		if (!file) return;
		if (file.size > 5 * 1024 * 1024) { scl_flash('La foto no puede superar 5MB.', 'error'); return; }

		var formData = new FormData();
		formData.append('action', 'scl_subir_foto_jugador');
		formData.append('nonce', scl_ajax.nonce);
		formData.append('foto', file);

		$('#scl_perfil_foto_preview').html('<p style="text-align:center;padding:1rem;">Subiendo...</p>');
		$.ajax({
			url: scl_ajax.url, type: 'POST', data: formData, processData: false, contentType: false,
			success: function(res) {
				if (res.success) {
					$('#scl_perfil_foto_id').val(res.data.attachment_id);
					$('#scl_perfil_foto_preview').html('<img src="' + res.data.url + '" style="max-height:80px;border-radius:50%;" alt="">');
				} else { scl_flash(res.data || 'Error al subir.', 'error'); }
			},
			error: function() { scl_flash('Error de conexión.', 'error'); }
		});
	});

	// ── Datos personales ──────────────────────────────────────────
	$('#scl_perfil_guardar').on('click', function() {
		var $btn = $(this).prop('disabled', true).text('<?php esc_attr_e( 'Guardando...', 'sportcriss-lite' ); ?>');
		$.post(scl_ajax.url, {
			action:    'scl_actualizar_perfil',
			nonce:     scl_ajax.nonce,
			nombre:    $('#scl_perfil_nombre').val().trim(),
			apellido:  $('#scl_perfil_apellido').val().trim(),
			foto_id:   parseInt($('#scl_perfil_foto_id').val()) || 0,
		}, function(res) {
			if (res.success) scl_flash('<?php esc_attr_e( 'Datos actualizados.', 'sportcriss-lite' ); ?>');
			else scl_flash(res.data || '<?php esc_attr_e( 'Error al guardar.', 'sportcriss-lite' ); ?>', 'error');
		}).always(function() {
			$btn.prop('disabled', false).text('<?php esc_attr_e( 'Guardar datos', 'sportcriss-lite' ); ?>');
		});
	});

	// ── Cambio de email ───────────────────────────────────────────
	$('#scl_enviar_codigo_email').on('click', function() {
		var email = $('#scl_perfil_nuevo_email').val().trim();
		if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
			scl_flash('<?php esc_attr_e( 'Ingresa un email válido.', 'sportcriss-lite' ); ?>', 'error'); return;
		}
		var $btn = $(this).prop('disabled', true).text('Enviando...');
		$.post(scl_ajax.url, { action: 'scl_enviar_codigo_email', nonce: scl_ajax.nonce, nuevo_email: email }, function(res) {
			if (res.success) {
				$('#scl_email_paso1').hide();
				$('#scl_email_paso2').show();
				scl_flash('<?php esc_attr_e( 'Código enviado. Revisa tu nuevo email.', 'sportcriss-lite' ); ?>');
			} else {
				scl_flash(res.data || 'Error al enviar el código.', 'error');
				$btn.prop('disabled', false).text('<?php esc_attr_e( 'Enviar código de verificación', 'sportcriss-lite' ); ?>');
			}
		}).fail(function() {
			scl_flash('Error de conexión.', 'error');
			$btn.prop('disabled', false).text('<?php esc_attr_e( 'Enviar código de verificación', 'sportcriss-lite' ); ?>');
		});
	});

	$('#scl_email_reintentar').on('click', function() {
		$('#scl_email_paso2').hide();
		$('#scl_email_paso1').show();
		$('#scl_perfil_codigo').val('');
		$('#scl_enviar_codigo_email').prop('disabled', false).text('<?php esc_attr_e( 'Enviar código de verificación', 'sportcriss-lite' ); ?>');
	});

	$('#scl_verificar_codigo_email').on('click', function() {
		var codigo = $('#scl_perfil_codigo').val().trim();
		if (!/^\d{6}$/.test(codigo)) { scl_flash('<?php esc_attr_e( 'El código debe ser de 6 dígitos.', 'sportcriss-lite' ); ?>', 'error'); return; }
		var $btn = $(this).prop('disabled', true).text('Verificando...');
		$.post(scl_ajax.url, { action: 'scl_verificar_codigo_email', nonce: scl_ajax.nonce, codigo: codigo }, function(res) {
			if (res.success) {
				scl_flash('<?php esc_attr_e( 'Email actualizado correctamente.', 'sportcriss-lite' ); ?>');
				$('#scl_email_paso2').hide();
				$('#scl_email_paso1').show();
				$('#scl_perfil_nuevo_email').val('');
				$('#scl_perfil_codigo').val('');
				location.reload();
			} else {
				scl_flash(res.data || 'Código incorrecto o expirado.', 'error');
				$btn.prop('disabled', false).text('<?php esc_attr_e( 'Verificar y actualizar', 'sportcriss-lite' ); ?>');
			}
		}).fail(function() {
			scl_flash('Error de conexión.', 'error');
			$btn.prop('disabled', false).text('<?php esc_attr_e( 'Verificar y actualizar', 'sportcriss-lite' ); ?>');
		});
	});

	// ── Cambio de contraseña ──────────────────────────────────────
	$('#scl_cambiar_contrasena').on('click', function() {
		var actual    = $('#scl_pwd_actual').val();
		var nueva     = $('#scl_pwd_nueva').val();
		var confirmar = $('#scl_pwd_confirmar').val();

		if (!actual) { scl_flash('<?php esc_attr_e( 'Ingresa tu contraseña actual.', 'sportcriss-lite' ); ?>', 'error'); return; }
		if (nueva.length < 8) { scl_flash('<?php esc_attr_e( 'La nueva contraseña debe tener al menos 8 caracteres.', 'sportcriss-lite' ); ?>', 'error'); return; }
		if (nueva !== confirmar) { scl_flash('<?php esc_attr_e( 'Las contraseñas no coinciden.', 'sportcriss-lite' ); ?>', 'error'); return; }

		var $btn = $(this).prop('disabled', true).text('Cambiando...');
		$.post(scl_ajax.url, {
			action:              'scl_cambiar_contrasena',
			nonce:               scl_ajax.nonce,
			contrasena_actual:   actual,
			contrasena_nueva:    nueva,
			contrasena_confirmar: confirmar,
		}, function(res) {
			if (res.success) {
				scl_flash('<?php esc_attr_e( 'Contraseña actualizada.', 'sportcriss-lite' ); ?>');
				$('#scl_pwd_actual, #scl_pwd_nueva, #scl_pwd_confirmar').val('');
			} else {
				scl_flash(res.data || 'Error al cambiar la contraseña.', 'error');
			}
		}).always(function() {
			$btn.prop('disabled', false).text('<?php esc_attr_e( 'Cambiar contraseña', 'sportcriss-lite' ); ?>');
		});
	});

});
</script>
