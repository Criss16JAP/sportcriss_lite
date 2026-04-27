/**
 * SportCriss Lite – Importador CSV (Sprint 8)
 *
 * @package SportCrissLite
 * @since   1.0.0
 */
(function ($) {
	'use strict';

	if (!$('#scl-importer-step-1').length) return;

	var $step1       = $('#scl-importer-step-1');
	var $step2       = $('#scl-importer-step-2');
	var $step3       = $('#scl-importer-step-3');
	var $dropzone    = $('#scl-csv-dropzone');
	var $fileInput   = $('#scl-csv-file');
	var hayErroresCriticos = false;

	// ── Drag-and-drop ──────────────────────────────────────────────────────

	$dropzone.on('dragover dragenter', function (e) {
		e.preventDefault();
		$(this).addClass('scl-dropzone--over');
	}).on('dragleave dragend', function () {
		$(this).removeClass('scl-dropzone--over');
	}).on('drop', function (e) {
		e.preventDefault();
		$(this).removeClass('scl-dropzone--over');
		var files = e.originalEvent.dataTransfer.files;
		if (files.length) handleFile(files[0]);
	}).on('click keydown', function (e) {
		if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) return;
		$fileInput.click();
	});

	$fileInput.on('change', function () {
		if (this.files.length) handleFile(this.files[0]);
	});

	// ── Subir y validar ────────────────────────────────────────────────────

	function handleFile(file) {
		var ext = file.name.split('.').pop().toLowerCase();
		if (ext !== 'csv') {
			scl_flash('Solo se aceptan archivos .csv.', 'error');
			return;
		}

		$dropzone.addClass('scl-dropzone--loading')
			.find('.scl-dropzone__text')
			.html('Procesando <em>' + escapeHtml(file.name) + '</em>…');

		var formData = new FormData();
		formData.append('action', 'scl_validar_csv');
		formData.append('nonce',  scl_ajax.nonce);
		formData.append('csv_file', file);

		$.ajax({
			url:         scl_ajax.url,
			type:        'POST',
			data:        formData,
			processData: false,
			contentType: false,
			success: function (res) {
				if (res.success) {
					mostrarValidacion(res.data);
				} else {
					scl_flash(res.data || 'Error al procesar el CSV.', 'error');
					resetDropzone();
				}
			},
			error: function () {
				scl_flash('Error de conexión.', 'error');
				resetDropzone();
			}
		});
	}

	function resetDropzone() {
		$dropzone.removeClass('scl-dropzone--loading scl-dropzone--over');
		$dropzone.find('.scl-dropzone__text').html(
			'Arrastra tu archivo CSV aquí o <span class="scl-link">haz clic para seleccionar</span>'
		);
		$fileInput.val('');
	}

	// ── Mostrar validación (paso 2) ────────────────────────────────────────

	function mostrarValidacion(data) {
		hayErroresCriticos = data.filas_con_error > 0;

		$('#scl-val-total').text(data.total);
		$('#scl-val-validas').text(data.filas_validas);
		$('#scl-val-errores').text(data.filas_con_error);

		// Entidades nuevas
		var $lista = $('#scl-nuevos-lista').empty();
		if (data.equipos_nuevos && data.equipos_nuevos.length) {
			$lista.append(
				'<li><strong>Equipos a crear:</strong> ' +
				escapeHtml(data.equipos_nuevos.join(', ')) + '</li>'
			);
		}
		if (data.temporadas_nuevas && data.temporadas_nuevas.length) {
			$lista.append(
				'<li><strong>Temporadas a crear:</strong> ' +
				escapeHtml(data.temporadas_nuevas.join(', ')) + '</li>'
			);
		}
		if (
			(!data.equipos_nuevos || !data.equipos_nuevos.length) &&
			(!data.temporadas_nuevas || !data.temporadas_nuevas.length)
		) {
			$lista.append('<li>No se crearán entidades nuevas.</li>');
		}

		// Errores
		var $errLista = $('#scl-errores-lista').empty();
		if (data.errores && data.errores.length) {
			$('#scl-errores-bloque').show();
			$.each(data.errores, function (i, err) {
				$errLista.append('<li>' + escapeHtml(err) + '</li>');
			});
		} else {
			$('#scl-errores-bloque').hide();
		}

		// Preview
		var $tbody = $('#scl-preview-tbody').empty();
		if (data.preview && data.preview.length) {
			$.each(data.preview, function (i, fila) {
				var cols = [
					fila.torneo, fila.temporada, fila.tipo_fase, fila.jornada,
					fila.grupo, fila.fecha, fila.equipo_local,
					fila.goles_local, fila.goles_visitante, fila.equipo_visitante, fila.estado
				];
				var tds = cols.map(function (v) {
					return '<td>' + escapeHtml(v != null ? String(v) : '') + '</td>';
				}).join('');
				$tbody.append('<tr>' + tds + '</tr>');
			});
		}

		// Botón importar
		var $btn = $('#scl-importar-btn');
		if (hayErroresCriticos) {
			$btn.text(
				'Importar ' + data.filas_validas + ' partidos válidos'
			).prop('disabled', data.filas_validas === 0);
		} else {
			$btn.text('Importar ' + data.filas_validas + ' partidos').prop('disabled', false);
		}

		$step1.hide();
		$step2.show();
		window.scrollTo(0, 0);
	}

	// ── Volver al paso 1 ───────────────────────────────────────────────────

	$('#scl-cambiar-archivo').on('click', function () {
		$step2.hide();
		$step1.show();
		resetDropzone();
		window.scrollTo(0, 0);
	});

	// ── Importar (paso 3) ──────────────────────────────────────────────────

	$('#scl-importar-btn').on('click', function () {
		if ($(this).prop('disabled')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).text('Importando…');

		$.post(scl_ajax.url, {
			action: 'scl_procesar_importacion',
			nonce:  scl_ajax.nonce,
		}, function (res) {
			if (res.success) {
				mostrarResultado(res.data);
			} else {
				scl_flash(res.data || 'Error al importar.', 'error');
				$btn.prop('disabled', false).text('Reintentar');
			}
		}).fail(function () {
			scl_flash('Error de conexión.', 'error');
			$btn.prop('disabled', false).text('Reintentar');
		});
	});

	function mostrarResultado(data) {
		$('#scl-res-creados').text(data.creados);
		$('#scl-res-omitidos').text(data.omitidos);

		var $errLista = $('#scl-res-errores-lista').empty();
		if (data.errores && data.errores.length) {
			$('#scl-res-errores-bloque').show();
			$.each(data.errores, function (i, err) {
				$errLista.append('<li>' + escapeHtml(err) + '</li>');
			});
		} else {
			$('#scl-res-errores-bloque').hide();
		}

		$step2.hide();
		$step3.show();
		window.scrollTo(0, 0);
	}

	// ── Importar otro CSV ──────────────────────────────────────────────────

	$('#scl-otro-csv').on('click', function () {
		$step3.hide();
		$step1.show();
		resetDropzone();
		window.scrollTo(0, 0);
	});

	// ── Utilidades ─────────────────────────────────────────────────────────

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

})(jQuery);
