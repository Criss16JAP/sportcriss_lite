<?php
/**
 * Loader centralizado de hooks y filters.
 *
 * Acumula actions y filters en arrays internos y los registra en WordPress
 * de una sola vez al llamar a run(). Esto permite que cada módulo declare
 * sus dependencias sin registrarlas directamente, facilitando pruebas y
 * desacoplamiento.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Loader
 */
class Scl_Loader {

	/**
	 * Colección de actions a registrar.
	 *
	 * @var array[]
	 */
	private $actions = [];

	/**
	 * Colección de filters a registrar.
	 *
	 * @var array[]
	 */
	private $filters = [];

	// -----------------------------------------------------------------------
	// API pública
	// -----------------------------------------------------------------------

	/**
	 * Encola un action para ser registrado en WordPress.
	 *
	 * @param string   $hook          Nombre del hook de WordPress.
	 * @param callable $callback      Callable (función, método, closure).
	 * @param int      $priority      Prioridad de ejecución. Default 10.
	 * @param int      $accepted_args Número de argumentos que acepta el callback. Default 1.
	 */
	public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = $this->build( $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Encola un filter para ser registrado en WordPress.
	 *
	 * @param string   $hook          Nombre del hook de WordPress.
	 * @param callable $callback      Callable (función, método, closure).
	 * @param int      $priority      Prioridad de ejecución. Default 10.
	 * @param int      $accepted_args Número de argumentos que acepta el callback. Default 1.
	 */
	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = $this->build( $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Registra en WordPress todos los actions y filters acumulados.
	 * Debe llamarse una única vez, al final del bootstrap del plugin.
	 */
	public function run() {
		foreach ( $this->actions as $action ) {
			add_action(
				$action['hook'],
				$action['callback'],
				$action['priority'],
				$action['accepted_args']
			);
		}

		foreach ( $this->filters as $filter ) {
			add_filter(
				$filter['hook'],
				$filter['callback'],
				$filter['priority'],
				$filter['accepted_args']
			);
		}
	}

	// -----------------------------------------------------------------------
	// Métodos internos
	// -----------------------------------------------------------------------

	/**
	 * Construye el array que representa un hook.
	 *
	 * @param string   $hook
	 * @param callable $callback
	 * @param int      $priority
	 * @param int      $accepted_args
	 * @return array
	 */
	private function build( $hook, $callback, $priority, $accepted_args ) {
		return [
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];
	}
}
