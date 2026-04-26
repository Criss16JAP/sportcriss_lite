<?php
/**
 * Template: Drawers de Partido (resultado rápido + crear/editar completo).
 * Incluido por partidos-lista.php. Requiere $mis_equipos, $mis_torneos,
 * $temporadas_all y $jornadas_all definidos en el template padre.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     DRAWER: Resultado rápido (marcador de un partido existente)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="scl-drawer" id="scl_resultado_drawer">
	<div class="scl-drawer__overlay"></div>
	<div class="scl-drawer__panel scl-drawer__panel--sm">

		<div class="scl-drawer__header">
			<h3 id="scl_resultado_drawer_titulo">
				<?php esc_html_e( 'Ingresar resultado', 'sportcriss-lite' ); ?>
			</h3>
			<button type="button" class="scl-drawer__close" id="scl_resultado_cerrar"
			        aria-label="<?php esc_attr_e( 'Cerrar', 'sportcriss-lite' ); ?>">&#x2715;</button>
		</div>

		<div class="scl-drawer__body">
			<input type="hidden" id="scl_resultado_partido_id" value="0">
			<input type="hidden" id="scl_resultado_tipo_fase"  value="">

			<!-- Marcador visual -->
			<div class="scl-marcador">
				<div class="scl-marcador__equipo">
					<div class="scl-escudo-sm" id="scl_resultado_escudo_local">
						<div class="scl-escudo-placeholder" id="scl_resultado_placeholder_local">?</div>
						<img id="scl_resultado_img_local" src="" alt="" style="display:none">
					</div>
					<span class="scl-marcador__nombre" id="scl_resultado_nombre_local">&mdash;</span>
					<input type="number" id="scl_resultado_goles_local"
					       class="scl-goles-input" min="0" max="99" placeholder="0">
				</div>

				<div class="scl-marcador__vs">VS</div>

				<div class="scl-marcador__equipo">
					<div class="scl-escudo-sm" id="scl_resultado_escudo_visita">
						<div class="scl-escudo-placeholder" id="scl_resultado_placeholder_visita">?</div>
						<img id="scl_resultado_img_visita" src="" alt="" style="display:none">
					</div>
					<span class="scl-marcador__nombre" id="scl_resultado_nombre_visita">&mdash;</span>
					<input type="number" id="scl_resultado_goles_visita"
					       class="scl-goles-input" min="0" max="99" placeholder="0">
				</div>
			</div>

			<!-- Sección penales (solo visible en playoff con empate) -->
			<div class="scl-penales-seccion" id="scl_resultado_penales_seccion" style="display:none">
				<p class="scl-description scl-description--center">
					<?php esc_html_e( 'Empate — definición por penales', 'sportcriss-lite' ); ?>
				</p>
				<div class="scl-marcador scl-marcador--penales">
					<div class="scl-marcador__equipo">
						<span class="scl-marcador__nombre scl-marcador__nombre--sm">
							<?php esc_html_e( 'Penales', 'sportcriss-lite' ); ?> <span id="scl_resultado_pen_label_local"></span>
						</span>
						<input type="number" id="scl_resultado_penales_local"
						       class="scl-goles-input" min="0" max="99" placeholder="0">
					</div>
					<div class="scl-marcador__vs scl-marcador__vs--sm">&ndash;</div>
					<div class="scl-marcador__equipo">
						<span class="scl-marcador__nombre scl-marcador__nombre--sm">
							<?php esc_html_e( 'Penales', 'sportcriss-lite' ); ?> <span id="scl_resultado_pen_label_visita"></span>
						</span>
						<input type="number" id="scl_resultado_penales_visita"
						       class="scl-goles-input" min="0" max="99" placeholder="0">
					</div>
				</div>
			</div>

			<!-- Estado -->
			<div class="scl-field">
				<label for="scl_resultado_estado">
					<?php esc_html_e( 'Estado', 'sportcriss-lite' ); ?>
				</label>
				<select id="scl_resultado_estado">
					<option value="finalizado"><?php esc_html_e( 'Finalizado', 'sportcriss-lite' ); ?></option>
					<option value="pendiente"><?php esc_html_e( 'Pendiente', 'sportcriss-lite' ); ?></option>
				</select>
			</div>
		</div>

		<div class="scl-drawer__footer">
			<button type="button" class="scl-btn scl-btn--ghost" id="scl_resultado_cancelar">
				<?php esc_html_e( 'Cancelar', 'sportcriss-lite' ); ?>
			</button>
			<button type="button" class="scl-btn scl-btn--primary" id="scl_resultado_guardar">
				<?php esc_html_e( 'Guardar resultado', 'sportcriss-lite' ); ?>
			</button>
		</div>

	</div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     DRAWER: Partido completo (crear y editar)
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="scl-drawer" id="scl_partido_drawer">
	<div class="scl-drawer__overlay"></div>
	<div class="scl-drawer__panel">

		<div class="scl-drawer__header">
			<h3 id="scl_partido_drawer_titulo">
				<?php esc_html_e( 'Nuevo partido', 'sportcriss-lite' ); ?>
			</h3>
			<button type="button" class="scl-drawer__close" id="scl_partido_cerrar"
			        aria-label="<?php esc_attr_e( 'Cerrar', 'sportcriss-lite' ); ?>">&#x2715;</button>
		</div>

		<div class="scl-drawer__body">
			<input type="hidden" id="scl_partido_id" value="0">

			<!-- Torneo -->
			<div class="scl-field">
				<label for="scl_partido_torneo_id">
					<?php esc_html_e( 'Torneo', 'sportcriss-lite' ); ?> <span aria-hidden="true">*</span>
				</label>
				<select id="scl_partido_torneo_id">
					<option value=""><?php esc_html_e( '— Seleccionar torneo —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $mis_torneos as $torneo ) : ?>
						<option value="<?php echo esc_attr( $torneo->ID ); ?>">
							<?php echo esc_html( $torneo->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Temporada -->
			<div class="scl-field">
				<label for="scl_partido_temporada_id">
					<?php esc_html_e( 'Temporada', 'sportcriss-lite' ); ?> <span aria-hidden="true">*</span>
				</label>
				<select id="scl_partido_temporada_id">
					<option value=""><?php esc_html_e( '— Seleccionar temporada —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $temporadas_all as $temp ) : ?>
						<option value="<?php echo esc_attr( $temp->term_id ); ?>">
							<?php echo esc_html( $temp->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Tipo de fase -->
			<div class="scl-field">
				<label for="scl_partido_tipo_fase">
					<?php esc_html_e( 'Tipo de fase', 'sportcriss-lite' ); ?> <span aria-hidden="true">*</span>
				</label>
				<select id="scl_partido_tipo_fase">
					<option value="grupos"><?php esc_html_e( 'Grupos (suma puntos)', 'sportcriss-lite' ); ?></option>
					<option value="playoff"><?php esc_html_e( 'Playoff (eliminatoria)', 'sportcriss-lite' ); ?></option>
				</select>
			</div>

			<!-- Grupo (solo para fase grupos) -->
			<div class="scl-field" id="scl_partido_grupo_field">
				<label for="scl_partido_grupo_id">
					<?php esc_html_e( 'Grupo', 'sportcriss-lite' ); ?>
				</label>
				<select id="scl_partido_grupo_id">
					<option value=""><?php esc_html_e( '— Sin grupo / grupo único —', 'sportcriss-lite' ); ?></option>
					<!-- Cargado via JS al seleccionar torneo -->
				</select>
				<p class="scl-description">
					<?php esc_html_e( 'Opcional. Los grupos se cargan al seleccionar un torneo.', 'sportcriss-lite' ); ?>
				</p>
			</div>

			<!-- Jornada (solo para fase grupos) -->
			<div class="scl-field" id="scl_partido_jornada_field">
				<label for="scl_partido_jornada">
					<?php esc_html_e( 'Jornada', 'sportcriss-lite' ); ?>
				</label>
				<input type="text" id="scl_partido_jornada" list="scl_jornadas_list"
				       placeholder="<?php esc_attr_e( 'Ej: Fecha 1, Jornada 3', 'sportcriss-lite' ); ?>"
				       maxlength="100">
				<datalist id="scl_jornadas_list">
					<?php foreach ( $jornadas_all as $jornada ) : ?>
						<option value="<?php echo esc_attr( $jornada->name ); ?>">
					<?php endforeach; ?>
				</datalist>
				<p class="scl-description">
					<?php esc_html_e( 'Escribe o selecciona una jornada existente.', 'sportcriss-lite' ); ?>
				</p>
			</div>

			<!-- Equipo local -->
			<div class="scl-field">
				<label for="scl_partido_equipo_local_id">
					<?php esc_html_e( 'Equipo local', 'sportcriss-lite' ); ?> <span aria-hidden="true">*</span>
				</label>
				<select id="scl_partido_equipo_local_id">
					<option value=""><?php esc_html_e( '— Seleccionar equipo —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $mis_equipos as $equipo ) : ?>
						<option value="<?php echo esc_attr( $equipo->ID ); ?>">
							<?php echo esc_html( $equipo->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Equipo visitante -->
			<div class="scl-field">
				<label for="scl_partido_equipo_visita_id">
					<?php esc_html_e( 'Equipo visitante', 'sportcriss-lite' ); ?> <span aria-hidden="true">*</span>
				</label>
				<select id="scl_partido_equipo_visita_id">
					<option value=""><?php esc_html_e( '— Seleccionar equipo —', 'sportcriss-lite' ); ?></option>
					<?php foreach ( $mis_equipos as $equipo ) : ?>
						<option value="<?php echo esc_attr( $equipo->ID ); ?>">
							<?php echo esc_html( $equipo->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Fecha -->
			<div class="scl-field">
				<label for="scl_partido_fecha">
					<?php esc_html_e( 'Fecha del partido', 'sportcriss-lite' ); ?>
				</label>
				<input type="date" id="scl_partido_fecha">
			</div>

			<!-- Estado -->
			<div class="scl-field">
				<label for="scl_partido_estado">
					<?php esc_html_e( 'Estado', 'sportcriss-lite' ); ?>
				</label>
				<select id="scl_partido_estado">
					<option value="pendiente"><?php esc_html_e( 'Pendiente', 'sportcriss-lite' ); ?></option>
					<option value="finalizado"><?php esc_html_e( 'Finalizado', 'sportcriss-lite' ); ?></option>
				</select>
			</div>

			<!-- Marcador (visible solo cuando estado = finalizado) -->
			<div class="scl-field" id="scl_partido_marcador_fields" style="display:none">
				<label><?php esc_html_e( 'Marcador', 'sportcriss-lite' ); ?></label>
				<div class="scl-marcador scl-marcador--inline">
					<div class="scl-marcador__equipo scl-marcador__equipo--inline">
						<span class="scl-marcador__label" id="scl_partido_label_local">
							<?php esc_html_e( 'Local', 'sportcriss-lite' ); ?>
						</span>
						<input type="number" id="scl_partido_goles_local"
						       class="scl-goles-input" min="0" max="99" placeholder="0">
					</div>
					<div class="scl-marcador__vs">–</div>
					<div class="scl-marcador__equipo scl-marcador__equipo--inline">
						<input type="number" id="scl_partido_goles_visita"
						       class="scl-goles-input" min="0" max="99" placeholder="0">
						<span class="scl-marcador__label" id="scl_partido_label_visita">
							<?php esc_html_e( 'Visitante', 'sportcriss-lite' ); ?>
						</span>
					</div>
				</div>

				<!-- Penales (solo playoff + empate) -->
				<div class="scl-penales-seccion" id="scl_partido_penales_seccion" style="display:none">
					<p class="scl-description scl-description--center" style="margin-top:1rem">
						<?php esc_html_e( 'Empate — definición por penales', 'sportcriss-lite' ); ?>
					</p>
					<div class="scl-marcador scl-marcador--penales">
						<div class="scl-marcador__equipo">
							<span class="scl-marcador__nombre scl-marcador__nombre--sm">
								<?php esc_html_e( 'Pen. local', 'sportcriss-lite' ); ?>
							</span>
							<input type="number" id="scl_partido_penales_local"
							       class="scl-goles-input" min="0" max="99" placeholder="0">
						</div>
						<div class="scl-marcador__vs scl-marcador__vs--sm">&ndash;</div>
						<div class="scl-marcador__equipo">
							<span class="scl-marcador__nombre scl-marcador__nombre--sm">
								<?php esc_html_e( 'Pen. visitante', 'sportcriss-lite' ); ?>
							</span>
							<input type="number" id="scl_partido_penales_visita"
							       class="scl-goles-input" min="0" max="99" placeholder="0">
						</div>
					</div>
				</div>
			</div>

		</div><!-- /.scl-drawer__body -->

		<div class="scl-drawer__footer">
			<button type="button" class="scl-btn scl-btn--ghost" id="scl_partido_cancelar">
				<?php esc_html_e( 'Cancelar', 'sportcriss-lite' ); ?>
			</button>
			<button type="button" class="scl-btn scl-btn--primary" id="scl_partido_guardar">
				<?php esc_html_e( 'Guardar partido', 'sportcriss-lite' ); ?>
			</button>
		</div>

	</div>
</div>
