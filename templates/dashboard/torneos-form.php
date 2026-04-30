<?php
/**
 * Formulario de Crear / Editar Torneo
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$es_editar = ( isset( $_GET['scl_accion'] ) && $_GET['scl_accion'] === 'editar' );
$torneo_id = 0;
$nombre = '';
$siglas = '';
$logo_id = 0;
$victoria = 3;
$empate = 1;
$derrota = 0;
$desempate_orden = '["diferencia_goles","goles_favor","goles_contra","enfrentamiento_directo"]';
$color_primario = '#1a3a5c';
$color_secundario = '#f5a623';
$fondo_id = 0;

if ( $es_editar ) {
	$torneo_id = absint( $_GET['scl_id'] ?? 0 );
	$post = get_post( $torneo_id );

	if ( ! $post || 'scl_torneo' !== $post->post_type
		|| ( (int) $post->post_author !== scl_get_autor_efectivo() && ! current_user_can( 'manage_options' ) ) ) {
		echo '<div class="scl-flash scl-flash--error">Torneo no válido o sin permisos.</div>';
		return;
	}

	$nombre = $post->post_title;
	$siglas = get_post_meta( $torneo_id, 'scl_torneo_siglas', true );
	$logo_id = absint( get_post_meta( $torneo_id, 'scl_torneo_logo', true ) );
	$victoria = get_post_meta( $torneo_id, 'scl_torneo_puntos_victoria', true );
	if ( $victoria === '' ) $victoria = 3;
	$empate = get_post_meta( $torneo_id, 'scl_torneo_puntos_empate', true );
	if ( $empate === '' ) $empate = 1;
	$derrota = get_post_meta( $torneo_id, 'scl_torneo_puntos_derrota', true );
	if ( $derrota === '' ) $derrota = 0;

	$orden_guardado = get_post_meta( $torneo_id, 'scl_torneo_desempate_orden', true );
	if ( $orden_guardado ) {
		$desempate_orden = $orden_guardado;
	}

	$c_prim = get_post_meta( $torneo_id, 'scl_torneo_color_primario', true );
	if ( $c_prim ) $color_primario = $c_prim;

	$c_sec = get_post_meta( $torneo_id, 'scl_torneo_color_secundario', true );
	if ( $c_sec ) $color_secundario = $c_sec;

	$fondo_id = absint( get_post_meta( $torneo_id, 'scl_torneo_fondo', true ) );
}

$criterios = [
	'diferencia_goles'       => 'Diferencia de goles',
	'goles_favor'            => 'Goles a favor',
	'goles_contra'           => 'Goles en contra (menor es mejor)',
	'enfrentamiento_directo' => 'Enfrentamiento directo',
];

$orden_arr = json_decode( $desempate_orden, true );
if ( ! is_array( $orden_arr ) ) $orden_arr = array_keys( $criterios );

$logo_url  = $logo_id  ? ( wp_get_attachment_image_url( $logo_id,  'thumbnail' ) ?: '' ) : '';
$fondo_url = $fondo_id ? ( wp_get_attachment_image_url( $fondo_id, 'thumbnail' ) ?: '' ) : '';
?>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php echo $es_editar ? 'Editar Torneo: ' . esc_html( $nombre ) : 'Nuevo Torneo'; ?></h1>
</div>

<input type="hidden" id="scl_torneo_id_editar" value="<?php echo esc_attr( $torneo_id ); ?>">

<div class="scl-form-container">

	<!-- Sección 1: Identidad -->
	<div class="scl-form-section">
		<h3>Identidad</h3>
		<div class="scl-field-row">
			<div class="scl-field" style="flex:2">
				<label>Nombre del torneo *</label>
				<input type="text" id="scl_nombre" maxlength="100" required
				       value="<?php echo esc_attr( $nombre ); ?>"
				       placeholder="Ej: Torneo Las Colinas">
			</div>
			<div class="scl-field" style="flex:1">
				<label>Siglas * <span class="scl-hint">(máx. 6)</span></label>
				<input type="text" id="scl_siglas" maxlength="6"
				       value="<?php echo esc_attr( $siglas ); ?>"
				       placeholder="TLC" style="text-transform:uppercase">
			</div>
		</div>

		<!-- Logo -->
		<div class="scl-field" style="max-width:360px">
			<label>Logo del torneo</label>
			<div class="scl-file-uploader" id="scl_logo_dropzone">
				<div id="scl_logo_content">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>"
						     class="scl-file-uploader__preview" alt="Logo">
						<p class="scl-file-uploader__text"><small>Haz clic para cambiar</small></p>
					<?php else : ?>
						<div class="scl-file-uploader__icon">&#128444;</div>
						<p class="scl-file-uploader__text">
							Haz clic o arrastra tu logo aquí<br>
							<small>JPG, PNG, WebP o SVG &middot; Máx. 5MB</small>
						</p>
					<?php endif; ?>
				</div>
				<div class="scl-file-uploader__progress">
					<div class="scl-file-uploader__progress-bar" id="scl_logo_progress"></div>
				</div>
			</div>
			<input type="file" id="scl_logo_file" accept="image/jpeg,image/png,image/webp,image/svg+xml" style="display:none">
			<input type="hidden" id="scl_logo_id" value="<?php echo esc_attr( $logo_id ); ?>">
		</div>
	</div>

	<!-- Sección 2: Sistema de puntos -->
	<div class="scl-form-section">
		<h3>Sistema de puntos</h3>
		<div class="scl-field-row">
			<div class="scl-field">
				<label>Victoria</label>
				<input type="number" id="scl_victoria" value="<?php echo esc_attr( $victoria ); ?>" min="0" max="99">
			</div>
			<div class="scl-field">
				<label>Empate</label>
				<input type="number" id="scl_empate" value="<?php echo esc_attr( $empate ); ?>" min="0" max="99">
			</div>
			<div class="scl-field">
				<label>Derrota</label>
				<input type="number" id="scl_derrota" value="<?php echo esc_attr( $derrota ); ?>" min="0" max="99">
			</div>
		</div>
	</div>

	<!-- Sección 3: Criterios de desempate -->
	<div class="scl-form-section">
		<h3>Criterios de desempate</h3>
		<p class="scl-description">
			<strong>Criterio principal (siempre):</strong> Puntos acumulados.<br>
			Los criterios de abajo se aplican para desempatar equipos con los mismos puntos.
			Arrastra para definir el orden de prioridad.
		</p>
		<ul class="scl-sortable" id="scl_desempate_lista">
			<li class="scl-sortable__item scl-sortable__item--locked">
				&#128274; 1. Puntos (criterio principal)
			</li>
			<?php foreach ( $orden_arr as $crit ) :
				if ( ! isset( $criterios[ $crit ] ) ) continue;
			?>
				<li class="scl-sortable__item" data-value="<?php echo esc_attr( $crit ); ?>">
					<span class="scl-drag-handle">&#10783;</span>
					<span><?php echo esc_html( $criterios[ $crit ] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<input type="hidden" id="scl_desempate_orden" value="<?php echo esc_attr( $desempate_orden ); ?>">
	</div>

	<!-- Sección 4: Personalización visual -->
	<div class="scl-form-section">
		<h3>Personalización visual <span class="scl-hint">(para exportación de tabla)</span></h3>
		<div class="scl-field-row" style="max-width:480px">
			<div class="scl-field">
				<label>Color primario</label>
				<div class="scl-color-field"
				     onclick="document.getElementById('scl_color_primario').click()">
					<input type="color" id="scl_color_primario"
					       value="<?php echo esc_attr( $color_primario ); ?>"
					       oninput="scl_sync_color('primario', this.value)">
					<div class="scl-color-field__preview" id="scl_preview_primario"
					     style="background:<?php echo esc_attr( $color_primario ); ?>"></div>
					<span class="scl-color-field__hex" id="scl_hex_primario">
						<?php echo esc_html( $color_primario ); ?>
					</span>
				</div>
			</div>
			<div class="scl-field">
				<label>Color secundario</label>
				<div class="scl-color-field"
				     onclick="document.getElementById('scl_color_secundario').click()">
					<input type="color" id="scl_color_secundario"
					       value="<?php echo esc_attr( $color_secundario ); ?>"
					       oninput="scl_sync_color('secundario', this.value)">
					<div class="scl-color-field__preview" id="scl_preview_secundario"
					     style="background:<?php echo esc_attr( $color_secundario ); ?>"></div>
					<span class="scl-color-field__hex" id="scl_hex_secundario">
						<?php echo esc_html( $color_secundario ); ?>
					</span>
				</div>
			</div>
		</div>

		<!-- Fondo -->
		<div class="scl-field" style="max-width:360px; margin-top:1rem">
			<label>Imagen de fondo</label>
			<div class="scl-file-uploader" id="scl_fondo_dropzone">
				<div id="scl_fondo_content">
					<?php if ( $fondo_url ) : ?>
						<img src="<?php echo esc_url( $fondo_url ); ?>"
						     class="scl-file-uploader__preview" alt="Fondo">
						<p class="scl-file-uploader__text"><small>Haz clic para cambiar</small></p>
					<?php else : ?>
						<div class="scl-file-uploader__icon">&#128247;</div>
						<p class="scl-file-uploader__text">
							Haz clic o arrastra tu imagen de fondo<br>
							<small>JPG, PNG o WebP &middot; Máx. 5MB</small>
						</p>
					<?php endif; ?>
				</div>
				<div class="scl-file-uploader__progress">
					<div class="scl-file-uploader__progress-bar" id="scl_fondo_progress"></div>
				</div>
			</div>
			<input type="file" id="scl_fondo_file" accept="image/jpeg,image/png,image/webp" style="display:none">
			<input type="hidden" id="scl_fondo_id" value="<?php echo esc_attr( $fondo_id ); ?>">
		</div>
	</div>

	<div class="scl-form-actions">
		<a href="?scl_ruta=torneos" class="scl-btn scl-btn--ghost">Cancelar</a>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_guardar_torneo">
			Guardar torneo
		</button>
	</div>

</div><!-- /.scl-form-container -->
