<?php
/**
 * Menú unificado del plugin en wp-admin.
 *
 * Agrupa todos los CPTs bajo un único ítem padre "SportCriss Lite" en lugar
 * de mostrarlos dispersos en la barra lateral. Los CPTs se registran con
 * show_in_menu => false en Scl_Cpts para que no aparezcan por su cuenta.
 *
 * Solo visible para el rol administrator. El rol scl_organizador nunca llega
 * al wp-admin (bloqueado por Scl_Access), así que no requiere lógica adicional.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Admin_Menu
 */
class Scl_Admin_Menu {

	/** Slug del menú padre, referenciado por los submenús. */
	const PARENT_SLUG = 'scl-sportcriss-lite';

	/** Slug de la página de configuración. */
	const CONFIG_SLUG = 'scl-configuracion';

	// -----------------------------------------------------------------------
	// Hook principal
	// -----------------------------------------------------------------------

	/**
	 * Registra el menú padre y todos los submenús.
	 * Callback del hook 'admin_menu'.
	 */
	public function registrar() {
		// Menú padre — apunta a la lista de torneos como página de aterrizaje
		add_menu_page(
			__( 'SportCriss Lite', 'sportcriss-lite' ),
			__( 'SportCriss Lite', 'sportcriss-lite' ),
			'manage_options',
			self::PARENT_SLUG,
			[ $this, 'render_pagina_resumen' ],
			'dashicons-awards',
			30
		);

		// Submenú: Torneos
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Torneos', 'sportcriss-lite' ),
			__( 'Torneos', 'sportcriss-lite' ),
			'manage_options',
			'edit.php?post_type=scl_torneo'
		);

