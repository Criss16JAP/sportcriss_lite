<?php
/**
 * Vistas públicas: shortcodes para Elementor y cualquier tema.
 *
 * Los shortcodes inyectan datos dinámicos. El layout de página lo controla
 * Elementor u el tema — este archivo solo gestiona componentes de datos.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ---------------------------------------------------------------------------
// Función auxiliar global
// ---------------------------------------------------------------------------

/**
 * Devuelve el term_id de la temporada más reciente con partidos en el torneo.
 */
function scl_get_temporada_activa( int $torneo_id ): int {
	$partidos = get_posts( [
		'post_type'      => 'scl_partido',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'meta_value',
		'meta_key'       => 'scl_partido_fecha',
		'order'          => 'DESC',
		'meta_query'     => [ [
			'key'   => 'scl_partido_torneo_id',
			'value' => $torneo_id,
			'type'  => 'NUMERIC',
		] ],
	] );
	if ( empty( $partidos ) ) return 0;

	$terms = wp_get_post_terms( $partidos[0]->ID, 'scl_temporada' );
	return ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? (int) $terms[0]->term_id : 0;
}

// ---------------------------------------------------------------------------
// Clase principal
// ---------------------------------------------------------------------------

/**
 * Class Scl_Public
 */
class Scl_Public {

	/**
	 * Registra shortcodes y assets.
	 * Se llama vía hook 'init' desde sportcriss-lite.php.
	 */
	public function init(): void {
		add_shortcode( 'scl_tabla_posiciones', [ $this, 'shortcode_tabla'    ] );
		add_shortcode( 'scl_resultados',       [ $this, 'shortcode_resultados' ] );
		add_shortcode( 'scl_proximos',         [ $this, 'shortcode_proximos' ] );
		add_shortcode( 'scl_perfil_equipo',    [ $this, 'shortcode_equipo'   ] );
		add_shortcode( 'scl_torneos',          [ $this, 'shortcode_torneos'  ] );
		add_action( 'wp_enqueue_scripts',      [ $this, 'enqueue_assets'     ] );
	}

	/**
	 * Encola public.css y public.js solo en páginas que contienen shortcodes del plugin.
	 */
	public function enqueue_assets(): void {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) return;

		$shortcodes = [
			'scl_tabla_posiciones', 'scl_resultados',
			'scl_proximos', 'scl_perfil_equipo', 'scl_torneos', 'scl_anuncio',
		];

		$tiene = false;
		foreach ( $shortcodes as $sc ) {
			if ( has_shortcode( $post->post_content, $sc ) ) {
				$tiene = true;
				break;
			}
		}
		if ( ! $tiene ) return;

