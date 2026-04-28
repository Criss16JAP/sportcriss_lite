<?php
/**
 * Template: Shortcode [scl_perfil_equipo]
 * Variables: $equipo (WP_Post), $escudo_url (string), $zona (string),
 *            $stats (array), $torneos_ids (array), $todos_partidos (array)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="scl-pub scl-pub-equipo">

    <div class="scl-pub-equipo__header">
        <?php if ( $escudo_url ) : ?>
            <img src="<?php echo esc_url( $escudo_url ); ?>"
                 alt="<?php echo esc_attr( $equipo->post_title ); ?>"
                 class="scl-pub-equipo__escudo">
        <?php else : ?>
            <div class="scl-pub-equipo__escudo scl-pub-equipo__escudo--placeholder">
                <?php echo esc_html( mb_strtoupper( mb_substr( $equipo->post_title, 0, 2 ) ) ); ?>
            </div>
        <?php endif; ?>
        <div class="scl-pub-equipo__info">
            <h2 class="scl-pub-equipo__nombre"><?php echo esc_html( $equipo->post_title ); ?></h2>
            <?php if ( $zona ) : ?>
                <span class="scl-pub-equipo__zona"><?php echo esc_html( $zona ); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="scl-pub-equipo__stats">
        <div class="scl-pub-stat">
            <span class="scl-pub-stat__valor"><?php echo (int) $stats['PJ']; ?></span>
            <span class="scl-pub-stat__label">PJ</span>
        </div>
        <div class="scl-pub-stat">
            <span class="scl-pub-stat__valor"><?php echo (int) $stats['PG']; ?></span>
            <span class="scl-pub-stat__label">PG</span>
        </div>
        <div class="scl-pub-stat">
            <span class="scl-pub-stat__valor"><?php echo (int) $stats['PE']; ?></span>
            <span class="scl-pub-stat__label">PE</span>
        </div>
        <div class="scl-pub-stat">
            <span class="scl-pub-stat__valor"><?php echo (int) $stats['PP']; ?></span>
            <span class="scl-pub-stat__label">PP</span>
        </div>
        <div class="scl-pub-stat">
            <span class="scl-pub-stat__valor"><?php echo (int) $stats['GF']; ?></span>
            <span class="scl-pub-stat__label">GF</span>
        </div>
        <div class="scl-pub-stat">
            <span class="scl-pub-stat__valor"><?php echo (int) $stats['GC']; ?></span>
            <span class="scl-pub-stat__label">GC</span>
        </div>
    </div>

    <?php if ( ! empty( $torneos_ids ) ) : ?>
        <div class="scl-pub-equipo__torneos">
            <h3 class="scl-pub-equipo__torneos-titulo">Participaciones</h3>
            <ul class="scl-pub-equipo__torneos-lista">
                <?php foreach ( $torneos_ids as $tid ) :
                    $torneo = get_post( $tid );
                    if ( ! $torneo ) continue;
                    $logo_id  = (int) get_post_meta( $tid, 'scl_torneo_logo', true );
                    $logo_url = $logo_id ? (string) wp_get_attachment_image_url( $logo_id, [ 32, 32 ] ) : '';
                ?>
                    <li class="scl-pub-equipo__torneo-item">
                        <?php if ( $logo_url ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>"
                                 alt="<?php echo esc_attr( $torneo->post_title ); ?>"
                                 class="scl-pub-equipo__torneo-logo">
                        <?php endif; ?>
                        <span><?php echo esc_html( $torneo->post_title ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div>
