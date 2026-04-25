<?php
/**
 * Gestión del rol Organizador.
 *
 * Responsable de crear y eliminar el rol `scl_organizador`.
 * Los métodos son estáticos para poder invocarse desde los hooks de
 * activación/desactivación sin necesidad de instanciar la clase.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Scl_Roles
 */
class Scl_Roles {

	/**
	 * Slug del rol Organizador.
	 */
	const SLUG = 'scl_organizador';

	// -----------------------------------------------------------------------
	// API pública
	// -----------------------------------------------------------------------

	/**
	 * Registra el rol `scl_organizador` en WordPress.
	 *
	 * Solo se crea si no existe previamente para evitar duplicados en
	 * activaciones repetidas. La única capacidad asignada es `read`:
	 * lo mínimo para que WordPress reconozca al usuario como válido, pero
	 * sin otorgarle acceso a ninguna pantalla nativa del wp-admin.
	 *
	 * El acceso al dashboard frontend se controla por código en Scl_Access,
	 * no mediante capacidades de WordPress.
	 *
	 * Llamado desde: register_activation_hook en sportcriss-lite.php
	 */
	public static function registrar() {
		if ( get_role( self::SLUG ) ) {
			return;
		}

		add_role(
			self::SLUG,
			__( 'Organizador', 'sportcriss-lite' ),
			[
				'read' => true,
			]
		);
	}

	/**
	 * Elimina el rol `scl_organizador` de WordPress.
	 *
	 * Llamado desde: uninstall hook (aún no implementado en Sprint 0).
	 * Los usuarios que tenían este rol pasan a no tener rol asignado;
	 * WordPress los mantiene como usuarios pero sin capacidades.
	 */
	public static function eliminar() {
		remove_role( self::SLUG );
	}

}
