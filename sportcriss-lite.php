<?php
/**
 * Plugin Name:       SportCriss Lite
 * Plugin URI:        https://crissDev.com/sportcriss-lite
 * Description:       Portal matriz centralizado para la gestión de torneos deportivos. Dashboard 100% frontend para organizadores con tabla de posiciones automática, llaves playoff y exportación visual.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            CrissDev
 * Author URI:        https://crissDev.com
 * Text Domain:       sportcriss-lite
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ---------------------------------------------------------------------------
// Constantes del plugin
// ---------------------------------------------------------------------------
define( 'SCL_VERSION',  '1.0.0' );
define( 'SCL_PATH',     plugin_dir_path( __FILE__ ) );
define( 'SCL_URL',      plugins_url( '/', __FILE__ ) );
define( 'SCL_BASENAME', plugin_basename( __FILE__ ) );

// ---------------------------------------------------------------------------
// Carga de clases
// ---------------------------------------------------------------------------
require_once SCL_PATH . 'includes/class-scl-loader.php';
require_once SCL_PATH . 'includes/class-scl-roles.php';
require_once SCL_PATH . 'includes/class-scl-access.php';
require_once SCL_PATH . 'includes/class-scl-admin-menu.php';
require_once SCL_PATH . 'includes/class-scl-cpts.php';
require_once SCL_PATH . 'includes/class-scl-taxonomies.php';
require_once SCL_PATH . 'includes/class-scl-meta-boxes.php';
require_once SCL_PATH . 'includes/class-scl-engine.php';
require_once SCL_PATH . 'includes/class-scl-llave.php';
require_once SCL_PATH . 'includes/class-scl-importer.php';
require_once SCL_PATH . 'includes/class-scl-auth.php';
require_once SCL_PATH . 'includes/class-scl-emails.php';
require_once SCL_PATH . 'includes/class-scl-dashboard.php';
require_once SCL_PATH . 'includes/class-scl-ajax.php';
require_once SCL_PATH . 'includes/class-scl-public.php';
require_once SCL_PATH . 'includes/class-scl-export.php';
require_once SCL_PATH . 'includes/class-scl-ads.php';
require_once SCL_PATH . 'includes/class-scl-ads-metrics.php';
require_once SCL_PATH . 'includes/class-scl-admin-columns.php';
require_once SCL_PATH . 'includes/class-scl-stats.php';

// ---------------------------------------------------------------------------
// Hooks de activación y desactivación
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, 'scl_activar_plugin' );
register_deactivation_hook( __FILE__, 'scl_desactivar_plugin' );

/**
 * Crea (o actualiza) las tablas de estadísticas individuales.
 * Seguro de llamar múltiples veces via dbDelta().
 */
function scl_crear_tablas_stats() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	// Tabla de inscripciones: vincula jugador ↔ equipo ↔ torneo ↔ temporada
	$sql_inscripciones = "CREATE TABLE {$wpdb->prefix}scl_inscripciones (
		id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		jugador_id    BIGINT(20) UNSIGNED NOT NULL,
		equipo_id     BIGINT(20) UNSIGNED NOT NULL,
		torneo_id     BIGINT(20) UNSIGNED NOT NULL,
		temporada_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		organizador_id    BIGINT(20) UNSIGNED NOT NULL,
		activo        TINYINT(1) NOT NULL DEFAULT 1,
		created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY inscripcion_activa (jugador_id, torneo_id, temporada_term_id),
		KEY equipo_torneo (equipo_id, torneo_id),
		KEY organizador_id (organizador_id)
	) $charset_collate;";

	// Tabla de estadísticas: una fila por jugador por partido
	$sql_estadisticas = "CREATE TABLE {$wpdb->prefix}scl_estadisticas (
		id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		partido_id       BIGINT(20) UNSIGNED NOT NULL,
		inscripcion_id   BIGINT(20) UNSIGNED NOT NULL,
		jugador_id       BIGINT(20) UNSIGNED NOT NULL,
		equipo_id        BIGINT(20) UNSIGNED NOT NULL,
		torneo_id        BIGINT(20) UNSIGNED NOT NULL,
		temporada_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		organizador_id   BIGINT(20) UNSIGNED NOT NULL,
		goles            TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
		asistencias      TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
		tarjeta_amarilla TINYINT(1) NOT NULL DEFAULT 0,
		tarjeta_roja     TINYINT(1) NOT NULL DEFAULT 0,
		calificacion     DECIMAL(3,1) DEFAULT NULL,
		es_penales       TINYINT(1) NOT NULL DEFAULT 0,
		created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY partido_jugador (partido_id, jugador_id),
		KEY jugador_torneo (jugador_id, torneo_id),
		KEY torneo_temporada (torneo_id, temporada_term_id),
		KEY organizador_id (organizador_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_inscripciones );
	dbDelta( $sql_estadisticas );
}

/**
 * Rutina de activación:
 * - Registra el rol Organizador
 * - Registra CPTs y taxonomías para que flush conozca sus slugs
 * - Recarga las reglas de reescritura
 */
