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

// ==========================================================================
// SPRINT 5: Equipos
// ==========================================================================

// Drawer genérico (global para acceso desde templates inline si fuera necesario)
window.scl_drawer_abrir = function(id) {
	jQuery('#' + id).addClass('scl-drawer--open');
	jQuery('body').addClass('scl-drawer-active');
};
window.scl_drawer_cerrar = function(id) {
	jQuery('#' + id).removeClass('scl-drawer--open');
	jQuery('body').removeClass('scl-drawer-active');
};

window.scl_equipo_nuevo = function() {
	jQuery('#scl_equipo_id').val(0);
	jQuery('#scl_equipo_nombre').val('').trigger('input');
	jQuery('#scl_equipo_zona').val('');
	jQuery('#scl_escudo_id').val(0);
	jQuery('#scl_escudo_img').hide().attr('src', '');
	jQuery('#scl_escudo_placeholder').show().text('?');
	jQuery('#scl_equipo_drawer_titulo').text('Nuevo Equipo');
	scl_drawer_abrir('scl_equipo_drawer');
};

window.scl_equipo_editar = function(id, nombre, zona, escudo_url, escudo_id) {
	jQuery('#scl_equipo_id').val(id);
	jQuery('#scl_equipo_nombre').val(nombre);
	jQuery('#scl_equipo_zona').val(zona || '');
	jQuery('#scl_escudo_id').val(escudo_id || 0);
	if (escudo_url) {
		jQuery('#scl_escudo_img').attr('src', escudo_url).show();
		jQuery('#scl_escudo_placeholder').hide();
	} else {
		jQuery('#scl_escudo_img').hide();
		jQuery('#scl_escudo_placeholder').show().text(nombre ? nombre.charAt(0).toUpperCase() : '?');
	}
	jQuery('#scl_equipo_drawer_titulo').text('Editar Equipo');
	scl_drawer_abrir('scl_equipo_drawer');
};

window.scl_equipo_eliminar = function(id, nombre) {
	if (!confirm('¿Eliminar el equipo "' + nombre + '"?')) return;
	jQuery.post(scl_ajax.url, {
		action:    'scl_eliminar_equipo',
		nonce:     scl_ajax.nonce,
		equipo_id: id,
	}, function(res) {
		if (res.success) {
			jQuery('#scl-equipo-' + id).fadeOut(300, function() { jQuery(this).remove(); });
			scl_flash('Equipo eliminado.');
		} else {
			scl_flash(res.data || 'No se pudo eliminar.', 'error');
		}
	});
};

jQuery(document).ready(function($) {

	// Guardar equipo (crear o editar)
	$(document).on('click', '#scl_equipo_guardar', function() {
		var equipo_id = parseInt($('#scl_equipo_id').val()) || 0;
		var nombre    = $('#scl_equipo_nombre').val().trim();

		if (!nombre) {
			scl_flash('El nombre del equipo es obligatorio.', 'error');
			$('#scl_equipo_nombre').focus();
			return;
		}

		var data = {
			action:    equipo_id ? 'scl_editar_equipo' : 'scl_crear_equipo',
			nonce:     scl_ajax.nonce,
			equipo_id: equipo_id,
			nombre:    nombre,
			zona:      $('#scl_equipo_zona').val().trim(),
			escudo_id: $('#scl_escudo_id').val() || 0,
		};

		var $btn = $(this);
		$btn.prop('disabled', true).text('Guardando...');

		$.post(scl_ajax.url, data, function(res) {
			if (res.success) {
				scl_drawer_cerrar('scl_equipo_drawer');
				sessionStorage.setItem('scl_flash', 'Equipo guardado correctamente.');
				window.location.reload();
			} else {
				scl_flash(res.data || 'Error al guardar.', 'error');
				$btn.prop('disabled', false).text('Guardar equipo');
			}
		}).fail(function() {
			scl_flash('Error de conexión.', 'error');
			$btn.prop('disabled', false).text('Guardar equipo');
		});
	});

	// Cerrar drawer
	$(document).on('click', '#scl_equipo_cancelar, #scl_equipo_cerrar', function() {
		scl_drawer_cerrar('scl_equipo_drawer');
	});
	$(document).on('click', '.scl-drawer__overlay', function() {
		$('.scl-drawer--open').each(function() {
			scl_drawer_cerrar($(this).attr('id'));
		});
	});

	// Botón Editar/Completar delegado (usa data-attributes)
	$(document).on('click', '.scl-equipo-editar-btn', function() {
		var $b = $(this);
		scl_equipo_editar(
			$b.data('id'),
			$b.data('nombre'),
			$b.data('zona'),
			$b.attr('data-escudo-url'),
			$b.data('escudo-id')
		);
	});

	// Botón Eliminar delegado
	$(document).on('click', '.scl-equipo-eliminar-btn', function() {
		scl_equipo_eliminar($(this).data('id'), $(this).data('nombre'));
	});

	// Botón "Registrar primer equipo" en estado vacío
	$(document).on('click', '#scl_nuevo_equipo_btn_empty', function() {
		scl_equipo_nuevo();
	});

	// Placeholder con inicial del nombre mientras se escribe
	$(document).on('input', '#scl_equipo_nombre', function() {
		if (!$('#scl_escudo_id').val() || $('#scl_escudo_id').val() === '0') {
			$('#scl_escudo_placeholder').text($(this).val().charAt(0).toUpperCase() || '?');
		}
	});

	// Búsqueda en tiempo real (filtra en cliente sin recargar)
	$(document).on('input', '#scl_buscar_equipo', function() {
		var q = $(this).val().toLowerCase().trim();
		$('.scl-equipo-card').each(function() {
			$(this).toggle(!q || $(this).data('nombre').indexOf(q) !== -1);
		});
	});

	// Filtro de estado → recarga la página con el parámetro GET
	$(document).on('change', '#scl_filtro_equipos', function() {
		var url = new URL(window.location.href);
		url.searchParams.set('filtro', $(this).val());
		window.location.href = url.toString();
	});

	// Uploader de escudo
	$(document).on('click', '#scl_escudo_btn', function() {
		$('#scl_escudo_file').trigger('click');
	});

	$(document).on('change', '#scl_escudo_file', function() {
		var file = this.files[0];
		if (!file) return;

		if (file.size > 2 * 1024 * 1024) {
			scl_flash('El escudo no puede superar 2MB.', 'error');
			$(this).val('');
			return;
		}

		var formData = new FormData();
		formData.append('action', 'scl_subir_escudo');
		formData.append('nonce', scl_ajax.nonce);
		formData.append('escudo', file);

		var $btn = $('#scl_escudo_btn');
		$btn.prop('disabled', true).text('Subiendo...');

		$.ajax({
			url:         scl_ajax.url,
			type:        'POST',
			data:        formData,
			processData: false,
			contentType: false,
			success: function(res) {
				if (res.success) {
					$('#scl_escudo_id').val(res.data.attachment_id);
					$('#scl_escudo_img').attr('src', res.data.url).show();
					$('#scl_escudo_placeholder').hide();
				} else {
					scl_flash(res.data || 'Error al subir imagen.', 'error');
				}
			},
			complete: function() {
				$btn.prop('disabled', false).html('&#128247; Subir escudo');
			}
		});
	});

});
