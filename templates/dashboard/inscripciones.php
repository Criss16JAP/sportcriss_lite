<?php
/**
 * Gestión de inscripciones de jugadores por torneo/equipo.
 *
 * URL esperada: ?scl_ruta=inscripciones&scl_id={torneo_id}
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$autor_ef  = scl_get_autor_efectivo();
$torneo_id = $id;

if ( $torneo_id <= 0 ) {
	// Vista de selección: lista de torneos para elegir cuál gestionar
	$mis_torneos = get_posts( [
		'post_type'      => 'scl_torneo',
		'author'         => $autor_ef,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );
	?>
	<div class="scl-dashboard-header">
		<h2><?php esc_html_e( 'Inscripciones por Torneo', 'sportcriss-lite' ); ?></h2>
	</div>
	<?php if ( empty( $mis_torneos ) ) : ?>
		<div class="scl-empty">
			<p>
				<?php esc_html_e( 'Aún no tienes torneos.', 'sportcriss-lite' ); ?>
				<a href="?scl_ruta=torneos&scl_accion=nuevo">
					<?php esc_html_e( 'Crea tu primer torneo', 'sportcriss-lite' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<div class="scl-equipos-list">
			<?php foreach ( $mis_torneos as $t ) : ?>
				<div class="scl-equipo-item">
					<div class="scl-equipo-item__info">
						<h4><?php echo esc_html( $t->post_title ); ?></h4>
					</div>
					<div class="scl-equipo-item__actions">
						<a href="?scl_ruta=inscripciones&scl_id=<?php echo esc_attr( $t->ID ); ?>"
						   class="scl-btn scl-btn--outline scl-btn--sm">
							<?php esc_html_e( 'Gestionar', 'sportcriss-lite' ); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php
	return;
}

// Vista de inscripciones de un torneo específico
$torneo = get_post( $torneo_id );

if ( ! $torneo || 'scl_torneo' !== $torneo->post_type
	|| ( (int) $torneo->post_author !== $autor_ef && ! current_user_can( 'manage_options' ) )
) {
	echo '<div class="scl-flash scl-flash--error">' . esc_html__( 'Torneo no válido o sin permisos.', 'sportcriss-lite' ) . '</div>';
	return;
}

// Equipos disponibles del organizador
$mis_equipos = get_posts( [
	'post_type'      => 'scl_equipo',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

// Jugadores disponibles del organizador
$mis_jugadores = get_posts( [
	'post_type'      => 'scl_jugador',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );

// Temporadas del torneo (via scl_temporada taxonomy terms on its partidos,
// but more practical: use scl_temporada terms that have partidos in this torneo)
global $wpdb;
$temporadas_raw = $wpdb->get_results( $wpdb->prepare(
	"SELECT DISTINCT t.term_id, t.name
	 FROM {$wpdb->terms} t
	 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id AND tt.taxonomy = 'scl_temporada'
	 INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
	 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = tr.object_id AND pm.meta_key = 'scl_partido_torneo_id' AND pm.meta_value = %d
	 ORDER BY t.name ASC",
	$torneo_id
) );
?>
<div class="scl-dashboard-header">
	<h2>
		<a href="?scl_ruta=inscripciones" class="scl-btn scl-btn--ghost" style="padding:0;margin-right:10px;">&larr;</a>
		<?php
		/* translators: %s = nombre del torneo */
		printf( esc_html__( 'Inscripciones: %s', 'sportcriss-lite' ), esc_html( $torneo->post_title ) );
		?>
	</h2>
</div>

<?php if ( empty( $mis_equipos ) ) : ?>
	<div class="scl-flash scl-flash--error">
		<?php esc_html_e( 'Debes crear equipos antes de gestionar inscripciones.', 'sportcriss-lite' ); ?>
	</div>
<?php else : ?>