		wp_enqueue_style(
			'scl-public',
			SCL_URL . 'assets/css/public.css',
			[],
			SCL_VERSION
		);
		wp_enqueue_script(
			'scl-public',
			SCL_URL . 'assets/js/public.js',
			[],
			SCL_VERSION,
			true
		);
		wp_localize_script( 'scl-public', 'scl_pub', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		] );
	}

	// -------------------------------------------------------------------------
	// Métodos heredados de la implementación anterior (sin lógica real)
	// -------------------------------------------------------------------------

	/** @deprecated Sustituido por init() + shortcodes */
	public function filtrar_template( $template ) {
		return $template;
	}

	/** @deprecated Sustituido por enqueue_assets() */
	public function encolar_assets(): void {}

	// -------------------------------------------------------------------------
	// Shortcodes
	// -------------------------------------------------------------------------

	/**
	 * [scl_tabla_posiciones torneo_id="5" temporada="apertura-2025" grupo="0"
	 *                       mostrar_escudos="si" limite="0"]
	 */
	public function shortcode_tabla( array $atts ): string {
		$atts = shortcode_atts( [
			'torneo_id'       => 0,
			'temporada'       => '',
			'grupo'           => 0,
			'mostrar_escudos' => 'si',
			'limite'          => 0,
		], $atts, 'scl_tabla_posiciones' );

		$torneo_id = absint( $atts['torneo_id'] );
		if ( ! $torneo_id ) {
			return '<p class="scl-pub-error">scl_tabla_posiciones: torneo_id es obligatorio.</p>';
		}

		$temporada_term_id = 0;
		if ( $atts['temporada'] ) {
			$term = get_term_by( 'slug', $atts['temporada'], 'scl_temporada' )
			     ?: get_term_by( 'name', $atts['temporada'], 'scl_temporada' );
			if ( $term ) $temporada_term_id = (int) $term->term_id;
		} else {
			$temporada_term_id = scl_get_temporada_activa( $torneo_id );
		}

		$grupo_id = absint( $atts['grupo'] );
		$tabla    = Scl_Export::get_tabla_render( $torneo_id, $temporada_term_id, $grupo_id );

		if ( empty( $tabla ) ) {
			return '<div class="scl-pub-empty">No hay datos de tabla disponibles aún.</div>';
		}

		if ( (int) $atts['limite'] > 0 ) {
			$tabla = array_slice( $tabla, 0, (int) $atts['limite'] );
		}

		$mostrar_escudos = ( 'si' === $atts['mostrar_escudos'] );
		$torneo          = get_post( $torneo_id );
		$temporada       = $temporada_term_id ? get_term( $temporada_term_id, 'scl_temporada' ) : null;
		$grupo           = $grupo_id ? get_post( $grupo_id ) : null;

		ob_start();
		include SCL_PATH . 'templates/public/tabla-posiciones.php';
		return ob_get_clean();
	}

	/**
	 * [scl_resultados torneo_id="5" temporada="apertura-2025" limite="10" mostrar_escudos="si"]
	 */
	public function shortcode_resultados( array $atts ): string {
		$atts = shortcode_atts( [
			'torneo_id'       => 0,
			'temporada'       => '',
			'limite'          => 10,
			'mostrar_escudos' => 'si',
		], $atts, 'scl_resultados' );

		$torneo_id = absint( $atts['torneo_id'] );
		if ( ! $torneo_id ) {
			return '<p class="scl-pub-error">scl_resultados: torneo_id es obligatorio.</p>';
		}

		$meta_query = [
			[ 'key' => 'scl_partido_torneo_id', 'value' => $torneo_id, 'type' => 'NUMERIC' ],
			[ 'key' => 'scl_partido_estado',    'value' => 'finalizado' ],
		];

		$tax_query = [];
		if ( $atts['temporada'] ) {
			$term = get_term_by( 'slug', $atts['temporada'], 'scl_temporada' )
			     ?: get_term_by( 'name', $atts['temporada'], 'scl_temporada' );
			if ( $term ) {
				$tax_query[] = [ 'taxonomy' => 'scl_temporada', 'terms' => (int) $term->term_id ];
			}
		}

		$query_args = [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limite'] ),
			'meta_query'     => $meta_query,
			'orderby'        => 'meta_value',
			'meta_key'       => 'scl_partido_fecha',
			'order'          => 'DESC',
		];
		if ( $tax_query ) $query_args['tax_query'] = $tax_query;

		$partidos = get_posts( $query_args );

		if ( empty( $partidos ) ) {
			return '<div class="scl-pub-empty">No hay resultados disponibles aún.</div>';
		}

		$mostrar_escudos = ( 'si' === $atts['mostrar_escudos'] );

		ob_start();
		include SCL_PATH . 'templates/public/resultados.php';
		return ob_get_clean();
	}

	/**
	 * [scl_proximos torneo_id="5" temporada="apertura-2025" limite="5" mostrar_escudos="si"]
	 */
	public function shortcode_proximos( array $atts ): string {
		$atts = shortcode_atts( [
			'torneo_id'       => 0,
			'temporada'       => '',
			'limite'          => 5,
			'mostrar_escudos' => 'si',
		], $atts, 'scl_proximos' );

		$torneo_id = absint( $atts['torneo_id'] );
		if ( ! $torneo_id ) {
			return '<p class="scl-pub-error">scl_proximos: torneo_id es obligatorio.</p>';
		}

		$meta_query = [
			'relation' => 'AND',
			[ 'key' => 'scl_partido_torneo_id', 'value' => $torneo_id, 'type' => 'NUMERIC' ],
			[ 'key' => 'scl_partido_estado',    'value' => 'pendiente' ],
			[
				'key'     => 'scl_partido_fecha',
				'value'   => gmdate( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			],
		];

		$tax_query = [];
		if ( $atts['temporada'] ) {
			$term = get_term_by( 'slug', $atts['temporada'], 'scl_temporada' )
			     ?: get_term_by( 'name', $atts['temporada'], 'scl_temporada' );
			if ( $term ) {
				$tax_query[] = [ 'taxonomy' => 'scl_temporada', 'terms' => (int) $term->term_id ];
			}
		}

		$query_args = [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limite'] ),
			'meta_query'     => $meta_query,
			'orderby'        => 'meta_value',
			'meta_key'       => 'scl_partido_fecha',
			'order'          => 'ASC',
		];
		if ( $tax_query ) $query_args['tax_query'] = $tax_query;

		$partidos = get_posts( $query_args );

		if ( empty( $partidos ) ) {
			return '<div class="scl-pub-empty">No hay próximos partidos programados.</div>';
		}

		$mostrar_escudos = ( 'si' === $atts['mostrar_escudos'] );

		ob_start();
		include SCL_PATH . 'templates/public/proximos.php';
		return ob_get_clean();
	}

	/**
	 * [scl_perfil_equipo equipo_id="12"]
	 * También acepta ?scl_equipo=12 por URL.
	 */
	public function shortcode_equipo( array $atts ): string {
		$atts = shortcode_atts( [
			'equipo_id' => 0,
		], $atts, 'scl_perfil_equipo' );

		$equipo_id = absint( $atts['equipo_id'] ) ?: absint( $_GET['scl_equipo'] ?? 0 );
		if ( ! $equipo_id ) {
			return '<p class="scl-pub-error">scl_perfil_equipo: equipo_id es obligatorio.</p>';
		}

		$equipo = get_post( $equipo_id );
		if ( ! $equipo || 'scl_equipo' !== $equipo->post_type ) {
			return '<div class="scl-pub-empty">Equipo no encontrado.</div>';
		}

		$escudo_id  = (int) get_post_meta( $equipo_id, 'scl_equipo_escudo', true );
		$escudo_url = $escudo_id ? (string) wp_get_attachment_image_url( $escudo_id, 'medium' ) : '';
		$zona       = get_post_meta( $equipo_id, 'scl_equipo_zona', true );

		$partidos_local = get_posts( [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [ [
				'key'   => 'scl_partido_equipo_local_id',
				'value' => $equipo_id,
				'type'  => 'NUMERIC',
			] ],
		] );
		$partidos_visita = get_posts( [
			'post_type'      => 'scl_partido',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [ [
				'key'   => 'scl_partido_equipo_visita_id',
				'value' => $equipo_id,
				'type'  => 'NUMERIC',
			] ],
		] );
		$todos_partidos = array_merge( $partidos_local, $partidos_visita );

		$stats = [ 'PJ' => 0, 'PG' => 0, 'PE' => 0, 'PP' => 0, 'GF' => 0, 'GC' => 0, 'Pts' => 0 ];
		foreach ( $todos_partidos as $p ) {
			if ( 'finalizado' !== get_post_meta( $p->ID, 'scl_partido_estado', true ) ) continue;
			if ( 'grupos' !== get_post_meta( $p->ID, 'scl_partido_tipo_fase', true ) ) continue;

			$gl       = (int) get_post_meta( $p->ID, 'scl_partido_goles_local',  true );
			$gv       = (int) get_post_meta( $p->ID, 'scl_partido_goles_visita', true );
			$es_local = (int) get_post_meta( $p->ID, 'scl_partido_equipo_local_id', true ) === $equipo_id;

			$gf = $es_local ? $gl : $gv;
			$gc = $es_local ? $gv : $gl;

			$stats['PJ']++;
			$stats['GF'] += $gf;
			$stats['GC'] += $gc;

			if ( $gf > $gc )      $stats['PG']++;
			elseif ( $gf === $gc ) $stats['PE']++;
			else                   $stats['PP']++;
		}

		$torneos_ids = array_unique( array_filter( array_map( fn( $p ) =>
			(int) get_post_meta( $p->ID, 'scl_partido_torneo_id', true ),
			$todos_partidos
		) ) );

		ob_start();
		include SCL_PATH . 'templates/public/perfil-equipo.php';
		return ob_get_clean();
	}

	/**
	 * [scl_torneos estado="todos" limite="12"]
	 */
	public function shortcode_torneos( array $atts ): string {
		$atts = shortcode_atts( [
			'estado' => 'todos',
			'limite' => 12,
		], $atts, 'scl_torneos' );

		$torneos = get_posts( [
			'post_type'      => 'scl_torneo',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limite'] ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		if ( empty( $torneos ) ) {
			return '<div class="scl-pub-empty">No hay torneos disponibles.</div>';
		}

		ob_start();
		include SCL_PATH . 'templates/public/directorio-torneos.php';
		return ob_get_clean();
	}
}
