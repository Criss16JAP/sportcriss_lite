<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $titulo_torneo . ' — Tabla de Posiciones' ); ?></title>
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
</head>
<body>

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
                    <?php if ( $titulo_temp && $titulo_grupo ) echo ' &middot; '; ?>
                    <?php if ( $titulo_grupo ) echo esc_html( strtoupper( $titulo_grupo ) ); ?>
                </div>
            </div>
        </div>

        <!-- Tabla de posiciones -->
        <?php if ( empty( $tabla ) ) : ?>
            <div class="scl-export-empty">
                <?php esc_html_e( 'No hay datos de tabla disponibles aún.', 'sportcriss-lite' ); ?>
            </div>
        <?php else : ?>
            <div class="scl-export-tabla-wrap">
                <table class="scl-export-tabla">
                    <thead>
                        <tr>
                            <th class="scl-col-pos">#</th>
                            <th class="scl-col-equipo"><?php esc_html_e( 'Equipo', 'sportcriss-lite' ); ?></th>
                            <th class="scl-col-stat" title="Partidos jugados">PJ</th>
                            <th class="scl-col-stat" title="Partidos ganados">PG</th>
                            <th class="scl-col-stat" title="Partidos empatados">PE</th>
                            <th class="scl-col-stat" title="Partidos perdidos">PP</th>
                            <th class="scl-col-stat" title="Goles a favor">GF</th>
                            <th class="scl-col-stat" title="Goles en contra">GC</th>
                            <th class="scl-col-stat" title="Diferencia de goles">DG</th>
                            <th class="scl-col-pts">PTS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tabla as $pos => $equipo ) :
                            $es_primero = ( 0 === $pos );
                            $dg_val     = (int) $equipo['DG'];
                            $dg_prefix  = $dg_val > 0 ? '+' : '';
                        ?>
                        <tr class="scl-fila<?php echo $es_primero ? ' scl-fila--lider' : ''; ?><?php echo $pos < 2 ? ' scl-fila--zona-verde' : ''; ?>">
                            <td class="scl-col-pos">
                                <span class="scl-posicion"><?php echo $pos + 1; ?></span>
                            </td>
                            <td class="scl-col-equipo">
                                <div class="scl-equipo-cell">
                                    <?php if ( ! empty( $equipo['escudo_url'] ) ) : ?>
                                        <img src="<?php echo esc_url( $equipo['escudo_url'] ); ?>"
                                             alt="<?php echo esc_attr( $equipo['nombre'] ); ?>"
                                             class="scl-escudo">
                                    <?php else : ?>
                                        <div class="scl-escudo scl-escudo--placeholder">
                                            <?php echo esc_html( mb_strtoupper( mb_substr( $equipo['nombre'], 0, 1 ) ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="scl-nombre-equipo"><?php echo esc_html( $equipo['nombre'] ); ?></span>
                                </div>
                            </td>
                            <td class="scl-col-stat"><?php echo (int) $equipo['PJ']; ?></td>
                            <td class="scl-col-stat"><?php echo (int) $equipo['PG']; ?></td>
                            <td class="scl-col-stat"><?php echo (int) $equipo['PE']; ?></td>
                            <td class="scl-col-stat"><?php echo (int) $equipo['PP']; ?></td>
                            <td class="scl-col-stat"><?php echo (int) $equipo['GF']; ?></td>
                            <td class="scl-col-stat"><?php echo (int) $equipo['GC']; ?></td>
                            <td class="scl-col-stat"><?php echo esc_html( $dg_prefix . $dg_val ); ?></td>
                            <td class="scl-col-pts">
                                <strong><?php echo (int) $equipo['Pts']; ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="scl-export-footer">
            <?php if ( $fecha_act ) : ?>
                <span class="scl-export-updated">
                    <?php echo esc_html__( 'Actualizado:', 'sportcriss-lite' ) . ' ' . esc_html( $fecha_act ); ?>
                </span>
            <?php else : ?>
                <span></span>
            <?php endif; ?>
            <span class="scl-export-brand">SportCriss Lite</span>
        </div>

    </div><!-- .scl-export-card -->
</div><!-- .scl-export-wrapper -->

</body>
</html>
<?php
/*
 * Variables esperadas (inyectadas por Scl_Export::interceptar_exportacion()):
 *   int    $torneo_id
 *   int    $temporada_term_id
 *   int    $grupo_id
 *   string $titulo_torneo
 *   string $titulo_temp
 *   string $titulo_grupo
 *   string $logo_url
 *   string $fondo_url
 *   string $color_1
 *   string $color_2
 *   string $siglas
 *   array  $tabla
 *   string $fecha_act
 */
