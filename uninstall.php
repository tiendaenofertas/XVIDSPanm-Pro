<?php
/**
 * Se ejecuta cuando el plugin XVIDSPRO ProComments es eliminado.
 * Limpia la base de datos y las opciones guardadas.
 */

// Si la desinstalación no fue llamada desde WordPress, salimos inmediatamente
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Eliminar la tabla personalizada de comentarios
$tabla_comentarios = $wpdb->prefix . 'custom_comments';
$wpdb->query( "DROP TABLE IF EXISTS $tabla_comentarios" );

// 2. Eliminar las opciones de configuración guardadas en la tabla wp_options
delete_option( 'xvidspro_comments_status' );

// Nota: Si en el futuro agregas más opciones de configuración (ej. color principal, 
// número de comentarios a mostrar), debes usar delete_option() aquí para eliminarlas.