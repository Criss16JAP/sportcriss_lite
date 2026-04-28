<?php
/**
 * Template: Shortcode [scl_torneos]
 * Variables: $torneos (array WP_Post[])
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="scl-pub scl-pub-torneos-grid">
    <?php foreach ( $torneos as $torneo ) :
        $logo_id  = (int) get_post_meta( $torneo->ID, 'scl_torneo_logo', true );
        $logo_url = $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
        $siglas   = get_post_meta( $torneo->ID, 'scl_torneo_siglas', true );
    ?>
        <div class="scl-pub-torneo-card">
            <?php if ( $logo_url ) : ?>
                <img src="<?php echo esc_url( $logo_url ); ?>"
                     alt="<?php echo esc_attr( $torneo->post_title ); ?>">
            <?php else : ?>
                <div class="scl-pub-torneo-card__placeholder">
                    <?php echo esc_html( $siglas ?: strtoupper( mb_substr( $torneo->post_title, 0, 3 ) ) ); ?>
                </div>
            <?php endif; ?>
            <span class="scl-pub-torneo-card__nombre"><?php echo esc_html( $torneo->post_title ); ?></span>
        </div>
    <?php endforeach; ?>
</div>
