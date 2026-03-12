<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function xvidspro_validar_seguridad_comentario( $comentario, $honeypot, $time_start, $ip ) {
    
    // 1. Trampa Honeypot (Si el bot llena este campo oculto, es spam)
    if ( ! empty( $honeypot ) ) {
        return '🤖 Bot detectado (código H1).';
    }

    // 2. Bloqueo de velocidad (Si tardó menos de 3 segundos en comentar)
    $tiempo_transcurrido = time() - intval( $time_start );
    if ( $tiempo_transcurrido < 3 ) {
        return '🚀 Estás comentando demasiado rápido. Tómate tu tiempo.';
    }

    // 3. Límite de IP (Máximo 3 comentarios por minuto)
    $transient_name = 'xvidspro_limit_' . md5( $ip );
    $intentos = get_transient( $transient_name );
    if ( $intentos !== false && $intentos >= 3 ) {
        return '🛑 Has alcanzado el límite de comentarios. Espera un minuto.';
    }
    set_transient( $transient_name, (int)$intentos + 1, 60 ); // Expira en 60 segundos

    // 4. Filtro de longitud mínima e inútil
    $comentario_limpio = trim( strtolower( $comentario ) );
    if ( strlen( $comentario_limpio ) < 5 ) {
        return '📝 Tu comentario es demasiado corto. Escribe algo más descriptivo.';
    }
    
    $frases_prohibidas = array('good', 'nice post', 'visit my site', 'hi', 'hello');
    if ( in_array( $comentario_limpio, $frases_prohibidas ) ) {
        return '🚫 Este comentario ha sido marcado como repetitivo o spam.';
    }

    // 5. Bloqueo de URLs / Enlaces
    $patron_url = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
    $patron_www = '/www\.[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
    if ( preg_match( $patron_url, $comentario ) || preg_match( $patron_www, $comentario ) ) {
        return '🚨 No se permiten enlaces en los comentarios.';
    }

    return false; // Pasó todas las pruebas
}
