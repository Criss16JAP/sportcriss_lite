<?php
/**
 * Vista de Auditoría en wp-admin.
 *
 * Página solo para administrators que muestra el log de auditoría
 * con filtros, paginación y exportación CSV.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Audit_Admin
 */
class Scl_Audit_Admin {

	// -----------------------------------------------------------------------
	// Constantes
	// -----------------------------------------------------------------------

	/** Registros por página en la tabla de auditoría. */
	const PER_PAGE = 20;

	/** Mapa de slugs de acción → etiqueta legible en español. */
	const LABELS_ACCION = [
		'torneo_creado'       => 'Torneo creado',
		'torneo_editado'      => 'Torneo editado',
		'torneo_eliminado'    => 'Torneo eliminado',
		'equipo_creado'       => 'Equipo creado',
		'equipo_editado'      => 'Equipo editado',
		'partido_creado'      => 'Partido creado',
		'resultado_guardado'  => 'Resultado guardado',
		'partido_eliminado'   => 'Partido eliminado',
		'llave_creada'        => 'Llave creada',
		'ganador_confirmado'  => 'Ganador confirmado',
		'importacion_csv'     => 'Importación CSV',
		'colaborador_agregado'=> 'Colaborador agregado',
		'colaborador_revocado'=> 'Colaborador revocado',
		'stats_guardadas'     => 'Estadísticas guardadas',
		'temporada_estado'    => 'Estado de temporada',
	];

	// -----------------------------------------------------------------------
	// Render principal
	// -----------------------------------------------------------------------

