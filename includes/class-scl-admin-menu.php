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



		// Submenú: Configuración (placeholder — completado en Sprint 11)
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

	/**
	 * Página de configuración del plugin.
	 * Placeholder hasta Sprint 11 (integración con CD License Server).
	 */
	public function render_configuracion() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'sportcriss-lite' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Configuración de SportCriss Lite', 'sportcriss-lite' ); ?></h1>
			<p><?php esc_html_e( 'Próximamente habrá más opciones de configuración aquí.', 'sportcriss-lite' ); ?></p>
		</div>
		<?php
	}
}
