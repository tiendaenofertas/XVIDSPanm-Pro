<?php
/**
 * Plantilla de reemplazo automático para el sistema de comentarios.
 * Este archivo es cargado por el filtro 'comments_template' para sustituir
 * los comentarios nativos del tema WP-Script.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ejecutamos nuestro shortcode automáticamente dentro de esta plantilla
echo do_shortcode( '[xvidspro_comentarios]' );
?>