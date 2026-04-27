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
require_once SCL_PATH . 'includes/class-scl-dashboard.php';
require_once SCL_PATH . 'includes/class-scl-ajax.php';
require_once SCL_PATH . 'includes/class-scl-public.php';
require_once SCL_PATH . 'includes/class-scl-export.php';

// ---------------------------------------------------------------------------
// Hooks de activación y desactivación
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, 'scl_activar_plugin' );
register_deactivation_hook( __FILE__, 'scl_desactivar_plugin' );

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
	$loader->add_action( 'init',               [ $access, 'bloquear_wp_admin' ],       1 );
	$loader->add_action( 'after_setup_theme',  [ $access, 'ocultar_admin_bar' ] );
	$loader->add_action( 'wp_enqueue_scripts', [ $access, 'eliminar_margen_admin_bar' ] );

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

	// Handlers AJAX
	$ajax = new Scl_Ajax();
	$ajax->registrar_handlers( $loader );

	// Vistas públicas
	$public = new Scl_Public();
	$loader->add_filter( 'template_include', [ $public, 'filtrar_template' ] );
	$loader->add_action( 'wp_enqueue_scripts', [ $public, 'encolar_assets' ] );

	// Exportación visual
	$export = new Scl_Export();
	$export->init();

	$loader->run();
}

scl_run();
