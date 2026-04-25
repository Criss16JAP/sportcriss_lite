<?php
/**
 * Registro de taxonomías y sus term metas.
 *
 * Las tres taxonomías se aplican únicamente al CPT `scl_partido`.
 * Todas son no jerárquicas (tipo "etiqueta") y no exponen REST API.
 *
 * La taxonomía `scl_fase` tiene dos term metas que controlan el
 * comportamiento del motor de cálculo:
 *   - scl_fase_suma_puntos: si true, los partidos de esta fase alimentan
 *     la tabla de posiciones.
 *   - scl_fase_es_playoff: si true, habilita la creación de llaves y
 *     permite penales.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Taxonomies
 */
class Scl_Taxonomies {

	/**
	 * Registra todas las taxonomías y sus term metas.
	 *
	 * Llamado desde:
	 *   - register_activation_hook (para flush correcto al activar)
	 *   - hook 'init' (en cada carga normal de WordPress)
	 */
	public static function registrar() {
		self::registrar_fase();
		self::registrar_jornada();
		self::registrar_grupo();
		self::registrar_term_metas_fase();
	}

	// -----------------------------------------------------------------------
	// Taxonomías individuales
	// -----------------------------------------------------------------------

	/**
	 * Taxonomía: scl_fase
	 *
	 * Identifica la etapa del torneo a la que pertenece un partido.
	 * Ejemplos: "Grupos", "Cuartos de Final", "Semifinal", "Final".
	 *
	 * Sus term metas (suma_puntos, es_playoff) controlan el comportamiento
	 * del motor de cálculo y de las llaves.
	 */
	private static function registrar_fase() {
		$labels = [
			'name'              => __( 'Fases',           'sportcriss-lite' ),
			'singular_name'     => __( 'Fase',            'sportcriss-lite' ),
			'search_items'      => __( 'Buscar fases',    'sportcriss-lite' ),
			'all_items'         => __( 'Todas las fases', 'sportcriss-lite' ),
			'edit_item'         => __( 'Editar fase',     'sportcriss-lite' ),
			'update_item'       => __( 'Actualizar fase', 'sportcriss-lite' ),
			'add_new_item'      => __( 'Añadir nueva fase', 'sportcriss-lite' ),
			'new_item_name'     => __( 'Nombre de la nueva fase', 'sportcriss-lite' ),
			'menu_name'         => __( 'Fases',           'sportcriss-lite' ),
		];

		register_taxonomy( 'scl_fase', [ 'scl_partido' ], [
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => 'fase', 'with_front' => false ],
		] );
	}

	/**
	 * Taxonomía: scl_jornada
	 *
	 * Agrupa partidos de la fase de grupos por fecha/jornada.
	 * Ejemplos: "Fecha 1", "Fecha 2", "Jornada 3".
	 * Es opcional: los partidos de playoff NO requieren jornada.
	 */
	private static function registrar_jornada() {
		$labels = [
			'name'              => __( 'Jornadas',           'sportcriss-lite' ),
			'singular_name'     => __( 'Jornada',            'sportcriss-lite' ),
			'search_items'      => __( 'Buscar jornadas',    'sportcriss-lite' ),
			'all_items'         => __( 'Todas las jornadas', 'sportcriss-lite' ),
			'edit_item'         => __( 'Editar jornada',     'sportcriss-lite' ),
			'update_item'       => __( 'Actualizar jornada', 'sportcriss-lite' ),
			'add_new_item'      => __( 'Añadir nueva jornada', 'sportcriss-lite' ),
			'new_item_name'     => __( 'Nombre de la nueva jornada', 'sportcriss-lite' ),
			'menu_name'         => __( 'Jornadas',           'sportcriss-lite' ),
		];

		register_taxonomy( 'scl_jornada', [ 'scl_partido' ], [
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => 'jornada', 'with_front' => false ],
		] );
	}

	/**
	 * Taxonomía: scl_grupo
	 *
	 * Subdivide la fase de grupos en grupos paralelos.
	 * Ejemplos: "Grupo A", "Grupo B", "Zona Norte".
	 * Es opcional: solo aplica a torneos con múltiples grupos.
	 */
	private static function registrar_grupo() {
		$labels = [
			'name'              => __( 'Grupos',           'sportcriss-lite' ),
			'singular_name'     => __( 'Grupo',            'sportcriss-lite' ),
			'search_items'      => __( 'Buscar grupos',    'sportcriss-lite' ),
			'all_items'         => __( 'Todos los grupos', 'sportcriss-lite' ),
			'edit_item'         => __( 'Editar grupo',     'sportcriss-lite' ),
			'update_item'       => __( 'Actualizar grupo', 'sportcriss-lite' ),
			'add_new_item'      => __( 'Añadir nuevo grupo', 'sportcriss-lite' ),
			'new_item_name'     => __( 'Nombre del nuevo grupo', 'sportcriss-lite' ),
			'menu_name'         => __( 'Grupos',           'sportcriss-lite' ),
		];

		register_taxonomy( 'scl_grupo', [ 'scl_partido' ], [
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => 'grupo', 'with_front' => false ],
		] );
	}

	// -----------------------------------------------------------------------
	// Term metas
	// -----------------------------------------------------------------------

	/**
	 * Registra los term metas de la taxonomía scl_fase.
	 *
	 * Se registran con register_term_meta() para habilitar sanitización
	 * automática y soporte de tipo en la base de datos.
	 *
	 * scl_fase_suma_puntos (bool, default: true):
	 *   Si es true, los partidos de esta fase alimentan el cálculo de la
	 *   tabla de posiciones. Típico de la fase de grupos.
	 *
	 * scl_fase_es_playoff (bool, default: false):
	 *   Si es true, permite crear llaves (scl_llave) y penales para esta fase.
	 *   Mutuamente complementario con suma_puntos, pero no exclusivo.
	 */
	private static function registrar_term_metas_fase() {
		register_term_meta( 'scl_fase', 'scl_fase_suma_puntos', [
			'type'              => 'boolean',
			'description'       => __( 'Los partidos de esta fase suman a la tabla de posiciones', 'sportcriss-lite' ),
			'single'            => true,
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => false,
		] );

		register_term_meta( 'scl_fase', 'scl_fase_es_playoff', [
			'type'              => 'boolean',
			'description'       => __( 'Esta fase habilita llaves de playoff y penales', 'sportcriss-lite' ),
			'single'            => true,
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => false,
		] );
	}
}
