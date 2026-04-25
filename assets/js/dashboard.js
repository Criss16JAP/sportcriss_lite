/**
 * Scripts para el Dashboard de SportCriss Lite.
 */

jQuery(document).ready(function($) {
	// Utilidad para construir URLs del dashboard
	window.scl_url = function(ruta, id, accion) {
		var url = scl_ajax.base;
		var params = [];
		if (ruta) params.push('scl_ruta=' + encodeURIComponent(ruta));
		if (id) params.push('scl_id=' + encodeURIComponent(id));
		if (accion) params.push('scl_accion=' + encodeURIComponent(accion));
		
		if (params.length > 0) {
			url += '?' + params.join('&');
		}
		return url;
	};

	// Lógica de filtrado de grupos en select de partidos (si existe el selector en la vista)
	var $temporadaSelect = $('#scl_dashboard_partido_temporada_id');
	var $grupoSelect = $('#scl_dashboard_partido_grupo_id');
	
	if ($temporadaSelect.length && $grupoSelect.length) {
		$temporadaSelect.on('change', function() {
			var temporadaId = $(this).val();
			if (!temporadaId) {
				$grupoSelect.html('<option value="0">— Sin grupo —</option>');
				return;
			}

			$.ajax({
				url: scl_ajax.url,
				type: 'POST',
				data: {
					action: 'scl_get_grupos_por_torneo',
					temporada_id: temporadaId,
					nonce: scl_ajax.nonce
				},
				success: function(response) {
					if (response.success && response.data) {
						var html = '<option value="0">— Sin grupo —</option>';
						response.data.forEach(function(grupo) {
							html += '<option value="' + grupo.ID + '">' + grupo.post_title + '</option>';
						});
						$grupoSelect.html(html);
					}
				}
			});
		});
	}
});
