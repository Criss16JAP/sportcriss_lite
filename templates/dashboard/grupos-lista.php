<?php
/**
 * Lista de Grupos — un torneo específico (scl_id > 0) o todos los del organizador
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$autor_ef  = scl_get_autor_efectivo();
$torneo_id = $id; // int, viene del dispatcher (0 si no hay scl_id en la URL)

// ── Modo A: torneo específico ──────────────────────────────────────────────
if ( $torneo_id > 0 ) {

	$torneo = get_post( $torneo_id );

	if ( ! $torneo || 'scl_torneo' !== $torneo->post_type
		|| ( (int) $torneo->post_author !== $autor_ef && ! current_user_can( 'manage_options' ) )
	) {
		echo '<div class="scl-empty">'
			. '<p style="font-size:2rem">📋</p>'
			. '<h3>' . esc_html__( 'Torneo no encontrado', 'sportcriss-lite' ) . '</h3>'
			. '<p>' . esc_html__( 'El torneo que buscas no existe o no tienes acceso.', 'sportcriss-lite' ) . '</p>'
			. '<a href="?scl_ruta=torneos" class="scl-btn scl-btn--primary">' . esc_html__( 'Ver mis torneos', 'sportcriss-lite' ) . '</a>'
			. '</div>';
		return;
	}

	$grupos = get_posts( [
		'post_type'      => 'scl_grupo',
		'post_parent'    => $torneo_id,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );
	?>
	<div class="scl-dashboard-header">
		<h2>
			<a href="?scl_ruta=torneos" class="scl-btn scl-btn--ghost" style="padding:0; margin-right:10px;">&larr; Mis Torneos</a>
			Grupos de: <?php echo esc_html( $torneo->post_title ); ?>
		</h2>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_btn_nuevo_grupo">+ Nuevo grupo</button>
	</div>

	<div class="scl-inline-form" id="scl_grupo_form" style="display:none">
		<input type="hidden" id="scl_grupo_torneo_id" value="<?php echo esc_attr( $torneo_id ); ?>">
		<div class="scl-field">
			<label>Nombre del grupo *</label>
			<input type="text" id="scl_grupo_nombre" placeholder="Ej: Grupo A">
		</div>
		<div class="scl-field">
			<label>Descripción (opcional)</label>
			<input type="text" id="scl_grupo_descripcion">
		</div>
		<div class="scl-form-actions">
			<button type="button" class="scl-btn scl-btn--ghost" id="scl_grupo_cancelar">Cancelar</button>
			<button type="button" class="scl-btn scl-btn--primary" id="scl_grupo_guardar">Guardar</button>
		</div>
	</div>

	<?php if ( empty( $grupos ) ) : ?>
		<div class="scl-empty">
			<p>Aún no hay grupos creados en este torneo.</p>
		</div>
	<?php else : ?>
		<div class="scl-grupos-list">
			<?php foreach ( $grupos as $g ) :
				$desc = get_post_meta( $g->ID, 'scl_grupo_descripcion', true );
			?>
				<div class="scl-grupo-item">
					<div class="scl-grupo-item__info">
						<h4><?php echo esc_html( $g->post_title ); ?></h4>
						<?php if ( $desc ) : ?>
							<p><?php echo esc_html( $desc ); ?></p>
						<?php endif; ?>
					</div>
					<div class="scl-grupo-item__actions">
						<button type="button" class="scl-btn scl-btn--outline"
							onclick="scl_confirmar_eliminar_grupo(<?php echo esc_attr( $g->ID ); ?>, '<?php echo esc_js( $g->post_title ); ?>')">
							Eliminar
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php
	return; // fin modo A
}

// ── Modo B: todos los torneos del organizador ──────────────────────────────
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
	<h2>Grupos</h2>
</div>

<?php if ( empty( $mis_torneos ) ) : ?>
	<div class="scl-empty">
		<p>
			Aún no tienes torneos.
			<a href="?scl_ruta=torneos&scl_accion=nuevo">Crea tu primer torneo</a>
			para luego agregar grupos.
		</p>
	</div>
<?php else : ?>
	<?php foreach ( $mis_torneos as $t ) :
		$grupos_del_torneo = get_posts( [
			'post_type'      => 'scl_grupo',
			'post_parent'    => $t->ID,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
	?>
		<div class="scl-section" style="margin-bottom:2rem">
			<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem">
				<h3 style="margin:0"><?php echo esc_html( $t->post_title ); ?></h3>
				<a href="?scl_ruta=grupos&scl_id=<?php echo esc_attr( $t->ID ); ?>"
				   class="scl-btn scl-btn--primary">+ Nuevo grupo</a>
			</div>
			<?php if ( empty( $grupos_del_torneo ) ) : ?>
				<p style="color:#888; font-size:0.9rem">
					Sin grupos. <a href="?scl_ruta=grupos&scl_id=<?php echo esc_attr( $t->ID ); ?>">Agregar</a>
				</p>
			<?php else : ?>
				<div class="scl-grupos-list">
					<?php foreach ( $grupos_del_torneo as $g ) :
						$desc = get_post_meta( $g->ID, 'scl_grupo_descripcion', true );
					?>
						<div class="scl-grupo-item">
							<div class="scl-grupo-item__info">
								<h4><?php echo esc_html( $g->post_title ); ?></h4>
								<?php if ( $desc ) : ?>
									<p><?php echo esc_html( $desc ); ?></p>
								<?php endif; ?>
							</div>
							<div class="scl-grupo-item__actions">
								<button type="button" class="scl-btn scl-btn--outline"
									onclick="scl_confirmar_eliminar_grupo(<?php echo esc_attr( $g->ID ); ?>, '<?php echo esc_js( $g->post_title ); ?>')">
									Eliminar
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
