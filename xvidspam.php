<?php
/**
 * Plugin Name: XvidsPam
 * Plugin URI: https://xvidspro.com/
 * Description: Sistema avanzado y profesional para la gestión y blindaje anti-spam de comentarios en tu plataforma de videos, optimizado para temas WP-Script.
 * Version: 1.0.3
 * Author: XVIDSPRO
 * Text Domain: xvidspam
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente
}

// Definir constantes del plugin 
define( 'XVIDSPAM_PATH', plugin_dir_path( __FILE__ ) );
define( 'XVIDSPAM_URL', plugin_dir_url( __FILE__ ) );

// 1. Cargar la base de datos y funciones
require_once XVIDSPAM_PATH . 'includes/database.php';
require_once XVIDSPAM_PATH . 'includes/functions.php';
require_once XVIDSPAM_PATH . 'includes/anti-spam.php';
require_once XVIDSPAM_PATH . 'includes/emoji.php';

// 2. Cargar el procesador AJAX
require_once XVIDSPAM_PATH . 'public/comments-ajax.php';

// 3. Cargar archivos del frontend 
require_once XVIDSPAM_PATH . 'public/comments-form.php';
require_once XVIDSPAM_PATH . 'public/comments-list.php';

// 4. Cargar archivos de admin SOLO si estamos en el panel
if ( is_admin() && ! wp_doing_ajax() ) {
    require_once XVIDSPAM_PATH . 'admin/admin-menu.php';
    require_once XVIDSPAM_PATH . 'admin/admin-settings.php';
    require_once XVIDSPAM_PATH . 'admin/admin-manager.php'; 
}

// =========================================================================
// Hook de activación (Mantiene el nombre original para evitar el error fatal)
// =========================================================================
register_activation_hook( __FILE__, 'xvidspro_crear_tabla_comentarios' );

// =========================================================================
// Cargar scripts y estilos (Versión 1.0.3 para limpiar la caché del navegador)
// =========================================================================
add_action( 'wp_enqueue_scripts', 'xvidspam_cargar_assets' );
function xvidspam_cargar_assets() {
    
    // Cargar CSS
    wp_enqueue_style( 'xvidspam-style', XVIDSPAM_URL . 'assets/css/style.css', array(), '1.0.3' );
    
    // Cargar JS
    wp_enqueue_script( 'xvidspam-script', XVIDSPAM_URL . 'assets/js/comments.js', array('jquery'), '1.0.3', true );
    
    // Pasar variables seguras al JS (Mantiene 'xvidspro_ajax' para no romper tu JS actual)
    wp_localize_script( 'xvidspam-script', 'xvidspro_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'xvidspro_comment_nonce' )
    ));
}