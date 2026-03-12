<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'xvidspam_comentarios_menu' );

function xvidspam_comentarios_menu() {
    // Menú principal CORTO y con nuevo icono de Escudo
    add_menu_page(
        'XvidsPam',             
        'XvidsPam',             
        'manage_options',                   
        'xvidspam',             
        'xvidspro_comentarios_settings_page', 
        'dashicons-buddicons-topics', // <--- ICONO ACTUAL        
        25                                  
    );

    // Submenú 1: Ajustes
    add_submenu_page(
        'xvidspam',
        'Ajustes de XvidsPam',
        'Ajustes',
        'manage_options',
        'xvidspam',
        'xvidspro_comentarios_settings_page'
    );

    // Submenú 2: Gestor de Comentarios
    add_submenu_page(
        'xvidspam',
        'Gestor de Comentarios',
        'Ver Comentarios',
        'manage_options',
        'xvidspam-manager',
        'xvidspro_comentarios_manager_page'
    );
}
