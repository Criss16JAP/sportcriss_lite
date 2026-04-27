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

	/**
	 * Slug del rol Colaborador.
	 */
	const SLUG_COLABORADOR = 'scl_colaborador';

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
		} else {
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

		if ( ! get_role( self::SLUG_COLABORADOR ) ) {
			add_role(
				self::SLUG_COLABORADOR,
				__( 'Colaborador', 'sportcriss-lite' ),
				[ 'read' => true ]
			);
		}
	}

	/**
	 * Elimina los roles del plugin de WordPress.
	 */
	public static function eliminar() {
		remove_role( self::SLUG );
		remove_role( self::SLUG_COLABORADOR );
	}

}

// ── Funciones auxiliares globales ──────────────────────────────────────────

/**
 * Retorna el ID del organizador al que está vinculado un colaborador.
 */
function scl_get_organizador_de_colaborador( int $colaborador_id ): int {
	return (int) get_user_meta( $colaborador_id, 'scl_colaborador_organizador_id', true );
}

/**
 * Indica si el usuario actual tiene el rol colaborador.
 */
function scl_es_colaborador(): bool {
	$user = wp_get_current_user();
	return in_array( Scl_Roles::SLUG_COLABORADOR, (array) $user->roles, true );
}

/**
 * Devuelve el ID del autor efectivo para queries de datos.
 * Si es colaborador, retorna el ID de su organizador.
 * Si es organizador o admin, retorna el propio ID.
 */
function scl_get_autor_efectivo(): int {
	if ( scl_es_colaborador() ) {
		$org_id = scl_get_organizador_de_colaborador( get_current_user_id() );
		return $org_id ?: get_current_user_id();
	}
	return get_current_user_id();
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
