<?php
/**
 * Template: Dashboard – Llaves Playoff
 * Ruta: /mi-panel/?scl_ruta=llaves
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_url      = home_url( '/mi-panel/' );
$autor_ef      = scl_get_autor_efectivo();
$torneo_filtro = absint( $_GET['torneo_id'] ?? 0 );

// Torneos del organizador (para select de filtro y drawer)
$mis_torneos = get_posts( [
	'post_type'      => 'scl_torneo',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

// Temporadas globales (para select de filtro y drawer)
$temporadas_all = get_terms( [
	'taxonomy'   => 'scl_temporada',
	'hide_empty' => false,
	'orderby'    => 'name',
] );
$temporadas_all = is_wp_error( $temporadas_all ) ? [] : $temporadas_all;

// Equipos del organizador (para selects del drawer)
$mis_equipos = get_posts( [
	'post_type'      => 'scl_equipo',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

// Query de llaves
$llave_args = [
	'post_type'      => 'scl_llave',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'date',
	'order'          => 'ASC',
];
if ( $torneo_filtro ) {
	$llave_args['meta_query'] = [ [
		'key'     => 'scl_llave_torneo_id',
		'value'   => $torneo_filtro,
		'type'    => 'NUMERIC',
		'compare' => '=',
	] ];
}
$llaves = get_posts( $llave_args );

// Agrupar llaves por nombre de fase
$llaves_por_fase = [];
foreach ( $llaves as $llave ) {
	$fase = get_post_meta( $llave->ID, 'scl_llave_nombre_fase', true ) ?: 'Playoff';
	$llaves_por_fase[ $fase ][] = $llave;
}
?>

<div class="scl-page-header">
	<h1 class="scl-page-title"><?php esc_html_e( 'Llaves Playoff', 'sportcriss-lite' ); ?></h1>
	<?php if ( ! scl_es_colaborador() ) : ?>
	<button type="button" class="scl-btn scl-btn--primary" id="scl_nueva_llave_btn">
		+ <?php esc_html_e( 'Nueva llave', 'sportcriss-lite' ); ?>
	</button>
	<?php endif; ?>
</div>

<!-- Filtro por torneo -->
<div class="scl-filtros">
	<select id="scl_filtro_llave_torneo" data-param="torneo_id">
		<option value="0"><?php esc_html_e( 'Todos los torneos', 'sportcriss-lite' ); ?></option>
		<?php foreach ( $mis_torneos as $t ) : ?>
			<option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $torneo_filtro, $t->ID ); ?>>
				<?php echo esc_html( $t->post_title ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>

<?php if ( ! empty( $llaves ) ) :
	foreach ( $llaves_por_fase as $fase_nombre => $llaves_fase ) : ?>

	<div class="scl-fase-grupo">
		<div class="scl-fase-grupo__titulo"><?php echo esc_html( strtoupper( $fase_nombre ) ); ?></div>

		<?php foreach ( $llaves_fase as $llave ) :
			$llave_id    = $llave->ID;
			$estado      = get_post_meta( $llave_id, 'scl_llave_estado',     true ) ?: 'en_curso';
			$es_doble    = get_post_meta( $llave_id, 'scl_llave_es_doble',   true ) === '1';
			$equipo_a_id = (int) get_post_meta( $llave_id, 'scl_llave_equipo_a_id', true );
			$equipo_b_id = (int) get_post_meta( $llave_id, 'scl_llave_equipo_b_id', true );
			$ganador_id  = (int) get_post_meta( $llave_id, 'scl_llave_ganador_id',  true );
			$ganador_prov = (int) get_post_meta( $llave_id, 'scl_llave_ganador_provisional_id', true );

			$ida_id    = (int) get_post_meta( $llave_id, 'scl_llave_partido_ida_id',    true );
			$vuelta_id = (int) get_post_meta( $llave_id, 'scl_llave_partido_vuelta_id', true );

			$nombre_a = get_the_title( $equipo_a_id ) ?: __( '(Equipo A)', 'sportcriss-lite' );
			$nombre_b = get_the_title( $equipo_b_id ) ?: __( '(Equipo B)', 'sportcriss-lite' );

			$esc_a_id  = absint( get_post_meta( $equipo_a_id, 'scl_equipo_escudo', true ) );
			$esc_b_id  = absint( get_post_meta( $equipo_b_id, 'scl_equipo_escudo', true ) );
			$esc_a_img = $esc_a_id ? wp_get_attachment_image( $esc_a_id, [ 48, 48 ] ) : '';
			$esc_b_img = $esc_b_id ? wp_get_attachment_image( $esc_b_id, [ 48, 48 ] ) : '';

			// Marcadores de ida/vuelta
			$gl_ida = get_post_meta( $ida_id, 'scl_partido_goles_local',  true );
			$gv_ida = get_post_meta( $ida_id, 'scl_partido_goles_visita', true );
			$est_ida = get_post_meta( $ida_id, 'scl_partido_estado', true );

			$gl_vue = '';
			$gv_vue = '';
			$est_vue = '';
			if ( $es_doble && $vuelta_id ) {
				$gl_vue  = get_post_meta( $vuelta_id, 'scl_partido_goles_local',  true );
				$gv_vue  = get_post_meta( $vuelta_id, 'scl_partido_goles_visita', true );
				$est_vue = get_post_meta( $vuelta_id, 'scl_partido_estado', true );
			}

			// Agregados
			$agg_a = '';
			$agg_b = '';
			if ( $es_doble && 'finalizado' === $est_ida && 'finalizado' === $est_vue ) {
				$agg_a = (int) $gl_ida + (int) $gv_vue;
				$agg_b = (int) $gv_ida + (int) $gl_vue;
			}

			// Estado badge
			$badge_mapa = [
				'en_curso'              => [ 'label' => __( 'En curso', 'sportcriss-lite' ),              'css' => 'scl-badge--secondary' ],
				'pendiente_confirmacion'=> [ 'label' => __( 'Pend. confirmación', 'sportcriss-lite' ),    'css' => 'scl-badge--warning' ],
				'requiere_penales'      => [ 'label' => __( 'Requiere penales', 'sportcriss-lite' ),      'css' => 'scl-badge--warning' ],
				'resuelta'              => [ 'label' => __( 'Resuelta', 'sportcriss-lite' ),               'css' => 'scl-badge--success' ],
			];
			$badge = $badge_mapa[ $estado ] ?? $badge_mapa['en_curso'];
		?>

		<div class="scl-llave-card" id="scl-llave-<?php echo esc_attr( $llave_id ); ?>">

			<!-- Encabezado -->
			<div class="scl-llave-card__header">
				<span><?php echo esc_html( $fase_nombre ); ?> &middot; <?php echo $es_doble ? esc_html__( 'Ida y vuelta', 'sportcriss-lite' ) : esc_html__( 'Partido único', 'sportcriss-lite' ); ?></span>
				<span class="scl-badge <?php echo esc_attr( $badge['css'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
			</div>

			<!-- Equipos -->
			<div class="scl-llave-card__equipos">
				<div class="scl-llave-card__equipo">
					<div class="scl-escudo-sm">
						<?php if ( $esc_a_img ) : echo $esc_a_img; else : ?>
							<?php echo esc_html( mb_strtoupper( mb_substr( $nombre_a, 0, 1 ) ) ); ?>
						<?php endif; ?>
					</div>
					<strong><?php echo esc_html( $nombre_a ); ?></strong>
				</div>
				<div class="scl-llave-card__vs">VS</div>
				<div class="scl-llave-card__equipo scl-llave-card__equipo--b">
					<div class="scl-escudo-sm">
						<?php if ( $esc_b_img ) : echo $esc_b_img; else : ?>
							<?php echo esc_html( mb_strtoupper( mb_substr( $nombre_b, 0, 1 ) ) ); ?>
						<?php endif; ?>
					</div>
					<strong><?php echo esc_html( $nombre_b ); ?></strong>
				</div>
			</div>

			<!-- Marcadores -->
			<div class="scl-llave-card__marcadores">
				<table>
					<?php if ( $ida_id ) : ?>
					<tr>
						<td><?php echo $es_doble ? esc_html__( 'Ida:', 'sportcriss-lite' ) : esc_html__( 'Partido:', 'sportcriss-lite' ); ?></td>
						<td>
							<?php if ( 'finalizado' === $est_ida && $gl_ida !== '' ) :
								echo esc_html( $nombre_a . ' ' . $gl_ida . ' - ' . $gv_ida . ' ' . $nombre_b );
							else : ?>
								<span style="color:#aaa;"><?php esc_html_e( 'Pendiente', 'sportcriss-lite' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( $es_doble && $vuelta_id ) : ?>
					<tr>
						<td><?php esc_html_e( 'Vuelta:', 'sportcriss-lite' ); ?></td>
						<td>
							<?php if ( 'finalizado' === $est_vue && $gl_vue !== '' ) :
								echo esc_html( $nombre_b . ' ' . $gl_vue . ' - ' . $gv_vue . ' ' . $nombre_a );
							else : ?>
								<span style="color:#aaa;"><?php esc_html_e( 'Pendiente', 'sportcriss-lite' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( $agg_a !== '' ) : ?>
					<tr class="scl-llave-card__global">
						<td><?php esc_html_e( 'Global:', 'sportcriss-lite' ); ?></td>
						<td>
							<?php echo esc_html( $nombre_a . ' ' . $agg_a . ' - ' . $agg_b . ' ' . $nombre_b ); ?>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( 'resuelta' === $estado && $ganador_id ) : ?>
					<tr>
						<td><?php esc_html_e( 'Ganador:', 'sportcriss-lite' ); ?></td>
						<td><strong style="color: var(--scl-success);">&#10003; <?php echo esc_html( get_the_title( $ganador_id ) ); ?></strong></td>
					</tr>
					<?php endif; ?>
				</table>
			</div>

			<!-- Confirmación de ganador -->
			<?php if ( ! scl_es_colaborador() && in_array( $estado, [ 'pendiente_confirmacion', 'requiere_penales' ], true ) ) : ?>
			<div class="scl-llave-confirmar">

				<?php if ( 'pendiente_confirmacion' === $estado && $ganador_prov ) : ?>
					<p><?php printf(
						/* translators: %s: team name */
						esc_html__( 'El sistema calculó: %s avanza.', 'sportcriss-lite' ),
						'<strong>' . esc_html( get_the_title( $ganador_prov ) ) . '</strong>'
					); ?></p>
					<button type="button"
					        class="scl-btn scl-btn--primary scl-btn--sm"
					        onclick="scl_confirmar_ganador(<?php echo esc_js( $llave_id ); ?>, <?php echo esc_js( $ganador_prov ); ?>)">
						&#10003; <?php esc_html_e( 'Confirmar', 'sportcriss-lite' ); ?>
					</button>

				<?php elseif ( 'requiere_penales' === $estado ) : ?>
					<p>&#9917; <?php esc_html_e( 'Empate en el global — definir ganador por penales', 'sportcriss-lite' ); ?></p>
					<div class="scl-penales-form">
						<span><?php echo esc_html( $nombre_a ); ?></span>
						<input type="number" id="scl_pen_a_<?php echo esc_attr( $llave_id ); ?>"
						       class="scl-goles-input" placeholder="0" min="0">
						<span>&mdash;</span>
						<input type="number" id="scl_pen_b_<?php echo esc_attr( $llave_id ); ?>"
						       class="scl-goles-input" placeholder="0" min="0">
						<span><?php echo esc_html( $nombre_b ); ?></span>
					</div>
					<div class="scl-ganador-select">
						<p style="margin: 0 0 0.5rem; font-size: 0.85rem;"><?php esc_html_e( 'Confirmar ganador:', 'sportcriss-lite' ); ?></p>
						<button type="button"
						        class="scl-btn scl-btn--outline scl-btn--sm"
						        onclick="scl_confirmar_con_penales(<?php echo esc_js( $llave_id ); ?>, <?php echo esc_js( $equipo_a_id ); ?>)">
							<?php echo esc_html( $nombre_a ); ?>
						</button>
						<button type="button"
						        class="scl-btn scl-btn--outline scl-btn--sm"
						        onclick="scl_confirmar_con_penales(<?php echo esc_js( $llave_id ); ?>, <?php echo esc_js( $equipo_b_id ); ?>)">
							<?php echo esc_html( $nombre_b ); ?>
						</button>
					</div>
				<?php endif; ?>

			</div>
			<?php endif; ?>

			<!-- Pie de card: acciones -->
			<div class="scl-llave-card__footer">
				<a href="<?php echo esc_url( add_query_arg( [ 'scl_ruta' => 'partidos', 'torneo_id' => get_post_meta( $llave_id, 'scl_llave_torneo_id', true ) ], $home_url ) ); ?>"
				   class="scl-btn scl-btn--outline scl-btn--sm">
					<?php esc_html_e( 'Ver partidos', 'sportcriss-lite' ); ?>
				</a>
				<?php if ( ! scl_es_colaborador() && 'resuelta' !== $estado ) : ?>
				<button type="button"
				        class="scl-btn scl-btn--danger scl-btn--sm scl-llave-eliminar-btn"
				        data-id="<?php echo esc_attr( $llave_id ); ?>"
				        data-titulo="<?php echo esc_attr( $nombre_a . ' vs ' . $nombre_b ); ?>">
					<?php esc_html_e( 'Eliminar', 'sportcriss-lite' ); ?>
				</button>
				<?php endif; ?>
			</div>

		</div>
		<?php endforeach; // $llaves_fase ?>
	</div>
