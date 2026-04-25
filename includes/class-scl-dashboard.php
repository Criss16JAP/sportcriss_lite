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
		add_filter( 'query_vars', [ $this, 'registrar_query_vars' ] );
	}

	public function registrar_query_vars( $vars ) {
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
			'nonce' => wp_create_nonce( 'scl_ajax_nonce' ),
			'base'  => home_url( '/mi-panel/' ),
		] );
	}

	public function render(): string {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( home_url( '/mi-panel/' ) ) );
			exit;
		}

		$usuario = wp_get_current_user();
		if ( ! in_array( 'scl_organizador', (array) $usuario->roles, true ) && ! in_array( 'administrator', (array) $usuario->roles, true ) ) {
			return '<p>' . esc_html__( 'No tienes permiso para acceder a este panel.', 'sportcriss-lite' ) . '</p>';
		}

		// Para Sprint 3, la licencia siempre es activa. (Sprint 11 implementará validación)
		$licencia_activa = true;

		$ruta   = get_query_var( 'scl_ruta', 'home' );
		$id     = (int) get_query_var( 'scl_id', 0 );
		$accion = sanitize_key( get_query_var( 'scl_accion', '' ) );

		ob_start();

		$this->render_header( $usuario, $licencia_activa );

		if ( ! $licencia_activa ) {
			$this->render_banner_licencia();
		}

		$this->dispatch( $ruta, $id, $accion, $licencia_activa );

		$this->render_footer();

		return ob_get_clean();
	}

	private function dispatch( string $ruta, int $id, string $accion, bool $licencia_activa ): void {
		$templates = [
			'home'       => 'dashboard/home.php',
			'torneos'    => 'dashboard/torneos-lista.php',
			'temporadas' => 'dashboard/temporadas-lista.php',
			'partidos'   => 'dashboard/partidos-lista.php',
			'llaves'     => 'dashboard/llaves-lista.php',
			'equipos'    => 'dashboard/equipos-lista.php',
			'importar'   => 'dashboard/importador.php',
			'exportar'   => 'dashboard/exportar.php',
		];

		$template = $templates[ $ruta ] ?? $templates['home'];
		$path = SCL_PATH . 'templates/' . $template;

		if ( file_exists( $path ) ) {
			include $path;
		} else {
			echo '<p>' . esc_html__( 'Vista no encontrada.', 'sportcriss-lite' ) . '</p>';
		}
	}

	private function render_header( $usuario, $licencia_activa ) {
		$logout_url = wp_logout_url( home_url() );
		$home_url   = home_url( '/mi-panel/' );
		?>
		<div class="scl-dashboard">
			<nav class="scl-nav">
				<div class="scl-nav__logo">
					<?php esc_html_e( 'SportCriss Lite', 'sportcriss-lite' ); ?>
				</div>
				<ul class="scl-nav__links">
					<li><a href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Inicio', 'sportcriss-lite' ); ?></a></li>
					<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'torneos', $home_url ) ); ?>"><?php esc_html_e( 'Mis Torneos', 'sportcriss-lite' ); ?></a></li>
					<li><a href="<?php echo esc_url( add_query_arg( 'scl_ruta', 'equipos', $home_url ) ); ?>"><?php esc_html_e( 'Mis Equipos', 'sportcriss-lite' ); ?></a></li>
				</ul>
				<div class="scl-nav__user">
					<?php printf( esc_html__( 'Hola, %s', 'sportcriss-lite' ), esc_html( $usuario->display_name ) ); ?> | 
					<a href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Cerrar sesión', 'sportcriss-lite' ); ?></a>
				</div>
			</nav>
			<main class="scl-main">
		<?php
	}

	private function render_footer() {
		?>
			</main>
		</div>
		<?php
	}

	private function render_banner_licencia() {
		?>
		<div class="scl-banner scl-banner--warning">
			<?php esc_html_e( '⚠ Tu licencia ha vencido. El panel está en modo solo lectura.', 'sportcriss-lite' ); ?>
			<a href="#"><?php esc_html_e( 'Renovar licencia', 'sportcriss-lite' ); ?></a>
		</div>
		<?php
	}
}
