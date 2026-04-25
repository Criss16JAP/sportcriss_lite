<?php
/**
 * Registro de Custom Post Types del plugin.
 *
 * Todos los CPTs usan show_in_rest => false porque la API REST de WordPress
 * no se utiliza en este plugin (todo AJAX va por admin-ajax.php, regla #3).
 *
 * Los slugs de los CPTs determinan las URLs públicas de los single y archive.
 * Se mantienen cortos y en español para URLs amigables.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Cpts
 */
class Scl_Cpts {

	/**
	 * Registra todos los CPTs del plugin.
	 *
	 * Llamado desde:
	 *   - register_activation_hook (para flush correcto al activar)
	 *   - hook 'init' (en cada carga normal de WordPress)
	 */
	public static function registrar() {
		self::registrar_equipo();
		self::registrar_torneo();
		self::registrar_temporada();
		self::registrar_partido();
		self::registrar_llave();
	}

	// -----------------------------------------------------------------------
	// CPTs individuales
	// -----------------------------------------------------------------------

	/**
	 * CPT: scl_equipo
	 *
	 * Equipos deportivos. Pertenecen al Organizador que los creó (post_author).
	 * No existe pool global: cada Organizador gestiona únicamente sus equipos.
	 */
	private static function registrar_equipo() {
		$labels = [
			'name'               => __( 'Equipos',              'sportcriss-lite' ),
			'singular_name'      => __( 'Equipo',               'sportcriss-lite' ),
			'add_new'            => __( 'Añadir equipo',        'sportcriss-lite' ),
			'add_new_item'       => __( 'Añadir nuevo equipo',  'sportcriss-lite' ),
			'edit_item'          => __( 'Editar equipo',        'sportcriss-lite' ),
			'new_item'           => __( 'Nuevo equipo',         'sportcriss-lite' ),
			'view_item'          => __( 'Ver equipo',           'sportcriss-lite' ),
			'search_items'       => __( 'Buscar equipos',       'sportcriss-lite' ),
			'not_found'          => __( 'No se encontraron equipos', 'sportcriss-lite' ),
			'not_found_in_trash' => __( 'No hay equipos en la papelera', 'sportcriss-lite' ),
			'menu_name'          => __( 'Equipos',              'sportcriss-lite' ),
		];

		register_post_type( 'scl_equipo', [
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => true,
			'hierarchical'  => false,
			'show_in_rest'  => false,
			'show_in_menu'  => false,
			'supports'      => [ 'title', 'thumbnail' ],
			'rewrite'       => [ 'slug' => 'equipo', 'with_front' => false ],
		] );
	}

	/**
	 * CPT: scl_torneo
	 *
	 * Torneos deportivos. Cada Organizador gestiona sus propios torneos.
	 * Es el objeto padre de las temporadas.
	 */
	private static function registrar_torneo() {
		$labels = [
			'name'               => __( 'Torneos',              'sportcriss-lite' ),
			'singular_name'      => __( 'Torneo',               'sportcriss-lite' ),
			'add_new'            => __( 'Añadir torneo',        'sportcriss-lite' ),
			'add_new_item'       => __( 'Añadir nuevo torneo',  'sportcriss-lite' ),
			'edit_item'          => __( 'Editar torneo',        'sportcriss-lite' ),
			'new_item'           => __( 'Nuevo torneo',         'sportcriss-lite' ),
			'view_item'          => __( 'Ver torneo',           'sportcriss-lite' ),
			'search_items'       => __( 'Buscar torneos',       'sportcriss-lite' ),
			'not_found'          => __( 'No se encontraron torneos', 'sportcriss-lite' ),
			'not_found_in_trash' => __( 'No hay torneos en la papelera', 'sportcriss-lite' ),
			'menu_name'          => __( 'Torneos',              'sportcriss-lite' ),
		];

		register_post_type( 'scl_torneo', [
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => true,
			'hierarchical'  => false,
			'show_in_rest'  => false,
			'show_in_menu'  => false,
			'supports'      => [ 'title', 'thumbnail' ],
			'rewrite'       => [ 'slug' => 'torneo', 'with_front' => false ],
		] );
	}