<?php endforeach; // $llaves_por_fase

else : ?>
	<div class="scl-empty">
		<p><?php esc_html_e( 'No hay llaves creadas todavía.', 'sportcriss-lite' ); ?></p>
		<?php if ( ! scl_es_colaborador() ) : ?>
		<p><?php esc_html_e( 'Crea una llave para organizar los cruces eliminatorios de tu torneo.', 'sportcriss-lite' ); ?></p>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_nueva_llave_btn_empty">
			+ <?php esc_html_e( 'Crear primera llave', 'sportcriss-lite' ); ?>
		</button>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( ! scl_es_colaborador() ) : ?>
<!-- ── Drawer: Nueva Llave ──────────────────────────────────────────────── -->
<div class="scl-drawer" id="scl_llave_drawer">
	<div class="scl-drawer__overlay"></div>
	<div class="scl-drawer__panel">
		<div class="scl-drawer__header">
			<h3><?php esc_html_e( 'Nueva Llave Playoff', 'sportcriss-lite' ); ?></h3>
			<button class="scl-drawer__close" id="scl_llave_cerrar">&#10005;</button>
		</div>
		<div class="scl-drawer__body">

			<div class="scl-field">
				<label for="scl_llave_torneo_id"><?php esc_html_e( 'Torneo *', 'sportcriss-lite' ); ?></label>
				<select id="scl_llave_torneo_id">
					<option value="0"><?php esc_html_e( '— Seleccionar torneo —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $mis_torneos as $t ) : ?>
						<option value="<?php echo esc_attr( $t->ID ); ?>"><?php echo esc_html( $t->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="scl-field">
				<label for="scl_llave_temporada_term_id"><?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?></label>
				<select id="scl_llave_temporada_term_id">
					<option value="0"><?php esc_html_e( '— Sin temporada —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $temporadas_all as $t ) : ?>
						<option value="<?php echo esc_attr( $t->term_id ); ?>"><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="scl-field">
				<label for="scl_llave_nombre_fase"><?php esc_html_e( 'Nombre de la fase *', 'sportcriss-lite' ); ?></label>
				<input type="text" id="scl_llave_nombre_fase"
				       list="scl_fases_list"
				       placeholder="<?php esc_attr_e( 'Ej: Cuartos, Semifinal, Final', 'sportcriss-lite' ); ?>">
				<datalist id="scl_fases_list">
					<option value="<?php esc_attr_e( 'Octavos de final', 'sportcriss-lite' ); ?>">
					<option value="<?php esc_attr_e( 'Cuartos de final', 'sportcriss-lite' ); ?>">
					<option value="<?php esc_attr_e( 'Semifinal', 'sportcriss-lite' ); ?>">
					<option value="<?php esc_attr_e( 'Final', 'sportcriss-lite' ); ?>">
					<option value="<?php esc_attr_e( 'Tercer puesto', 'sportcriss-lite' ); ?>">
				</datalist>
			</div>

			<div class="scl-field">
				<label><?php esc_html_e( 'Formato', 'sportcriss-lite' ); ?></label>
				<div class="scl-toggle-group">
					<label class="scl-toggle-option">
						<input type="radio" name="scl_llave_formato" value="0" checked>
						<span><?php esc_html_e( 'Partido único', 'sportcriss-lite' ); ?></span>
					</label>
					<label class="scl-toggle-option">
						<input type="radio" name="scl_llave_formato" value="1">
						<span><?php esc_html_e( 'Ida y vuelta', 'sportcriss-lite' ); ?></span>
					</label>
				</div>
			</div>

			<div class="scl-field-row">
				<div class="scl-field">
					<label for="scl_llave_equipo_a_id"><?php esc_html_e( 'Equipo A *', 'sportcriss-lite' ); ?></label>
					<select id="scl_llave_equipo_a_id">
						<option value="0"><?php esc_html_e( '— Seleccionar —', 'sportcriss-lite' ); ?></option>
						<?php foreach ( $mis_equipos as $e ) : ?>
							<option value="<?php echo esc_attr( $e->ID ); ?>"><?php echo esc_html( $e->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="scl-field">
					<label for="scl_llave_equipo_b_id"><?php esc_html_e( 'Equipo B *', 'sportcriss-lite' ); ?></label>
					<select id="scl_llave_equipo_b_id">
						<option value="0"><?php esc_html_e( '— Seleccionar —', 'sportcriss-lite' ); ?></option>
						<?php foreach ( $mis_equipos as $e ) : ?>
							<option value="<?php echo esc_attr( $e->ID ); ?>"><?php echo esc_html( $e->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="scl-info-box" id="scl_llave_preview"></div>

		</div>
		<div class="scl-drawer__footer">
			<button type="button" class="scl-btn scl-btn--ghost" id="scl_llave_cancelar">
				<?php esc_html_e( 'Cancelar', 'sportcriss-lite' ); ?>
			</button>
			<button type="button" class="scl-btn scl-btn--primary" id="scl_llave_guardar">
				<?php esc_html_e( 'Crear llave y partidos', 'sportcriss-lite' ); ?>
			</button>
		</div>
	</div>
</div>
<?php endif; ?>
