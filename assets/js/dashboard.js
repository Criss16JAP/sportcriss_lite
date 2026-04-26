/**
 * Scripts para el Dashboard de SportCriss Lite.
 */

// Utilidad para construir URLs del dashboard
window.scl_url = function(ruta, id, accion) {
	var url = scl_ajax.base || window.location.href.split('?')[0];
	var params = [];
	if (ruta) params.push('scl_ruta=' + encodeURIComponent(ruta));
	if (id) params.push('scl_id=' + encodeURIComponent(id));
	if (accion) params.push('scl_accion=' + encodeURIComponent(accion));
	
	if (params.length > 0) {
		url += '?' + params.join('&');
	}
	return url;
};

// Mostrar mensaje flash (éxito o error)
function scl_flash(mensaje, tipo = 'success') {
	var $flash = jQuery('<div class="scl-flash scl-flash--' + tipo + '">' + mensaje + '</div>');
	jQuery('body').append($flash);
	$flash.css({
		position: 'fixed',
		top: '20px',
		right: '20px',
		zIndex: 9999,
		boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
	});
	setTimeout(function() {
		$flash.fadeOut(function() { jQuery(this).remove(); });
	}, 4000);
}

// Al cargar la página: leer sessionStorage para mensajes de éxito
document.addEventListener('DOMContentLoaded', function() {
	var flash = sessionStorage.getItem('scl_flash');
	if (flash) {
		scl_flash(flash);
		sessionStorage.removeItem('scl_flash');
	}
});

