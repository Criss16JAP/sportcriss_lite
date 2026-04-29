<?php
/**
 * Gestión de Temporadas globales
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$temporadas = get_terms( [
	'taxonomy'   => 'scl_temporada',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );
?>
<div class="scl-dashboard-header">
	<h2>Temporadas</h2>
	<button type="button" class="scl-btn scl-btn--primary" id="scl_btn_nueva_temporada">+ Nueva temporada</button>
</div>

<div class="scl-flash scl-flash--warning" style="margin-bottom: 20px;">
	Nota: Las temporadas son compartidas entre todos los torneos. Solo el administrador puede eliminarlas.
</div>

<div class="scl-inline-form" id="scl_temporada_form" style="display:none">
	<div class="scl-field-row">
		<div class="scl-field">
			<label>Nombre *</label>
			<input type="text" id="scl_temp_nombre" placeholder="Ej: Apertura 2025">
		</div>
		<div class="scl-field">
			<label>Año</label>
			<input type="number" id="scl_temp_anio" value="<?php echo esc_attr( date( 'Y' ) ); ?>">
		</div>
		<div class="scl-field">
			<label>Estado</label>
			<select id="scl_temp_estado">
				<option value="activa">Activa</option>
				<option value="finalizada">Finalizada</option>
			</select>
		</div>
	</div>
	<p class="scl-description">
		Si la temporada ya existe no se duplicará — se reutilizará la existente.
	</p>
	<div class="scl-form-actions">
		<button type="button" class="scl-btn scl-btn--ghost" id="scl_temp_cancelar">Cancelar</button>
		<button type="button" class="scl-btn scl-btn--primary" id="scl_temp_guardar">Guardar temporada</button>
	</div>
</div>

<?php if ( is_wp_error( $temporadas ) || empty( $temporadas ) ) : ?>
	<div class="scl-empty">
		<p>No hay temporadas registradas.</p>
	</div>
<?php else : ?>
	<table class="scl-table">
		<thead>
			<tr>
				<th>Nombre</th>
				<th>Año</th>
				<th>Estado</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $temporadas as $temp ) : 
				$estado = get_term_meta( $temp->term_id, 'scl_temporada_estado', true );
				$anio   = get_term_meta( $temp->term_id, 'scl_temporada_anio', true );
			?>
				<tr>
					<td><strong><?php echo esc_html( $temp->name ); ?></strong></td>
					<td><?php echo esc_html( $anio ); ?></td>
					<td>
						<button type="button"
						        class="scl-btn scl-btn--sm <?php echo 'activa' === $estado ? 'scl-btn--outline' : 'scl-btn--ghost'; ?>"
						        onclick="scl_cambiar_estado_temporada(<?php echo esc_attr( $temp->term_id ); ?>, '<?php echo 'activa' === $estado ? 'finalizada' : 'activa'; ?>')">
							<?php echo 'activa' === $estado ? esc_html__( '→ Marcar finalizada', 'sportcriss-lite' ) : esc_html__( '→ Reactivar', 'sportcriss-lite' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