	/**
	 * CPT: scl_temporada
	 *
	 * Temporadas de un torneo. Usa post_parent para vincularse al scl_torneo.
	 * Es jerárquico (hierarchical: true) para que WordPress gestione post_parent.
	 * Ejemplos: "Apertura 2025", "Clausura 2025".
	 */
	private static function registrar_temporada() {
		$labels = [
			'name'               => __( 'Temporadas',              'sportcriss-lite' ),
			'singular_name'      => __( 'Temporada',               'sportcriss-lite' ),
			'add_new'            => __( 'Añadir temporada',        'sportcriss-lite' ),
			'add_new_item'       => __( 'Añadir nueva temporada',  'sportcriss-lite' ),
			'edit_item'          => __( 'Editar temporada',        'sportcriss-lite' ),
			'new_item'           => __( 'Nueva temporada',         'sportcriss-lite' ),
			'view_item'          => __( 'Ver temporada',           'sportcriss-lite' ),
			'search_items'       => __( 'Buscar temporadas',       'sportcriss-lite' ),
			'not_found'          => __( 'No se encontraron temporadas', 'sportcriss-lite' ),
			'not_found_in_trash' => __( 'No hay temporadas en la papelera', 'sportcriss-lite' ),
			'menu_name'          => __( 'Temporadas',              'sportcriss-lite' ),
		];

		register_post_type( 'scl_temporada', [
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => false,
			'hierarchical'  => true,
			'show_in_rest'  => false,
			'show_in_menu'  => false,
			'supports'      => [ 'title', 'page-attributes' ],
			'rewrite'       => [ 'slug' => 'temporada', 'with_front' => false ],
		] );
	}

	/**
	 * CPT: scl_partido
	 *
	 * Partidos individuales. Vinculados a una temporada, equipos y fase.
	 * No tienen archivo propio (has_archive: false): la vista de partidos
	 * se muestra dentro del single del torneo/temporada.
	 */
	private static function registrar_partido() {
		$labels = [
			'name'               => __( 'Partidos',              'sportcriss-lite' ),
			'singular_name'      => __( 'Partido',               'sportcriss-lite' ),
			'add_new'            => __( 'Añadir partido',        'sportcriss-lite' ),
			'add_new_item'       => __( 'Añadir nuevo partido',  'sportcriss-lite' ),
			'edit_item'          => __( 'Editar partido',        'sportcriss-lite' ),
			'new_item'           => __( 'Nuevo partido',         'sportcriss-lite' ),
			'view_item'          => __( 'Ver partido',           'sportcriss-lite' ),
			'search_items'       => __( 'Buscar partidos',       'sportcriss-lite' ),
			'not_found'          => __( 'No se encontraron partidos', 'sportcriss-lite' ),
			'not_found_in_trash' => __( 'No hay partidos en la papelera', 'sportcriss-lite' ),
			'menu_name'          => __( 'Partidos',              'sportcriss-lite' ),
		];

		register_post_type( 'scl_partido', [
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => false,
			'hierarchical'  => false,
			'show_in_rest'  => false,
			'show_in_menu'  => false,
			'supports'      => [ 'title' ],
			'rewrite'       => [ 'slug' => 'partido', 'with_front' => false ],
		] );
	}

	/**
	 * CPT: scl_llave
	 *
	 * Llaves de playoff (partido único o ida y vuelta).
	 * No tienen archivo propio: se gestionan exclusivamente desde el dashboard.
	 */
	private static function registrar_llave() {
		$labels = [
			'name'               => __( 'Llaves',              'sportcriss-lite' ),
			'singular_name'      => __( 'Llave',               'sportcriss-lite' ),
			'add_new'            => __( 'Añadir llave',        'sportcriss-lite' ),
			'add_new_item'       => __( 'Añadir nueva llave',  'sportcriss-lite' ),
			'edit_item'          => __( 'Editar llave',        'sportcriss-lite' ),
			'new_item'           => __( 'Nueva llave',         'sportcriss-lite' ),
			'view_item'          => __( 'Ver llave',           'sportcriss-lite' ),
			'search_items'       => __( 'Buscar llaves',       'sportcriss-lite' ),
			'not_found'          => __( 'No se encontraron llaves', 'sportcriss-lite' ),
			'not_found_in_trash' => __( 'No hay llaves en la papelera', 'sportcriss-lite' ),
			'menu_name'          => __( 'Llaves',              'sportcriss-lite' ),
		];

		register_post_type( 'scl_llave', [
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => false,
			'hierarchical'  => false,
			'show_in_rest'  => false,
			'show_in_menu'  => false,
			'supports'      => [ 'title' ],
			'rewrite'       => [ 'slug' => 'llave', 'with_front' => false ],
		] );
	}
}