jQuery(document).ready(function($) {

	// Drag & drop del desempate — usar jQuery UI Sortable
	window.scl_init_sortable = function() {
		var $lista = $('#scl_desempate_lista');
		if (!$lista.length) return;

		$lista.sortable({
			items: '.scl-sortable__item:not(.scl-sortable__item--locked)',
			handle: '.scl-drag-handle',
			update: function() {
				var orden = [];
				$('#scl_desempate_lista .scl-sortable__item[data-value]').each(function() {
					orden.push($(this).data('value'));
				});
				$('#scl_desempate_orden').val(JSON.stringify(orden));
			}
		});
		// Disparar update inicial para poblar el campo hidden
		$lista.sortable('option', 'update').call($lista[0]);
	};
	scl_init_sortable();

	// Uploader de imagen frontend
	window.scl_init_uploader = function(btn_id, file_id, hidden_id, preview_id) {
		var $btn = $('#' + btn_id);
		var $file = $('#' + file_id);
		var $hidden = $('#' + hidden_id);
		var $preview = $('#' + preview_id);

		if (!$btn.length) return;

		$btn.on('click', function() {
			$file.click();
		});

		$file.on('change', function() {
			var file = this.files[0];
			if (!file) return;

			var formData = new FormData();
			formData.append('action', 'scl_subir_imagen_torneo');
			formData.append('nonce', scl_ajax.nonce);
			formData.append('file', file);

			$btn.text('Subiendo...').prop('disabled', true);

			$.ajax({
				url: scl_ajax.url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(res) {
					$btn.text('Seleccionar imagen').prop('disabled', false);
					if (res.success) {
						$hidden.val(res.data.attachment_id);
						$preview.html('<img src="' + res.data.url + '">');
					} else {
						scl_flash(res.data || 'Error al subir la imagen', 'error');
					}
				},
				error: function() {
					$btn.text('Seleccionar imagen').prop('disabled', false);
					scl_flash('Error de conexión al subir', 'error');
				}
			});
		});
	};
	scl_init_uploader('scl_logo_btn', 'scl_logo_file', 'scl_logo_id', 'scl_logo_preview');
	scl_init_uploader('scl_fondo_btn', 'scl_fondo_file', 'scl_fondo_id', 'scl_fondo_preview');

	// Guardar torneo
	$('#scl_guardar_torneo').on('click', function() {
		var torneoId = parseInt($('#scl_torneo_id').val()) || 0;
		var action = torneoId ? 'scl_editar_torneo' : 'scl_crear_torneo';

		// Serializar orden de desempate desde la lista sortable
		var orden = [];
		$('#scl_desempate_lista .scl-sortable__item[data-value]').each(function() {
			orden.push($(this).data('value'));
		});
		$('#scl_desempate_orden').val(JSON.stringify(orden));

		// Recoger todos los campos
		var data = {
			action: action,
			nonce: scl_ajax.nonce,
			torneo_id: torneoId,
			titulo: $('#scl_nombre').val().trim(),
			siglas: $('#scl_siglas').val().trim().toUpperCase(),
			puntos_victoria: $('#scl_victoria').val(),
			puntos_empate: $('#scl_empate').val(),
			puntos_derrota: $('#scl_derrota').val(),
			desempate_orden: $('#scl_desempate_orden').val(),
			color_primario: $('#scl_color_primario').val(),
			color_secundario: $('#scl_color_secundario').val(),
			logo_id: $('#scl_logo_id').val() || 0,
			fondo_id: $('#scl_fondo_id').val() || 0
		};

		// Validación mínima antes de enviar
		if (!data.titulo) {
			scl_flash('El nombre del torneo es obligatorio.', 'error');
			$('#scl_nombre').focus();
			return;
		}
		if (!data.siglas) {
			scl_flash('Las siglas son obligatorias.', 'error');
			$('#scl_siglas').focus();
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).text('Guardando...');

		$.post(scl_ajax.url, data, function(res) {
			if (res.success) {
				sessionStorage.setItem('scl_flash', 'Torneo guardado correctamente.');
				window.location.href = window.scl_url('torneos');
			} else {
				scl_flash(res.data || 'Error al guardar.', 'error');
				$btn.prop('disabled', false).text('Guardar torneo');
			}
		}).fail(function() {
			scl_flash('Error de conexión. Intenta de nuevo.', 'error');
			$btn.prop('disabled', false).text('Guardar torneo');
		});
	});

	// Eliminar torneo
	window.scl_confirmar_eliminar_torneo = function(torneo_id, nombre) {
		if (!confirm('¿Eliminar el torneo "' + nombre + '"? Esta acción no se puede deshacer.')) return;
		$.post(scl_ajax.url, {
			action: 'scl_eliminar_torneo',
			nonce: scl_ajax.nonce,
			torneo_id: torneo_id
		}, function(res) {
			if (res.success) {
				scl_flash('Torneo eliminado.');
				setTimeout(function() { window.location.reload(); }, 1000);
			} else {
				scl_flash(res.data || 'Error al eliminar', 'error');
			}
		});
	};

	// Grupos
	$('#scl_btn_nuevo_grupo').on('click', function() {
		$('#scl_grupo_form').slideDown();
	});
	$('#scl_grupo_cancelar').on('click', function() {
		$('#scl_grupo_form').slideUp();
		$('#scl_grupo_nombre, #scl_grupo_descripcion').val('');
	});
	$('#scl_grupo_guardar').on('click', function() {
		var data = {
			action: 'scl_crear_grupo',
			nonce: scl_ajax.nonce,
			torneo_id: $('#scl_grupo_torneo_id').val(),
			nombre: $('#scl_grupo_nombre').val(),
			descripcion: $('#scl_grupo_descripcion').val()
		};
		var $btn = $(this);
		$btn.prop('disabled', true).text('Guardando...');

		$.post(scl_ajax.url, data, function(res) {
			if (res.success) {
				sessionStorage.setItem('scl_flash', 'Grupo guardado exitosamente.');
				window.location.reload();
			} else {
				scl_flash(res.data || 'Error al guardar grupo', 'error');
				$btn.prop('disabled', false).text('Guardar');
			}
		});
	});

	window.scl_confirmar_eliminar_grupo = function(grupo_id, nombre) {
		if (!confirm('¿Eliminar el grupo "' + nombre + '"?')) return;
		$.post(scl_ajax.url, {
			action: 'scl_eliminar_grupo',
			nonce: scl_ajax.nonce,
			grupo_id: grupo_id
		}, function(res) {
			if (res.success) {
				scl_flash('Grupo eliminado.');
				setTimeout(function() { window.location.reload(); }, 1000);
			} else {
				scl_flash(res.data || 'Error al eliminar', 'error');
			}
		});
	};

	// Temporadas
	$('#scl_btn_nueva_temporada').on('click', function() {
		$('#scl_temporada_form').slideDown();
	});
	$('#scl_temp_cancelar').on('click', function() {
		$('#scl_temporada_form').slideUp();
		$('#scl_temp_nombre').val('');
	});
	$('#scl_temp_guardar').on('click', function() {
		var data = {
			action: 'scl_crear_temporada',
			nonce: scl_ajax.nonce,
			nombre: $('#scl_temp_nombre').val(),
			anio: $('#scl_temp_anio').val(),
			estado: $('#scl_temp_estado').val()
		};
		var $btn = $(this);
		$btn.prop('disabled', true).text('Guardando...');

		$.post(scl_ajax.url, data, function(res) {
			if (res.success) {
				if (res.data.created) {
					sessionStorage.setItem('scl_flash', 'Temporada creada exitosamente.');
				} else {
					sessionStorage.setItem('scl_flash', 'Esta temporada ya existía y ha sido reutilizada.');
				}
				window.location.reload();
			} else {
				scl_flash(res.data || 'Error al crear temporada', 'error');
				$btn.prop('disabled', false).text('Guardar temporada');
			}
		});
	});

});