	/**
	 * Renderiza la página de auditoría.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'sportcriss-lite' ) );
		}

		// ¿Exportar CSV?
		if ( isset( $_GET['scl_export_audit'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'scl_export_audit' )
		) {
			$this->exportar_csv();
		}

		// Filtros de búsqueda
		$filtros    = $this->obtener_filtros();
		$pagina     = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$offset     = ( $pagina - 1 ) * self::PER_PAGE;

		// Obtener datos paginados
		$resultado  = $this->obtener_registros( $filtros, self::PER_PAGE, $offset );
		$registros  = $resultado['rows'];
		$total      = $resultado['total'];
		$num_pages  = (int) ceil( $total / self::PER_PAGE );

		// Organizadores para el select de filtros
		$organizadores = get_users( [ 'role' => 'scl_organizador' ] );

		?>
		<div class="wrap">
			<h1>🔍 <?php esc_html_e( 'Auditoría — SportCriss Lite', 'sportcriss-lite' ); ?></h1>

			<!-- Formulario de filtros -->
			<form method="get" id="scl-audit-filtros">
				<input type="hidden" name="page" value="scl-auditoria">

				<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;background:#fff;padding:14px;border:1px solid #ccd0d4;border-radius:4px;">

					<div>
						<label for="scl_audit_desde"><strong><?php esc_html_e( 'Desde', 'sportcriss-lite' ); ?></strong></label><br>
						<input type="date" id="scl_audit_desde" name="desde"
							value="<?php echo esc_attr( $filtros['desde'] ); ?>"
							style="width:150px;">
					</div>

					<div>
						<label for="scl_audit_hasta"><strong><?php esc_html_e( 'Hasta', 'sportcriss-lite' ); ?></strong></label><br>
						<input type="date" id="scl_audit_hasta" name="hasta"
							value="<?php echo esc_attr( $filtros['hasta'] ); ?>"
							style="width:150px;">
					</div>

					<div>
						<label for="scl_audit_org"><strong><?php esc_html_e( 'Organizador', 'sportcriss-lite' ); ?></strong></label><br>
						<select id="scl_audit_org" name="organizador_id" style="min-width:180px;">
							<option value=""><?php esc_html_e( '— Todos —', 'sportcriss-lite' ); ?></option>
							<?php foreach ( $organizadores as $org ) : ?>
								<option value="<?php echo esc_attr( $org->ID ); ?>"
									<?php selected( $filtros['organizador_id'], (string) $org->ID ); ?>>
									<?php echo esc_html( $org->display_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div>
						<label for="scl_audit_accion"><strong><?php esc_html_e( 'Acción', 'sportcriss-lite' ); ?></strong></label><br>
						<select id="scl_audit_accion" name="accion" style="min-width:200px;">
							<option value=""><?php esc_html_e( '— Todas —', 'sportcriss-lite' ); ?></option>
							<?php foreach ( self::LABELS_ACCION as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"
									<?php selected( $filtros['accion'], $slug ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div>
						<?php submit_button( __( 'Filtrar', 'sportcriss-lite' ), 'secondary', '', false ); ?>
					</div>

					<div style="margin-left:auto;">
						<?php
						$export_url = add_query_arg(
							array_merge(
								$filtros,
								[
									'page'             => 'scl-auditoria',
									'scl_export_audit' => 1,
									'_wpnonce'         => wp_create_nonce( 'scl_export_audit' ),
								]
							),
							admin_url( 'admin.php' )
						);
						?>
						<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
							⬇ <?php esc_html_e( 'Exportar CSV', 'sportcriss-lite' ); ?>
						</a>
					</div>
				</div>
			</form>

			<!-- Totales -->
			<p style="color:#50575e;">
				<?php
				printf(
					/* translators: %d: total de registros */
					esc_html__( 'Total de registros: %d', 'sportcriss-lite' ),
					(int) $total
				);
				?>
			</p>

			<!-- Tabla de registros -->
			<table class="wp-list-table widefat fixed striped" id="scl-audit-table">
				<thead>
					<tr>
						<th style="width:140px;"><?php esc_html_e( 'Fecha y hora', 'sportcriss-lite' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Usuario', 'sportcriss-lite' ); ?></th>
						<th style="width:150px;"><?php esc_html_e( 'Organizador', 'sportcriss-lite' ); ?></th>
						<th style="width:170px;"><?php esc_html_e( 'Acción', 'sportcriss-lite' ); ?></th>
						<th style="width:150px;"><?php esc_html_e( 'Objeto', 'sportcriss-lite' ); ?></th>
						<th><?php esc_html_e( 'Detalle', 'sportcriss-lite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $registros ) ) : ?>
						<tr>
							<td colspan="6" style="text-align:center;padding:20px;color:#777;">
								<?php esc_html_e( 'No hay registros de auditoría para los filtros seleccionados.', 'sportcriss-lite' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $registros as $row ) : ?>
							<tr>
								<td style="white-space:nowrap;"><?php echo esc_html( $row->created_at ); ?></td>
								<td><?php echo esc_html( $this->get_user_label( (int) $row->user_id ) ); ?></td>
								<td><?php echo esc_html( $this->get_user_label( (int) $row->organizador_id ) ); ?></td>
								<td>
									<span class="scl-audit-accion" style="<?php echo esc_attr( $this->accion_style( $row->accion ) ); ?>">
										<?php echo esc_html( self::LABELS_ACCION[ $row->accion ] ?? $row->accion ); ?>
									</span>
								</td>
								<td>
									<?php echo esc_html( $row->objeto_tipo ); ?>
									<?php if ( $row->objeto_id ) : ?>
										<?php
										$edit_url = get_edit_post_link( (int) $row->objeto_id );
										?>
										<?php if ( $edit_url ) : ?>
											<a href="<?php echo esc_url( $edit_url ); ?>" target="_blank"
												title="<?php esc_attr_e( 'Ver en wp-admin', 'sportcriss-lite' ); ?>">
												#<?php echo absint( $row->objeto_id ); ?> ↗
											</a>
										<?php else : ?>
											#<?php echo absint( $row->objeto_id ); ?>
										<?php endif; ?>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! empty( $row->detalle ) && '[]' !== $row->detalle && 'null' !== $row->detalle ) : ?>
										<button type="button" class="button button-small scl-audit-toggle-detalle"
											data-target="scl-det-<?php echo absint( $row->id ); ?>">
											<?php esc_html_e( 'Ver detalle', 'sportcriss-lite' ); ?>
										</button>
										<pre id="scl-det-<?php echo absint( $row->id ); ?>"
											style="display:none;margin-top:6px;padding:8px;background:#f0f0f1;border-radius:3px;font-size:11px;white-space:pre-wrap;word-break:break-all;max-width:400px;"><?php
											echo esc_html(
												json_encode(
													json_decode( $row->detalle, true ),
													JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
												)
											);
										?></pre>
									<?php else : ?>
										<span style="color:#aaa;">—</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Paginación -->
			<?php if ( $num_pages > 1 ) : ?>
				<div style="margin-top:12px;">
					<?php
					echo paginate_links( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $pagina,
						'total'     => $num_pages,
						'prev_text' => '&laquo; ' . __( 'Anterior', 'sportcriss-lite' ),
						'next_text' => __( 'Siguiente', 'sportcriss-lite' ) . ' &raquo;',
					] );
					?>
				</div>
			<?php endif; ?>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.scl-audit-toggle-detalle').forEach(function(btn) {
				btn.addEventListener('click', function() {
					var pre = document.getElementById(btn.getAttribute('data-target'));
					if (!pre) return;
					var visible = pre.style.display !== 'none';
					pre.style.display = visible ? 'none' : 'block';
					btn.textContent = visible
						? '<?php echo esc_js( __( 'Ver detalle', 'sportcriss-lite' ) ); ?>'
						: '<?php echo esc_js( __( 'Ocultar', 'sportcriss-lite' ) ); ?>';
				});
			});
		});
		</script>
		<?php
	}

	// -----------------------------------------------------------------------
	// Lógica interna
	// -----------------------------------------------------------------------

	/**
	 * Lee y sanitiza los filtros desde $_GET.
	 *
	 * @return array<string,string>
	 */
	private function obtener_filtros(): array {
		return [
			'desde'          => sanitize_text_field( wp_unslash( $_GET['desde'] ?? '' ) ),
			'hasta'          => sanitize_text_field( wp_unslash( $_GET['hasta'] ?? '' ) ),
			'organizador_id' => sanitize_text_field( wp_unslash( $_GET['organizador_id'] ?? '' ) ),
			'accion'         => sanitize_key( wp_unslash( $_GET['accion'] ?? '' ) ),
		];
	}

	/**
	 * Obtiene los registros de auditoría aplicando filtros y paginación.
	 *
	 * @param array<string,string> $filtros
	 * @param int                  $limit
	 * @param int                  $offset
	 * @return array{rows: array, total: int}
	 */
	private function obtener_registros( array $filtros, int $limit, int $offset ): array {
		global $wpdb;

		$tabla  = $wpdb->prefix . 'scl_audit_log';
		$where  = '1=1';
		$params = [];

		if ( ! empty( $filtros['desde'] ) ) {
			$where   .= ' AND created_at >= %s';
			$params[] = $filtros['desde'] . ' 00:00:00';
		}
		if ( ! empty( $filtros['hasta'] ) ) {
			$where   .= ' AND created_at <= %s';
			$params[] = $filtros['hasta'] . ' 23:59:59';
		}
		if ( ! empty( $filtros['organizador_id'] ) ) {
			$where   .= ' AND organizador_id = %d';
			$params[] = (int) $filtros['organizador_id'];
		}
		if ( ! empty( $filtros['accion'] ) ) {
			$where   .= ' AND accion = %s';
			$params[] = $filtros['accion'];
		}

		// Total para paginación
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count_sql = "SELECT COUNT(*) FROM {$tabla} WHERE {$where}";
		$total     = (int) ( $params
			? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			: $wpdb->get_var( $count_sql ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		// Registros paginados
		$data_params   = array_merge( $params, [ $limit, $offset ] );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data_sql = "SELECT * FROM {$tabla} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$rows     = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return [
			'rows'  => $rows ?: [],
			'total' => $total,
		];
	}

	/**
	 * Exporta los registros filtrados como CSV y termina la ejecución.
	 */
	private function exportar_csv(): void {
		$filtros   = $this->obtener_filtros();
		$resultado = $this->obtener_registros( $filtros, PHP_INT_MAX, 0 );
		$registros = $resultado['rows'];

		$bom = "\xEF\xBB\xBF";
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="audit-log-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );

		$out = fopen( 'php://output', 'wb' );
		fwrite( $out, $bom ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		// Cabecera
		fputcsv( $out, [
			'ID',
			'Fecha y hora',
			'Usuario ID',
			'Usuario nombre',
			'Organizador ID',
			'Organizador nombre',
			'Acción',
			'Objeto tipo',
			'Objeto ID',
			'Detalle (JSON)',
		] );

		foreach ( $registros as $row ) {
			fputcsv( $out, [
				$row->id,
				$row->created_at,
				$row->user_id,
				$this->get_user_label( (int) $row->user_id ),
				$row->organizador_id,
				$this->get_user_label( (int) $row->organizador_id ),
				self::LABELS_ACCION[ $row->accion ] ?? $row->accion,
				$row->objeto_tipo,
				$row->objeto_id,
				$row->detalle,
			] );
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Devuelve "Nombre (rol)" para un user_id dado.
	 *
	 * @param int $user_id
	 * @return string
	 */
	private function get_user_label( int $user_id ): string {
		if ( ! $user_id ) return '—';
		$u = get_userdata( $user_id );
		if ( ! $u ) return "(ID {$user_id})";
		$rol = ! empty( $u->roles ) ? implode( ', ', $u->roles ) : 'sin rol';
		return "{$u->display_name} ({$rol})";
	}

	/**
	 * Devuelve un estilo inline de color de badge según la acción.
	 *
	 * @param string $accion
	 * @return string
	 */
	private function accion_style( string $accion ): string {
		$color = '#50575e'; // default gris

		if ( str_contains( $accion, 'creado' ) || str_contains( $accion, 'agregado' ) || str_contains( $accion, 'confirmado' ) ) {
			$color = '#00a32a'; // verde
		} elseif ( str_contains( $accion, 'eliminado' ) || str_contains( $accion, 'revocado' ) ) {
			$color = '#d63638'; // rojo
		} elseif ( str_contains( $accion, 'editado' ) || str_contains( $accion, 'guardado' ) || str_contains( $accion, 'estado' ) || 'importacion_csv' === $accion ) {
			$color = '#dba617'; // amarillo oscuro
		}

		return "display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;background:{$color};color:#fff;white-space:nowrap;";
	}
}
