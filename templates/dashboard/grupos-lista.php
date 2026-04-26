<?php
/**
 * Lista de Grupos de un Torneo
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$torneo_id = absint( $_GET['scl_id'] ?? 0 );
$torneo = get_post( $torneo_id );

if ( ! $torneo || 'scl_torneo' !== $torneo->post_type || $torneo->post_author != $usuario->ID ) {
	echo '<div class="scl-flash scl-flash--error">Torneo no válido o sin permisos.</div>';
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
		<a href="?scl_ruta=torneos" class="scl-btn scl-btn--ghost" style="padding: 0; margin-right: 10px;">&larr; Mis Torneos</a>
		Grupos de: <?php echo esc_html( $torneo->post_title ); ?>
	</h2>
	<?php if ( $licencia_activa ) : ?>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_btn_nuevo_grupo">+ Nuevo grupo</button>
	<?php endif; ?>
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
				<?php if ( $licencia_activa ) : ?>
				<div class="scl-grupo-item__actions">
					<button type="button" class="scl-btn scl-btn--outline" onclick="scl_confirmar_eliminar_grupo(<?php echo esc_attr( $g->ID ); ?>, '<?php echo esc_js( $g->post_title ); ?>')">Eliminar</button>
				</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
