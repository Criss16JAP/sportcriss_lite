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
		self::registrar_partido();
		self::registrar_llave();
		self::registrar_grupo();
		self::registrar_anunciante();
		self::registrar_anuncio();
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

	/**
	 * CPT: scl_grupo
	 *
	 * Grupo de una fase de grupos. Usa post_parent para vincularse al scl_torneo.
	 */
	private static function registrar_grupo() {
		$labels = [
			'name'               => __( 'Grupos',              'sportcriss-lite' ),
			'singular_name'      => __( 'Grupo',               'sportcriss-lite' ),
			'add_new'            => __( 'Añadir grupo',        'sportcriss-lite' ),
			'add_new_item'       => __( 'Añadir nuevo grupo',  'sportcriss-lite' ),
			'edit_item'          => __( 'Editar grupo',        'sportcriss-lite' ),
			'new_item'           => __( 'Nuevo grupo',         'sportcriss-lite' ),
			'view_item'          => __( 'Ver grupo',           'sportcriss-lite' ),
			'search_items'       => __( 'Buscar grupos',       'sportcriss-lite' ),
			'not_found'          => __( 'No se encontraron grupos', 'sportcriss-lite' ),
		];

		register_post_type( 'scl_grupo', [
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => false,
			'hierarchical'  => true,
			'show_in_rest'  => false,
			'show_in_menu'  => false,
			'supports'      => [ 'title' ],
			'rewrite'       => [ 'slug' => 'grupo', 'with_front' => false ],
		] );
	}

	/**
	 * CPT: scl_anunciante
	 * Solo editable por administrator. No visible en frontend.
	 */
	private static function registrar_anunciante() {
		register_post_type( 'scl_anunciante', [
			'labels' => [
				'name'          => __( 'Anunciantes',       'sportcriss-lite' ),
				'singular_name' => __( 'Anunciante',        'sportcriss-lite' ),
				'add_new_item'  => __( 'Nuevo anunciante',  'sportcriss-lite' ),
				'edit_item'     => __( 'Editar anunciante', 'sportcriss-lite' ),
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'show_in_rest' => false,
			'supports'     => [ 'title' ],
			'capabilities' => [
				'edit_post'     => 'manage_options',
				'delete_post'   => 'manage_options',
				'publish_posts' => 'manage_options',
				'edit_posts'    => 'manage_options',
				'edit_others_posts'   => 'manage_options',
				'delete_posts'        => 'manage_options',
				'delete_others_posts' => 'manage_options',
				'read_private_posts'  => 'manage_options',
				'edit_private_posts'  => 'manage_options',
				'delete_private_posts'=> 'manage_options',
			],
		] );
	}

	/**
	 * CPT: scl_anuncio
	 * Jerárquico: post_parent = scl_anunciante ID.
	 * Solo editable por administrator.
	 */
	private static function registrar_anuncio() {
		register_post_type( 'scl_anuncio', [
			'labels' => [
				'name'          => __( 'Anuncios',       'sportcriss-lite' ),
				'singular_name' => __( 'Anuncio',        'sportcriss-lite' ),
				'add_new_item'  => __( 'Nuevo anuncio',  'sportcriss-lite' ),
				'edit_item'     => __( 'Editar anuncio', 'sportcriss-lite' ),
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'show_in_rest' => false,
			'hierarchical' => true,
			'supports'     => [ 'title' ],
			'capabilities' => [
				'edit_post'     => 'manage_options',
				'delete_post'   => 'manage_options',
				'publish_posts' => 'manage_options',
				'edit_posts'    => 'manage_options',
				'edit_others_posts'   => 'manage_options',
				'delete_posts'        => 'manage_options',
				'delete_others_posts' => 'manage_options',
				'read_private_posts'  => 'manage_options',
				'edit_private_posts'  => 'manage_options',
				'delete_private_posts'=> 'manage_options',
			],
		] );
	}
}
