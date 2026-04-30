<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $titulo_torneo . ' — Resultados' ); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --scl-color-1:     <?php echo esc_attr( $color_1 ); ?>;
    --scl-color-2:     <?php echo esc_attr( $color_2 ); ?>;
    --scl-color-1-rgb: <?php echo esc_attr( scl_hex_to_rgb( $color_1 ) ); ?>;
    --scl-fondo:       <?php echo $fondo_url ? "url('" . esc_url( $fondo_url ) . "')" : 'none'; ?>;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Inter', Arial, sans-serif;
    background-color: #111;
    background-image: var(--scl-fondo);
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: #fff;
    min-height: 100vh;
    padding: 24px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 28px;
}

/* ── Header ─────────────────────────────────────────────────── */
.ep-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
}
.ep-logo {
    width: 90px;
    height: auto;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.6));
}
.ep-logo-placeholder {
    width: 90px; height: 90px;
    border-radius: 50%;
    background: rgba(var(--scl-color-1-rgb),0.3);
    border: 2px solid rgba(var(--scl-color-1-rgb),0.6);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 800; text-transform: uppercase;
    color: var(--scl-color-1);
}
.ep-titulo {
    display: flex;
    flex-direction: column;
    font-size: clamp(24px, 5vw, 40px);
    font-weight: 900;
    text-transform: uppercase;
    line-height: 1;
    letter-spacing: 2px;
    text-shadow: 2px 2px 5px rgba(0,0,0,0.8);
}
.ep-subtitulo {
    font-size: 0.85rem;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(255,255,255,0.5);
    margin-top: 6px;
}

/* ── Contenedor grid ────────────────────────────────────────── */
.ep-contenedor {
    width: 100%;
    max-width: 960px;
    background-color: rgba(0,0,0,0.3);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.8);
    backdrop-filter: blur(12px);
}

.ep-titulo-seccion {
    background: #fff;
    color: #231A3F;
    text-align: center;
    padding: 14px;
    font-size: 1.1rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin: 0;
}

.ep-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
    background-color: rgba(255,255,255,0.08);
}

/* ── Jornada card ───────────────────────────────────────────── */
.ep-jornada {
    background-color: rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    min-height: 180px;
}
.ep-jornada-titulo {
    background-color: #E8E8E8;
    color: #1B1B1B;
    margin: 0;
    padding: 10px;
    font-size: 0.78rem;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-weight: 700;
    border-bottom: 2px solid var(--scl-color-1);
}
.ep-lista-partidos {
    padding: 14px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 14px;
}

