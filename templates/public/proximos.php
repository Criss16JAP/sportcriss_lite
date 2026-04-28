<?php
/**
 * Template: Shortcode [scl_proximos]
 * Variables: $partidos (array WP_Post[]), $mostrar_escudos (bool)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="scl-pub scl-pub-proximos">
    <?php foreach ( $partidos as $partido ) :
        $local_id  = (int) get_post_meta( $partido->ID, 'scl_partido_equipo_local_id',  true );
        $visita_id = (int) get_post_meta( $partido->ID, 'scl_partido_equipo_visita_id', true );
        $fecha     = get_post_meta( $partido->ID, 'scl_partido_fecha', true );
        $tipo_fase = get_post_meta( $partido->ID, 'scl_partido_tipo_fase', true );

        $local_nombre  = get_the_title( $local_id );
        $visita_nombre = get_the_title( $visita_id );

        $escudo_l_id  = (int) get_post_meta( $local_id,  'scl_equipo_escudo', true );
        $escudo_v_id  = (int) get_post_meta( $visita_id, 'scl_equipo_escudo', true );
        $escudo_l_url = $escudo_l_id ? (string) wp_get_attachment_image_url( $escudo_l_id, [ 40, 40 ] ) : '';
        $escudo_v_url = $escudo_v_id ? (string) wp_get_attachment_image_url( $escudo_v_id, [ 40, 40 ] ) : '';

        $jornadas = wp_get_post_terms( $partido->ID, 'scl_jornada' );
        $jornada  = ( ! is_wp_error( $jornadas ) && ! empty( $jornadas ) ) ? $jornadas[0]->name : '';
    ?>
        <div class="scl-pub-proximo">
            <div class="scl-pub-resultado__meta">
                <?php if ( $fecha ) : ?>
                    <span class="scl-pub-fecha">
                        <?php echo esc_html( date_i18n( 'j M Y', strtotime( $fecha ) ) ); ?>
                    </span>
                <?php endif; ?>
                <?php if ( $jornada ) : ?>
                    <span class="scl-pub-jornada"><?php echo esc_html( $jornada ); ?></span>
                <?php endif; ?>
                <?php if ( 'playoff' === $tipo_fase ) : ?>
                    <span class="scl-pub-badge scl-pub-badge--playoff">Playoff</span>
                <?php endif; ?>
            </div>
            <div class="scl-pub-resultado__marcador">
                <div class="scl-pub-resultado__equipo scl-pub-resultado__equipo--local">
                    <?php if ( $mostrar_escudos ) : ?>
                        <?php if ( $escudo_l_url ) : ?>
                            <img src="<?php echo esc_url( $escudo_l_url ); ?>"
                                 alt="<?php echo esc_attr( $local_nombre ); ?>"
                                 class="scl-pub-escudo">
                        <?php else : ?>
                            <div class="scl-pub-escudo scl-pub-escudo--placeholder">
                                <?php echo esc_html( mb_strtoupper( mb_substr( $local_nombre, 0, 1 ) ) ); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="scl-pub-nombre"><?php echo esc_html( $local_nombre ); ?></span>
                </div>
                <div class="scl-pub-resultado__score">
                    <span class="scl-pub-vs">vs</span>
                </div>
                <div class="scl-pub-resultado__equipo scl-pub-resultado__equipo--visita">
                    <span class="scl-pub-nombre"><?php echo esc_html( $visita_nombre ); ?></span>
                    <?php if ( $mostrar_escudos ) : ?>
                        <?php if ( $escudo_v_url ) : ?>
                            <img src="<?php echo esc_url( $escudo_v_url ); ?>"
                                 alt="<?php echo esc_attr( $visita_nombre ); ?>"
                                 class="scl-pub-escudo">
                        <?php else : ?>
                            <div class="scl-pub-escudo scl-pub-escudo--placeholder">
                                <?php echo esc_html( mb_strtoupper( mb_substr( $visita_nombre, 0, 1 ) ) ); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
