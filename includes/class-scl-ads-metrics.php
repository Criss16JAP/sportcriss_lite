<?php
/**
 * Página de métricas globales de publicidad en wp-admin.
 * Solo accesible para administrator.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Scl_Ads_Metrics {

	public function render_pagina(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sin permisos.', 'sportcriss-lite' ) );
		}

		global $wpdb;
		$tabla = $wpdb->prefix . 'scl_ad_log';

		$desde = sanitize_text_field( $_GET['desde'] ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$hasta = sanitize_text_field( $_GET['hasta'] ?? gmdate( 'Y-m-d' ) );

		// Validar formato de fecha
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $desde ) ) $desde = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $hasta ) ) $hasta = gmdate( 'Y-m-d' );

		$total_imp = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$tabla} WHERE tipo = 'impresion' AND fecha BETWEEN %s AND %s",
			$desde, $hasta
		) );
		$total_clics = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$tabla} WHERE tipo = 'clic' AND fecha BETWEEN %s AND %s",
			$desde, $hasta
		) );
		$ctr_global = $total_imp > 0 ? round( ( $total_clics / $total_imp ) * 100, 2 ) : 0;

		$por_anuncio = $wpdb->get_results( $wpdb->prepare(
			"SELECT ad_id,
			        SUM(tipo = 'impresion') AS impresiones,
			        SUM(tipo = 'clic')      AS clics
			 FROM {$tabla}
			 WHERE fecha BETWEEN %s AND %s
			 GROUP BY ad_id
			 ORDER BY impresiones DESC",
			$desde, $hasta
		) );

		$por_anunciante = [];
		foreach ( $por_anuncio as $row ) {
			$anunciante_id = (int) get_post_meta( (int) $row->ad_id, 'scl_anuncio_anunciante_id', true );
			if ( ! isset( $por_anunciante[ $anunciante_id ] ) ) {
				$por_anunciante[ $anunciante_id ] = [ 'impresiones' => 0, 'clics' => 0 ];
			}
			$por_anunciante[ $anunciante_id ]['impresiones'] += (int) $row->impresiones;
			$por_anunciante[ $anunciante_id ]['clics']       += (int) $row->clics;
		}
		arsort( $por_anunciante );

		$por_dia = $wpdb->get_results( $wpdb->prepare(
			"SELECT fecha,
			        SUM(tipo = 'impresion') AS impresiones,
			        SUM(tipo = 'clic')      AS clics
			 FROM {$tabla}
			 WHERE fecha BETWEEN %s AND %s
			 GROUP BY fecha ORDER BY fecha ASC",
			$desde, $hasta
		) );

		include SCL_PATH . 'templates/admin/metricas-anuncios.php';
	}

	public function exportar_csv_anunciante(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		if ( ! check_ajax_referer( 'scl_exportar_metricas', 'nonce', false ) ) wp_die( 'Nonce inválido.' );

		global $wpdb;
		$anunciante_id = absint( $_GET['anunciante_id'] ?? 0 );
		$desde = sanitize_text_field( $_GET['desde'] ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$hasta = sanitize_text_field( $_GET['hasta'] ?? gmdate( 'Y-m-d' ) );

		$anuncios = get_posts( [
			'post_type'      => 'scl_anuncio',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [ [
				'key'   => 'scl_anuncio_anunciante_id',
				'value' => $anunciante_id,
				'type'  => 'NUMERIC',
			] ],
		] );

		$tabla = $wpdb->prefix . 'scl_ad_log';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="metricas-anunciante-' . $anunciante_id . '.csv"' );
		header( 'Pragma: no-cache' );
		echo "\xEF\xBB\xBF"; // BOM para Excel

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, [ 'Tier', 'Anunciante', 'Anuncio', 'Tipo', 'Estado', 'Período desde', 'Período hasta', 'Impresiones', 'Clics', 'CTR %' ] );

		$tier_label = Scl_Ads::TIERS[ Scl_Ads::get_tier_anunciante( $anunciante_id ) ]['label'] ?? 'Bronce';
		$nombre_anunciante = get_the_title( $anunciante_id ) ?: '';

		foreach ( $anuncios as $anuncio ) {
			$imp = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$tabla} WHERE ad_id = %d AND tipo = 'impresion' AND fecha BETWEEN %s AND %s",
				$anuncio->ID, $desde, $hasta
			) );
			$clics = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$tabla} WHERE ad_id = %d AND tipo = 'clic' AND fecha BETWEEN %s AND %s",
				$anuncio->ID, $desde, $hasta
			) );
			$ctr = $imp > 0 ? round( ( $clics / $imp ) * 100, 2 ) : 0;

			fputcsv( $output, [
				$tier_label,
				$nombre_anunciante,
				$anuncio->post_title,
				get_post_meta( $anuncio->ID, 'scl_anuncio_tipo',   true ),
				get_post_meta( $anuncio->ID, 'scl_anuncio_estado', true ),
				$desde,
				$hasta,
				$imp,
				$clics,
				$ctr,
			] );
		}
		fclose( $output );
		exit;
	}
}
