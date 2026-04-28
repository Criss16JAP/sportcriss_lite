<?php
/**
 * Rutina de desinstalación de SportCriss Lite.
 *
 * Se ejecuta cuando el administrador elimina el plugin desde Plugins → Eliminar.
 * Los posts CPT (torneos, equipos, partidos, etc.) NO se eliminan aquí —
 * el administrador debe hacer limpieza manual si lo desea.
 *
 * @package SportCrissLite
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Eliminar roles personalizados
remove_role( 'scl_organizador' );
remove_role( 'scl_colaborador' );

// 2. Eliminar opciones del plugin
$opciones = [
	'scl_portal_nombre',
	'scl_importador_limite',
	'scl_tabla_transient_ttl',
	'scl_db_version',
	'scl_flush_rewrite',
];
foreach ( $opciones as $opcion ) {
	delete_option( $opcion );
}

// 3. Eliminar user_meta de colaboradores
$wpdb->delete(
	$wpdb->usermeta,
	[ 'meta_key' => 'scl_colaborador_organizador_id' ],
	[ '%s' ]
);

// 4. Eliminar transients del plugin
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '_transient_scl_%'
	 OR option_name LIKE '_transient_timeout_scl_%'"
);

// 5. Eliminar tabla de log de anuncios
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}scl_ad_log" );

// 6. Eliminar caches de tabla de posiciones (post_meta con prefijo scl_tabla_)
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta}
	 WHERE meta_key LIKE 'scl_tabla_%'"
);

/*
 * NOTA: Los CPTs (scl_torneo, scl_equipo, scl_partido, scl_anuncio, etc.)
 * y sus posts NO se eliminan automáticamente — el admin debe decidir.
 * Esto protege los datos en caso de desinstalación accidental.
 * Para borrado total usar "Eliminar todos los datos" en Configuración.
 */
