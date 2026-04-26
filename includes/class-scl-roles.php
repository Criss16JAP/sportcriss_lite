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
		$role = get_role( self::SLUG );
		if ( $role ) {
			$role->add_cap( 'assign_terms' );
			$role->add_cap( 'edit_terms' );
			return;
		}

		add_role(
			self::SLUG,
			__( 'Organizador', 'sportcriss-lite' ),
			[
				'read'         => true,
				'assign_terms' => true,
				'edit_terms'   => true,
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

// scl_organizador puede crear y asignar términos pero NO eliminarlos
add_filter( 'map_meta_cap', function( $caps, $cap, $user_id, $args ) {
	if ( in_array( $cap, [ 'delete_term', 'manage_terms' ], true ) ) {
		$term_id = $args[0] ?? 0;
		if ( $term_id ) {
			$term = get_term( $term_id );
			if ( $term && in_array( $term->taxonomy, [ 'scl_temporada', 'scl_jornada' ], true ) ) {
				$user = get_userdata( $user_id );
				if ( $user && in_array( 'scl_organizador', (array) $user->roles, true ) ) {
					$caps[] = 'do_not_allow';
				}
			}
		}
	}
	return $caps;
}, 10, 4 );
