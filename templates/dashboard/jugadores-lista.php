<?php
/**
 * Lista de Jugadores del organizador — con drawer inline para crear/editar.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$autor_ef = scl_get_autor_efectivo();

$jugadores = get_posts( [
	'post_type'      => 'scl_jugador',
	'author'         => $autor_ef,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );
?>
<div class="scl-dashboard-header">
	<h2><?php esc_html_e( 'Mis Jugadores', 'sportcriss-lite' ); ?></h2>
	<?php if ( ! scl_es_colaborador() ) : ?>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_nuevo_jugador_btn">
			+ <?php esc_html_e( 'Nuevo jugador', 'sportcriss-lite' ); ?>
		</button>
	<?php endif; ?>
</div>

<?php if ( ! scl_es_colaborador() ) : ?>
<!-- Drawer de creación/edición -->
<div class="scl-inline-form" id="scl_jugador_form" style="display:none">
	<input type="hidden" id="scl_jugador_id" value="0">
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
		<div class="scl-field">
			<label><?php esc_html_e( 'Nombre *', 'sportcriss-lite' ); ?></label>
			<input type="text" id="scl_jugador_nombre" maxlength="100">
		</div>
		<div class="scl-field">
			<label><?php esc_html_e( 'Posición', 'sportcriss-lite' ); ?></label>
			<input type="text" id="scl_jugador_posicion" placeholder="Ej: Delantero, Portero..." maxlength="50">
		</div>
		<div class="scl-field">
			<label><?php esc_html_e( 'Documento / Identificador', 'sportcriss-lite' ); ?></label>
			<input type="text" id="scl_jugador_documento" maxlength="30">
		</div>
		<div class="scl-field">
			<label><?php esc_html_e( 'Foto', 'sportcriss-lite' ); ?></label>
			<div class="scl-file-uploader" id="scl_jugador_foto_uploader">
				<div id="scl_jugador_foto_preview">
					<span class="scl-upload-icon">&#128247;</span>
					<p><?php esc_html_e( 'Click para subir (máx. 5MB)', 'sportcriss-lite' ); ?></p>
				</div>
			</div>
			<input type="file" id="scl_jugador_foto_file" accept="image/*" style="display:none">
			<input type="hidden" id="scl_jugador_foto_id" value="0">
		</div>
	</div>
	<div class="scl-form-actions">
		<button type="button" class="scl-btn scl-btn--ghost" id="scl_jugador_cancelar">
			<?php esc_html_e( 'Cancelar', 'sportcriss-lite' ); ?>
		</button>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_jugador_guardar">
			<?php esc_html_e( 'Guardar', 'sportcriss-lite' ); ?>
		</button>
	</div>
</div>
<?php endif; ?>

<?php if ( empty( $jugadores ) ) : ?>
	<div class="scl-empty">
		<p><?php esc_html_e( 'Aún no has creado ningún jugador.', 'sportcriss-lite' ); ?></p>
	</div>
<?php else : ?>
	<div class="scl-equipos-list" id="scl_jugadores_list">
		<?php foreach ( $jugadores as $j ) :
			$foto_id   = (int) get_post_meta( $j->ID, 'scl_jugador_foto', true );
			$posicion  = get_post_meta( $j->ID, 'scl_jugador_posicion',  true );
			$documento = get_post_meta( $j->ID, 'scl_jugador_documento', true );
			$foto_url  = $foto_id ? wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '';
		?>
			<div class="scl-equipo-item" id="scl_jugador_item_<?php echo esc_attr( $j->ID ); ?>">
				<div class="scl-equipo-item__escudo">
					<?php if ( $foto_url ) : ?>
						<img src="<?php echo esc_url( $foto_url ); ?>" alt="<?php echo esc_attr( $j->post_title ); ?>">
					<?php else : ?>
						<span class="scl-escudo-placeholder">&#128100;</span>
					<?php endif; ?>
				</div>
				<div class="scl-equipo-item__info">
					<h4><?php echo esc_html( $j->post_title ); ?></h4>
					<p>
						<?php if ( $posicion ) : ?>
							<span><?php echo esc_html( $posicion ); ?></span>
						<?php endif; ?>
						<?php if ( $documento ) : ?>
							<span class="scl-badge scl-badge--secondary" style="margin-left:0.5rem">
								<?php echo esc_html( $documento ); ?>
							</span>
						<?php endif; ?>
					</p>
				</div>
				<?php if ( ! scl_es_colaborador() ) : ?>
				<div class="scl-equipo-item__actions">
					<button type="button" class="scl-btn scl-btn--outline scl-btn--sm"
						data-id="<?php echo esc_attr( $j->ID ); ?>"
						data-nombre="<?php echo esc_attr( $j->post_title ); ?>"
						data-posicion="<?php echo esc_attr( $posicion ); ?>"
						data-documento="<?php echo esc_attr( $documento ); ?>"
						data-foto-id="<?php echo esc_attr( $foto_id ); ?>"
						data-foto-url="<?php echo esc_attr( $foto_url ); ?>"
						class="scl_editar_jugador_btn">
						<?php esc_html_e( 'Editar', 'sportcriss-lite' ); ?>
					</button>
					<button type="button" class="scl-btn scl-btn--danger scl-btn--sm scl_eliminar_jugador_btn"
						data-id="<?php echo esc_attr( $j->ID ); ?>"
						data-nombre="<?php echo esc_js( $j->post_title ); ?>">
						<?php esc_html_e( 'Eliminar', 'sportcriss-lite' ); ?>
					</button>
				</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
