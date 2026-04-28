<?php
/**
 * Módulo de publicidad: shortcode, selección ponderada, tracking de impresiones y clics.
 * Exclusivo del Administrador_Portal — los organizadores no interactúan con este módulo.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Ads {

	// -------------------------------------------------------------------------
	// Sistema de tiers de anunciantes
	// -------------------------------------------------------------------------

	const TIERS = [
		'diamante' => [
			'multiplicador' => 5,
			'ubicaciones'   => [
				'home_destacado',
				'header_publico',
				'sidebar_tabla',
				'tabla_posiciones',
				'entre_partidos',
				'footer_publico',
			],
			'label'  => 'Diamante',
			'emoji'  => '💎',
			'color'  => '#00bcd4',
		],
		'platino' => [
			'multiplicador' => 4,
			'ubicaciones'   => [
				'header_publico',
				'sidebar_tabla',
				'tabla_posiciones',
				'entre_partidos',
				'footer_publico',
			],
			'label'  => 'Platino',
			'emoji'  => '⬜',
			'color'  => '#9e9e9e',
		],
		'oro' => [
			'multiplicador' => 3,
			'ubicaciones'   => [
				'sidebar_tabla',
				'tabla_posiciones',
				'entre_partidos',
				'footer_publico',
			],
			'label'  => 'Oro',
			'emoji'  => '🥇',
			'color'  => '#ffc107',
		],
		'plata' => [
			'multiplicador' => 2,
			'ubicaciones'   => [
				'entre_partidos',
				'footer_publico',
			],
			'label'  => 'Plata',
			'emoji'  => '🥈',
			'color'  => '#78909c',
		],
		'bronce' => [
			'multiplicador' => 1,
			'ubicaciones'   => [
				'footer_publico',
			],
			'label'  => 'Bronce',
			'emoji'  => '🥉',
			'color'  => '#8d6e63',
		],
	];

	// -------------------------------------------------------------------------
	// Métodos auxiliares estáticos de tier
	// -------------------------------------------------------------------------

	public static function get_tier_anunciante( int $anunciante_id ): string {
		return get_post_meta( $anunciante_id, 'scl_anunciante_tier', true ) ?: 'bronce';
	}

	public static function tier_tiene_acceso( string $tier, string $ubicacion ): bool {
		return in_array( $ubicacion, self::TIERS[ $tier ]['ubicaciones'] ?? [], true );
	}

	public static function get_multiplicador( string $tier ): int {
		return self::TIERS[ $tier ]['multiplicador'] ?? 1;
	}

	// -------------------------------------------------------------------------
	// Registro de hooks
	// -------------------------------------------------------------------------

	public function init(): void {
		add_shortcode( 'scl_anuncio',          [ $this, 'shortcode_anuncio' ] );
		add_shortcode( 'scl_anuncio_destacado', [ $this, 'shortcode_destacado' ] );
		add_action( 'wp_ajax_nopriv_scl_track_ad', [ $this, 'track_evento' ] );
		add_action( 'wp_ajax_scl_track_ad',        [ $this, 'track_evento' ] );
		add_action( 'wp_ajax_scl_recalcular_metricas_anuncio', [ $this, 'recalcular_metricas' ] );
		add_action( 'template_redirect', [ $this, 'interceptar_clic' ] );
	}

	// -------------------------------------------------------------------------
	// Shortcode
	// -------------------------------------------------------------------------

	/**
	 * [scl_anuncio ubicacion="sidebar_tabla" tipo="cuadrado"]
	 */
	public function shortcode_anuncio( array $atts ): string {
		$atts = shortcode_atts( [
			'ubicacion' => '',
			'tipo'      => '',
		], $atts, 'scl_anuncio' );

		$anuncio = $this->seleccionar_anuncio(
			sanitize_key( $atts['ubicacion'] ),
			sanitize_key( $atts['tipo'] )
		);

		if ( ! $anuncio ) return '';

		return $this->render_anuncio( $anuncio );
	}

	// -------------------------------------------------------------------------
	// Selección y renderizado
	// -------------------------------------------------------------------------

	private function seleccionar_anuncio( string $ubicacion, string $tipo ): ?WP_Post {
		$hoy = gmdate( 'Y-m-d' );

		$anuncios = get_posts( [
			'post_type'      => 'scl_anuncio',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				'relation' => 'AND',
				[ 'key' => 'scl_anuncio_estado', 'value' => 'activo' ],
				[ 'key' => 'scl_anuncio_tipo',   'value' => $tipo ],
				[
					'relation' => 'OR',
					[ 'key' => 'scl_anuncio_fecha_inicio', 'compare' => 'NOT EXISTS' ],
					[ 'key' => 'scl_anuncio_fecha_inicio', 'value' => '', 'compare' => '=' ],
					[ 'key' => 'scl_anuncio_fecha_inicio', 'value' => $hoy, 'compare' => '<=' ],
				],
				[
					'relation' => 'OR',
					[ 'key' => 'scl_anuncio_fecha_fin', 'compare' => 'NOT EXISTS' ],
					[ 'key' => 'scl_anuncio_fecha_fin', 'value' => '', 'compare' => '=' ],
					[ 'key' => 'scl_anuncio_fecha_fin', 'value' => $hoy, 'compare' => '>=' ],
				],
			],
		] );

		if ( empty( $anuncios ) ) return null;

		// Filtrar por:
		// 1. Ubicación seleccionada en el anuncio (array serializado)
		// 2. Límite de impresiones no superado
		// 3. El tier del anunciante tiene acceso a esta ubicación
		$anuncios = array_filter( $anuncios, function( $a ) use ( $ubicacion ) {
			// Verificar que el anuncio esté configurado para esta ubicación
			$ubs = get_post_meta( $a->ID, 'scl_anuncio_ubicacion', true );
			if ( ! is_array( $ubs ) || ! in_array( $ubicacion, $ubs, true ) ) return false;

			// Verificar límite de impresiones
			$limite   = (int) get_post_meta( $a->ID, 'scl_anuncio_impresiones_limite', true );
			$actuales = (int) get_post_meta( $a->ID, 'scl_anuncio_impresiones', true );
			if ( $limite > 0 && $actuales >= $limite ) return false;

			// Verificar que el anunciante tiene tier con acceso a esta ubicación
			$anunciante_id = (int) get_post_meta( $a->ID, 'scl_anuncio_anunciante_id', true );
			if ( ! $anunciante_id ) return false;
			$tier = self::get_tier_anunciante( $anunciante_id );
			return self::tier_tiene_acceso( $tier, $ubicacion );
		} );

		if ( empty( $anuncios ) ) return null;

		// Selección ponderada: peso_base × multiplicador_del_tier
		// Los anunciantes Diamante tienen hasta 5x más chances de aparecer que Bronce
		$pool = [];
		foreach ( $anuncios as $anuncio ) {
			$peso_base     = max( 1, (int) get_post_meta( $anuncio->ID, 'scl_anuncio_peso', true ) );
			$anunciante_id = (int) get_post_meta( $anuncio->ID, 'scl_anuncio_anunciante_id', true );
			$tier          = self::get_tier_anunciante( $anunciante_id );
			$multiplicador = self::get_multiplicador( $tier );
			$peso_final    = $peso_base * $multiplicador;

			for ( $i = 0; $i < $peso_final; $i++ ) {
				$pool[] = $anuncio;
			}
		}

		return $pool[ array_rand( $pool ) ];
	}

	// -------------------------------------------------------------------------
	// Shortcode especial para home_destacado (solo Diamante)
	// -------------------------------------------------------------------------

	/**
	 * [scl_anuncio_destacado tipo="horizontal"]
	 * Renderizado más prominente para la ubicación home_destacado.
	 */
	public function shortcode_destacado( array $atts ): string {
		$atts = shortcode_atts( [
			'tipo' => 'horizontal',
		], $atts, 'scl_anuncio_destacado' );

		$anuncio = $this->seleccionar_anuncio(
			'home_destacado',
			sanitize_key( $atts['tipo'] )
		);

		if ( ! $anuncio ) return '';

		// Render especial para Diamante — más prominente
		$imagen_id         = (int) get_post_meta( $anuncio->ID, 'scl_anuncio_imagen',      true );
		$imagen_url        = $imagen_id ? wp_get_attachment_image_url( $imagen_id, 'full' ) : '';
		$url_destino       = get_post_meta( $anuncio->ID, 'scl_anuncio_url_destino', true );
		$titulo            = get_the_title( $anuncio->ID );
		$anunciante_id     = (int) get_post_meta( $anuncio->ID, 'scl_anuncio_anunciante_id', true );

		if ( ! $imagen_url ) return '';

		$clic_url  = add_query_arg( [
			'scl_ad_clic' => $anuncio->ID,
			'scl_nonce'   => wp_create_nonce( 'scl_ad_' . $anuncio->ID ),
		], home_url( '/' ) );
		$nonce_imp = wp_create_nonce( 'scl_ad_imp_' . $anuncio->ID );

		ob_start();
		?>
		<div class="scl-ad scl-ad--destacado scl-ad--<?php echo esc_attr( $atts['tipo'] ); ?>"
		     data-ad-id="<?php echo esc_attr( $anuncio->ID ); ?>"
		     data-nonce="<?php echo esc_attr( $nonce_imp ); ?>">
			<a href="<?php echo esc_url( $clic_url ); ?>"
			   target="_blank" rel="noopener sponsored"
			   class="scl-ad__link"
			   aria-label="<?php echo esc_attr( $titulo ); ?>">
				<img src="<?php echo esc_url( $imagen_url ); ?>"
				     alt="<?php echo esc_attr( $titulo ); ?>"
				     class="scl-ad__img" loading="eager">
			</a>
			<span class="scl-ad__label">Publicidad</span>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_anuncio( WP_Post $anuncio ): string {
		$imagen_id   = (int) get_post_meta( $anuncio->ID, 'scl_anuncio_imagen',      true );
		$url_destino = get_post_meta( $anuncio->ID, 'scl_anuncio_url_destino', true );
		$tipo        = get_post_meta( $anuncio->ID, 'scl_anuncio_tipo',        true ) ?: 'cuadrado';
		$titulo      = get_the_title( $anuncio->ID );

		if ( ! $imagen_id ) return '';
		$imagen_url = wp_get_attachment_image_url( $imagen_id, 'full' );
		if ( ! $imagen_url ) return '';

		$dims = [
			'horizontal' => [ 728, 90  ],
			'vertical'   => [ 160, 600 ],
			'cuadrado'   => [ 300, 250 ],
			'movil'      => [ 320, 50  ],
		];
		[ $w, $h ] = $dims[ $tipo ] ?? [ 300, 250 ];

		// URL de tracking de clic (redirect server-side)
		$clic_url = add_query_arg( [
			'scl_ad_clic' => $anuncio->ID,
			'scl_nonce'   => wp_create_nonce( 'scl_ad_' . $anuncio->ID ),
		], home_url( '/' ) );

		// Nonce para registrar impresión vía JS
		$nonce_imp = wp_create_nonce( 'scl_ad_imp_' . $anuncio->ID );

		ob_start();
		?>
		<div class="scl-ad scl-ad--<?php echo esc_attr( $tipo ); ?>"
		     data-ad-id="<?php echo esc_attr( $anuncio->ID ); ?>"
		     data-nonce="<?php echo esc_attr( $nonce_imp ); ?>"
		     style="max-width:<?php echo esc_attr( $w ); ?>px;overflow:hidden;">
			<a href="<?php echo esc_url( $clic_url ); ?>"
			   target="_blank"
			   rel="noopener sponsored"
			   class="scl-ad__link"
			   aria-label="<?php echo esc_attr( $titulo ); ?>">
				<img src="<?php echo esc_url( $imagen_url ); ?>"
				     alt="<?php echo esc_attr( $titulo ); ?>"
				     width="<?php echo esc_attr( $w ); ?>"
				     height="<?php echo esc_attr( $h ); ?>"
				     class="scl-ad__img"
				     loading="lazy">
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Tracking: clic vía redirect server-side
	// -------------------------------------------------------------------------

	public function interceptar_clic(): void {
		$ad_id = absint( $_GET['scl_ad_clic'] ?? 0 );
		if ( ! $ad_id ) return;

		$nonce = $_GET['scl_nonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, 'scl_ad_' . $ad_id ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		global $wpdb;
		$ip_hash = hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'secure_auth' ) );

		$wpdb->insert(
			$wpdb->prefix . 'scl_ad_log',
			[
				'ad_id'    => $ad_id,
				'tipo'     => 'clic',
				'ip_hash'  => $ip_hash,
				'fecha'    => gmdate( 'Y-m-d' ),
				'hora'     => (int) gmdate( 'G' ),
			],
			[ '%d', '%s', '%s', '%s', '%d' ]
		);

		$actual = (int) get_post_meta( $ad_id, 'scl_anuncio_clics', true );
		update_post_meta( $ad_id, 'scl_anuncio_clics', $actual + 1 );

		// Recalcular CTR
		$imp  = (int) get_post_meta( $ad_id, 'scl_anuncio_impresiones', true );
		$ctr  = $imp > 0 ? round( ( ( $actual + 1 ) / $imp ) * 100, 2 ) : 0;
		update_post_meta( $ad_id, 'scl_anuncio_ctr', $ctr );

		$url = get_post_meta( $ad_id, 'scl_anuncio_url_destino', true );
		wp_safe_redirect( $url ? esc_url_raw( $url ) : home_url() );
		exit;
	}

	// -------------------------------------------------------------------------
	// Tracking: impresión vía AJAX (desde JS con IntersectionObserver)
	// -------------------------------------------------------------------------

	public function track_evento(): void {
		global $wpdb;

		$ad_id = absint( $_POST['ad_id'] ?? 0 );
		$tipo  = in_array( $_POST['tipo'] ?? '', [ 'impresion', 'clic' ], true )
		         ? $_POST['tipo'] : '';

		if ( ! $ad_id || ! $tipo ) {
			wp_send_json_error( 'Datos inválidos.' );
		}

		$nonce_action = 'impresion' === $tipo
			? 'scl_ad_imp_' . $ad_id
			: 'scl_ad_' . $ad_id;

		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', $nonce_action ) ) {
			wp_send_json_error( 'Nonce inválido.' );
		}

		$ip_hash = hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt( 'secure_auth' ) );
		$hoy     = gmdate( 'Y-m-d' );
		$hora    = (int) gmdate( 'G' );
		$pagina  = esc_url_raw( substr( $_POST['pagina'] ?? '', 0, 500 ) );

		// Deduplicar impresiones: máximo 1 por ad + ip_hash + día
		if ( 'impresion' === $tipo ) {
			$existe = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}scl_ad_log
				 WHERE ad_id = %d AND tipo = 'impresion' AND ip_hash = %s AND fecha = %s
				 LIMIT 1",
				$ad_id, $ip_hash, $hoy
			) );
			if ( $existe ) {
				wp_send_json_success( [ 'dedup' => true ] );
			}
		}

		$wpdb->insert(
			$wpdb->prefix . 'scl_ad_log',
			[
				'ad_id'      => $ad_id,
				'tipo'       => $tipo,
				'ip_hash'    => $ip_hash,
				'pagina_url' => $pagina,
				'fecha'      => $hoy,
				'hora'       => $hora,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%d' ]
		);

		$meta_key = 'impresion' === $tipo ? 'scl_anuncio_impresiones' : 'scl_anuncio_clics';
		$actual   = (int) get_post_meta( $ad_id, $meta_key, true );
		update_post_meta( $ad_id, $meta_key, $actual + 1 );

		// Recalcular CTR tras cada impresión/clic
		$imp  = (int) get_post_meta( $ad_id, 'scl_anuncio_impresiones', true );
		$clic = (int) get_post_meta( $ad_id, 'scl_anuncio_clics',       true );
		$ctr  = $imp > 0 ? round( ( $clic / $imp ) * 100, 2 ) : 0;
		update_post_meta( $ad_id, 'scl_anuncio_ctr', $ctr );

		wp_send_json_success();
	}

	// -------------------------------------------------------------------------
	// Reconciliación de métricas desde el log
	// -------------------------------------------------------------------------

	public function recalcular_metricas(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sin permisos.' );
		}
		if ( ! check_ajax_referer( 'scl_recalcular_metricas', 'nonce', false ) ) {
			wp_send_json_error( 'Nonce inválido.' );
		}

		global $wpdb;
		$ad_id = absint( $_POST['ad_id'] ?? 0 );
		if ( ! $ad_id ) wp_send_json_error( 'ad_id requerido.' );

		$tabla = $wpdb->prefix . 'scl_ad_log';

		$imp = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$tabla} WHERE ad_id = %d AND tipo = 'impresion'",
			$ad_id
		) );
		$clics = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$tabla} WHERE ad_id = %d AND tipo = 'clic'",
			$ad_id
		) );
		$ctr = $imp > 0 ? round( ( $clics / $imp ) * 100, 2 ) : 0;

		update_post_meta( $ad_id, 'scl_anuncio_impresiones', $imp );
		update_post_meta( $ad_id, 'scl_anuncio_clics',       $clics );
		update_post_meta( $ad_id, 'scl_anuncio_ctr',         $ctr );

		wp_send_json_success( [
			'impresiones' => number_format( $imp ),
			'clics'       => number_format( $clics ),
			'ctr'         => $ctr . '%',
		] );
	}

	// -------------------------------------------------------------------------
	// Creación de tabla en BD (llamado desde activación del plugin)
	// -------------------------------------------------------------------------

	public static function crear_tabla_ad_log(): void {
		global $wpdb;
		$tabla   = $wpdb->prefix . 'scl_ad_log';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$tabla} (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ad_id       BIGINT(20) UNSIGNED NOT NULL,
			tipo        ENUM('impresion','clic') NOT NULL,
			ip_hash     VARCHAR(64) NOT NULL DEFAULT '',
			pagina_url  VARCHAR(500) NOT NULL DEFAULT '',
			fecha       DATE NOT NULL,
			hora        TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
			created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_ad_tipo_fecha (ad_id, tipo, fecha),
			KEY idx_fecha (fecha)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
