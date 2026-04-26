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
		self::registrar_jornada();
		self::registrar_temporada();
		self::registrar_term_metas_temporada();
	}

	// -----------------------------------------------------------------------
	// Taxonomías individuales
	// -----------------------------------------------------------------------

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
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => 'jornada', 'with_front' => false ],
		] );
	}

	/**
	 * Taxonomía: scl_temporada
	 *
	 * Temporadas de un torneo. Aplicada a partidos.
	 */
	private static function registrar_temporada() {
		$labels = [
			'name'              => __( 'Temporadas',           'sportcriss-lite' ),
			'singular_name'     => __( 'Temporada',            'sportcriss-lite' ),
			'search_items'      => __( 'Buscar temporadas',    'sportcriss-lite' ),
			'all_items'         => __( 'Todas las temporadas', 'sportcriss-lite' ),
			'edit_item'         => __( 'Editar temporada',     'sportcriss-lite' ),
			'update_item'       => __( 'Actualizar temporada', 'sportcriss-lite' ),
			'add_new_item'      => __( 'Añadir nueva temporada', 'sportcriss-lite' ),
			'new_item_name'     => __( 'Nombre de la nueva temporada', 'sportcriss-lite' ),
			'menu_name'         => __( 'Temporadas',           'sportcriss-lite' ),
			'not_found'         => __( 'No se encontraron temporadas', 'sportcriss-lite' ),
		];

		register_taxonomy( 'scl_temporada', [ 'scl_partido' ], [
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => 'temporada', 'with_front' => false ],
		] );
	}



	// -----------------------------------------------------------------------
	// Term metas
	// -----------------------------------------------------------------------

	/**
	 * Registra los term metas de la taxonomía scl_temporada.
	 */
	private static function registrar_term_metas_temporada() {

		register_term_meta( 'scl_temporada', 'scl_temporada_estado', [
			'type'       => 'string',
			'single'     => true,
			'default'    => 'activa',
		] );

		register_term_meta( 'scl_temporada', 'scl_temporada_anio', [
			'type'       => 'integer',
			'single'     => true,
			'default'    => (int) date( 'Y' ),
		] );
	}

}
