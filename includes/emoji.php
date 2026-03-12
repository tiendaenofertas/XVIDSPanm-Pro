<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function xvidspro_generar_emoji_aleatorio() {
    $emojis = array( '😀', '😎', '🚀', '💡', '🔥', '🥳', '👾', '🌟', '🤖', '🦊', '🍕', '🎯' );
    
    // Seleccionar un índice aleatorio del array
    $clave_aleatoria = array_rand( $emojis );
    
    return $emojis[ $clave_aleatoria ];
}