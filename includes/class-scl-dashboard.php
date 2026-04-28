<?php
/**
 * Controlador del dashboard frontend privado.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Dashboard
 */
class Scl_Dashboard {

	public function __construct() {
		add_shortcode( 'scl_dashboard', [ $this, 'render' ] );
	}

	public function registrar_query_vars( array $vars ): array {
		$vars[] = 'scl_ruta';
		$vars[] = 'scl_id';
		$vars[] = 'scl_accion';
		return $vars;
	}

	// Template redirect no lo usamos para el routing interno, el shortcode maneja el render.
	// Solo despachar descargas/exportaciones si es necesario, pero eso lo hace Scl_Export
	public function despachar() {
	}

	public function encolar_assets() {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'scl_dashboard' ) ) {
			return;
		}

		wp_enqueue_style( 'scl-dashboard-css', SCL_URL . 'assets/css/dashboard.css', [], SCL_VERSION );
		wp_enqueue_script( 'scl-dashboard-js', SCL_URL . 'assets/js/dashboard.js', [ 'jquery' ], SCL_VERSION, true );

		wp_localize_script( 'scl-dashboard-js', 'scl_ajax', [
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'scl_dashboard_nonce' ),
			'base'  => home_url( '/mi-panel/' ),
		] );

		$ruta = get_query_var( 'scl_ruta', 'home' );
		if ( 'importar' === $ruta ) {
			wp_enqueue_script(
				'scl-importer-js',
				SCL_URL . 'assets/js/importer.js',
				[ 'jquery', 'scl-dashboard-js' ],
				SCL_VERSION,
				true
			);
		}
	}

	public function render(): string {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/acceso/' ) );
			exit;
		}

		$usuario = wp_get_current_user();
		$roles_permitidos = [ 'scl_organizador', 'scl_colaborador', 'administrator' ];
		if ( empty( array_intersect( $roles_permitidos, (array) $usuario->roles ) ) ) {
			return '<p>No tienes permiso para acceder a este panel.</p>';
		}

		$ruta   = get_query_var( 'scl_ruta', 'home' );
		$id     = (int) get_query_var( 'scl_id', 0 );
		$accion = sanitize_key( get_query_var( 'scl_accion', '' ) );

		ob_start();
		$this->render_header( $usuario );
		$this->dispatch( $ruta, $id, $accion );
		$this->render_footer();
		return ob_get_clean();
	}

	private function dispatch( string $ruta, int $id, string $accion ): void {
		// Rutas bloqueadas para colaboradores
		if ( scl_es_colaborador() ) {
			$rutas_permitidas_colaborador = [ 'home', 'partidos' ];
			if ( ! in_array( $ruta, $rutas_permitidas_colaborador, true ) ) {
				include SCL_PATH . 'templates/dashboard/acceso-denegado.php';
				return;
			}
			// Colaboradores solo pueden ver la lista, no crear/editar partidos
			if ( 'partidos' === $ruta && in_array( $accion, [ 'nuevo', 'editar' ], true ) ) {
				include SCL_PATH . 'templates/dashboard/acceso-denegado.php';
				return;
			}
		}

		$templates = [
			'home'          => 'dashboard/home.php',
			'torneos'       => 'dashboard/torneos-lista.php',
			'grupos'        => 'dashboard/grupos-lista.php',
			'temporadas'    => 'dashboard/temporadas.php',
			'partidos'      => 'dashboard/partidos-lista.php',
			'llaves'        => 'dashboard/llaves-lista.php',
			'equipos'       => 'dashboard/equipos-lista.php',
			'importar'      => 'dashboard/importador.php',
			'exportar'      => 'dashboard/exportar.php',
			'configuracion' => 'dashboard/configuracion.php',
		];

		$template = $templates[ $ruta ] ?? $templates['home'];

		if ( 'torneos' === $ruta && in_array( $accion, [ 'nuevo', 'editar' ], true ) ) {
			$template = 'dashboard/torneos-form.php';
		}

		$path = SCL_PATH . 'templates/' . $template;

		if ( file_exists( $path ) ) {
			include $path;
		} else {
			echo '<p>' . esc_html__( 'Vista no encontrada.', 'sportcriss-lite' ) . '</p>';
		}
	}

	private function render_header( $usuario ): void {
		$logout_url     = wp_logout_url( home_url( '/acceso/' ) );
		$home_url       = home_url( '/mi-panel/' );
		$es_colaborador = scl_es_colaborador();
		$portal_nombre  = get_option( 'scl_portal_nombre', 'SportCriss Lite' );
		?>
		<div class="scl-dashboard">
		<nav class="scl-nav">
			<div class="scl-nav__inner">
				<div class="scl-nav__logo">&#9917; <?php echo esc_html( $portal_nombre ); ?></div>

				<button class="scl-nav__hamburger" id="scl_hamburger" aria-label="Menú">
					<span></span><span></span><span></span>
				</button>

				<ul class="scl-nav__links" id="scl_nav_links">
					<li><a href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Inicio', 'sportcriss-lite' ); ?></a></li>
					<?php if ( ! $es_colaborador ) : ?>
						<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'torneos', $home_url ) ); ?>"><?php esc_html_e( 'Mis Torneos', 'sportcriss-lite' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'equipos', $home_url ) ); ?>"><?php esc_html_e( 'Mis Equipos', 'sportcriss-lite' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'temporadas', $home_url ) ); ?>"><?php esc_html_e( 'Temporadas', 'sportcriss-lite' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'llaves', $home_url ) ); ?>"><?php esc_html_e( 'Llaves', 'sportcriss-lite' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'importar', $home_url ) ); ?>"><?php esc_html_e( 'Importar CSV', 'sportcriss-lite' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'exportar', $home_url ) ); ?>"><?php esc_html_e( 'Exportar', 'sportcriss-lite' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'configuracion', $home_url ) ); ?>"><?php esc_html_e( 'Configuración', 'sportcriss-lite' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'partidos', $home_url ) ); ?>"><?php esc_html_e( 'Partidos', 'sportcriss-lite' ); ?></a></li>
				</ul>

				<div class="scl-nav__user">
					<?php if ( $es_colaborador ) : ?>
						<span class="scl-badge scl-badge--secondary" style="margin-right:0.5rem;"><?php esc_html_e( 'Colaborador', 'sportcriss-lite' ); ?></span>
					<?php endif; ?>
					<span class="scl-nav__user-name"><?php echo esc_html( $usuario->display_name ); ?></span>
					<a href="<?php echo esc_url( $logout_url ); ?>" class="scl-nav__logout"><?php esc_html_e( 'Salir', 'sportcriss-lite' ); ?></a>
				</div>
			</div>
		</nav>
		<main class="scl-main">
		<div class="scl-main__inner">
		<?php
	}

	private function render_footer(): void {
		echo '</div></main></div>';
	}


}