		// Submenú: Grupos
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Grupos', 'sportcriss-lite' ),
			__( 'Grupos', 'sportcriss-lite' ),
			'manage_options',
			'edit.php?post_type=scl_grupo'
		);

		// Submenú: Equipos
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Equipos', 'sportcriss-lite' ),
			__( 'Equipos', 'sportcriss-lite' ),
			'manage_options',
			'edit.php?post_type=scl_equipo'
		);

		// Submenú: Temporadas
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Temporadas', 'sportcriss-lite' ),
			__( 'Temporadas', 'sportcriss-lite' ),
			'manage_options',
			'edit-tags.php?taxonomy=scl_temporada'
		);

		// Submenú: Partidos
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Partidos', 'sportcriss-lite' ),
			__( 'Partidos', 'sportcriss-lite' ),
			'manage_options',
			'edit.php?post_type=scl_partido'
		);

		// Submenú: Llaves
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Llaves', 'sportcriss-lite' ),
			__( 'Llaves', 'sportcriss-lite' ),
			'manage_options',
			'edit.php?post_type=scl_llave'
		);

		// Submenú: Jornadas (taxonomía de partidos)
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Jornadas', 'sportcriss-lite' ),
			__( 'Jornadas', 'sportcriss-lite' ),
			'manage_options',
			'edit-tags.php?taxonomy=scl_jornada'
		);



		// Submenú: Anunciantes (módulo publicidad)
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Anunciantes', 'sportcriss-lite' ),
			__( '— Anunciantes', 'sportcriss-lite' ),
			'manage_options',
			'edit.php?post_type=scl_anunciante'
		);

		// Submenú: Anuncios
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Anuncios', 'sportcriss-lite' ),
			__( '— Anuncios', 'sportcriss-lite' ),
			'manage_options',
			'edit.php?post_type=scl_anuncio'
		);

		// Submenú: Métricas de publicidad
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Métricas de Publicidad — SportCriss Lite', 'sportcriss-lite' ),
			__( '— Métricas', 'sportcriss-lite' ),
			'manage_options',
			'scl-metricas-anuncios',
			[ $this, 'render_metricas_anuncios' ]
		);

		// Submenú: Configuración
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Configuración — SportCriss Lite', 'sportcriss-lite' ),
			__( 'Configuración', 'sportcriss-lite' ),
			'manage_options',
			self::CONFIG_SLUG,
			[ $this, 'render_configuracion' ]
		);

		// WordPress genera automáticamente un submenú duplicado del padre con
		// el mismo label; lo eliminamos para que la lista quede limpia.
		remove_submenu_page( self::PARENT_SLUG, self::PARENT_SLUG );
	}

	// -----------------------------------------------------------------------
	// Renders de páginas propias
	// -----------------------------------------------------------------------

	/**
	 * Página de resumen del plugin (menú padre).
	 * Redirige a la lista de torneos para evitar una página en blanco.
	 */
	public function render_pagina_resumen() {
		// En el improbable caso de que alguien llegue directamente al slug del
		// menú padre, redirigir a Torneos.
		wp_safe_redirect( admin_url( 'edit.php?post_type=scl_torneo' ) );
		exit;
	}

	public function render_metricas_anuncios(): void {
		( new Scl_Ads_Metrics() )->render_pagina();
	}

	/**
	 * Página de configuración del plugin.
	 */
	public function render_configuracion(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'sportcriss-lite' ) );
		}

		// Guardar si viene el form
		if ( isset( $_POST['scl_config_nonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_config_nonce'] ) ), 'scl_guardar_config' ) ) {
			update_option( 'scl_portal_nombre',         sanitize_text_field( wp_unslash( $_POST['scl_portal_nombre'] ?? '' ) ) );
			update_option( 'scl_importador_limite',     absint( $_POST['scl_importador_limite'] ?? 500 ) );
			update_option( 'scl_tabla_transient_ttl',   absint( $_POST['scl_tabla_transient_ttl'] ?? 5 ) );
			update_option( 'scl_audit_retension_dias',  absint( $_POST['scl_audit_retension_dias'] ?? 90 ) );
			add_settings_error( 'scl_config', 'guardado', 'Configuración guardada.', 'success' );
		}

		settings_errors( 'scl_config' );
		?>
		<div class="wrap">
			<h1>⚽ <?php esc_html_e( 'Configuración de SportCriss Lite', 'sportcriss-lite' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'scl_guardar_config', 'scl_config_nonce' ); ?>

				<h2><?php esc_html_e( 'General', 'sportcriss-lite' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="scl_portal_nombre"><?php esc_html_e( 'Nombre del portal', 'sportcriss-lite' ); ?></label></th>
						<td>
							<input type="text" name="scl_portal_nombre" id="scl_portal_nombre"
								class="regular-text"
								value="<?php echo esc_attr( get_option( 'scl_portal_nombre', '' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Aparece en el header del dashboard y en correos del sistema.', 'sportcriss-lite' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Importador CSV', 'sportcriss-lite' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="scl_importador_limite"><?php esc_html_e( 'Límite de filas por importación', 'sportcriss-lite' ); ?></label></th>
						<td>
							<input type="number" name="scl_importador_limite" id="scl_importador_limite"
								class="small-text" min="50" max="2000"
								value="<?php echo esc_attr( get_option( 'scl_importador_limite', 500 ) ); ?>">
							<p class="description"><?php esc_html_e( 'Default: 500. Máximo recomendado en Hostinger Shared: 1000.', 'sportcriss-lite' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Performance', 'sportcriss-lite' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="scl_tabla_transient_ttl"><?php esc_html_e( 'Cache de tabla (minutos)', 'sportcriss-lite' ); ?></label></th>
						<td>
							<input type="number" name="scl_tabla_transient_ttl" id="scl_tabla_transient_ttl"
								class="small-text" min="1" max="60"
								value="<?php echo esc_attr( get_option( 'scl_tabla_transient_ttl', 5 ) ); ?>">
							<p class="description"><?php esc_html_e( 'Minutos que se cachea la tabla de posiciones. Se invalida automáticamente al guardar un resultado.', 'sportcriss-lite' ); ?></p>
						</td>
					</tr>
				</table>

			<h2><?php esc_html_e( 'Auditoría', 'sportcriss-lite' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="scl_audit_retension_dias"><?php esc_html_e( 'Retención del log de auditoría (días)', 'sportcriss-lite' ); ?></label></th>
					<td>
						<input type="number" name="scl_audit_retension_dias" id="scl_audit_retension_dias"
							class="small-text" min="7" max="365"
							value="<?php echo esc_attr( get_option( 'scl_audit_retension_dias', 90 ) ); ?>">
						<p class="description"><?php esc_html_e( 'Registros con más de este número de días se eliminan automáticamente cada noche via WP-Cron. Default: 90.', 'sportcriss-lite' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Datos del sistema', 'sportcriss-lite' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Versión del plugin', 'sportcriss-lite' ); ?></th>
						<td><code><?php echo esc_html( SCL_VERSION ); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Organizadores registrados', 'sportcriss-lite' ); ?></th>
						<td><?php echo esc_html( count( get_users( [ 'role' => 'scl_organizador' ] ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Colaboradores registrados', 'sportcriss-lite' ); ?></th>
						<td><?php echo esc_html( count( get_users( [ 'role' => 'scl_colaborador' ] ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Tabla de log de anuncios', 'sportcriss-lite' ); ?></th>
						<td>
							<?php
							global $wpdb;
							$tabla  = $wpdb->prefix . 'scl_ad_log';
							// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$existe = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla ) ) === $tabla;
							echo $existe ? '✅ Activa' : '❌ No encontrada';
							?>
						</td>
					</tr>
				</table>

				<h2 style="color:#dc3545"><?php esc_html_e( 'Zona de peligro', 'sportcriss-lite' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Limpiar transients', 'sportcriss-lite' ); ?></th>
						<td>
							<button type="button" class="button" id="scl_limpiar_transients">
								<?php esc_html_e( 'Limpiar cache de tablas', 'sportcriss-lite' ); ?>
							</button>
							<span id="scl_transients_result" style="margin-left:8px"></span>
							<p class="description"><?php esc_html_e( 'Elimina todos los transients de tablas de posiciones. Se regeneran automáticamente en la próxima visita.', 'sportcriss-lite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Recalcular todas las tablas', 'sportcriss-lite' ); ?></th>
						<td>
							<button type="button" class="button" id="scl_recalcular_todo">
								<?php esc_html_e( 'Recalcular todas las tablas', 'sportcriss-lite' ); ?>
							</button>
							<span id="scl_recalcular_result" style="margin-left:8px"></span>
							<p class="description"><?php esc_html_e( 'Recalcula la tabla de posiciones de cada temporada activa. Útil si se detectan inconsistencias.', 'sportcriss-lite' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Guardar configuración', 'sportcriss-lite' ) ); ?>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( wp_create_nonce( 'scl_admin_actions' ) ); ?>';

			$('#scl_limpiar_transients').on('click', function() {
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Limpiando…', 'sportcriss-lite' ) ); ?>');
				$.post(ajaxurl, {
					action: 'scl_limpiar_transients',
					nonce:  nonce,
				}, function(res) {
					$('#scl_transients_result').text(
						res.success
							? '✅ ' + res.data.eliminados + ' transients eliminados'
							: '❌ Error'
					);
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Limpiar cache de tablas', 'sportcriss-lite' ) ); ?>');
				});
			});

			$('#scl_recalcular_todo').on('click', function() {
				if ( ! confirm('<?php echo esc_js( __( '¿Recalcular todas las tablas? Puede tardar unos segundos.', 'sportcriss-lite' ) ); ?>') ) return;
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Calculando…', 'sportcriss-lite' ) ); ?>');
				$.post(ajaxurl, {
					action: 'scl_recalcular_todas_tablas',
					nonce:  nonce,
				}, function(res) {
					$('#scl_recalcular_result').text(
						res.success
							? '✅ ' + res.data.temporadas + ' temporadas recalculadas'
							: '❌ Error'
					);
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Recalcular todas las tablas', 'sportcriss-lite' ) ); ?>');
				});
			});
		});
		</script>
		<?php
	}
}
