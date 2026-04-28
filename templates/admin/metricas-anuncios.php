<?php
/**
 * Template: Página de métricas de publicidad en wp-admin.
 * Variables inyectadas desde Scl_Ads_Metrics::render_pagina():
 *   $desde, $hasta, $total_imp, $total_clics, $ctr_global,
 *   $por_anuncio (array stdClass: ad_id, impresiones, clics),
 *   $por_anunciante (array: anunciante_id => [impresiones, clics]),
 *   $por_dia (array stdClass: fecha, impresiones, clics)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_url  = admin_url( 'admin.php?page=scl-metricas-anuncios' );
$export_nonce = wp_create_nonce( 'scl_exportar_metricas' );
?>
<div class="wrap">
<h1><?php esc_html_e( 'Métricas de Publicidad', 'sportcriss-lite' ); ?></h1>

<!-- Selector de período -->
<form method="get" style="margin-bottom:1.5rem;">
    <input type="hidden" name="page" value="scl-metricas-anuncios">
    <label><?php esc_html_e( 'Desde:', 'sportcriss-lite' ); ?>
        <input type="date" name="desde" value="<?php echo esc_attr( $desde ); ?>">
    </label>
    &nbsp;
    <label><?php esc_html_e( 'Hasta:', 'sportcriss-lite' ); ?>
        <input type="date" name="hasta" value="<?php echo esc_attr( $hasta ); ?>">
    </label>
    &nbsp;
    <input type="submit" class="button" value="<?php esc_attr_e( 'Filtrar', 'sportcriss-lite' ); ?>">
</form>

<!-- Cards globales -->
<div style="display:flex;gap:1.25rem;flex-wrap:wrap;margin-bottom:1.75rem;">
    <div class="postbox" style="min-width:160px;padding:1rem 1.5rem;text-align:center;">
        <div style="font-size:2rem;font-weight:800;line-height:1;"><?php echo number_format( $total_imp ); ?></div>
        <div style="color:#666;font-size:.875rem;margin-top:.25rem;"><?php esc_html_e( 'Impresiones', 'sportcriss-lite' ); ?></div>
    </div>
    <div class="postbox" style="min-width:160px;padding:1rem 1.5rem;text-align:center;">
        <div style="font-size:2rem;font-weight:800;line-height:1;"><?php echo number_format( $total_clics ); ?></div>
        <div style="color:#666;font-size:.875rem;margin-top:.25rem;"><?php esc_html_e( 'Clics', 'sportcriss-lite' ); ?></div>
    </div>
    <div class="postbox" style="min-width:160px;padding:1rem 1.5rem;text-align:center;">
        <div style="font-size:2rem;font-weight:800;line-height:1;"><?php echo esc_html( $ctr_global ); ?>%</div>
        <div style="color:#666;font-size:.875rem;margin-top:.25rem;">CTR <?php esc_html_e( 'global', 'sportcriss-lite' ); ?></div>
    </div>
</div>

<!-- Gráfico de evolución diaria -->
<?php if ( ! empty( $por_dia ) ) : ?>
<div class="postbox" style="margin-bottom:1.75rem;">
    <div class="postbox-header">
        <h2 class="hndle"><?php esc_html_e( 'Evolución diaria', 'sportcriss-lite' ); ?></h2>
    </div>
    <div class="inside" style="padding:1rem;">
        <canvas id="scl-chart-diario" height="80"></canvas>
    </div>
</div>
<script>
(function() {
    var labels = <?php echo wp_json_encode( array_column( $por_dia, 'fecha' ) ); ?>;
    var imp    = <?php echo wp_json_encode( array_map( 'intval', array_column( $por_dia, 'impresiones' ) ) ); ?>;
    var clics  = <?php echo wp_json_encode( array_map( 'intval', array_column( $por_dia, 'clics' ) ) ); ?>;

    if ( typeof Chart === 'undefined' ) return;

    new Chart( document.getElementById('scl-chart-diario'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Impresiones',
                    data: imp,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34,113,177,0.1)',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Clics',
                    data: clics,
                    borderColor: '#d63638',
                    backgroundColor: 'rgba(214,54,56,0.1)',
                    tension: 0.3,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    } );
})();
</script>
<?php endif; ?>

<!-- Por anunciante -->
<?php if ( ! empty( $por_anunciante ) ) : ?>
<div class="postbox" style="margin-bottom:1.75rem;">
    <div class="postbox-header">
        <h2 class="hndle"><?php esc_html_e( 'Por anunciante', 'sportcriss-lite' ); ?></h2>
    </div>
    <div class="inside" style="padding:0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Anunciante', 'sportcriss-lite' ); ?></th>
                    <th><?php esc_html_e( 'Impresiones', 'sportcriss-lite' ); ?></th>
                    <th><?php esc_html_e( 'Clics', 'sportcriss-lite' ); ?></th>
                    <th>CTR</th>
                    <th><?php esc_html_e( 'Exportar', 'sportcriss-lite' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $por_anunciante as $aid => $datos ) :
					$anunciante = get_post( $aid );
					if ( ! $anunciante ) continue;
					$tier   = Scl_Ads::get_tier_anunciante( (int) $aid );
					$tinfo  = Scl_Ads::TIERS[ $tier ];
					$imp_a  = $datos['impresiones'];
					$clic_a = $datos['clics'];
					$ctr_a  = $imp_a > 0 ? round( ( $clic_a / $imp_a ) * 100, 2 ) : 0;
					$csv_url = add_query_arg( [
						'action'         => 'scl_exportar_metricas_anunciante',
						'anunciante_id'  => $aid,
						'desde'          => $desde,
						'hasta'          => $hasta,
						'nonce'          => $export_nonce,
					], admin_url( 'admin-ajax.php' ) );
				?>
					<tr>
						<td>
							<span class="scl-tier-badge" style="background:<?php echo esc_attr( $tinfo['color'] ); ?>">
								<?php echo esc_html( $tinfo['emoji'] . ' ' . $tinfo['label'] ); ?>
							</span>
							<?php echo esc_html( $anunciante->post_title ); ?>
						</td>
						<td><?php echo number_format( $imp_a ); ?></td>
						<td><?php echo number_format( $clic_a ); ?></td>
						<td><?php echo esc_html( $ctr_a ); ?>%</td>
						<td>
							<a href="<?php echo esc_url( $csv_url ); ?>" class="button button-small">⬇ CSV</a>
						</td>
					</tr>
				<?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Por anuncio -->
<?php if ( ! empty( $por_anuncio ) ) : ?>
<div class="postbox">
    <div class="postbox-header">
        <h2 class="hndle"><?php esc_html_e( 'Por anuncio', 'sportcriss-lite' ); ?></h2>
    </div>
    <div class="inside" style="padding:0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Anuncio', 'sportcriss-lite' ); ?></th>
                    <th><?php esc_html_e( 'Tipo', 'sportcriss-lite' ); ?></th>
                    <th><?php esc_html_e( 'Anunciante', 'sportcriss-lite' ); ?></th>
                    <th><?php esc_html_e( 'Impresiones', 'sportcriss-lite' ); ?></th>
                    <th><?php esc_html_e( 'Clics', 'sportcriss-lite' ); ?></th>
                    <th>CTR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $por_anuncio as $row ) :
                    $anuncio       = get_post( (int) $row->ad_id );
                    if ( ! $anuncio ) continue;
                    $tipo_a        = get_post_meta( $anuncio->ID, 'scl_anuncio_tipo', true );
                    $anunciante_id = (int) get_post_meta( $anuncio->ID, 'scl_anuncio_anunciante_id', true );
                    $anunciante    = $anunciante_id ? get_post( $anunciante_id ) : null;
                    $imp_r         = (int) $row->impresiones;
                    $clic_r        = (int) $row->clics;
                    $ctr_r         = $imp_r > 0 ? round( ( $clic_r / $imp_r ) * 100, 2 ) : 0;
                ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( get_edit_post_link( $anuncio->ID ) ); ?>">
                                <?php echo esc_html( $anuncio->post_title ); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html( $tipo_a ); ?></td>
                        <td><?php echo $anunciante ? esc_html( $anunciante->post_title ) : '—'; ?></td>
                        <td><?php echo number_format( $imp_r ); ?></td>
                        <td><?php echo number_format( $clic_r ); ?></td>
                        <td><?php echo esc_html( $ctr_r ); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div><!-- .wrap -->

<?php
// Cargar Chart.js desde CDN solo en esta página
wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js', [], null, true );