function scl_activar_plugin() {
	Scl_Roles::registrar();
	Scl_Cpts::registrar();
	Scl_Taxonomies::registrar();
	// Rewrite rule para la vista limpia de exportación
	add_rewrite_rule( '^scl-exportar/?$', 'index.php?scl_exportar=1', 'top' );
	flush_rewrite_rules();
	// Tabla de log de publicidad
	Scl_Ads::crear_tabla_ad_log();

	// Tablas de estadísticas individuales
	scl_crear_tablas_stats();

	// Sprint 3: Página del dashboard
	$pagina = get_page_by_path( 'mi-panel' );
	if ( ! $pagina ) {
		wp_insert_post( [
			'post_title'   => 'Mi Panel',
			'post_name'    => 'mi-panel',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[scl_dashboard]',
		] );
	}

	// Página de login frontend
	$login_page = get_page_by_path( 'acceso' );
	if ( ! $login_page ) {
		wp_insert_post( [
			'post_title'   => 'Acceso',
			'post_name'    => 'acceso',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[scl_login]',
		] );
	}

	// Página de registro de nuevos organizadores
	$registro_page = get_page_by_path( 'registro' );
	if ( ! $registro_page ) {
		wp_insert_post( [
			'post_title'   => 'Registro',
			'post_name'    => 'registro',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[scl_registro]',
		] );
	}
}

/**
 * Rutina de desactivación: limpia las reglas de reescritura.
 * No elimina datos ni roles (eso queda para uninstall).
 */
function scl_desactivar_plugin() {
	flush_rewrite_rules();
}

// ---------------------------------------------------------------------------
// Arranque del plugin
// ---------------------------------------------------------------------------
/**
 * Instancia el Loader, registra todos los hooks de cada módulo y ejecuta.
 */
function scl_run() {
	$loader = new Scl_Loader();

	// Acceso y bloqueo de wp-admin para el rol Organizador
	$access = new Scl_Access();
	$loader->add_action( 'init',               [ $access, 'bloquear_wp_admin' ],         1 );
	$loader->add_action( 'after_setup_theme',  [ $access, 'ocultar_admin_bar' ] );
	$loader->add_action( 'wp_enqueue_scripts', [ $access, 'eliminar_margen_admin_bar' ] );
	$loader->add_action( 'login_init',         [ $access, 'interceptar_wp_login' ] );
	$loader->add_filter( 'login_redirect',     [ $access, 'redirigir_tras_login' ], 10, 3 );

	// Menú unificado en wp-admin (solo para administrator)
	$admin_menu = new Scl_Admin_Menu();
	$loader->add_action( 'admin_menu', [ $admin_menu, 'registrar' ] );

	// Registro de CPTs y taxonomías en cada carga
	$loader->add_action( 'init', [ 'Scl_Cpts',       'registrar' ] );
	$loader->add_action( 'init', [ 'Scl_Taxonomies', 'registrar' ] );

	// Meta boxes (solo en admin)
	$meta_boxes = new Scl_Meta_Boxes();
	$loader->add_action( 'add_meta_boxes', [ $meta_boxes, 'registrar' ] );
	$loader->add_action( 'save_post',      [ $meta_boxes, 'guardar' ], 10, 2 );

	// Motor de cálculo de tabla
	$engine = new Scl_Engine();
	$loader->add_action( 'save_post_scl_partido', [ $engine, 'recalcular_si_aplica' ], 10, 2 );

	// Motor de llaves playoff
	$llave = new Scl_Llave();
	$loader->add_action( 'save_post_scl_partido', [ $llave, 'evaluar_llave' ], 20, 2 );

	// Dashboard frontend
	$dashboard = new Scl_Dashboard();
	$loader->add_filter( 'query_vars',      [ $dashboard, 'registrar_query_vars' ] );
	$loader->add_action( 'template_redirect', [ $dashboard, 'despachar' ] );
	$loader->add_action( 'wp_enqueue_scripts', [ $dashboard, 'encolar_assets' ] );

	// Columnas personalizadas en wp-admin
	$admin_columns = new Scl_Admin_Columns();
	$admin_columns->registrar( $loader );

	// Handlers AJAX
	$ajax = new Scl_Ajax();
	$ajax->registrar_handlers( $loader );

	// Módulo de autenticación frontend (shortcode [scl_login])
	$auth = new Scl_Auth();
	$loader->add_action( 'init', [ $auth, 'init' ] );

	// Vistas públicas (shortcodes)
	$public = new Scl_Public();
	$loader->add_action( 'init', [ $public, 'init' ] );

	// Exportación visual
	$export = new Scl_Export();
	$export->init();

	// Módulo de publicidad
	$ads = new Scl_Ads();
	$loader->add_action( 'init', [ $ads, 'init' ] );

	// Handler AJAX de exportación de métricas (GET-based, sin nonce de formulario)
	$loader->add_action( 'wp_ajax_scl_exportar_metricas_anunciante',
		[ new Scl_Ads_Metrics(), 'exportar_csv_anunciante' ]
	);

	$loader->run();
}

scl_run();