/* ── Partido mini ───────────────────────────────────────────── */
.ep-partido {
    text-align: center;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.ep-partido:last-child { border-bottom: none; padding-bottom: 0; }

.ep-enfrentamiento {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.ep-equipo {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 90px;
}
.ep-escudo {
    width: 44px; height: 44px;
    object-fit: contain;
    margin-bottom: 5px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
}
.ep-escudo-placeholder {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.4);
    margin-bottom: 5px;
}
.ep-equipo-nombre {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    line-height: 1.15;
    text-align: center;
}
.ep-equipo.ganador .ep-equipo-nombre { font-weight: 900; color: #fff; }
.ep-equipo.perdedor .ep-equipo-nombre { color: #999; font-weight: 400; }
.ep-equipo.empate .ep-equipo-nombre { color: #ccc; font-weight: 400; }

/* Marcador */
.ep-marcador {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background-color: rgba(0,0,0,0.6);
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid rgba(255,255,255,0.1);
    flex-shrink: 0;
}
.ep-goles {
    font-size: 1.25rem;
    font-weight: 900;
    color: var(--scl-color-2);
}
.ep-goles-sep { font-size: 0.9rem; color: #666; }

/* VS (para pendientes) */
.ep-vs {
    color: var(--scl-color-2);
    font-style: italic;
    font-weight: 900;
    font-size: 1rem;
    flex-shrink: 0;
}

/* Fecha / badge */
.ep-fecha {
    display: inline-block;
    margin-top: 7px;
    font-size: 0.72rem;
    background: rgba(0,0,0,0.5);
    padding: 2px 8px;
    border-radius: 10px;
    color: #ccc;
    border: 1px solid rgba(255,255,255,0.1);
}
.ep-badge-pendiente {
    display: inline-block;
    background-color: var(--scl-color-2);
    color: #1B1B1B;
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 6px;
    border-radius: 4px;
    margin-bottom: 6px;
    letter-spacing: 1px;
}

/* Vacío */
.ep-vacio {
    padding: 32px 16px;
    text-align: center;
    color: rgba(255,255,255,0.35);
    font-size: 0.9rem;
}

/* Responsive: 1 columna en pantallas pequeñas */
@media (max-width: 600px) {
    .ep-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Header del torneo -->
<div class="ep-header">
    <?php if ( $logo_url ) : ?>
        <img src="<?php echo esc_url( $logo_url ); ?>"
             alt="<?php echo esc_attr( $titulo_torneo ); ?>"
             class="ep-logo">
    <?php else : ?>
        <div class="ep-logo-placeholder">
            <?php echo esc_html( $siglas ?: strtoupper( mb_substr( $titulo_torneo, 0, 3 ) ) ); ?>
        </div>
    <?php endif; ?>

    <div class="ep-titulo">
        <?php echo esc_html( strtoupper( $titulo_torneo ) ); ?>
        <?php if ( $titulo_temp ) : ?>
            <span class="ep-subtitulo"><?php echo esc_html( $titulo_temp ); ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- Grid de resultados -->
<?php if ( empty( $partidos_data ) ) : ?>
    <div class="ep-contenedor">
        <div class="ep-vacio">
            <?php esc_html_e( 'No hay partidos registrados aún.', 'sportcriss-lite' ); ?>
        </div>
    </div>
<?php else : ?>
    <div class="ep-contenedor">
        <h2 class="ep-titulo-seccion"><?php esc_html_e( 'Resultados', 'sportcriss-lite' ); ?></h2>

        <div class="ep-grid">
            <?php foreach ( $partidos_data as $grupo ) :
                $jornada_nombre = $grupo['jornada'] ? $grupo['jornada']->name : __( 'Sin jornada', 'sportcriss-lite' );
            ?>
            <div class="ep-jornada">
                <h3 class="ep-jornada-titulo"><?php echo esc_html( $jornada_nombre ); ?></h3>
                <div class="ep-lista-partidos">
                    <?php foreach ( $grupo['partidos'] as $partido ) :
                        $finalizado = ( $partido['estado'] === 'finalizado' );
                        $gl = $partido['goles_local'];
                        $gv = $partido['goles_visita'];

                        if ( $finalizado && $gl !== null && $gv !== null ) {
                            if ( $gl > $gv ) {
                                $clase_local  = 'ganador';
                                $clase_visita = 'perdedor';
                            } elseif ( $gv > $gl ) {
                                $clase_local  = 'perdedor';
                                $clase_visita = 'ganador';
                            } else {
                                $clase_local  = 'empate';
                                $clase_visita = 'empate';
                            }
                        } else {
                            $clase_local  = '';
                            $clase_visita = '';
                        }
                    ?>
                    <div class="ep-partido">
                        <?php if ( ! $finalizado ) : ?>
                            <div><span class="ep-badge-pendiente"><?php esc_html_e( 'Pendiente', 'sportcriss-lite' ); ?></span></div>
                        <?php endif; ?>
                        <div class="ep-enfrentamiento">
                            <!-- Local -->
                            <div class="ep-equipo <?php echo esc_attr( $clase_local ); ?>">
                                <?php if ( $partido['escudo_local'] ) : ?>
                                    <img src="<?php echo esc_url( $partido['escudo_local'] ); ?>"
                                         alt="<?php echo esc_attr( $partido['local'] ); ?>"
                                         class="ep-escudo">
                                <?php else : ?>
                                    <div class="ep-escudo-placeholder">
                                        <?php echo esc_html( mb_substr( $partido['local'], 0, 2 ) ); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="ep-equipo-nombre"><?php echo esc_html( $partido['local'] ); ?></span>
                            </div>

                            <!-- Marcador o VS -->
                            <?php if ( $finalizado && $gl !== null && $gv !== null ) : ?>
                                <div class="ep-marcador">
                                    <span class="ep-goles"><?php echo esc_html( $gl ); ?></span>
                                    <span class="ep-goles-sep">-</span>
                                    <span class="ep-goles"><?php echo esc_html( $gv ); ?></span>
                                </div>
                            <?php else : ?>
                                <span class="ep-vs">VS</span>
                            <?php endif; ?>

                            <!-- Visitante -->
                            <div class="ep-equipo <?php echo esc_attr( $clase_visita ); ?>">
                                <?php if ( $partido['escudo_visita'] ) : ?>
                                    <img src="<?php echo esc_url( $partido['escudo_visita'] ); ?>"
                                         alt="<?php echo esc_attr( $partido['visita'] ); ?>"
                                         class="ep-escudo">
                                <?php else : ?>
                                    <div class="ep-escudo-placeholder">
                                        <?php echo esc_html( mb_substr( $partido['visita'], 0, 2 ) ); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="ep-equipo-nombre"><?php echo esc_html( $partido['visita'] ); ?></span>
                            </div>
                        </div>
                        <?php if ( $partido['fecha'] ) : ?>
                            <div class="ep-fecha"><?php echo esc_html( $partido['fecha'] ); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
