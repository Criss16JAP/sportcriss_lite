<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $titulo_torneo . ' — ' . ucfirst( $stats_tipo ) ); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --scl-color-1:     <?php echo esc_attr( $color_1 ); ?>;
    --scl-color-2:     <?php echo esc_attr( $color_2 ); ?>;
    --scl-color-1-rgb: <?php echo esc_attr( scl_hex_to_rgb( $color_1 ) ); ?>;
    --scl-fondo:       <?php echo $fondo_url ? "url('" . esc_url( $fondo_url ) . "')" : 'none'; ?>;
}
</style>
<link rel="stylesheet" href="<?php echo esc_url( SCL_URL . 'assets/css/export.css' ); ?>?v=<?php echo esc_attr( SCL_VERSION ); ?>">
<style>
/* ── Stats-specific overrides ─────────────────────────────────── */
.scl-stats-tabla { width: 100%; border-collapse: collapse; }
.scl-stats-tabla thead tr {
    background: rgba(255,255,255,0.08);
    border-bottom: 2px solid var(--scl-color-1);
}
.scl-stats-tabla th {
    padding: 10px 12px;
    text-align: left;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.5);
    font-weight: 600;
}
.scl-stats-tabla th.num { text-align: center; width: 60px; }
.scl-stats-tabla td {
    padding: 10px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    font-size: 0.9rem;
    color: rgba(255,255,255,0.9);
    vertical-align: middle;
}
.scl-stats-tabla tbody tr:last-child td { border-bottom: none; }
.scl-stats-tabla td.num {
    text-align: center;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--scl-color-2);
}
.scl-stats-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px; height: 24px;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 700;
    background: rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.5);
    margin-right: 10px;
    flex-shrink: 0;
}
.scl-stats-rank.top { background: var(--scl-color-1); color: #fff; }
.scl-stats-jugador { display: flex; align-items: center; gap: 0; }
.scl-stats-jugador-nombre { font-weight: 600; }
.scl-stats-equipo {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.45);
    display: block;
    margin-top: 2px;
}
.scl-stats-tarjeta {
    display: inline-block;
    width: 14px; height: 20px;
    border-radius: 2px;
    vertical-align: middle;
    margin-right: 4px;
}
.scl-stats-tarjeta.amarilla { background: #f5c518; }
.scl-stats-tarjeta.roja     { background: #e63946; }
.scl-stats-empty {
    padding: 40px 20px;
    text-align: center;
    color: rgba(255,255,255,0.4);
    font-size: 0.9rem;
}
</style>
</head>
<body>
<?php
$tipo_labels = [
    'goleadores'         => 'Goleadores',
    'asistencias'        => 'Asistencias',
    'tarjetas_amarillas' => 'Tarjetas Amarillas',
    'tarjetas_rojas'     => 'Tarjetas Rojas',
    'calificaciones'     => 'Mejores Calificaciones',
];
$titulo_stat = $tipo_labels[ $stats_tipo ] ?? ucfirst( str_replace( '_', ' ', $stats_tipo ) );
?>

<div class="scl-export-wrapper">

    <div class="scl-export-bg"></div>

    <div class="scl-export-card">

        <!-- Header del torneo -->
        <div class="scl-export-header">
            <?php if ( $logo_url ) : ?>
                <img src="<?php echo esc_url( $logo_url ); ?>"
                     alt="<?php echo esc_attr( $titulo_torneo ); ?>"
                     class="scl-export-logo">
            <?php else : ?>
                <div class="scl-export-logo-placeholder">
                    <?php echo esc_html( $siglas ?: strtoupper( mb_substr( $titulo_torneo, 0, 3 ) ) ); ?>
                </div>
            <?php endif; ?>

            <div class="scl-export-header__info">
                <h1 class="scl-export-titulo">
                    <?php echo esc_html( strtoupper( $titulo_torneo ) ); ?>
                </h1>
                <div class="scl-export-subtitulo">
                    <?php if ( $titulo_temp ) echo esc_html( $titulo_temp ); ?>
                    <?php if ( $titulo_temp ) echo ' &middot; '; ?>
                    <?php echo esc_html( $titulo_stat ); ?>
                </div>
            </div>
        </div>

        <!-- Tabla de estadísticas -->
        <?php if ( empty( $stats_data ) ) : ?>
            <div class="scl-stats-empty">
                <?php esc_html_e( 'No hay estadísticas disponibles aún.', 'sportcriss-lite' ); ?>
            </div>
        <?php else : ?>
            <div class="scl-export-tabla-wrap">
                <table class="scl-stats-tabla">
                    <thead>
                        <tr>
                            <th class="num">#</th>
                            <th><?php esc_html_e( 'Jugador', 'sportcriss-lite' ); ?></th>
                            <?php if ( 'tarjetas_amarillas' === $stats_tipo || 'tarjetas_rojas' === $stats_tipo ) : ?>
                                <th class="num">🟨</th>
                                <th class="num">🟥</th>
                            <?php elseif ( 'calificaciones' === $stats_tipo ) : ?>
                                <th class="num"><?php esc_html_e( 'PJ', 'sportcriss-lite' ); ?></th>
                                <th class="num"><?php esc_html_e( 'Prom', 'sportcriss-lite' ); ?></th>
                            <?php elseif ( 'asistencias' === $stats_tipo ) : ?>
                                <th class="num"><?php esc_html_e( 'Asist.', 'sportcriss-lite' ); ?></th>
                            <?php else : ?>
                                <th class="num"><?php esc_html_e( 'Goles', 'sportcriss-lite' ); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $stats_data as $i => $row ) :
                            $pos = $i + 1;
                            $is_top = $pos <= 3;
                        ?>
                        <tr>
                            <td class="num">
                                <span class="scl-stats-rank <?php echo $is_top ? 'top' : ''; ?>">
                                    <?php echo esc_html( $pos ); ?>
                                </span>
                            </td>
                            <td>
                                <div class="scl-stats-jugador">
                                    <div>
                                        <span class="scl-stats-jugador-nombre">
                                            <?php echo esc_html( $row->jugador_nombre ?? '—' ); ?>
                                        </span>
                                        <span class="scl-stats-equipo">
                                            <?php echo esc_html( $row->equipo_nombre ?? '' ); ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <?php if ( 'tarjetas_amarillas' === $stats_tipo || 'tarjetas_rojas' === $stats_tipo ) : ?>
                                <td class="num">
                                    <span class="scl-stats-tarjeta amarilla"></span>
                                    <?php echo esc_html( (int) ( $row->amarillas ?? 0 ) ); ?>
                                </td>
                                <td class="num">
                                    <span class="scl-stats-tarjeta roja"></span>
                                    <?php echo esc_html( (int) ( $row->rojas ?? 0 ) ); ?>
                                </td>
                            <?php elseif ( 'calificaciones' === $stats_tipo ) : ?>
                                <td class="num"><?php echo esc_html( (int) ( $row->partidos ?? 0 ) ); ?></td>
                                <td class="num"><?php echo esc_html( number_format( (float) ( $row->promedio ?? 0 ), 1 ) ); ?></td>
                            <?php elseif ( 'asistencias' === $stats_tipo ) : ?>
                                <td class="num"><?php echo esc_html( (int) ( $row->asistencias ?? 0 ) ); ?></td>
                            <?php else : ?>
                                <td class="num"><?php echo esc_html( (int) ( $row->goles ?? 0 ) ); ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="scl-export-footer">
            <span><?php echo esc_html( date_i18n( 'j M Y · H:i' ) ); ?></span>
        </div>

    </div>

</div>

</body>
</html>
