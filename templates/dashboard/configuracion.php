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

// Obtener colaboradores actuales del organizador
$colaboradores = get_users( [
	'role'       => 'scl_colaborador',
	'meta_key'   => 'scl_colaborador_organizador_id',
	'meta_value' => get_current_user_id(),
] );
?>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php esc_html_e( 'Configuración', 'sportcriss-lite' ); ?></h1>
</div>

<div class="scl-form-section">
	<h3><?php esc_html_e( 'Mis colaboradores', 'sportcriss-lite' ); ?></h3>
	<p class="scl-description">
		<?php esc_html_e( 'Los colaboradores pueden ingresar resultados de tus partidos desde su propia cuenta. El usuario debe existir previamente en el sistema.', 'sportcriss-lite' ); ?>
	</p>

	<div class="scl-colaborador-form">
		<div class="scl-field-row" style="align-items: flex-end;">
			<div class="scl-field">
				<label for="scl_colaborador_email"><?php esc_html_e( 'Email del colaborador', 'sportcriss-lite' ); ?></label>
				<input type="email" id="scl_colaborador_email"
				       placeholder="<?php esc_attr_e( 'correo@ejemplo.com', 'sportcriss-lite' ); ?>">
			</div>
			<div class="scl-field" style="flex: 0 0 auto;">
				<button type="button" class="scl-btn scl-btn--primary" id="scl_asignar_colaborador_btn">
					+ <?php esc_html_e( 'Asignar', 'sportcriss-lite' ); ?>
				</button>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $colaboradores ) ) : ?>
		<div id="scl_colaboradores_lista" class="scl-table-wrapper" style="margin-top: 1.5rem;">
			<table class="scl-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nombre', 'sportcriss-lite' ); ?></th>
						<th><?php esc_html_e( 'Email', 'sportcriss-lite' ); ?></th>
						<th style="width: 120px;"></th>
					</tr>
				</thead>
				<tbody id="scl_colaboradores_tbody">
					<?php foreach ( $colaboradores as $colaborador ) : ?>
					<tr id="scl-colaborador-<?php echo esc_attr( $colaborador->ID ); ?>">
						<td><?php echo esc_html( $colaborador->display_name ); ?></td>
						<td><?php echo esc_html( $colaborador->user_email ); ?></td>
						<td>
							<button type="button"
							        class="scl-btn scl-btn--danger scl-btn--sm scl-revocar-colaborador-btn"
							        data-id="<?php echo esc_attr( $colaborador->ID ); ?>"
							        data-nombre="<?php echo esc_attr( $colaborador->display_name ); ?>">
								<?php esc_html_e( 'Revocar', 'sportcriss-lite' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div id="scl_colaboradores_lista">
			<div class="scl-empty" style="padding: 2rem;" id="scl_colaboradores_empty">
				<p style="margin: 0; color: #999;">
					<?php esc_html_e( 'Aún no tienes colaboradores asignados.', 'sportcriss-lite' ); ?>
				</p>
			</div>
			<table class="scl-table" id="scl_colaboradores_table" style="display:none; margin-top:1rem;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nombre', 'sportcriss-lite' ); ?></th>
						<th><?php esc_html_e( 'Email', 'sportcriss-lite' ); ?></th>
						<th style="width: 120px;"></th>
					</tr>
				</thead>
				<tbody id="scl_colaboradores_tbody"></tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {

	// Asignar colaborador
	$('#scl_asignar_colaborador_btn').on('click', function() {
		var email = $('#scl_colaborador_email').val().trim();
		if (!email) {
			scl_flash('Ingresa el email del colaborador.', 'error');
			$('#scl_colaborador_email').focus();
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('Asignando...');

		$.post(scl_ajax.url, {
			action: 'scl_asignar_colaborador',
			nonce:  scl_ajax.nonce,
			email:  email,
		}, function(res) {
			if (res.success) {
				$('#scl_colaborador_email').val('');
				scl_flash('Colaborador asignado correctamente.');
				// Agregar fila a la tabla
				var d = res.data;
				var fila = '<tr id="scl-colaborador-' + d.user_id + '">'
					+ '<td>' + $('<span>').text(d.display_name).html() + '</td>'
					+ '<td>' + $('<span>').text(d.email).html() + '</td>'
					+ '<td><button type="button" class="scl-btn scl-btn--danger scl-btn--sm scl-revocar-colaborador-btn"'
					+ ' data-id="' + d.user_id + '" data-nombre="' + $('<span>').text(d.display_name).html() + '">'
					+ '<?php echo esc_js( __( 'Revocar', 'sportcriss-lite' ) ); ?>'
					+ '</button></td></tr>';
				$('#scl_colaboradores_empty').hide();
				$('#scl_colaboradores_table').show();
				$('#scl_colaboradores_tbody').append(fila);
			} else {
				scl_flash(res.data || 'Error al asignar colaborador.', 'error');
			}
		}).fail(function() {
			scl_flash('Error de conexión.', 'error');
		}).always(function() {
			$btn.prop('disabled', false).text('+ <?php echo esc_js( __( 'Asignar', 'sportcriss-lite' ) ); ?>');
		});
	});

	// Revocar colaborador
	$(document).on('click', '.scl-revocar-colaborador-btn', function() {
		var id     = $(this).data('id');
		var nombre = $(this).data('nombre');
		if (!confirm('¿Revocar el acceso de "' + nombre + '"? El usuario perderá su rol de colaborador.')) return;

		$.post(scl_ajax.url, {
			action:         'scl_revocar_colaborador',
			nonce:          scl_ajax.nonce,
			colaborador_id: id,
		}, function(res) {
			if (res.success) {
				$('#scl-colaborador-' + id).fadeOut(300, function() { $(this).remove(); });
				scl_flash('Colaborador revocado.');
			} else {
				scl_flash(res.data || 'Error al revocar.', 'error');
			}
		});
	});

});
</script>
