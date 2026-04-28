<?php
/**
 * Columnas personalizadas en los listados de CPTs de wp-admin.
 *
 * Permite al Administrador_Portal ver de un vistazo la información clave
 * de cada registro sin necesidad de abrir el detalle del post.
 *
 * Sprint 12 — QA y Seguridad.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Admin_Columns {

	/**
	 * Registra todos los filtros y acciones de columnas en el loader.
	 *
	 * @param Scl_Loader $loader
	 */
	public function registrar( Scl_Loader $loader ): void {
		// scl_torneo
		$loader->add_filter( 'manage_scl_torneo_posts_columns',        [ $this, 'columnas_torneo' ] );
		$loader->add_action( 'manage_scl_torneo_posts_custom_column',  [ $this, 'render_torneo'  ], 10, 2 );

		// scl_partido
		$loader->add_filter( 'manage_scl_partido_posts_columns',       [ $this, 'columnas_partido' ] );
		$loader->add_action( 'manage_scl_partido_posts_custom_column', [ $this, 'render_partido'   ], 10, 2 );

		// scl_equipo
		$loader->add_filter( 'manage_scl_equipo_posts_columns',        [ $this, 'columnas_equipo' ] );
		$loader->add_action( 'manage_scl_equipo_posts_custom_column',  [ $this, 'render_equipo'   ], 10, 2 );

		// scl_anuncio
		$loader->add_filter( 'manage_scl_anuncio_posts_columns',       [ $this, 'columnas_anuncio' ] );
		$loader->add_action( 'manage_scl_anuncio_posts_custom_column', [ $this, 'render_anuncio'   ], 10, 2 );

		// scl_anunciante
		$loader->add_filter( 'manage_scl_anunciante_posts_columns',       [ $this, 'columnas_anunciante' ] );
		$loader->add_action( 'manage_scl_anunciante_posts_custom_column', [ $this, 'render_anunciante'   ], 10, 2 );
	}

	// -----------------------------------------------------------------------
	// scl_torneo
	// -----------------------------------------------------------------------

	public function columnas_torneo( array $cols ): array {
		$nuevo = [];
		foreach ( $cols as $k => $v ) {
			$nuevo[ $k ] = $v;
			if ( 'title' === $k ) {
				$nuevo['scl_siglas']      = 'Siglas';
				$nuevo['scl_organizador'] = 'Organizador';
				$nuevo['scl_grupos']      = 'Grupos';
			}
		}
		return $nuevo;
	}

	public function render_torneo( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'scl_siglas':
				echo esc_html( get_post_meta( $post_id, 'scl_torneo_siglas', true ) ?: '—' );
				break;
			case 'scl_organizador':
				$autor = (int) get_post_field( 'post_author', $post_id );
				$user  = get_userdata( $autor );
				echo $user ? esc_html( $user->display_name ) : '—';
				break;
			case 'scl_grupos':
				$n = count( get_posts( [
					'post_type'      => 'scl_grupo',
					'post_parent'    => $post_id,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				] ) );
				echo esc_html( $n );
				break;
		}
	}

	// -----------------------------------------------------------------------
	// scl_partido
	// -----------------------------------------------------------------------

	public function columnas_partido( array $cols ): array {
		return [
			'cb'              => $cols['cb'],
			'title'           => 'Partido',
			'scl_organizador' => 'Organizador',
			'scl_torneo'      => 'Torneo',
			'scl_tipo_fase'   => 'Fase',
			'scl_estado'      => 'Estado',
			'scl_fecha'       => 'Fecha',
			'date'            => $cols['date'],
		];
	}

	public function render_partido( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'scl_organizador':
				$autor = (int) get_post_field( 'post_author', $post_id );
				$user  = get_userdata( $autor );
				echo $user ? esc_html( $user->display_name ) : '—';
				break;
			case 'scl_torneo':
				$tid = (int) get_post_meta( $post_id, 'scl_partido_torneo_id', true );
				echo $tid ? esc_html( get_the_title( $tid ) ) : '—';
				break;
			case 'scl_tipo_fase':
				$fase  = get_post_meta( $post_id, 'scl_partido_tipo_fase', true );
				$label = ( 'playoff' === $fase ) ? '🏆 Playoff' : '📋 Grupos';
				echo esc_html( $label );
				break;
			case 'scl_estado':
				$estado = get_post_meta( $post_id, 'scl_partido_estado', true );
				$color  = ( 'finalizado' === $estado ) ? 'green' : 'orange';
				echo '<span style="color:' . esc_attr( $color ) . ';font-weight:600">'
					. esc_html( ucfirst( $estado ) ) . '</span>';
				break;
			case 'scl_fecha':
				$fecha = get_post_meta( $post_id, 'scl_partido_fecha', true );
				echo $fecha ? esc_html( date_i18n( 'j M Y', strtotime( $fecha ) ) ) : '—';
				break;
		}
	}

	// -----------------------------------------------------------------------
	// scl_equipo
	// -----------------------------------------------------------------------

	public function columnas_equipo( array $cols ): array {
		$nuevo = [];
		foreach ( $cols as $k => $v ) {
			$nuevo[ $k ] = $v;
			if ( 'title' === $k ) {
				$nuevo['scl_escudo_col']  = 'Escudo';
				$nuevo['scl_organizador'] = 'Organizador';
				$nuevo['scl_zona']        = 'Zona';
				$nuevo['scl_incompleto']  = 'Estado';
			}
		}
		return $nuevo;
	}

	public function render_equipo( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'scl_escudo_col':
				$escudo_id = (int) get_post_meta( $post_id, 'scl_equipo_escudo', true );
				if ( $escudo_id ) {
					$url = wp_get_attachment_image_url( $escudo_id, [ 36, 36 ] );
					if ( $url ) {
						echo '<img src="' . esc_url( $url ) . '" width="36" height="36" style="border-radius:50%;object-fit:contain;" alt="">';
					}
				} else {
					echo '—';
				}
				break;
			case 'scl_organizador':
				$autor = (int) get_post_field( 'post_author', $post_id );
				$user  = get_userdata( $autor );
				echo $user ? esc_html( $user->display_name ) : '—';
				break;
			case 'scl_zona':
				$zona = get_post_meta( $post_id, 'scl_equipo_zona', true );
				echo $zona ? esc_html( $zona ) : '—';
				break;
			case 'scl_incompleto':
				$incompleto = get_post_meta( $post_id, 'scl_equipo_incompleto', true );
				if ( '1' === $incompleto ) {
					echo '<span style="color:orange;font-weight:600">⚠ Sin escudo</span>';
				} else {
					echo '<span style="color:green;font-weight:600">✅ Completo</span>';
				}
				break;
		}
	}

	// -----------------------------------------------------------------------
	// scl_anuncio
	// -----------------------------------------------------------------------

	public function columnas_anuncio( array $cols ): array {
		return [
			'cb'            => $cols['cb'],
			'title'         => 'Anuncio',
			'scl_anunciante' => 'Anunciante',
			'scl_tier'      => 'Tier',
			'scl_tipo_ad'   => 'Tipo',
			'scl_estado_ad' => 'Estado',
			'scl_imp'       => 'Impresiones',
			'scl_clics'     => 'Clics',
			'scl_ctr'       => 'CTR',
			'date'          => $cols['date'],
		];
	}

	public function render_anuncio( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'scl_anunciante':
				$aid = (int) get_post_meta( $post_id, 'scl_anuncio_anunciante_id', true );
				echo $aid ? esc_html( get_the_title( $aid ) ) : '—';
				break;
			case 'scl_tier':
				$aid  = (int) get_post_meta( $post_id, 'scl_anuncio_anunciante_id', true );
				$tier = $aid ? Scl_Ads::get_tier_anunciante( $aid ) : 'bronce';
				$info = Scl_Ads::TIERS[ $tier ];
				echo '<span style="background:' . esc_attr( $info['color'] )
					. ';color:white;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700">'
					. esc_html( $info['emoji'] . ' ' . $info['label'] ) . '</span>';
				break;
			case 'scl_tipo_ad':
				echo esc_html( get_post_meta( $post_id, 'scl_anuncio_tipo', true ) ?: '—' );
				break;
			case 'scl_estado_ad':
				$estado  = get_post_meta( $post_id, 'scl_anuncio_estado', true );
				$colores = [ 'activo' => 'green', 'pausado' => 'orange', 'finalizado' => '#999' ];
				$color   = $colores[ $estado ] ?? '#999';
				echo '<span style="color:' . esc_attr( $color ) . ';font-weight:600">'
					. esc_html( ucfirst( $estado ) ) . '</span>';
				break;
			case 'scl_imp':
				echo esc_html( number_format(
					(int) get_post_meta( $post_id, 'scl_anuncio_impresiones', true )
				) );
				break;
			case 'scl_clics':
				echo esc_html( number_format(
					(int) get_post_meta( $post_id, 'scl_anuncio_clics', true )
				) );
				break;
			case 'scl_ctr':
				$ctr = get_post_meta( $post_id, 'scl_anuncio_ctr', true );
				echo esc_html( $ctr ? $ctr . '%' : '0%' );
				break;
		}
	}

	// -----------------------------------------------------------------------
	// scl_anunciante
	// -----------------------------------------------------------------------

	public function columnas_anunciante( array $cols ): array {
		$nuevo = [];
		foreach ( $cols as $k => $v ) {
			$nuevo[ $k ] = $v;
			if ( 'title' === $k ) {
				$nuevo['scl_tier_col']    = 'Tier';
				$nuevo['scl_contacto']    = 'Contacto';
				$nuevo['scl_estado_anunciante'] = 'Estado';
			}
		}
		return $nuevo;
	}

	public function render_anunciante( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'scl_tier_col':
				$tier = Scl_Ads::get_tier_anunciante( $post_id );
				$info = Scl_Ads::TIERS[ $tier ];
				echo '<span style="background:' . esc_attr( $info['color'] )
					. ';color:white;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700">'
					. esc_html( $info['emoji'] . ' ' . $info['label'] ) . '</span>';
				break;
			case 'scl_contacto':
				$contacto = get_post_meta( $post_id, 'scl_anunciante_contacto', true );
				echo $contacto ? esc_html( $contacto ) : '—';
				break;
			case 'scl_estado_anunciante':
				$estado = get_post_meta( $post_id, 'scl_anunciante_estado', true );
				$color  = ( 'activo' === $estado ) ? 'green' : '#999';
				echo '<span style="color:' . esc_attr( $color ) . ';font-weight:600">'
					. esc_html( ucfirst( $estado ?: 'activo' ) ) . '</span>';
				break;
		}
	}
}
