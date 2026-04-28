<?php
/**
 * Template: Shortcode [scl_tabla_posiciones]
 * Variables inyectadas desde Scl_Public::shortcode_tabla():
 *   $torneo, $temporada, $grupo, $tabla, $mostrar_escudos, $torneo_id, $temporada_term_id
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="scl-pub scl-pub-tabla">

    <div class="scl-pub-tabla__header">
        <span class="scl-pub-tabla__titulo">
            <?php echo esc_html( $torneo ? $torneo->post_title : '' ); ?>
            <?php if ( $temporada && ! is_wp_error( $temporada ) ) echo ' &middot; ' . esc_html( $temporada->name ); ?>
        </span>
        <?php if ( $grupo ) : ?>
            <span class="scl-pub-tabla__grupo">
                <?php echo esc_html( $grupo->post_title ); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="scl-pub-tabla__wrap">
        <table class="scl-pub-tabla__table">
            <thead>
                <tr>
                    <th>#</th>
                    <?php if ( $mostrar_escudos ) : ?><th></th><?php endif; ?>
                    <th class="scl-pub-col-equipo">Equipo</th>
                    <th title="Partidos Jugados">PJ</th>
                    <th title="Partidos Ganados">PG</th>
                    <th title="Partidos Empatados">PE</th>
                    <th title="Partidos Perdidos">PP</th>
                    <th title="Goles a Favor">GF</th>
                    <th title="Goles en Contra">GC</th>
                    <th title="Diferencia de Goles">DG</th>
                    <th title="Puntos">PTS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $tabla as $pos => $equipo ) :
                    $dg_val = (int) $equipo['DG'];
                    $dg     = $dg_val > 0 ? '+' . $dg_val : (string) $dg_val;
                ?>
                    <tr class="scl-pub-tabla__fila<?php echo 0 === $pos ? ' scl-pub-tabla__fila--lider' : ''; ?>">
                        <td class="scl-pub-col-pos"><?php echo esc_html( $pos + 1 ); ?></td>
                        <?php if ( $mostrar_escudos ) : ?>
                            <td class="scl-pub-col-escudo">
                                <?php if ( ! empty( $equipo['escudo_url'] ) ) : ?>
                                    <img src="<?php echo esc_url( $equipo['escudo_url'] ); ?>"
                                         alt="<?php echo esc_attr( $equipo['nombre'] ); ?>"
                                         class="scl-pub-escudo">
                                <?php else : ?>
                                    <div class="scl-pub-escudo scl-pub-escudo--placeholder">
                                        <?php echo esc_html( strtoupper( mb_substr( $equipo['nombre'], 0, 1 ) ) ); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="scl-pub-col-equipo"><?php echo esc_html( $equipo['nombre'] ); ?></td>
                        <td><?php echo (int) $equipo['PJ']; ?></td>
                        <td><?php echo (int) $equipo['PG']; ?></td>
                        <td><?php echo (int) $equipo['PE']; ?></td>
                        <td><?php echo (int) $equipo['PP']; ?></td>
                        <td><?php echo (int) $equipo['GF']; ?></td>
                        <td><?php echo (int) $equipo['GC']; ?></td>
                        <td><?php echo esc_html( $dg ); ?></td>
                        <td class="scl-pub-col-pts"><strong><?php echo (int) $equipo['Pts']; ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="scl-pub-tabla__footer">
        <?php
        $updated_at = get_post_meta( $torneo_id, 'scl_tabla_' . $temporada_term_id . '_updated_at', true );
        if ( $updated_at ) :
        ?>
            <small>Actualizado: <?php echo esc_html( date_i18n( 'j M Y · H:i', strtotime( $updated_at ) ) ); ?></small>
        <?php endif; ?>
    </div>

</div>
