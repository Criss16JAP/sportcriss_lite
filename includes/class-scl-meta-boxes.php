<?php
/**
 * Meta boxes nativos para todos los CPTs del plugin.
 *
 * Implementado en Sprint 1.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Meta_Boxes
 */
class Scl_Meta_Boxes {

	/**
	 * Registra los meta boxes en la pantalla de edición de wp-admin.
	 * Callback del hook 'add_meta_boxes'.
	 */
	public function registrar() {
		// TODO Sprint 1
	}

	/**
	 * Guarda los valores de los meta boxes al publicar/actualizar un post.
	 * Callback del hook 'save_post'.
	 *
	 * @param int     $post_id ID del post.
	 * @param WP_Post $post    Objeto post.
	 */
	public function guardar( $post_id, $post ) {
		// TODO Sprint 1
	}
}
