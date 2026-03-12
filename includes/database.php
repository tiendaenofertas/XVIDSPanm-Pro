<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function xvidspro_crear_tabla_comentarios() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'custom_comments';
    $charset_collate = $wpdb->get_charset_collate();

    // Se añadieron las columnas 'is_pinned' e 'is_admin'
    $sql = "CREATE TABLE $tabla (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        name varchar(100) NOT NULL,
        comment text NOT NULL,
        likes int(11) DEFAULT 0 NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        emoji varchar(10) NOT NULL,
        status varchar(20) DEFAULT 'approved' NOT NULL,
        ip_address varchar(45) DEFAULT '' NOT NULL,
        is_pinned tinyint(1) DEFAULT 0 NOT NULL,
        is_admin tinyint(1) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}