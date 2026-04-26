<?php
/**
 * Meta boxes nativos para todos los CPTs del plugin.
 *
 * Usa exclusivamente la API nativa de WordPress: add_meta_box, save_post,
 * wp_nonce_field, wp_verify_nonce. Sin ACF ni librerías externas.
 *
 * Convenciones de guardado:
 *   - Siempre verificar nonce + autosave + current_user_can antes de guardar.
 *   - Sanitizar según tipo: absint, sanitize_text_field, sanitize_hex_color...
 *   - Goles cuando estado = pendiente → guardar '' (string vacío, no 0 ni null).
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Meta_Boxes
 */
class Scl_Meta_Boxes {

	private static bool $generando_titulo = false;

	// -----------------------------------------------------------------------
	// Registro de meta boxes
	// -----------------------------------------------------------------------

	/**
	 * Registra todos los meta boxes en las pantallas de edición de wp-admin.
	 * Callback del hook 'add_meta_boxes'.
	 */
	public function registrar() {
		// scl_equipo
		add_meta_box(
			'scl_equipo_datos',
			__( 'Datos del equipo', 'sportcriss-lite' ),
			[ $this, 'render_equipo' ],
			'scl_equipo',
			'normal',
			'high'
		);

		// scl_torneo
		add_meta_box(
			'scl_torneo_identidad',
			__( 'Identidad del torneo', 'sportcriss-lite' ),
			[ $this, 'render_torneo_identidad' ],
			'scl_torneo',
			'normal',
			'high'
		);
		add_meta_box(
			'scl_torneo_puntos',
			__( 'Sistema de puntos', 'sportcriss-lite' ),
			[ $this, 'render_torneo_puntos' ],
			'scl_torneo',
			'normal',
			'high'
		);
		add_meta_box(
			'scl_torneo_desempate',
			__( 'Criterios de desempate', 'sportcriss-lite' ),
			[ $this, 'render_torneo_desempate' ],
			'scl_torneo',
			'normal',
			'default'
		);
		add_meta_box(
			'scl_torneo_visual',
			__( 'Personalización visual', 'sportcriss-lite' ),
			[ $this, 'render_torneo_visual' ],
			'scl_torneo',
			'normal',
			'default'
		);



		// scl_partido
		add_meta_box(
			'scl_partido_datos',
			__( 'Datos del partido', 'sportcriss-lite' ),
			[ $this, 'render_partido_datos' ],
			'scl_partido',
			'normal',
			'high'
		);
		add_meta_box(
			'scl_partido_resultado',
			__( 'Equipos y resultado', 'sportcriss-lite' ),
			[ $this, 'render_partido_resultado' ],
			'scl_partido',
			'normal',
			'high'
		);
		add_meta_box(
			'scl_partido_clasificacion',
			__( 'Clasificación', 'sportcriss-lite' ),
			[ $this, 'render_partido_clasificacion' ],
			'scl_partido',
			'side',
			'default'
		);

		// scl_llave
		add_meta_box(
			'scl_llave_datos',
			__( 'Datos de la llave', 'sportcriss-lite' ),
			[ $this, 'render_llave' ],
			'scl_llave',
			'normal',
			'high'
		);

		// Remover term meta box nativo de scl_temporada para evitar duplicados en la edición
		add_action( 'add_meta_boxes', function() {
			remove_meta_box( 'tagsdiv-scl_temporada', 'scl_partido', 'side' );
			remove_meta_box( 'tagsdiv-scl_temporada', 'scl_partido', 'normal' );
		}, 99 );

		// Cargar scripts nativos para el widget de tags de scl_jornada
		add_action( 'admin_enqueue_scripts', function( $hook ) {
			if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
			$screen = get_current_screen();
			if ( ! $screen || $screen->post_type !== 'scl_partido' ) return;

			wp_enqueue_script( 'post' );
			wp_enqueue_script( 'tags-box' );
			wp_enqueue_style( 'tags-box' );
		} );

		// Term meta boxes para scl_temporada
		add_action( 'scl_temporada_add_form_fields',  [ $this, 'temporada_add_fields' ] );
		add_action( 'scl_temporada_edit_form_fields', [ $this, 'temporada_edit_fields' ] );
		add_action( 'created_scl_temporada',          [ $this, 'temporada_save_fields' ] );
		add_action( 'edited_scl_temporada',           [ $this, 'temporada_save_fields' ] );

		// scl_grupo
		add_meta_box(
			'scl_grupo_datos',
			__( 'Datos del grupo', 'sportcriss-lite' ),
			[ $this, 'render_grupo' ],
			'scl_grupo',
			'normal',
			'high'
		);
	}

	// -----------------------------------------------------------------------
	// Render: scl_equipo
	// -----------------------------------------------------------------------

