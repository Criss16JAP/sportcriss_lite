/**
 * Estadísticas individuales de partido — dashboard.
 * Requiere: jquery, scl-dashboard-js (scl_ajax.url, scl_ajax.nonce)
 */
(function ($) {
	'use strict';

	var partidoId    = parseInt($('#scl_stats_partido_id').val()) || 0;
	var llave_id     = parseInt($('#scl_stats_llave_id').val())   || 0;
	var golesLocalM  = parseInt($('#scl_stats_goles_local_marcador').val())  || 0;
	var golesVisitaM = parseInt($('#scl_stats_goles_visita_marcador').val()) || 0;
	var localId      = parseInt($('#scl_stats_local_id').val())  || 0;
	var visitaId     = parseInt($('#scl_stats_visita_id').val()) || 0;

	// -----------------------------------------------------------------------
	// Actualizar totalizadores de goles en tiempo real
	// -----------------------------------------------------------------------
	function actualizarTotalesGoles() {
		var sumLocal  = 0;
		var sumVisita = 0;

		$('.scl-stats-row').each(function () {
			var equipoId = parseInt($(this).data('equipo-id'));
			var goles    = parseInt($(this).find('.scl-stats-goles').val()) || 0;

			if (equipoId === localId)  sumLocal  += goles;
			if (equipoId === visitaId) sumVisita += goles;
		});

		$('#scl_sum_goles_local').text(sumLocal);
		$('#scl_sum_goles_visita').text(sumVisita);

		// Visual feedback: verde = coincide, rojo = no coincide
		$('#scl_total_goles_local').css('color',  sumLocal  === golesLocalM  ? '#2ecc71' : '#e74c3c');
		$('#scl_total_goles_visita').css('color', sumVisita === golesVisitaM ? '#2ecc71' : '#e74c3c');
	}

	$(document).on('input', '.scl-stats-goles', actualizarTotalesGoles);

	// Inicializar al cargar
	actualizarTotalesGoles();

	// -----------------------------------------------------------------------
	// Guardar estadísticas
	// -----------------------------------------------------------------------
	$('#scl_stats_guardar').on('click', function () {
		var $btn = $(this);
		var $msg = $('#scl_stats_msg');

		$btn.prop('disabled', true).text('Guardando...');
		$msg.html('');

		var stats = [];

		$('.scl-stats-row').each(function () {
			var $row        = $(this);
			var jugadorId   = parseInt($row.data('jugador-id')) || 0;
			var equipoId    = parseInt($row.data('equipo-id'))  || 0;
			var insc_id     = parseInt($row.data('inscripcion-id')) || 0;

			if (!jugadorId) return;

			stats.push({
				jugador_id:      jugadorId,
				equipo_id:       equipoId,
				inscripcion_id:  insc_id,
				goles:           parseInt($row.find('.scl-stats-goles').val())        || 0,
				asistencias:     parseInt($row.find('.scl-stats-asistencias').val())  || 0,
				tarjeta_amarilla: $row.find('.scl-stats-amarilla').is(':checked') ? 1 : 0,
				tarjeta_roja:    $row.find('.scl-stats-roja').is(':checked')      ? 1 : 0,
				calificacion:    $row.find('.scl-stats-calificacion').val() || '',
				es_penales:      0,
			});
		});

		var data = {
			action:     'scl_guardar_estadisticas_partido',
			nonce:      scl_ajax.nonce,
			partido_id: partidoId,
			stats:      stats,
		};

		if (llave_id) {
			data.llave_id       = llave_id;
			data.penales_local  = parseInt($('#scl_penales_local').val())  || 0;
			data.penales_visita = parseInt($('#scl_penales_visita').val()) || 0;
		}

		$.post(scl_ajax.url, data, function (resp) {
			if (resp.success) {
				$msg.html('<span style="color:#2ecc71">&#10003; Estadísticas guardadas (' + resp.data.guardados + ' registros).</span>');

				// Actualizar advertencia de validación
				var vl = resp.data.val_local;
				var vv = resp.data.val_visita;
				if (vl && vv && vl.ok && vv.ok) {
					$('#scl_stats_advertencia').remove();
				}
			} else {
				var msg = resp.data && typeof resp.data === 'string' ? resp.data : 'Error al guardar.';
				$msg.html('<span style="color:#e74c3c">&#10008; ' + msg + '</span>');
			}
		}).always(function () {
			$btn.prop('disabled', false).text('Guardar estadísticas');
		});
	});

	// -----------------------------------------------------------------------
	// Jugadores lista: drawer de creación/edición (si la ruta es jugadores)
	// -----------------------------------------------------------------------
	if ($('#scl_jugador_form').length) {
		scl_init_jugadores_crud();
	}

	function scl_init_jugadores_crud() {
		// Uploader de foto
		var $uploader = $('#scl_jugador_foto_uploader');
		var $fileInput = $('#scl_jugador_foto_file');
		var $preview   = $('#scl_jugador_foto_preview');
		var $fotoId    = $('#scl_jugador_foto_id');

		$uploader.on('click', function () { $fileInput.trigger('click'); });

		$fileInput.on('change', function () {
			var file = this.files[0];
			if (!file) return;
			if (file.size > 5 * 1024 * 1024) {
				alert('La imagen no puede superar 5MB.');
				return;
			}

			$preview.html('<div class="scl-upload-loading">&#9203; Subiendo imagen...</div>');

			var fd = new FormData();
			fd.append('action', 'scl_subir_foto_jugador');
			fd.append('nonce',  scl_ajax.nonce);
			fd.append('foto',   file);

			$.ajax({
				url:         scl_ajax.url,
				type:        'POST',
				data:        fd,
				processData: false,
				contentType: false,
			}).done(function (resp) {
				if (resp.success) {
					$fotoId.val(resp.data.attachment_id);
					$preview.html('<img src="' + resp.data.url + '" style="max-width:100%;border-radius:4px">');
				} else {
					$preview.html('<div class="scl-upload-loading">&#10060; Error al subir.</div>');
				}
			});
		});

		// Abrir drawer vacío
		$('#scl_nuevo_jugador_btn').on('click', function () {
			$('#scl_jugador_id').val(0);
			$('#scl_jugador_nombre').val('');
			$('#scl_jugador_posicion').val('');
			$('#scl_jugador_documento').val('');
			$fotoId.val(0);
			$preview.html('<span class="scl-upload-icon">&#128247;</span><p>Click para subir (máx. 5MB)</p>');
			$('#scl_jugador_form').slideDown(200);
		});

		// Abrir drawer con datos de edición
		$(document).on('click', '.scl_editar_jugador_btn', function () {
			var $btn = $(this);
			$('#scl_jugador_id').val($btn.data('id'));
			$('#scl_jugador_nombre').val($btn.data('nombre'));
			$('#scl_jugador_posicion').val($btn.data('posicion'));
			$('#scl_jugador_documento').val($btn.data('documento'));
			$fotoId.val($btn.data('foto-id') || 0);
			if ($btn.data('foto-url')) {
				$preview.html('<img src="' + $btn.data('foto-url') + '" style="max-width:100%;border-radius:4px">');
			} else {
				$preview.html('<span class="scl-upload-icon">&#128247;</span><p>Click para subir (máx. 5MB)</p>');
			}
			$('#scl_jugador_form').slideDown(200);
			$('html, body').animate({ scrollTop: $('#scl_jugador_form').offset().top - 80 }, 300);
		});

		// Cancelar
		$('#scl_jugador_cancelar').on('click', function () {
			$('#scl_jugador_form').slideUp(200);
		});

		// Guardar
		$('#scl_jugador_guardar').on('click', function () {
			var jugadorId = parseInt($('#scl_jugador_id').val()) || 0;
			var nombre    = $.trim($('#scl_jugador_nombre').val());

			if (!nombre) {
				alert('El nombre es obligatorio.');
				return;
			}

			var action = jugadorId ? 'scl_editar_jugador' : 'scl_crear_jugador';
			var data   = {
				action:      action,
				nonce:       scl_ajax.nonce,
				nombre:      nombre,
				posicion:    $.trim($('#scl_jugador_posicion').val()),
				documento:   $.trim($('#scl_jugador_documento').val()),
				foto_id:     $fotoId.val() || 0,
			};
			if (jugadorId) data.jugador_id = jugadorId;

			$(this).prop('disabled', true).text('Guardando...');

			$.post(scl_ajax.url, data, function (resp) {
				if (resp.success) {
					// Recargar la página para reflejar cambios
					location.reload();
				} else {
					alert(resp.data || 'Error al guardar.');
				}
			}).always(function () {
				$('#scl_jugador_guardar').prop('disabled', false).text('Guardar');
			});
		});

		// Eliminar
		$(document).on('click', '.scl_eliminar_jugador_btn', function () {
			var id     = $(this).data('id');
			var nombre = $(this).data('nombre');

			if (!confirm('¿Eliminar al jugador "' + nombre + '"? Esta acción no se puede deshacer.')) return;

			$.post(scl_ajax.url, {
				action:     'scl_eliminar_jugador',
				nonce:      scl_ajax.nonce,
				jugador_id: id,
			}, function (resp) {
				if (resp.success) {
					$('#scl_jugador_item_' + id).fadeOut(300, function () { $(this).remove(); });
				} else {
					alert(resp.data || 'Error al eliminar.');
				}
			});
		});
	}

	// -----------------------------------------------------------------------
	// Inscripciones: botón inscribir y retirar
	// -----------------------------------------------------------------------
	$('#scl_inscribir_btn').on('click', function () {
		var equipoId   = $('#scl_insc_equipo_id').val();
		var jugadorId  = $('#scl_insc_jugador_id').val();
		var torneoId   = $('#scl_insc_torneo_id').val();
		var tempId     = $('#scl_insc_temporada_id').val() || 0;
		var $msg       = $('#scl_insc_msg');

		if (!equipoId || !jugadorId) {
			$msg.html('<span style="color:#e74c3c">Selecciona equipo y jugador.</span>');
			return;
		}

		$(this).prop('disabled', true);

		$.post(scl_ajax.url, {
			action:            'scl_inscribir_jugador',
			nonce:             scl_ajax.nonce,
			equipo_id:         equipoId,
			jugador_id:        jugadorId,
			torneo_id:         torneoId,
			temporada_term_id: tempId,
		}, function (resp) {
			if (resp.success) {
				$msg.html('<span style="color:#2ecc71">&#10003; ' + resp.data.jugador_nombre + ' inscrito correctamente.</span>');
				setTimeout(function () { location.reload(); }, 1000);
			} else {
				$msg.html('<span style="color:#e74c3c">&#10008; ' + (resp.data || 'Error.') + '</span>');
			}
		}).always(function () {
			$('#scl_inscribir_btn').prop('disabled', false);
		});
	});

	$(document).on('click', '.scl_desinscribir_btn', function () {
		var id     = $(this).data('id');
		var nombre = $(this).data('nombre');

		if (!confirm('¿Retirar a "' + nombre + '" de este torneo?')) return;

		$.post(scl_ajax.url, {
			action:         'scl_desinscribir_jugador',
			nonce:          scl_ajax.nonce,
			inscripcion_id: id,
		}, function (resp) {
			if (resp.success) {
				$('#scl_insc_item_' + id).fadeOut(300, function () { $(this).remove(); });
			} else {
				alert(resp.data || 'Error al retirar.');
			}
		});
	});

})(jQuery);