<!-- Formulario de inscripción -->
<div class="scl-inline-form" style="margin-bottom:2rem">
	<h3 style="margin-top:0"><?php esc_html_e( 'Inscribir jugador', 'sportcriss-lite' ); ?></h3>
	<input type="hidden" id="scl_insc_torneo_id" value="<?php echo esc_attr( $torneo_id ); ?>">
	<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
		<div class="scl-field">
			<label><?php esc_html_e( 'Equipo *', 'sportcriss-lite' ); ?></label>
			<select id="scl_insc_equipo_id">
				<option value=""><?php esc_html_e( 'Seleccionar equipo', 'sportcriss-lite' ); ?></option>
				<?php foreach ( $mis_equipos as $eq ) : ?>
					<option value="<?php echo esc_attr( $eq->ID ); ?>"><?php echo esc_html( $eq->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="scl-field">
			<label><?php esc_html_e( 'Jugador *', 'sportcriss-lite' ); ?></label>
			<select id="scl_insc_jugador_id">
				<option value=""><?php esc_html_e( 'Seleccionar jugador', 'sportcriss-lite' ); ?></option>
				<?php foreach ( $mis_jugadores as $jug ) : ?>
					<option value="<?php echo esc_attr( $jug->ID ); ?>"><?php echo esc_html( $jug->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="scl-field">
			<label><?php esc_html_e( 'Temporada (opcional)', 'sportcriss-lite' ); ?></label>
			<select id="scl_insc_temporada_id">
				<option value="0"><?php esc_html_e( 'Sin temporada específica', 'sportcriss-lite' ); ?></option>
				<?php foreach ( $temporadas_raw as $temp ) : ?>
					<option value="<?php echo esc_attr( $temp->term_id ); ?>"><?php echo esc_html( $temp->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="scl-form-actions">
		<button type="button" class="scl-btn scl-btn--primary" id="scl_inscribir_btn">
			<?php esc_html_e( 'Inscribir', 'sportcriss-lite' ); ?>
		</button>
	</div>
	<div id="scl_insc_msg" style="margin-top:0.75rem"></div>
</div>

<!-- Lista de inscripciones actuales por equipo -->
<?php foreach ( $mis_equipos as $eq ) :
	$inscritos = Scl_Stats::get_jugadores_inscritos( $eq->ID, $torneo_id );
	if ( empty( $inscritos ) ) continue;
?>
	<div class="scl-section" style="margin-bottom:2rem">
		<h3 style="margin:0 0 0.75rem"><?php echo esc_html( $eq->post_title ); ?>
			<small style="font-size:0.8rem;color:#888;font-weight:normal">
				(<?php echo count( $inscritos ); ?> <?php esc_html_e( 'jugadores', 'sportcriss-lite' ); ?>)
			</small>
		</h3>
		<div class="scl-equipos-list">
			<?php foreach ( $inscritos as $ins ) :
				$foto_url = $ins->jugador_foto_id
					? wp_get_attachment_image_url( (int) $ins->jugador_foto_id, 'thumbnail' )
					: '';
			?>
				<div class="scl-equipo-item" id="scl_insc_item_<?php echo esc_attr( $ins->id ); ?>">
					<div class="scl-equipo-item__escudo">
						<?php if ( $foto_url ) : ?>
							<img src="<?php echo esc_url( $foto_url ); ?>" alt="<?php echo esc_attr( $ins->jugador_nombre ); ?>">
						<?php else : ?>
							<span class="scl-escudo-placeholder">&#128100;</span>
						<?php endif; ?>
					</div>
					<div class="scl-equipo-item__info">
						<h4><?php echo esc_html( $ins->jugador_nombre ); ?></h4>
					</div>
					<div class="scl-equipo-item__actions">
						<button type="button" class="scl-btn scl-btn--danger scl-btn--sm scl_desinscribir_btn"
							data-id="<?php echo esc_attr( $ins->id ); ?>"
							data-nombre="<?php echo esc_attr( $ins->jugador_nombre ); ?>">
							<?php esc_html_e( 'Retirar', 'sportcriss-lite' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endforeach; ?>

<?php endif; ?>