	/**
	 * Renderiza el meta box "Datos del equipo".
	 *
	 * @param WP_Post $post
	 */
	public function render_equipo( $post ) {
		wp_nonce_field( 'scl_guardar_equipo', 'scl_equipo_nonce' );

		$escudo      = absint( get_post_meta( $post->ID, 'scl_equipo_escudo', true ) );
		$zona        = get_post_meta( $post->ID, 'scl_equipo_zona', true );
		$incompleto  = get_post_meta( $post->ID, 'scl_equipo_incompleto', true );

		$imagen_url  = $escudo ? wp_get_attachment_image_url( $escudo, 'thumbnail' ) : '';
		?>
		<?php if ( $incompleto ) : ?>
			<div style="background:#fff3cd;border:1px solid #ffc107;padding:10px 14px;margin-bottom:12px;border-radius:4px;">
				&#9888; <?php esc_html_e( 'Este equipo fue creado por el importador y le faltan datos.', 'sportcriss-lite' ); ?>
			</div>
		<?php endif; ?>

		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Escudo del equipo', 'sportcriss-lite' ); ?></label></th>
				<td>
					<div id="scl-escudo-preview" style="margin-bottom:8px;">
						<?php if ( $imagen_url ) : ?>
							<img src="<?php echo esc_url( $imagen_url ); ?>" style="max-width:100px;max-height:100px;display:block;">
						<?php endif; ?>
					</div>
					<input type="hidden" id="scl_equipo_escudo" name="scl_equipo_escudo"
						value="<?php echo esc_attr( $escudo ?: '' ); ?>">
					<button type="button" class="button" id="scl-btn-escudo">
						<?php esc_html_e( 'Seleccionar escudo', 'sportcriss-lite' ); ?>
					</button>
					<?php if ( $escudo ) : ?>
						<button type="button" class="button" id="scl-btn-escudo-remove">
							<?php esc_html_e( 'Quitar', 'sportcriss-lite' ); ?>
						</button>
					<?php endif; ?>
					<?php $this->render_media_uploader_script(
						'scl-btn-escudo',
						'scl_equipo_escudo',
						'scl-escudo-preview',
						__( 'Seleccionar escudo del equipo', 'sportcriss-lite' ),
						'scl-btn-escudo-remove'
					); ?>
				</td>
			</tr>
			<tr>
				<th><label for="scl_equipo_zona"><?php esc_html_e( 'Zona / Región', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="text" id="scl_equipo_zona" name="scl_equipo_zona"
						value="<?php echo esc_attr( $zona ); ?>"
						class="regular-text">
				</td>
			</tr>
		</table>
		<?php
	}

	// -----------------------------------------------------------------------
	// Render: scl_torneo
	// -----------------------------------------------------------------------

	/**
	 * Renderiza el meta box "Identidad del torneo".
	 *
	 * @param WP_Post $post
	 */
	public function render_torneo_identidad( $post ) {
		wp_nonce_field( 'scl_guardar_torneo', 'scl_torneo_nonce' );

		$siglas     = get_post_meta( $post->ID, 'scl_torneo_siglas', true );
		$logo       = absint( get_post_meta( $post->ID, 'scl_torneo_logo', true ) );
		$imagen_url = $logo ? wp_get_attachment_image_url( $logo, 'thumbnail' ) : '';
		?>
		<table class="form-table">
			<tr>
				<th><label for="scl_torneo_siglas"><?php esc_html_e( 'Siglas del torneo', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="text" id="scl_torneo_siglas" name="scl_torneo_siglas"
						value="<?php echo esc_attr( $siglas ); ?>"
						maxlength="6"
						placeholder="<?php esc_attr_e( 'Ej: TLC, LRN, CPA', 'sportcriss-lite' ); ?>"
						class="small-text"
						style="text-transform:uppercase;">
					<p class="description"><?php esc_html_e( 'Máximo 6 caracteres. Se usará en el título automático de partidos.', 'sportcriss-lite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Logo del torneo', 'sportcriss-lite' ); ?></label></th>
				<td>
					<div id="scl-logo-preview" style="margin-bottom:8px;">
						<?php if ( $imagen_url ) : ?>
							<img src="<?php echo esc_url( $imagen_url ); ?>" style="max-width:120px;max-height:120px;display:block;">
						<?php endif; ?>
					</div>
					<input type="hidden" id="scl_torneo_logo" name="scl_torneo_logo"
						value="<?php echo esc_attr( $logo ?: '' ); ?>">
					<button type="button" class="button" id="scl-btn-logo">
						<?php esc_html_e( 'Seleccionar logo', 'sportcriss-lite' ); ?>
					</button>
					<?php if ( $logo ) : ?>
						<button type="button" class="button" id="scl-btn-logo-remove">
							<?php esc_html_e( 'Quitar', 'sportcriss-lite' ); ?>
						</button>
					<?php endif; ?>
					<?php $this->render_media_uploader_script(
						'scl-btn-logo',
						'scl_torneo_logo',
						'scl-logo-preview',
						__( 'Seleccionar logo del torneo', 'sportcriss-lite' ),
						'scl-btn-logo-remove'
					); ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Renderiza el meta box "Sistema de puntos".
	 *
	 * @param WP_Post $post
	 */
	public function render_torneo_puntos( $post ) {
		// El nonce ya fue registrado en render_torneo_identidad (mismo post).
		// Solo se necesita uno por formulario de edición.
		$victoria = get_post_meta( $post->ID, 'scl_torneo_puntos_victoria', true );
		$empate   = get_post_meta( $post->ID, 'scl_torneo_puntos_empate',   true );
		$derrota  = get_post_meta( $post->ID, 'scl_torneo_puntos_derrota',  true );

		// Defaults cuando el meta aún no existe
		$victoria = ( '' === $victoria ) ? 3 : absint( $victoria );
		$empate   = ( '' === $empate )   ? 1 : absint( $empate );
		$derrota  = ( '' === $derrota )  ? 0 : absint( $derrota );
		?>
		<table class="form-table">
			<tr>
				<th><label for="scl_torneo_puntos_victoria"><?php esc_html_e( 'Victoria', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="number" id="scl_torneo_puntos_victoria" name="scl_torneo_puntos_victoria"
						value="<?php echo esc_attr( $victoria ); ?>" min="0" class="small-text">
					<?php esc_html_e( 'puntos', 'sportcriss-lite' ); ?>
				</td>
			</tr>
			<tr>
				<th><label for="scl_torneo_puntos_empate"><?php esc_html_e( 'Empate', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="number" id="scl_torneo_puntos_empate" name="scl_torneo_puntos_empate"
						value="<?php echo esc_attr( $empate ); ?>" min="0" class="small-text">
					<?php esc_html_e( 'puntos', 'sportcriss-lite' ); ?>
				</td>
			</tr>
			<tr>
				<th><label for="scl_torneo_puntos_derrota"><?php esc_html_e( 'Derrota', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="number" id="scl_torneo_puntos_derrota" name="scl_torneo_puntos_derrota"
						value="<?php echo esc_attr( $derrota ); ?>" min="0" class="small-text">
					<?php esc_html_e( 'puntos', 'sportcriss-lite' ); ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Renderiza el meta box "Criterios de desempate" con drag & drop.
	 *
	 * @param WP_Post $post
	 */
	public function render_torneo_desempate( $post ) {
		$json_guardado = get_post_meta( $post->ID, 'scl_torneo_desempate_orden', true );
		$orden = $json_guardado ? json_decode( $json_guardado, true ) : [];

		// Todos los criterios posibles con sus etiquetas en español
		$criterios = [
			'diferencia_goles'      => __( 'Diferencia de goles', 'sportcriss-lite' ),
			'goles_favor'           => __( 'Goles a favor', 'sportcriss-lite' ),
			'goles_contra'          => __( 'Goles en contra (menor es mejor)', 'sportcriss-lite' ),
			'enfrentamiento_directo' => __( 'Enfrentamiento directo', 'sportcriss-lite' ),
		];

		// Si no hay orden guardado, usar el orden por defecto
		if ( empty( $orden ) ) {
			$orden = array_keys( $criterios );
		}

		// Encolar jQuery UI Sortable (ya viene con WordPress)
		wp_enqueue_script( 'jquery-ui-sortable' );
		?>
		<p class="description" style="margin-bottom:12px;">
			<strong><?php esc_html_e( 'Criterio principal (siempre):', 'sportcriss-lite' ); ?></strong>
			<?php esc_html_e( 'Puntos acumulados.', 'sportcriss-lite' ); ?><br>
			<?php esc_html_e( 'Los criterios de abajo se aplican únicamente para desempatar equipos que terminen con el mismo número de puntos. Arrastra para definir el orden de prioridad.', 'sportcriss-lite' ); ?>
		</p>
		<ul id="scl-desempate-lista" style="margin:12px 0;padding:0;">
			<li style="padding:8px 12px;background:#f0f0f0;color:#999;border:1px dashed #ccc;margin-bottom:4px;cursor:default;list-style:none;">
				&#128274; 1. <?php esc_html_e( 'Puntos (criterio principal — no modificable)', 'sportcriss-lite' ); ?>
			</li>
			<?php
			$posicion = 2;
			foreach ( $orden as $clave ) :
				if ( ! isset( $criterios[ $clave ] ) ) continue; ?>
				<li data-criterio="<?php echo esc_attr( $clave ); ?>"
					style="background:#f9f9f9;border:1px solid #ddd;padding:8px 12px;margin-bottom:4px;border-radius:3px;list-style:none;display:flex;align-items:center;gap:8px;cursor:move;">
					<span style="color:#999;cursor:grab;">&#9776;</span>
					<?php echo esc_html( $posicion . '. ' . $criterios[ $clave ] ); ?>
				</li>
			<?php $posicion++; endforeach; ?>
		</ul>
		<input type="hidden" id="scl_torneo_desempate_orden" name="scl_torneo_desempate_orden"
			value="<?php echo esc_attr( $json_guardado ?: wp_json_encode( $orden ) ); ?>">

		<script>
		jQuery(function($) {
			var $lista = $('#scl-desempate-lista');
			var $input = $('#scl_torneo_desempate_orden');

			function actualizarInput() {
				var orden = [];
				// El primer <li> es el ítem fijo de Puntos (sin data-criterio), lo saltamos
				$lista.find('li[data-criterio]').each(function(i) {
					orden.push( $(this).data('criterio') );
					// Renumerar el label visual (posición 2 en adelante)
					var $span = $(this).find('span');
					var textoOriginal = $(this).text().trim().replace(/^\d+\.\s*/, '');
					$(this).contents().filter(function() {
						return this.nodeType === 3; // nodo de texto
					}).last().replaceWith( ' ' + (i + 2) + '. ' + textoOriginal );
				});
				$input.val( JSON.stringify(orden) );
			}

			$lista.sortable({
				axis:   'y',
				handle: 'span',
				items:  'li[data-criterio]', // excluye el ítem fijo de Puntos
				update: actualizarInput
			});
		});
		</script>
		<?php
	}

	/**
	 * Renderiza el meta box "Personalización visual".
	 *
	 * @param WP_Post $post
	 */
	public function render_torneo_visual( $post ) {
		$color_primario   = get_post_meta( $post->ID, 'scl_torneo_color_primario',   true ) ?: '#1a3a5c';
		$color_secundario = get_post_meta( $post->ID, 'scl_torneo_color_secundario', true ) ?: '#f5a623';
		$fondo            = absint( get_post_meta( $post->ID, 'scl_torneo_fondo', true ) );
		$fondo_url        = $fondo ? wp_get_attachment_image_url( $fondo, 'thumbnail' ) : '';
		?>
		<table class="form-table">
			<tr>
				<th><label for="scl_torneo_color_primario"><?php esc_html_e( 'Color primario', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="color" id="scl_torneo_color_primario" name="scl_torneo_color_primario"
						value="<?php echo esc_attr( $color_primario ); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="scl_torneo_color_secundario"><?php esc_html_e( 'Color secundario', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="color" id="scl_torneo_color_secundario" name="scl_torneo_color_secundario"
						value="<?php echo esc_attr( $color_secundario ); ?>">
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Imagen de fondo para exportación', 'sportcriss-lite' ); ?></label></th>
				<td>
					<div id="scl-fondo-preview" style="margin-bottom:8px;">
						<?php if ( $fondo_url ) : ?>
							<img src="<?php echo esc_url( $fondo_url ); ?>" style="max-width:150px;max-height:100px;display:block;">
						<?php endif; ?>
					</div>
					<input type="hidden" id="scl_torneo_fondo" name="scl_torneo_fondo"
						value="<?php echo esc_attr( $fondo ?: '' ); ?>">
					<button type="button" class="button" id="scl-btn-fondo">
						<?php esc_html_e( 'Seleccionar imagen', 'sportcriss-lite' ); ?>
					</button>
					<?php if ( $fondo ) : ?>
						<button type="button" class="button" id="scl-btn-fondo-remove">
							<?php esc_html_e( 'Quitar', 'sportcriss-lite' ); ?>
						</button>
					<?php endif; ?>
					<?php $this->render_media_uploader_script(
						'scl-btn-fondo',
						'scl_torneo_fondo',
						'scl-fondo-preview',
						__( 'Seleccionar imagen de fondo', 'sportcriss-lite' ),
						'scl-btn-fondo-remove'
					); ?>
				</td>
			</tr>
		</table>
		<?php
	}

	// -----------------------------------------------------------------------
	// Render: scl_partido
	// -----------------------------------------------------------------------

	/**
	 * Renderiza el meta box "Datos del partido".
	 *
	 * @param WP_Post $post
	 */
	public function render_partido_datos( $post ) {
		wp_nonce_field( 'scl_guardar_partido', 'scl_partido_nonce' );

		$torneo_id    = absint( get_post_meta( $post->ID, 'scl_partido_torneo_id', true ) );
		$grupo_id     = absint( get_post_meta( $post->ID, 'scl_partido_grupo_id', true ) );
		$fecha        = get_post_meta( $post->ID, 'scl_partido_fecha',   true );
		$estado       = get_post_meta( $post->ID, 'scl_partido_estado',  true ) ?: 'pendiente';
		$tipo_fase    = get_post_meta( $post->ID, 'scl_partido_tipo_fase', true ) ?: 'grupos';

		$terms = wp_get_post_terms( $post->ID, 'scl_temporada' );
		$temporada_term_id = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->term_id : 0;

		$torneos = get_posts( [
			'post_type'      => 'scl_torneo',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$nonce = wp_create_nonce( 'scl_dashboard_nonce' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="scl_partido_torneo_id"><?php esc_html_e( 'Torneo', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_partido_torneo_id" name="scl_partido_torneo_id">
						<option value="0"><?php esc_html_e( '— Seleccionar torneo —', 'sportcriss-lite' ); ?></option>
						<?php foreach ( $torneos as $t ) : ?>
							<option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $torneo_id, $t->ID ); ?>>
								<?php echo esc_html( $t->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="scl_partido_temporada_term_id"><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></label></th>
				<td>
					<?php
					$terms_asignados = wp_get_post_terms( $post->ID, 'scl_temporada' );
					$temporada_actual = ( ! is_wp_error( $terms_asignados ) && ! empty( $terms_asignados ) )
						? $terms_asignados[0]->term_id : 0;

					$todas_temporadas = get_terms( [
						'taxonomy'   => 'scl_temporada',
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
					] );

					echo '<select id="scl_partido_temporada_term_id" name="scl_partido_temporada_term_id" data-selected="' . esc_attr( $temporada_actual ) . '">';
					echo '<option value="0">— Sin temporada —</option>';
					if ( ! is_wp_error( $todas_temporadas ) ) {
						foreach ( $todas_temporadas as $t ) {
							$selected = selected( $temporada_actual, $t->term_id, false );
							echo '<option value="' . esc_attr( $t->term_id ) . '"' . $selected . '>' . esc_html( $t->name ) . '</option>';
						}
					}
					echo '</select>';
					?>
					<p class="description">Si la temporada no existe, créala desde el menú Temporadas.</p>
				</td>
			</tr>
			<tr>
				<th><label for="tax-input-scl_jornada">Jornada</label></th>
				<td>
					<?php
					$jornadas_asignadas = wp_get_post_terms( $post->ID, 'scl_jornada' );
					$jornada_names = ( ! is_wp_error( $jornadas_asignadas ) && ! empty( $jornadas_asignadas ) )
						? implode( ', ', wp_list_pluck( $jornadas_asignadas, 'name' ) ) : '';
					?>
					<div class="tagsdiv" id="scl_jornada_wrapper">
						<input
							data-wp-taxonomy="scl_jornada"
							type="text"
							id="new-tag-scl_jornada"
							name="newtag[scl_jornada]"
							class="newtag form-input-tip"
							size="20"
							autocomplete="off"
							placeholder="Escribir o buscar jornada..."
						>
						<input type="hidden"
							name="tax_input[scl_jornada]"
							id="tax-input-scl_jornada"
							value="<?php echo esc_attr( $jornada_names ); ?>"
						>
					</div>
					<p class="description">
						Escribe el nombre de la jornada (ej: "Fecha 1").
						Si ya existe la seleccionará, si no, la creará automáticamente.
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="scl_partido_tipo_fase">Tipo de fase</label></th>
				<td>
					<select id="scl_partido_tipo_fase" name="scl_partido_tipo_fase">
						<option value="grupos" <?php selected( $tipo_fase, 'grupos' ); ?>>
							Grupos (suma puntos a la tabla)
						</option>
						<option value="playoff" <?php selected( $tipo_fase, 'playoff' ); ?>>
							Playoff (no suma puntos · puede tener penales)
						</option>
					</select>
					<p class="description">
						Los partidos de Grupos afectan la tabla de posiciones.
						Los de Playoff determinan quién avanza pero no suman puntos.
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="scl_partido_grupo_id"><?php esc_html_e( 'Grupo', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_partido_grupo_id" name="scl_partido_grupo_id" data-selected="<?php echo esc_attr( $grupo_id ); ?>">
						<option value="0"><?php esc_html_e( '— Sin grupo —', 'sportcriss-lite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="scl_partido_fecha"><?php esc_html_e( 'Fecha del partido', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="date" id="scl_partido_fecha" name="scl_partido_fecha"
						value="<?php echo esc_attr( $fecha ); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="scl_partido_estado"><?php esc_html_e( 'Estado', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_partido_estado" name="scl_partido_estado">
						<option value="pendiente"  <?php selected( $estado, 'pendiente' ); ?>><?php esc_html_e( 'Pendiente', 'sportcriss-lite' ); ?></option>
						<option value="finalizado" <?php selected( $estado, 'finalizado' ); ?>><?php esc_html_e( 'Finalizado', 'sportcriss-lite' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<script>
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( $nonce ); ?>';

			var $torneoSelect = $('#scl_partido_torneo_id');
			var $grupoSelect  = $('#scl_partido_grupo_id');

			var grupoGuardado = parseInt($grupoSelect.data('selected')) || 0;

			function cargarGrupos(torneoId) {
				if (!torneoId || torneoId == '0') {
					$grupoSelect.html('<option value="0">— Sin grupo —</option>');
					return;
				}
				$.post(ajaxurl, {
					action: 'scl_get_grupos_por_torneo',
					torneo_id: torneoId,
					nonce: nonce
				}, function(res) {
					if (res.success) {
						var html = '<option value="0">— Sin grupo —</option>';
						res.data.forEach(function(g) {
							var sel = (g.ID == grupoGuardado) ? ' selected' : '';
							html += '<option value="' + g.ID + '"' + sel + '>' + g.post_title + '</option>';
						});
						$grupoSelect.html(html);
					}
				});
			}

			$torneoSelect.on('change', function() {
				var torneoId = $(this).val();
				cargarGrupos(torneoId);
			});

			// Disparar al cargar para recuperar valores guardados
			if ($torneoSelect.val() !== '0') {
				cargarGrupos($torneoSelect.val());
			}
		});
		</script>
		<?php
	}

	/**
	 * Renderiza el meta box "Equipos y resultado".
	 *
	 * @param WP_Post $post
	 */
	public function render_partido_resultado( $post ) {
		$equipo_local_id  = absint( get_post_meta( $post->ID, 'scl_partido_equipo_local_id',  true ) );
		$equipo_visita_id = absint( get_post_meta( $post->ID, 'scl_partido_equipo_visita_id', true ) );
		$goles_local      = get_post_meta( $post->ID, 'scl_partido_goles_local',  true );
		$goles_visita     = get_post_meta( $post->ID, 'scl_partido_goles_visita', true );
		$estado           = get_post_meta( $post->ID, 'scl_partido_estado', true ) ?: 'pendiente';

		$equipos = $this->get_equipos_usuario( get_current_user_id() );
		$pendiente = ( 'pendiente' === $estado );
		?>
		<table class="form-table">
			<tr>
				<th><label for="scl_partido_equipo_local_id"><?php esc_html_e( 'Equipo local', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_partido_equipo_local_id" name="scl_partido_equipo_local_id">
						<option value=""><?php esc_html_e( '— Seleccionar equipo —', 'sportcriss-lite' ); ?></option>
						<?php foreach ( $equipos as $e ) : ?>
							<option value="<?php echo esc_attr( $e->ID ); ?>" <?php selected( $equipo_local_id, $e->ID ); ?>>
								<?php echo esc_html( $e->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
				<th><label for="scl_partido_goles_local"><?php esc_html_e( 'Goles local', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="number" id="scl_partido_goles_local" name="scl_partido_goles_local"
						value="<?php echo esc_attr( $goles_local ); ?>"
						min="0" class="small-text"
						<?php echo $pendiente ? 'disabled' : ''; ?>>
				</td>
			</tr>
			<tr>
				<th><label for="scl_partido_equipo_visita_id"><?php esc_html_e( 'Equipo visitante', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_partido_equipo_visita_id" name="scl_partido_equipo_visita_id">
						<option value=""><?php esc_html_e( '— Seleccionar equipo —', 'sportcriss-lite' ); ?></option>
						<?php foreach ( $equipos as $e ) : ?>
							<option value="<?php echo esc_attr( $e->ID ); ?>" <?php selected( $equipo_visita_id, $e->ID ); ?>>
								<?php echo esc_html( $e->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
				<th><label for="scl_partido_goles_visita"><?php esc_html_e( 'Goles visitante', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="number" id="scl_partido_goles_visita" name="scl_partido_goles_visita"
						value="<?php echo esc_attr( $goles_visita ); ?>"
						min="0" class="small-text"
						<?php echo $pendiente ? 'disabled' : ''; ?>>
				</td>
			</tr>
		</table>
		<p class="description">
			<?php esc_html_e( 'Los goles solo se ingresan cuando el estado es "Finalizado".', 'sportcriss-lite' ); ?>
		</p>
		<?php
	}

	/**
	 * Renderiza el meta box "Clasificación" (sidebar).
	 *
	 * @param WP_Post $post
	 */
	public function render_partido_clasificacion( $post ) {
		$llave_id  = get_post_meta( $post->ID, 'scl_partido_llave_id',  true );
		$es_vuelta = get_post_meta( $post->ID, 'scl_partido_es_vuelta', true );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'ID de llave asociada', 'sportcriss-lite' ); ?></th>
				<td>
					<input type="text" value="<?php echo $llave_id ? esc_attr( $llave_id ) : '—'; ?>"
						readonly disabled class="small-text">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Es partido de vuelta', 'sportcriss-lite' ); ?></th>
				<td>
					<input type="checkbox" id="scl_partido_es_vuelta" name="scl_partido_es_vuelta"
						value="1" <?php checked( $es_vuelta, '1' ); ?>>
					<label for="scl_partido_es_vuelta">
						<?php esc_html_e( 'Es partido de vuelta en una llave', 'sportcriss-lite' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	// -----------------------------------------------------------------------
	// Render: scl_llave
	// -----------------------------------------------------------------------

	/**
	 * Renderiza el meta box "Datos de la llave".
	 *
	 * @param WP_Post $post
	 */
	public function render_llave( $post ) {
		wp_nonce_field( 'scl_guardar_llave', 'scl_llave_nonce' );

		$torneo_id         = absint( get_post_meta( $post->ID, 'scl_llave_torneo_id',         true ) );

		$terms = wp_get_post_terms( $post->ID, 'scl_temporada' );
		$temporada_term_id = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->term_id : 0;

		$torneos = get_posts( [
			'post_type'      => 'scl_torneo',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$nonce = wp_create_nonce( 'scl_dashboard_nonce' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="scl_llave_torneo_id"><?php esc_html_e( 'Torneo', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_llave_torneo_id" name="scl_llave_torneo_id">
						<option value="0"><?php esc_html_e( '— Seleccionar torneo —', 'sportcriss-lite' ); ?></option>
						<?php foreach ( $torneos as $t ) : ?>
							<option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $torneo_id, $t->ID ); ?>>
								<?php echo esc_html( $t->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="scl_llave_temporada_term_id"><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_llave_temporada_term_id" name="scl_llave_temporada_term_id" data-selected="<?php echo esc_attr( $temporada_term_id ); ?>">
						<option value="0"><?php esc_html_e( '— Sin temporada —', 'sportcriss-lite' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th><?php esc_html_e( 'ID partido de ida', 'sportcriss-lite' ); ?></th>
				<td>
					<input type="text" value="<?php echo $partido_ida_id ? esc_attr( $partido_ida_id ) : '—'; ?>"
						readonly disabled class="small-text">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'ID partido de vuelta', 'sportcriss-lite' ); ?></th>
				<td>
					<input type="text" value="<?php echo $partido_vuelta_id ? esc_attr( $partido_vuelta_id ) : '—'; ?>"
						readonly disabled class="small-text">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '¿Es ida y vuelta?', 'sportcriss-lite' ); ?></th>
				<td>
					<input type="checkbox" id="scl_llave_es_doble" name="scl_llave_es_doble"
						value="1" <?php checked( $es_doble, '1' ); ?>>
					<label for="scl_llave_es_doble"><?php esc_html_e( 'Ida y vuelta', 'sportcriss-lite' ); ?></label>
				</td>
			</tr>
			<?php 
			$ganador_provisional_id = get_post_meta( $post->ID, 'scl_llave_ganador_provisional_id', true );
			if ( $ganador_provisional_id ) : ?>
			<tr>
				<th><?php esc_html_e( 'Ganador Provisional', 'sportcriss-lite' ); ?></th>
				<td>
					<input type="text" value="<?php echo esc_attr( get_the_title( $ganador_provisional_id ) ); ?>"
						readonly disabled class="regular-text">
					<p class="description"><?php esc_html_e( 'Calculado por el motor. El organizador debe confirmarlo abajo.', 'sportcriss-lite' ); ?></p>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( $equipo_local_id && $equipo_visita_id ) : ?>
			<tr>
				<th><label for="scl_llave_ganador_id"><?php esc_html_e( 'Equipo ganador confirmado', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_llave_ganador_id" name="scl_llave_ganador_id">
						<option value=""><?php esc_html_e( '— Sin confirmar —', 'sportcriss-lite' ); ?></option>
						<?php
						$e_local  = get_post( $equipo_local_id );
						$e_visita = get_post( $equipo_visita_id );
						if ( $e_local ) : ?>
							<option value="<?php echo esc_attr( $equipo_local_id ); ?>" <?php selected( $ganador_id, $equipo_local_id ); ?>>
								<?php echo esc_html( $e_local->post_title ); ?>
							</option>
						<?php endif; if ( $e_visita ) : ?>
							<option value="<?php echo esc_attr( $equipo_visita_id ); ?>" <?php selected( $ganador_id, $equipo_visita_id ); ?>>
								<?php echo esc_html( $e_visita->post_title ); ?>
							</option>
						<?php endif; ?>
					</select>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<th><label for="scl_llave_penales_local"><?php esc_html_e( 'Penales equipo local', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="number" id="scl_llave_penales_local" name="scl_llave_penales_local"
						value="<?php echo esc_attr( $penales_local ); ?>" min="0" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="scl_llave_penales_visita"><?php esc_html_e( 'Penales equipo visitante', 'sportcriss-lite' ); ?></label></th>
				<td>
					<input type="number" id="scl_llave_penales_visita" name="scl_llave_penales_visita"
						value="<?php echo esc_attr( $penales_visita ); ?>" min="0" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="scl_llave_estado"><?php esc_html_e( 'Estado de la llave', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select id="scl_llave_estado" name="scl_llave_estado">
						<option value="en_curso"  <?php selected( $estado, 'en_curso' ); ?>><?php esc_html_e( 'En curso', 'sportcriss-lite' ); ?></option>
						<option value="resuelta"  <?php selected( $estado, 'resuelta' ); ?>><?php esc_html_e( 'Resuelta', 'sportcriss-lite' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<script>
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( $nonce ); ?>';

			var $torneoSelect    = $('#scl_llave_torneo_id');
			var $temporadaSelect = $('#scl_llave_temporada_term_id');

			var temporadaGuardada = parseInt($temporadaSelect.data('selected')) || 0;

			function cargarTemporadas(torneoId) {
				if (!torneoId || torneoId == '0') {
					$temporadaSelect.html('<option value="0">— Sin temporada —</option>');
					return;
				}
				$.post(ajaxurl, {
					action: 'scl_get_temporadas_por_torneo',
					torneo_id: torneoId,
					nonce: nonce
				}, function(res) {
					if (res.success) {
						var html = '<option value="0">— Sin temporada —</option>';
						res.data.forEach(function(t) {
							var sel = (t.term_id == temporadaGuardada) ? ' selected' : '';
							html += '<option value="' + t.term_id + '"' + sel + '>' + t.name + '</option>';
						});
						$temporadaSelect.html(html);
					}
				});
			}

			$torneoSelect.on('change', function() {
				var torneoId = $(this).val();
				cargarTemporadas(torneoId);
			});

			// Disparar al cargar para recuperar valores guardados
			if ($torneoSelect.val() !== '0') {
				cargarTemporadas($torneoSelect.val());
			}
		});
		</script>
		<?php
	}


	// -----------------------------------------------------------------------
	// Render: term meta boxes para scl_temporada
	// -----------------------------------------------------------------------

	/**
	 * Campos en el formulario de CREACIÓN de término scl_temporada.
	 */
	public function temporada_add_fields( string $taxonomy ): void {
		wp_nonce_field( 'scl_guardar_term_temporada', 'scl_temporada_nonce' );
		?>
		<div class="form-field">
			<label for="scl_temporada_estado"><?php esc_html_e( 'Estado', 'sportcriss-lite' ); ?></label>
			<select name="scl_temporada_estado" id="scl_temporada_estado">
				<option value="activa"><?php esc_html_e( 'Activa', 'sportcriss-lite' ); ?></option>
				<option value="finalizada"><?php esc_html_e( 'Finalizada', 'sportcriss-lite' ); ?></option>
			</select>
		</div>
		<div class="form-field">
			<label for="scl_temporada_anio"><?php esc_html_e( 'Año', 'sportcriss-lite' ); ?></label>
			<input type="number" name="scl_temporada_anio" id="scl_temporada_anio"
				value="<?php echo esc_attr( date( 'Y' ) ); ?>">
		</div>
		<?php
	}

	/**
	 * Campos en el formulario de EDICIÓN de término scl_temporada.
	 */
	public function temporada_edit_fields( $term ) {
		wp_nonce_field( 'scl_guardar_term_temporada', 'scl_temporada_nonce' );

		$estado    = get_term_meta( $term->term_id, 'scl_temporada_estado', true ) ?: 'activa';
		$anio      = get_term_meta( $term->term_id, 'scl_temporada_anio', true ) ?: date('Y');
		?>
		<tr class="form-field">
			<th><label for="scl_temporada_estado"><?php esc_html_e( 'Estado', 'sportcriss-lite' ); ?></label></th>
			<td>
				<select name="scl_temporada_estado" id="scl_temporada_estado">
					<option value="activa" <?php selected( $estado, 'activa' ); ?>><?php esc_html_e( 'Activa', 'sportcriss-lite' ); ?></option>
					<option value="finalizada" <?php selected( $estado, 'finalizada' ); ?>><?php esc_html_e( 'Finalizada', 'sportcriss-lite' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="scl_temporada_anio"><?php esc_html_e( 'Año', 'sportcriss-lite' ); ?></label></th>
			<td>
				<input type="number" name="scl_temporada_anio" id="scl_temporada_anio"
					value="<?php echo esc_attr( $anio ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * Guarda los term metas de scl_temporada.
	 */
	public function temporada_save_fields( int $term_id ): void {
		if ( ! isset( $_POST['scl_temporada_nonce'] ) ) return;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_temporada_nonce'] ) ), 'scl_guardar_term_temporada' ) ) return;

		update_term_meta( $term_id, 'scl_temporada_estado',
			sanitize_key( wp_unslash( $_POST['scl_temporada_estado'] ?? 'activa' ) ) );
		update_term_meta( $term_id, 'scl_temporada_anio',
			absint( $_POST['scl_temporada_anio'] ?? date( 'Y' ) ) );
	}

	// -----------------------------------------------------------------------
	// Render: scl_grupo
	// -----------------------------------------------------------------------

	/**
	 * Renderiza el meta box "Datos del grupo".
	 *
	 * @param WP_Post $post
	 */
	public function render_grupo( $post ) {
		wp_nonce_field( 'scl_guardar_grupo', 'scl_grupo_nonce' );

		$torneo_id   = $post->post_parent;
		$descripcion = get_post_meta( $post->ID, 'scl_grupo_descripcion', true );

		$torneos = get_posts( [
			'post_type'      => 'scl_torneo',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		?>
		<table class="form-table">
			<tr>
				<th><label for="scl_grupo_torneo_id"><?php esc_html_e( 'Torneo al que pertenece', 'sportcriss-lite' ); ?></label></th>
				<td>
					<select name="scl_grupo_torneo_id" id="scl_grupo_torneo_id" required>
						<option value=""><?php esc_html_e( '— Seleccionar torneo —', 'sportcriss-lite' ); ?></option>
						<?php foreach ( $torneos as $t ) : ?>
							<option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $torneo_id, $t->ID ); ?>><?php echo esc_html( $t->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="scl_grupo_descripcion"><?php esc_html_e( 'Descripción (opcional)', 'sportcriss-lite' ); ?></label></th>
				<td>
					<textarea name="scl_grupo_descripcion" id="scl_grupo_descripcion" rows="3" class="large-text"><?php echo esc_textarea( $descripcion ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	// -----------------------------------------------------------------------
	// Guardado central (save_post)
	// -----------------------------------------------------------------------

	/**
	 * Dispatcher central de guardado. Detecta el post_type y delega.
	 * Callback del hook 'save_post'.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function guardar( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		switch ( $post->post_type ) {
			case 'scl_equipo':
				$this->guardar_equipo( $post_id );
				break;
			case 'scl_torneo':
				$this->guardar_torneo( $post_id );
				break;
			case 'scl_partido':
				$this->guardar_partido( $post_id, $post );
				break;
			case 'scl_llave':
				$this->guardar_llave( $post_id );
				break;
			case 'scl_grupo':
				$this->guardar_grupo( $post_id, $post );
				break;
		}
	}

	// -----------------------------------------------------------------------
	// Guardado por CPT
	// -----------------------------------------------------------------------

	/**
	 * Guarda los metas de scl_equipo.
	 *
	 * @param int $post_id
	 */
	private function guardar_equipo( $post_id ) {
		if ( ! isset( $_POST['scl_equipo_nonce'] ) ) return;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_equipo_nonce'] ) ), 'scl_guardar_equipo' ) ) return;

		$escudo = isset( $_POST['scl_equipo_escudo'] ) ? absint( $_POST['scl_equipo_escudo'] ) : 0;
		$zona   = isset( $_POST['scl_equipo_zona'] )   ? sanitize_text_field( wp_unslash( $_POST['scl_equipo_zona'] ) ) : '';

		update_post_meta( $post_id, 'scl_equipo_escudo', $escudo );
		update_post_meta( $post_id, 'scl_equipo_zona',   $zona );
		// scl_equipo_incompleto no se toca aquí: lo gestiona el importador.
	}

	/**
	 * Guarda los metas de scl_torneo.
	 *
	 * @param int $post_id
	 */
	private function guardar_torneo( $post_id ) {
		if ( ! isset( $_POST['scl_torneo_nonce'] ) ) return;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_torneo_nonce'] ) ), 'scl_guardar_torneo' ) ) return;

		// Identidad
		$siglas = isset( $_POST['scl_torneo_siglas'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_POST['scl_torneo_siglas'] ) ) )
			: '';
		// Limitar a 6 caracteres tras sanitizar
		$siglas = substr( $siglas, 0, 6 );
		update_post_meta( $post_id, 'scl_torneo_siglas', $siglas );

		$logo = isset( $_POST['scl_torneo_logo'] ) ? absint( $_POST['scl_torneo_logo'] ) : 0;
		update_post_meta( $post_id, 'scl_torneo_logo', $logo );

		// Puntos
		$victoria = isset( $_POST['scl_torneo_puntos_victoria'] ) ? absint( $_POST['scl_torneo_puntos_victoria'] ) : 3;
		$empate   = isset( $_POST['scl_torneo_puntos_empate'] )   ? absint( $_POST['scl_torneo_puntos_empate'] )   : 1;
		$derrota  = isset( $_POST['scl_torneo_puntos_derrota'] )  ? absint( $_POST['scl_torneo_puntos_derrota'] )  : 0;
		update_post_meta( $post_id, 'scl_torneo_puntos_victoria', $victoria );
		update_post_meta( $post_id, 'scl_torneo_puntos_empate',   $empate );
		update_post_meta( $post_id, 'scl_torneo_puntos_derrota',  $derrota );

		// Desempate (JSON array)
		if ( isset( $_POST['scl_torneo_desempate_orden'] ) ) {
			$raw    = wp_unslash( $_POST['scl_torneo_desempate_orden'] );
			$decoded = json_decode( $raw, true );
			$valores_validos = [ 'diferencia_goles', 'goles_favor', 'goles_contra', 'enfrentamiento_directo' ];
			if ( is_array( $decoded ) ) {
				$limpio = array_values( array_intersect( $decoded, $valores_validos ) );
				update_post_meta( $post_id, 'scl_torneo_desempate_orden', wp_json_encode( $limpio ) );
			}
		}

		// Colores
		$color_primario   = isset( $_POST['scl_torneo_color_primario'] )   ? sanitize_hex_color( wp_unslash( $_POST['scl_torneo_color_primario'] ) )   : '#1a3a5c';
		$color_secundario = isset( $_POST['scl_torneo_color_secundario'] ) ? sanitize_hex_color( wp_unslash( $_POST['scl_torneo_color_secundario'] ) ) : '#f5a623';
		update_post_meta( $post_id, 'scl_torneo_color_primario',   $color_primario );
		update_post_meta( $post_id, 'scl_torneo_color_secundario', $color_secundario );

		// Fondo
		$fondo = isset( $_POST['scl_torneo_fondo'] ) ? absint( $_POST['scl_torneo_fondo'] ) : 0;
		update_post_meta( $post_id, 'scl_torneo_fondo', $fondo );
	}

	/**
	 * Guarda los metas de scl_partido.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function guardar_partido( int $post_id, WP_Post $post ): void {
		// Evitar loop infinito
		if ( self::$generando_titulo ) return;

		if ( ! isset( $_POST['scl_partido_nonce'] ) ) return;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_partido_nonce'] ) ), 'scl_guardar_partido' ) ) return;

		$temporada_term_id = absint( $_POST['scl_partido_temporada_term_id'] ?? 0 );
		if ( $temporada_term_id ) {
			wp_set_post_terms( $post_id, [ $temporada_term_id ], 'scl_temporada' );
		} else {
			wp_set_post_terms( $post_id, [], 'scl_temporada' );
		}

		$torneo_id        = isset( $_POST['scl_partido_torneo_id'] )        ? absint( $_POST['scl_partido_torneo_id'] )        : 0;
		$equipo_local_id  = isset( $_POST['scl_partido_equipo_local_id'] )  ? absint( $_POST['scl_partido_equipo_local_id'] )  : 0;
		$equipo_visita_id = isset( $_POST['scl_partido_equipo_visita_id'] ) ? absint( $_POST['scl_partido_equipo_visita_id'] ) : 0;
		$es_vuelta        = isset( $_POST['scl_partido_es_vuelta'] )        ? '1' : '0';

		$grupo_id         = isset( $_POST['scl_partido_grupo_id'] )         ? absint( $_POST['scl_partido_grupo_id'] )         : 0;

		$tipo_fase = in_array( $_POST['scl_partido_tipo_fase'] ?? '', [ 'grupos', 'playoff' ], true )
			? $_POST['scl_partido_tipo_fase']
			: 'grupos';

		$estados_validos = [ 'pendiente', 'finalizado' ];
		$estado = isset( $_POST['scl_partido_estado'] ) ? sanitize_text_field( wp_unslash( $_POST['scl_partido_estado'] ) ) : 'pendiente';
		if ( ! in_array( $estado, $estados_validos, true ) ) {
			$estado = 'pendiente';
		}

		// Fecha: validar formato Y-m-d
		$fecha = '';
		if ( ! empty( $_POST['scl_partido_fecha'] ) ) {
			$fecha_raw = sanitize_text_field( wp_unslash( $_POST['scl_partido_fecha'] ) );
			$dt = DateTime::createFromFormat( 'Y-m-d', $fecha_raw );
			if ( $dt && $dt->format( 'Y-m-d' ) === $fecha_raw ) {
				$fecha = $fecha_raw;
			}
		}

		// Goles: '' si estado pendiente; absint si finalizado
		if ( 'pendiente' === $estado ) {
			$goles_local  = '';
			$goles_visita = '';
		} else {
			$goles_local  = isset( $_POST['scl_partido_goles_local'] )  && '' !== $_POST['scl_partido_goles_local']
				? absint( $_POST['scl_partido_goles_local'] )  : '';
			$goles_visita = isset( $_POST['scl_partido_goles_visita'] ) && '' !== $_POST['scl_partido_goles_visita']
				? absint( $_POST['scl_partido_goles_visita'] ) : '';
		}

		update_post_meta( $post_id, 'scl_partido_torneo_id',        $torneo_id );
		update_post_meta( $post_id, 'scl_partido_equipo_local_id',  $equipo_local_id );
		update_post_meta( $post_id, 'scl_partido_equipo_visita_id', $equipo_visita_id );
		update_post_meta( $post_id, 'scl_partido_goles_local',      $goles_local );
		update_post_meta( $post_id, 'scl_partido_goles_visita',     $goles_visita );
		update_post_meta( $post_id, 'scl_partido_estado',           $estado );
		update_post_meta( $post_id, 'scl_partido_fecha',            $fecha );
		update_post_meta( $post_id, 'scl_partido_es_vuelta',        $es_vuelta );
		update_post_meta( $post_id, 'scl_partido_grupo_id',         $grupo_id );
		update_post_meta( $post_id, 'scl_partido_tipo_fase',        $tipo_fase );
		// llave_id es readonly: lo gestiona el motor de llaves.

		// Generar título automático
		$titulo = $this->generar_titulo_partido( $post_id );
		if ( ! empty( $titulo ) && $titulo !== get_the_title( $post_id ) ) {
			self::$generando_titulo = true;
			wp_update_post( [
				'ID'         => $post_id,
				'post_title' => $titulo,
				'post_name'  => sanitize_title( $titulo ),
			] );
			self::$generando_titulo = false;
		}
	}

	private function generar_titulo_partido( int $post_id ): string {
		$torneo_id    = (int) get_post_meta( $post_id, 'scl_partido_torneo_id', true );
		$local_id     = (int) get_post_meta( $post_id, 'scl_partido_equipo_local_id', true );
		$visita_id    = (int) get_post_meta( $post_id, 'scl_partido_equipo_visita_id', true );

		// Sin equipos no hay título posible — salir silenciosamente
		if ( ! $local_id || ! $visita_id ) {
			return '';
		}

		$local  = get_the_title( $local_id );
		$visita = get_the_title( $visita_id );

		// Siglas del torneo (opcional)
		$prefijo = '';
		if ( $torneo_id ) {
			$siglas = strtoupper( trim( get_post_meta( $torneo_id, 'scl_torneo_siglas', true ) ) );
			if ( ! $siglas ) {
				$siglas = strtoupper( substr( get_the_title( $torneo_id ), 0, 3 ) );
			}
			$prefijo = "[{$siglas}] · ";
		}

		// Temporada desde taxonomía (opcional)
		$terms = wp_get_post_terms( $post_id, 'scl_temporada' );
		$temporada = ( ! is_wp_error( $terms ) && ! empty( $terms ) )
			? ' · ' . $terms[0]->name
			: '';

		// Jornada (opcional)
		$jornadas = wp_get_post_terms( $post_id, 'scl_jornada' );
		$jornada  = ( ! is_wp_error( $jornadas ) && ! empty( $jornadas ) )
			? ' · ' . $jornadas[0]->name
			: '';

		return $prefijo . $local . ' vs ' . $visita . $jornada . $temporada;
	}

	/**
	 * Guarda los metas de scl_llave.
	 *
	 * @param int $post_id
	 */
	private function guardar_llave( $post_id ) {
		if ( ! isset( $_POST['scl_llave_nonce'] ) ) return;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_llave_nonce'] ) ), 'scl_guardar_llave' ) ) return;

		$temporada_term_id = absint( $_POST['scl_llave_temporada_term_id'] ?? 0 );
		if ( $temporada_term_id ) {
			wp_set_post_terms( $post_id, [ $temporada_term_id ], 'scl_temporada' );
		} else {
			wp_set_post_terms( $post_id, [], 'scl_temporada' );
		}

		$torneo_id     = isset( $_POST['scl_llave_torneo_id'] )     ? absint( $_POST['scl_llave_torneo_id'] )     : 0;
		$es_doble      = isset( $_POST['scl_llave_es_doble'] )      ? '1' : '0';
		$ganador_id    = isset( $_POST['scl_llave_ganador_id'] )    ? absint( $_POST['scl_llave_ganador_id'] )    : 0;
		$penales_local = isset( $_POST['scl_llave_penales_local'] ) && '' !== $_POST['scl_llave_penales_local']
			? absint( $_POST['scl_llave_penales_local'] ) : '';
		$penales_visita = isset( $_POST['scl_llave_penales_visita'] ) && '' !== $_POST['scl_llave_penales_visita']
			? absint( $_POST['scl_llave_penales_visita'] ) : '';

		$estados_validos = [ 'en_curso', 'resuelta' ];
		$estado = isset( $_POST['scl_llave_estado'] ) ? sanitize_text_field( wp_unslash( $_POST['scl_llave_estado'] ) ) : 'en_curso';
		if ( ! in_array( $estado, $estados_validos, true ) ) {
			$estado = 'en_curso';
		}

		update_post_meta( $post_id, 'scl_llave_torneo_id',         $torneo_id );
		update_post_meta( $post_id, 'scl_llave_es_doble',      $es_doble );
		update_post_meta( $post_id, 'scl_llave_ganador_id',    $ganador_id );
		update_post_meta( $post_id, 'scl_llave_penales_local', $penales_local );
		update_post_meta( $post_id, 'scl_llave_penales_visita', $penales_visita );
		update_post_meta( $post_id, 'scl_llave_estado',        $estado );
		// partido_ida_id y partido_vuelta_id los gestiona Scl_Llave.
	}

	/**
	 * Guarda los metas de scl_grupo.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	private function guardar_grupo( $post_id, $post ) {
		if ( ! isset( $_POST['scl_grupo_nonce'] ) ) return;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_grupo_nonce'] ) ), 'scl_guardar_grupo' ) ) return;

		$torneo_id   = isset( $_POST['scl_grupo_torneo_id'] )   ? absint( $_POST['scl_grupo_torneo_id'] )   : 0;
		$descripcion = isset( $_POST['scl_grupo_descripcion'] ) ? sanitize_textarea_field( wp_unslash( $_POST['scl_grupo_descripcion'] ) ) : '';

		update_post_meta( $post_id, 'scl_grupo_descripcion', $descripcion );

		if ( $torneo_id && $post->post_parent !== $torneo_id ) {
			remove_action( 'save_post', [ $this, 'guardar' ], 10 );
			wp_update_post( [
				'ID'          => $post_id,
				'post_parent' => $torneo_id,
			] );
			add_action( 'save_post', [ $this, 'guardar' ], 10, 2 );
		}
	}

	// -----------------------------------------------------------------------
	// Helpers de consulta
	// -----------------------------------------------------------------------

	/**
	 * Devuelve las temporadas del usuario agrupadas por nombre de torneo.
	 *
	 * @param int $user_id
	 * @return array [ 'Nombre Torneo' => [ ['id'=>..., 'nombre'=>...], ... ], ... ]
	 */
	private function get_temporadas_usuario( $user_id ) {
		$temporadas = get_posts( [
			'post_type'      => 'scl_temporada',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$agrupadas = [];
		foreach ( $temporadas as $t ) {
			$torneo_id = (int) get_post_meta( $t->ID, 'scl_temporada_torneo_id', true );
			$torneo = $torneo_id ? get_post( $torneo_id ) : null;
			$torneo_nombre = $torneo ? $torneo->post_title : __( 'Sin torneo', 'sportcriss-lite' );
			$agrupadas[ $torneo_nombre ][] = [
				'id'     => $t->ID,
				'nombre' => $t->post_title,
			];
		}

		return $agrupadas;
	}

	/**
	 * Devuelve los equipos del usuario ordenados alfabéticamente.
	 *
	 * @param int $user_id
	 * @return WP_Post[]
	 */
	private function get_equipos_usuario( $user_id ) {
		return get_posts( [
			'post_type'      => 'scl_equipo',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
	}

	/**
	 * Devuelve los términos de scl_fase que tienen scl_fase_es_playoff = true.
	 *
	 * @return WP_Term[]
	 */
	private function get_fases_playoff() {
		$terms = get_terms( [
			'taxonomy'   => 'scl_fase',
			'hide_empty' => false,
		] );

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return array_filter( $terms, function( $term ) {
			return (bool) get_term_meta( $term->term_id, 'scl_fase_es_playoff', true );
		} );
	}

	// -----------------------------------------------------------------------
	// Helper: Media Uploader script
	// -----------------------------------------------------------------------

	/**
	 * Inyecta el JS inline necesario para abrir el Media Uploader nativo de WordPress.
	 * Reutilizable para escudo, logo y fondo.
	 *
	 * @param string $btn_id       ID del botón que abre el uploader.
	 * @param string $input_id     ID del input hidden que recibe el attachment ID.
	 * @param string $preview_id   ID del div que muestra la preview de la imagen.
	 * @param string $title        Título del modal del uploader.
	 * @param string $remove_btn_id ID del botón "Quitar" (puede estar vacío).
	 */
	private function render_media_uploader_script( $btn_id, $input_id, $preview_id, $title, $remove_btn_id = '' ) {
		wp_enqueue_media();
		$title_escaped = esc_js( $title );
		?>
		<script>
		jQuery(function($) {
			var frame;
			$('#<?php echo esc_js( $btn_id ); ?>').on('click', function(e) {
				e.preventDefault();
				if ( frame ) { frame.open(); return; }
				frame = wp.media({
					title: '<?php echo $title_escaped; ?>',
					button: { text: '<?php echo esc_js( __( 'Usar esta imagen', 'sportcriss-lite' ) ); ?>' },
					multiple: false,
					library: { type: 'image' }
				});
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#<?php echo esc_js( $input_id ); ?>').val( attachment.id );
					var imgUrl = attachment.sizes && attachment.sizes.thumbnail
						? attachment.sizes.thumbnail.url
						: attachment.url;
					$('#<?php echo esc_js( $preview_id ); ?>').html(
						'<img src="' + imgUrl + '" style="max-width:150px;max-height:150px;display:block;">'
					);
				});
				frame.open();
			});
			<?php if ( $remove_btn_id ) : ?>
			$('#<?php echo esc_js( $remove_btn_id ); ?>').on('click', function(e) {
				e.preventDefault();
				$('#<?php echo esc_js( $input_id ); ?>').val('');
				$('#<?php echo esc_js( $preview_id ); ?>').html('');
			});
			<?php endif; ?>
		});
		</script>
		<?php
	}
}
