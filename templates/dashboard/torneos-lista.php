<?php
/**
 * Lista de Torneos del organizador
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$torneos = get_posts( [
	'post_type'      => 'scl_torneo',
	'author'         => scl_get_autor_efectivo(),
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );
?>
<div class="scl-dashboard-header">
	<h2>Mis Torneos</h2>
	<a href="?scl_ruta=torneos&scl_accion=nuevo" class="scl-btn scl-btn--primary">+ Nuevo torneo</a>
</div>

<div class="scl-dashboard-search">
	<input type="text" id="scl_buscar_torneo" placeholder="Buscar torneo..." class="scl-field">
</div>

<?php if ( empty( $torneos ) ) : ?>
	<div class="scl-empty">
		<p>Aún no tienes torneos creados.</p>
		<a href="?scl_ruta=torneos&scl_accion=nuevo" class="scl-btn scl-btn--primary">Crear mi primer torneo</a>
	</div>
<?php else : ?>
	<div class="scl-torneos-grid">
		<?php foreach ( $torneos as $t ) : 
			$logo_id = get_post_meta( $t->ID, 'scl_torneo_logo', true );
			$siglas  = get_post_meta( $t->ID, 'scl_torneo_siglas', true ) ?: substr( $t->post_title, 0, 3 );
			$grupos  = count( get_posts( [
				'post_type'      => 'scl_grupo',
				'post_parent'    => $t->ID,
				'posts_per_page' => -1,
			] ) );
		?>
			<div class="scl-torneo-card">
				<div class="scl-torneo-card__header">
					<div class="scl-torneo-card__logo">
						<?php if ( $logo_id ) : ?>
							<?php echo wp_get_attachment_image( $logo_id, 'thumbnail' ); ?>
						<?php else : ?>
							<span class="scl-torneo-card__logo-placeholder"><?php echo esc_html( strtoupper( substr( $t->post_title, 0, 1 ) ) ); ?></span>
						<?php endif; ?>
					</div>
					<div class="scl-torneo-card__info">
						<h3><?php echo esc_html( $t->post_title ); ?> <span class="scl-torneo-card__siglas">[<?php echo esc_html( strtoupper( $siglas ) ); ?>]</span></h3>
						<p><?php echo esc_html( $grupos ); ?> grupo(s)</p>
					</div>
				</div>
				<div class="scl-torneo-card__actions">
					<a href="?scl_ruta=partidos&scl_torneo_id=<?php echo esc_attr( $t->ID ); ?>" class="scl-btn scl-btn--outline">Ver partidos</a>
					<a href="?scl_ruta=grupos&scl_id=<?php echo esc_attr( $t->ID ); ?>" class="scl-btn scl-btn--outline">Grupos</a>
					<a href="?scl_ruta=torneos&scl_id=<?php echo esc_attr( $t->ID ); ?>&scl_accion=editar" class="scl-btn scl-btn--outline">Editar</a>
					<button type="button" class="scl-btn scl-btn--danger" onclick="scl_confirmar_eliminar_torneo(<?php echo esc_attr( $t->ID ); ?>, '<?php echo esc_js( $t->post_title ); ?>')">Eliminar</button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
