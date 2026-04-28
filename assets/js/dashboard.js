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

	// Hamburguesa mobile
	document.getElementById('scl_hamburger')?.addEventListener('click', function() {
		document.getElementById('scl_nav_links')?.classList.toggle('open');
	});
});

// ── Color picker sync ─────────────────────────────────────────
window.scl_sync_color = function(tipo, valor) {
	document.getElementById('scl_preview_' + tipo).style.background = valor;
	document.getElementById('scl_hex_' + tipo).textContent = valor;
};

// ── Uploader con barra de progreso (formulario de torneo) ──────
window.scl_init_form_uploader = function(fileInputId, contentId, progressId, hiddenId) {
	var fileInput  = document.getElementById(fileInputId);
	var contentEl  = document.getElementById(contentId);
	if (!fileInput || !contentEl) return;

	// El div visual actúa como trigger; el input está fuera del div con display:none
	var dropzone = contentEl.parentElement;
	if (dropzone) {
		dropzone.addEventListener('click', function() {
			fileInput.click();
		});
	}

	fileInput.addEventListener('change', function() {
		var file = this.files[0];
		if (!file) return;

		if (file.size > 5 * 1024 * 1024) {
			scl_flash('La imagen no puede superar 5MB.', 'error');
			return;
		}

		var formData = new FormData();
		formData.append('action', 'scl_subir_imagen_torneo');
		formData.append('nonce', scl_ajax.nonce);
		formData.append('file', file);

		var xhr = new XMLHttpRequest();
		xhr.open('POST', scl_ajax.url);

		contentEl.innerHTML = '<div class="scl-upload-loading">&#9203; Subiendo imagen...</div>';

		xhr.upload.onprogress = function(e) {
			if (e.lengthComputable) {
				var pct = Math.round((e.loaded / e.total) * 100);
				document.getElementById(progressId).style.width = pct + '%';
			}
		};

		xhr.onload = function() {
			var res;
			try { res = JSON.parse(xhr.responseText); } catch(e) { res = { success: false }; }
			if (res.success) {
				document.getElementById(hiddenId).value = res.data.attachment_id;
				contentEl.innerHTML =
					'<img src="' + res.data.url + '" class="scl-file-uploader__preview" alt="">'
					+ '<p class="scl-file-uploader__text"><small>Haz clic para cambiar</small></p>';
			} else {
				contentEl.innerHTML = '<div class="scl-upload-loading">&#10060; Error. Intenta de nuevo.</div>';
				scl_flash(res.data || 'Error al subir la imagen.', 'error');
			}
			document.getElementById(progressId).style.width = '0';
		};

		xhr.onerror = function() {
			contentEl.innerHTML = '<div class="scl-upload-loading">&#10060; Error de conexión. Intenta de nuevo.</div>';
			scl_flash('Error de conexión al subir.', 'error');
			document.getElementById(progressId).style.width = '0';
		};

		xhr.send(formData);
	});
};

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
		$lista.sortable('option', 'update').call($lista[0]);
	};
	scl_init_sortable();

	// Inicializar uploaders del formulario de torneo
	scl_init_form_uploader('scl_logo_file', 'scl_logo_content', 'scl_logo_progress', 'scl_logo_id');
	scl_init_form_uploader('scl_fondo_file', 'scl_fondo_content', 'scl_fondo_progress', 'scl_fondo_id');

	// Sincronizar colores al cargar con valores precargados
	(function() {
		var cp = document.getElementById('scl_color_primario');
		var cs = document.getElementById('scl_color_secundario');
		if (cp) scl_sync_color('primario',   cp.value);
		if (cs) scl_sync_color('secundario', cs.value);
	})();

	// Guardar torneo
	$('#scl_guardar_torneo').on('click', function() {
		var torneoId = parseInt($('#scl_torneo_id_editar').val()) || 0;
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

	// Botón principal "Nuevo Equipo" en el header de la lista
	$(document).on('click', '#scl_nuevo_equipo_btn', scl_equipo_nuevo);

	// Botón "Registrar primer equipo" en estado vacío
	$(document).on('click', '#scl_nuevo_equipo_btn_empty', scl_equipo_nuevo);

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

		if (file.size > 5 * 1024 * 1024) {
			scl_flash('El escudo no puede superar 5MB.', 'error');
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

// ==========================================================================
// SPRINT 6: Partidos
// ==========================================================================

var scl_pending_grupo_id = 0;

window.scl_resultado_abrir = function(data) {
	var $ = jQuery;
	$('#scl_resultado_partido_id').val(data.id);
	$('#scl_resultado_tipo_fase').val(data.tipo_fase || 'grupos');

	// Escudo local
	if (data.escudo_local) {
		$('#scl_resultado_img_local').attr('src', data.escudo_local).show();
		$('#scl_resultado_placeholder_local').hide();
	} else {
		$('#scl_resultado_img_local').hide();
		$('#scl_resultado_placeholder_local').show()
			.text(data.nombre_local ? data.nombre_local.charAt(0).toUpperCase() : '?');
	}
	// Escudo visitante
	if (data.escudo_visita) {
		$('#scl_resultado_img_visita').attr('src', data.escudo_visita).show();
		$('#scl_resultado_placeholder_visita').hide();
	} else {
		$('#scl_resultado_img_visita').hide();
		$('#scl_resultado_placeholder_visita').show()
			.text(data.nombre_visita ? data.nombre_visita.charAt(0).toUpperCase() : '?');
	}

	$('#scl_resultado_nombre_local').text(data.nombre_local || '—');
	$('#scl_resultado_nombre_visita').text(data.nombre_visita || '—');
	$('#scl_resultado_pen_label_local').text(data.nombre_local || '');
	$('#scl_resultado_pen_label_visita').text(data.nombre_visita || '');

	var gl = (data.goles_local  !== '' && data.goles_local  != null) ? data.goles_local  : '';
	var gv = (data.goles_visita !== '' && data.goles_visita != null) ? data.goles_visita : '';
	$('#scl_resultado_goles_local').val(gl);
	$('#scl_resultado_goles_visita').val(gv);
	$('#scl_resultado_penales_local').val('');
	$('#scl_resultado_penales_visita').val('');
	$('#scl_resultado_estado').val(gl !== '' ? 'finalizado' : 'pendiente');

	scl_resultado_toggle_penales();
	scl_drawer_abrir('scl_resultado_drawer');
};

function scl_resultado_toggle_penales() {
	var tipo_fase = jQuery('#scl_resultado_tipo_fase').val();
	var gl = jQuery('#scl_resultado_goles_local').val();
	var gv = jQuery('#scl_resultado_goles_visita').val();
	var empate = gl !== '' && gv !== '' && parseInt(gl, 10) === parseInt(gv, 10);
	jQuery('#scl_resultado_penales_seccion').toggle(tipo_fase === 'playoff' && empate);
}

window.scl_partido_nuevo = function() {
	var $ = jQuery;
	$('#scl_partido_id').val(0);
	$('#scl_partido_torneo_id').val('');
	$('#scl_partido_temporada_id').val('');
	$('#scl_partido_tipo_fase').val('grupos');
	$('#scl_partido_grupo_id').html('<option value="">— Sin grupo / grupo único —</option>');
	$('#scl_partido_jornada').val('');
	$('#scl_partido_equipo_local_id').val('');
	$('#scl_partido_equipo_visita_id').val('');
	$('#scl_partido_fecha').val('');
	$('#scl_partido_estado').val('pendiente');
	$('#scl_partido_goles_local').val('');
	$('#scl_partido_goles_visita').val('');
	$('#scl_partido_penales_local').val('');
	$('#scl_partido_penales_visita').val('');
	$('#scl_partido_marcador_fields').hide();
	$('#scl_partido_penales_seccion').hide();
	$('#scl_partido_grupo_field').show();
	$('#scl_partido_jornada_field').show();
	$('#scl_partido_drawer_titulo').text('Nuevo partido');
	scl_drawer_abrir('scl_partido_drawer');
};

window.scl_partido_editar = function(data) {
	var $ = jQuery;
	$('#scl_partido_id').val(data.id);
	$('#scl_partido_torneo_id').val(data.torneo_id || '');
	$('#scl_partido_temporada_id').val(data.temporada_term_id || '');
	$('#scl_partido_tipo_fase').val(data.tipo_fase || 'grupos');
	$('#scl_partido_jornada').val(data.jornada || '');
	$('#scl_partido_equipo_local_id').val(data.equipo_local_id || '');
	$('#scl_partido_equipo_visita_id').val(data.equipo_visita_id || '');
	$('#scl_partido_fecha').val(data.fecha || '');
	$('#scl_partido_estado').val(data.estado || 'pendiente');

	if (data.estado === 'finalizado') {
		$('#scl_partido_marcador_fields').show();
		$('#scl_partido_goles_local').val(data.goles_local  != null && data.goles_local  !== '' ? data.goles_local  : '');
		$('#scl_partido_goles_visita').val(data.goles_visita != null && data.goles_visita !== '' ? data.goles_visita : '');
	} else {
		$('#scl_partido_marcador_fields').hide();
		$('#scl_partido_goles_local').val('');
		$('#scl_partido_goles_visita').val('');
	}
	$('#scl_partido_penales_local').val('');
	$('#scl_partido_penales_visita').val('');

	var es_playoff = data.tipo_fase === 'playoff';
	$('#scl_partido_grupo_field').toggle(!es_playoff);
	$('#scl_partido_jornada_field').toggle(!es_playoff);

	if (data.torneo_id) {
		scl_pending_grupo_id = data.grupo_id || 0;
		scl_cargar_grupos(data.torneo_id, data.grupo_id);
	}

	scl_partido_actualizar_labels();
	scl_partido_toggle_penales();
	$('#scl_partido_drawer_titulo').text('Editar partido');
	scl_drawer_abrir('scl_partido_drawer');
};

function scl_cargar_grupos(torneo_id, selected_id) {
	var $select = jQuery('#scl_partido_grupo_id');
	$select.prop('disabled', true);
	jQuery.post(scl_ajax.url, {
		action:    'scl_get_grupos_por_torneo',
		nonce:     scl_ajax.nonce,
		torneo_id: torneo_id
	}, function(res) {
		$select.html('<option value="">— Sin grupo / grupo único —</option>');
		if (res.success && res.data.length) {
			jQuery.each(res.data, function(i, grupo) {
				$select.append('<option value="' + grupo.ID + '">' + grupo.post_title + '</option>');
			});
		}
		var target = selected_id || scl_pending_grupo_id;
		if (target) {
			$select.val(target);
		}
		scl_pending_grupo_id = 0;
	}).always(function() {
		$select.prop('disabled', false);
	});
}

function scl_partido_actualizar_labels() {
	var nombre_local  = jQuery('#scl_partido_equipo_local_id option:selected').text()  || 'Local';
	var nombre_visita = jQuery('#scl_partido_equipo_visita_id option:selected').text() || 'Visitante';
	jQuery('#scl_partido_label_local').text(nombre_local);
	jQuery('#scl_partido_label_visita').text(nombre_visita);
}

function scl_partido_toggle_penales() {
	var tipo_fase = jQuery('#scl_partido_tipo_fase').val();
	var estado    = jQuery('#scl_partido_estado').val();
	if (tipo_fase !== 'playoff' || estado !== 'finalizado') {
		jQuery('#scl_partido_penales_seccion').hide();
		return;
	}
	var gl = jQuery('#scl_partido_goles_local').val();
	var gv = jQuery('#scl_partido_goles_visita').val();
	var empate = gl !== '' && gv !== '' && parseInt(gl, 10) === parseInt(gv, 10);
	jQuery('#scl_partido_penales_seccion').toggle(empate);
}

jQuery(document).ready(function($) {

	// Nuevo partido
	$(document).on('click', '#scl_nuevo_partido_btn', function() {
		scl_partido_nuevo();
	});

	// Filtros → recargar con params en URL
	$(document).on('change', '.scl-filtros select[data-param]', function() {
		var url   = new URL(window.location.href);
		var param = $(this).data('param');
		var val   = $(this).val();
		if (val && val !== '0') {
			url.searchParams.set(param, val);
		} else {
			url.searchParams.delete(param);
		}
		window.location.href = url.toString();
	});

	// Abrir drawer resultado rápido
	$(document).on('click', '.scl-resultado-btn', function() {
		var $b = $(this);
		scl_resultado_abrir({
			id:            $b.data('id'),
			tipo_fase:     $b.data('tipo-fase'),
			nombre_local:  $b.data('nombre-local'),
			nombre_visita: $b.data('nombre-visita'),
			escudo_local:  $b.attr('data-escudo-local'),
			escudo_visita: $b.attr('data-escudo-visita'),
			goles_local:   $b.data('goles-local'),
			goles_visita:  $b.data('goles-visita'),
		});
	});

	// Cerrar drawer resultado
	$(document).on('click', '#scl_resultado_cerrar, #scl_resultado_cancelar', function() {
		scl_drawer_cerrar('scl_resultado_drawer');
	});

	// Toggle penales en drawer resultado
	$(document).on('input', '#scl_resultado_goles_local, #scl_resultado_goles_visita', function() {
		scl_resultado_toggle_penales();
	});

	// Guardar resultado rápido
	$(document).on('click', '#scl_resultado_guardar', function() {
		var partido_id = parseInt($('#scl_resultado_partido_id').val()) || 0;
		if (!partido_id) return;

		var data = {
			action:         'scl_guardar_resultado',
			nonce:          scl_ajax.nonce,
			partido_id:     partido_id,
			estado:         $('#scl_resultado_estado').val(),
			goles_local:    $('#scl_resultado_goles_local').val().trim(),
			goles_visita:   $('#scl_resultado_goles_visita').val().trim(),
			penales_local:  $('#scl_resultado_penales_local').val().trim(),
			penales_visita: $('#scl_resultado_penales_visita').val().trim(),
		};

		var $btn = $(this);
		$btn.prop('disabled', true).text('Guardando...');

		$.post(scl_ajax.url, data, function(res) {
			if (res.success) {
				scl_drawer_cerrar('scl_resultado_drawer');
				sessionStorage.setItem('scl_flash', 'Resultado guardado correctamente.');
				window.location.reload();
			} else {
				scl_flash(res.data || 'Error al guardar resultado.', 'error');
				$btn.prop('disabled', false).text('Guardar resultado');
			}
		}).fail(function() {
			scl_flash('Error de conexión.', 'error');
			$btn.prop('disabled', false).text('Guardar resultado');
		});
	});

	// Abrir drawer partido completo (editar)
	$(document).on('click', '.scl-partido-editar-btn', function() {
		try {
			var data = JSON.parse($(this).attr('data-partido'));
			scl_partido_editar(data);
		} catch(e) {
			scl_flash('Error al leer datos del partido.', 'error');
		}
	});

	// Cerrar drawer partido completo
	$(document).on('click', '#scl_partido_cerrar, #scl_partido_cancelar', function() {
		scl_drawer_cerrar('scl_partido_drawer');
	});

	// Eliminar partido
	$(document).on('click', '.scl-partido-eliminar-btn', function() {
		var id     = $(this).data('id');
		var titulo = $(this).data('titulo');
		if (!confirm('¿Eliminar el partido "' + titulo + '"? Esta acción no se puede deshacer.')) return;
		$.post(scl_ajax.url, {
			action:     'scl_eliminar_partido',
			nonce:      scl_ajax.nonce,
			partido_id: id,
		}, function(res) {
			if (res.success) {
				$('#scl-partido-' + id).fadeOut(300, function() { $(this).remove(); });
				scl_flash('Partido eliminado.');
			} else {
				scl_flash(res.data || 'No se pudo eliminar.', 'error');
			}
		});
	});

	// Estado → mostrar/ocultar marcador
	$(document).on('change', '#scl_partido_estado', function() {
		if ($(this).val() === 'finalizado') {
			$('#scl_partido_marcador_fields').show();
		} else {
			$('#scl_partido_marcador_fields').hide();
			$('#scl_partido_penales_seccion').hide();
		}
	});

	// Tipo de fase → grupo y jornada
	$(document).on('change', '#scl_partido_tipo_fase', function() {
		var es_playoff = $(this).val() === 'playoff';
		$('#scl_partido_grupo_field').toggle(!es_playoff);
		$('#scl_partido_jornada_field').toggle(!es_playoff);
		scl_partido_toggle_penales();
	});

	// Torneo → cargar grupos
	$(document).on('change', '#scl_partido_torneo_id', function() {
		var torneo_id = parseInt($(this).val()) || 0;
		if (torneo_id) {
			scl_cargar_grupos(torneo_id, 0);
		} else {
			$('#scl_partido_grupo_id').html('<option value="">— Sin grupo / grupo único —</option>');
		}
	});

	// Equipos → actualizar labels en marcador
	$(document).on('change', '#scl_partido_equipo_local_id, #scl_partido_equipo_visita_id', function() {
		scl_partido_actualizar_labels();
	});

	// Goles en drawer completo → toggle penales
	$(document).on('input', '#scl_partido_goles_local, #scl_partido_goles_visita', function() {
		scl_partido_toggle_penales();
	});

	// Guardar partido (crear o editar)
	$(document).on('click', '#scl_partido_guardar', function() {
		var partido_id = parseInt($('#scl_partido_id').val()) || 0;
		var torneo_id  = parseInt($('#scl_partido_torneo_id').val()) || 0;
		var temporada  = parseInt($('#scl_partido_temporada_id').val()) || 0;
		var tipo_fase  = $('#scl_partido_tipo_fase').val();
		var equipo_l   = parseInt($('#scl_partido_equipo_local_id').val()) || 0;
		var equipo_v   = parseInt($('#scl_partido_equipo_visita_id').val()) || 0;

		if (!torneo_id) {
			scl_flash('Selecciona un torneo.', 'error');
			$('#scl_partido_torneo_id').focus();
			return;
		}
		if (!temporada) {
			scl_flash('Selecciona una temporada.', 'error');
			$('#scl_partido_temporada_id').focus();
			return;
		}
		if (!equipo_l || !equipo_v) {
			scl_flash('Selecciona ambos equipos.', 'error');
			return;
		}
		if (equipo_l === equipo_v) {
			scl_flash('El equipo local y visitante no pueden ser el mismo.', 'error');
			return;
		}

		var estado  = $('#scl_partido_estado').val();
		var goles_l = '', goles_v = '', pen_l = '', pen_v = '';
		if (estado === 'finalizado') {
			goles_l = $('#scl_partido_goles_local').val().trim();
			goles_v = $('#scl_partido_goles_visita').val().trim();
			pen_l   = $('#scl_partido_penales_local').val().trim();
			pen_v   = $('#scl_partido_penales_visita').val().trim();
		}

		var jornada  = '';
		var grupo_id = 0;
		if (tipo_fase !== 'playoff') {
			jornada  = $('#scl_partido_jornada').val().trim();
			grupo_id = parseInt($('#scl_partido_grupo_id').val()) || 0;
		}

		var data = {
			action:           partido_id ? 'scl_editar_partido' : 'scl_crear_partido',
			nonce:            scl_ajax.nonce,
			partido_id:       partido_id,
			torneo_id:        torneo_id,
			temporada_id:     temporada,
			tipo_fase:        tipo_fase,
			grupo_id:         grupo_id,
			jornada:          jornada,
			equipo_local_id:  equipo_l,
			equipo_visita_id: equipo_v,
			fecha:            $('#scl_partido_fecha').val(),
			estado:           estado,
			goles_local:      goles_l,
			goles_visita:     goles_v,
			penales_local:    pen_l,
			penales_visita:   pen_v,
		};

		var $btn = $(this);
		$btn.prop('disabled', true).text('Guardando...');

		$.post(scl_ajax.url, data, function(res) {
			if (res.success) {
				scl_drawer_cerrar('scl_partido_drawer');
				sessionStorage.setItem('scl_flash', 'Partido guardado correctamente.');
				window.location.reload();
			} else {
				scl_flash(res.data || 'Error al guardar partido.', 'error');
				$btn.prop('disabled', false).text('Guardar partido');
			}
		}).fail(function() {
			scl_flash('Error de conexión.', 'error');
			$btn.prop('disabled', false).text('Guardar partido');
		});
	});

});

// ==========================================================================
// SPRINT 7: Llaves Playoff
// ==========================================================================

function scl_llave_preview_actualizar() {
	var $ = jQuery;
	var a     = $('#scl_llave_equipo_a_id option:selected').text();
	var b     = $('#scl_llave_equipo_b_id option:selected').text();
	var doble = $('input[name="scl_llave_formato"]:checked').val() === '1';
	var fase  = $('#scl_llave_nombre_fase').val() || '?';
	var aVal  = $('#scl_llave_equipo_a_id').val();
	var bVal  = $('#scl_llave_equipo_b_id').val();

	if (!aVal || aVal === '0' || !bVal || bVal === '0') {
		$('#scl_llave_preview').hide();
		return;
	}

	var html = '<strong>Se crearán:</strong><ul>';
	html += '<li>' + $('<span>').text(a).html() + ' vs ' + $('<span>').text(b).html()
		+ ' (' + $('<span>').text(fase).html() + ' - Ida)</li>';
	if (doble) {
		html += '<li>' + $('<span>').text(b).html() + ' vs ' + $('<span>').text(a).html()
			+ ' (' + $('<span>').text(fase).html() + ' - Vuelta)</li>';
	}
	html += '</ul>';
	$('#scl_llave_preview').html(html).show();
}

function scl_llave_guardar() {
	var $ = jQuery;
	var torneo_id = $('#scl_llave_torneo_id').val();
	var equipo_a  = $('#scl_llave_equipo_a_id').val();
	var equipo_b  = $('#scl_llave_equipo_b_id').val();
	var fase      = $('#scl_llave_nombre_fase').val().trim();
	var es_doble  = $('input[name="scl_llave_formato"]:checked').val();

	if (!torneo_id || torneo_id === '0') { scl_flash('Selecciona un torneo.', 'error'); return; }
	if (!equipo_a || equipo_a === '0' || !equipo_b || equipo_b === '0') { scl_flash('Selecciona ambos equipos.', 'error'); return; }
	if (equipo_a === equipo_b) { scl_flash('Los equipos deben ser diferentes.', 'error'); return; }
	if (!fase) { scl_flash('Escribe el nombre de la fase.', 'error'); return; }

	var $btn = $('#scl_llave_guardar');
	$btn.prop('disabled', true).text('Creando...');

	jQuery.post(scl_ajax.url, {
		action:            'scl_crear_llave',
		nonce:             scl_ajax.nonce,
		torneo_id:         torneo_id,
		temporada_term_id: $('#scl_llave_temporada_term_id').val() || 0,
		equipo_a_id:       equipo_a,
		equipo_b_id:       equipo_b,
		nombre_fase:       fase,
		es_doble:          es_doble,
	}, function(res) {
		if (res.success) {
			scl_drawer_cerrar('scl_llave_drawer');
			sessionStorage.setItem('scl_flash', 'Llave creada. Los partidos están listos.');
			window.location.reload();
		} else {
			scl_flash(res.data || 'Error al crear la llave.', 'error');
			$btn.prop('disabled', false).text('Crear llave y partidos');
		}
	}).fail(function() {
		scl_flash('Error de conexión.', 'error');
		$btn.prop('disabled', false).text('Crear llave y partidos');
	});
}

window.scl_confirmar_ganador = function(llave_id, ganador_id) {
	jQuery.post(scl_ajax.url, {
		action:     'scl_confirmar_ganador_llave',
		nonce:      scl_ajax.nonce,
		llave_id:   llave_id,
		ganador_id: ganador_id,
	}, function(res) {
		if (res.success) {
			sessionStorage.setItem('scl_flash', res.data.ganador + ' ha avanzado.');
			window.location.reload();
		} else {
			scl_flash(res.data || 'Error al confirmar.', 'error');
		}
	});
};

window.scl_confirmar_con_penales = function(llave_id, ganador_id) {
	var pen_a = parseInt(jQuery('#scl_pen_a_' + llave_id).val(), 10);
	var pen_b = parseInt(jQuery('#scl_pen_b_' + llave_id).val(), 10);
	if (isNaN(pen_a) || isNaN(pen_b)) { scl_flash('Ingresa los penales de ambos equipos.', 'error'); return; }
	if (pen_a === pen_b) { scl_flash('Los penales no pueden terminar en empate.', 'error'); return; }
	jQuery.post(scl_ajax.url, {
		action:     'scl_confirmar_ganador_llave',
		nonce:      scl_ajax.nonce,
		llave_id:   llave_id,
		ganador_id: ganador_id,
		penales_a:  pen_a,
		penales_b:  pen_b,
	}, function(res) {
		if (res.success) {
			sessionStorage.setItem('scl_flash', res.data.ganador + ' avanza por penales.');
			window.location.reload();
		} else {
			scl_flash(res.data || 'Error.', 'error');
		}
	});
};

jQuery(document).ready(function($) {

	// Abrir drawer nueva llave
	$(document).on('click', '#scl_nueva_llave_btn, #scl_nueva_llave_btn_empty', function() {
		$('#scl_llave_torneo_id').val('0');
		$('#scl_llave_temporada_term_id').val('0');
		$('#scl_llave_nombre_fase').val('');
		$('#scl_llave_equipo_a_id').val('0');
		$('#scl_llave_equipo_b_id').val('0');
		$('input[name="scl_llave_formato"][value="0"]').prop('checked', true);
		$('#scl_llave_preview').hide();
		scl_drawer_abrir('scl_llave_drawer');
	});

	// Cerrar drawer llave
	$(document).on('click', '#scl_llave_cerrar, #scl_llave_cancelar', function() {
		scl_drawer_cerrar('scl_llave_drawer');
	});

	// Guardar llave
	$(document).on('click', '#scl_llave_guardar', scl_llave_guardar);

	// Preview dinámico al cambiar equipos, formato o fase
	$(document).on('change input',
		'#scl_llave_equipo_a_id, #scl_llave_equipo_b_id, input[name="scl_llave_formato"], #scl_llave_nombre_fase',
		scl_llave_preview_actualizar
	);

	// Filtro de torneo en la página de llaves
	$(document).on('change', '#scl_filtro_llave_torneo', function() {
		var url = new URL(window.location.href);
		var val = $(this).val();
		if (val && val !== '0') { url.searchParams.set('torneo_id', val); }
		else { url.searchParams.delete('torneo_id'); }
		window.location.href = url.toString();
	});

	// Eliminar llave
	$(document).on('click', '.scl-llave-eliminar-btn', function() {
		var id     = $(this).data('id');
		var titulo = $(this).data('titulo');
		if (!confirm('¿Eliminar la llave "' + titulo + '"? Sus partidos también serán eliminados.')) return;
		$.post(scl_ajax.url, {
			action:   'scl_eliminar_llave',
			nonce:    scl_ajax.nonce,
			llave_id: id,
		}, function(res) {
			if (res.success) {
				$('#scl-llave-' + id).fadeOut(300, function() { $(this).remove(); });
				scl_flash('Llave eliminada.');
			} else {
				scl_flash(res.data || 'No se pudo eliminar.', 'error');
			}
		});
	});

});

// ── EXPORTACIÓN ────────────────────────────────────────────────────────────────
(function($) {
	var $tempSelect    = $('#scl_export_temporada');
	var $grupoSelect   = $('#scl_export_grupo');
	var $frame         = $('#scl_export_frame');
	var $btnAbrir      = $('#scl_abrir_export');
	var $btnActualizar = $('#scl_export_actualizar');

	if ( ! $frame.length ) return;

	function scl_build_export_url() {
		var params     = new URLSearchParams(window.location.search);
		var torneo     = params.get('scl_id') || (typeof scl_export_torneo_id !== 'undefined' ? scl_export_torneo_id : 0);
		var temporada  = $tempSelect.length  ? $tempSelect.val()  || 0 : 0;
		var grupo      = $grupoSelect.length ? $grupoSelect.val() || 0 : 0;
		return scl_ajax.url
			+ '?action=scl_get_export_url'
			+ '&nonce='     + encodeURIComponent(scl_ajax.nonce)
			+ '&torneo_id=' + encodeURIComponent(torneo)
			+ '&temporada=' + encodeURIComponent(temporada)
			+ '&grupo='     + encodeURIComponent(grupo);
	}

	function scl_actualizar_preview() {
		$.get(scl_build_export_url(), function(res) {
			if (res.success && res.data && res.data.url) {
				$frame.attr('src', res.data.url);
				$btnAbrir.attr('href', res.data.url);
			}
		});
	}

	$btnActualizar.on('click', scl_actualizar_preview);

	var debounce_timer;
	$tempSelect.add($grupoSelect).on('change', function() {
		clearTimeout(debounce_timer);
		debounce_timer = setTimeout(scl_actualizar_preview, 400);
	});
})(jQuery);
