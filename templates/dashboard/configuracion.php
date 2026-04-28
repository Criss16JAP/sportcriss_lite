<?php
/**
 * Template: Dashboard – Configuración (gestión de colaboradores).
 * Ruta: /mi-panel/?scl_ruta=configuracion
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Solo organizadores y administradores pueden acceder
if ( scl_es_colaborador() ) {
	include SCL_PATH . 'templates/dashboard/acceso-denegado.php';
	return;
}

$colaboradores      = get_users( [
	'role'       => 'scl_colaborador',
	'meta_key'   => 'scl_colaborador_organizador_id',
	'meta_value' => get_current_user_id(),
] );
$total_colaboradores = count( $colaboradores );
$puede_agregar       = $total_colaboradores < 5;
?>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php esc_html_e( 'Configuración', 'sportcriss-lite' ); ?></h1>
</div>

<div class="scl-form-section">
	<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
		<div>
			<h3 style="margin:0 0 0.25rem"><?php esc_html_e( 'Mis colaboradores', 'sportcriss-lite' ); ?></h3>
			<p class="scl-description" style="margin:0">
				<?php esc_html_e( 'Los colaboradores pueden ingresar resultados de tus partidos desde su propia cuenta. Puedes tener hasta', 'sportcriss-lite' ); ?>
				<strong><?php esc_html_e( '5 colaboradores', 'sportcriss-lite' ); ?></strong>
				<?php esc_html_e( 'activos.', 'sportcriss-lite' ); ?>
			</p>
		</div>
		<span class="scl-badge <?php echo $total_colaboradores >= 5 ? 'scl-badge--error' : 'scl-badge--success'; ?>">
			<?php echo esc_html( $total_colaboradores ); ?>/5 colaboradores
		</span>
	</div>

	<?php if ( $puede_agregar ) : ?>
	<div class="scl-inline-form" id="scl_nuevo_colaborador_form" style="margin-bottom:1.5rem">
		<div class="scl-field-row">
			<div class="scl-field">
				<label for="scl_col_nombre"><?php esc_html_e( 'Nombre *', 'sportcriss-lite' ); ?></label>
				<input type="text" id="scl_col_nombre" placeholder="Cristian">
			</div>
			<div class="scl-field">
				<label for="scl_col_apellido"><?php esc_html_e( 'Apellido *', 'sportcriss-lite' ); ?></label>
				<input type="text" id="scl_col_apellido" placeholder="Ávila">
			</div>
		</div>
		<div class="scl-field">
			<label for="scl_col_email"><?php esc_html_e( 'Correo electrónico *', 'sportcriss-lite' ); ?></label>
			<input type="email" id="scl_col_email" placeholder="colaborador@correo.com">
			<p class="scl-description" style="margin-top:4px">
				<?php esc_html_e( 'Se creará una cuenta nueva y se le enviará la contraseña por correo.', 'sportcriss-lite' ); ?>
			</p>
		</div>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_col_guardar">
			+ <?php esc_html_e( 'Agregar colaborador', 'sportcriss-lite' ); ?>
		</button>
	</div>
	<?php else : ?>
	<div class="scl-alert scl-alert--warning" style="margin-bottom:1.5rem">
		<?php esc_html_e( 'Has alcanzado el límite de 5 colaboradores. Revoca uno para poder agregar otro.', 'sportcriss-lite' ); ?>
	</div>
	<?php endif; ?>

	<!-- Lista de colaboradores actuales -->
	<div id="scl_colaboradores_lista">
		<?php if ( ! empty( $colaboradores ) ) : ?>
			<div class="scl-equipos-list">
			<?php foreach ( $colaboradores as $col ) : ?>
				<div class="scl-equipo-card" id="scl-col-<?php echo esc_attr( $col->ID ); ?>">
					<div class="scl-equipo-card__escudo">
						<?php echo esc_html( strtoupper( mb_substr( $col->display_name, 0, 1 ) ) ); ?>
					</div>
					<div class="scl-equipo-card__info">
						<div class="scl-equipo-card__nombre">
							<?php echo esc_html( $col->display_name ); ?>
						</div>
						<div class="scl-equipo-card__meta">
							<?php echo esc_html( $col->user_email ); ?>
						</div>
					</div>
					<div class="scl-equipo-card__actions">
						<button type="button"
						        class="scl-btn scl-btn--danger scl-btn--sm"
						        onclick="scl_revocar_colaborador(<?php echo esc_attr( $col->ID ); ?>, '<?php echo esc_js( $col->display_name ); ?>')">
							<?php esc_html_e( 'Revocar', 'sportcriss-lite' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="scl-description" id="scl_colaboradores_vacio">
				<?php esc_html_e( 'Aún no tienes colaboradores. Agrega uno arriba.', 'sportcriss-lite' ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>

<script>
jQuery(document).ready(function($) {

	// Guardar nuevo colaborador (crea usuario desde cero)
	$(document).on('click', '#scl_col_guardar', function() {
		var nombre   = $('#scl_col_nombre').val().trim();
		var apellido = $('#scl_col_apellido').val().trim();
		var email    = $('#scl_col_email').val().trim();

		if (!nombre || !apellido) {
			scl_flash('El nombre y apellido son obligatorios.', 'error'); return;
		}
		var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		if (!email || !emailRegex.test(email)) {
			scl_flash('Ingresa un correo válido.', 'error'); return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Creando cuenta...', 'sportcriss-lite' ) ); ?>');

		$.post(scl_ajax.url, {
			action:   'scl_asignar_colaborador',
			nonce:    scl_ajax.nonce,
			nombre:   nombre,
			apellido: apellido,
			email:    email,
		}, function(res) {
			if (res.success) {
				scl_flash(res.data.display_name + ' <?php echo esc_js( __( 'ha sido agregado como colaborador. Se le envió su contraseña por correo.', 'sportcriss-lite' ) ); ?>');
				$('#scl_col_nombre, #scl_col_apellido, #scl_col_email').val('');
				setTimeout(function() { window.location.reload(); }, 1800);
			} else {
				scl_flash(res.data || 'Error al crear el colaborador.', 'error');
				$btn.prop('disabled', false).text('+ <?php echo esc_js( __( 'Agregar colaborador', 'sportcriss-lite' ) ); ?>');
			}
		}).fail(function() {
			scl_flash('Error de conexión.', 'error');
			$btn.prop('disabled', false).text('+ <?php echo esc_js( __( 'Agregar colaborador', 'sportcriss-lite' ) ); ?>');
		});
	});

	// Revocar colaborador (elimina la cuenta)
	window.scl_revocar_colaborador = function(id, nombre) {
		if (!confirm('¿Revocar acceso de ' + nombre + '? Su cuenta será eliminada.')) return;
		$.post(scl_ajax.url, {
			action:          'scl_revocar_colaborador',
			nonce:           scl_ajax.nonce,
			colaborador_id:  id,
		}, function(res) {
			if (res.success) {
				$('#scl-col-' + id).fadeOut(300, function() { $(this).remove(); });
				scl_flash('Acceso revocado correctamente.');
			} else {
				scl_flash(res.data || 'Error al revocar.', 'error');
			}
		});
	};

});
</script>
