<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =========================================================================
// 1. MOTOR DE VERIFICACIÓN AUTOMÁTICA DE LICENCIA (Se actualiza cada 6 horas)
// =========================================================================
function xvidspam_check_active_license() {
    $license_key = get_option('xvidspam_license_key', '');
    
    if (empty($license_key)) {
        return ['status' => 'invalid', 'message' => 'No hay licencia configurada. Añade tu licencia en los ajustes de XvidsPam.'];
    }

    // Revisar caché para no saturar tu servidor central
    $cached_status = get_transient('xvidspam_license_cache_status');
    $cached_msg    = get_transient('xvidspam_license_cache_msg');
    
    if ($cached_status !== false) {
        return ['status' => $cached_status, 'message' => $cached_msg];
    }

    // Si la caché expiró, consultamos silenciosamente a la API
    $domain = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);
    $api_url = 'https://xvidspro.com/api/verify_license.php';

    $response = wp_remote_post($api_url, [
        'body'    => json_encode(['license_key' => $license_key, 'domain' => $domain]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        // En caso de caída de red, permitimos acceso temporal para no romper los comentarios
        return ['status' => 'valid', 'message' => ''];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($body && isset($body['status']) && $body['status'] === 'success') {
        // Todo en orden: Guardar en caché por 6 horas
        set_transient('xvidspam_license_cache_status', 'valid', 6 * HOUR_IN_SECONDS);
        set_transient('xvidspam_license_cache_msg', '', 6 * HOUR_IN_SECONDS);
        return ['status' => 'valid', 'message' => ''];
    } else {
        // LICENCIA VENCIDA O DOMINIO ELIMINADO: Bloquear por 6 horas
        $msg = $body['message'] ?? 'Licencia inválida o expirada.';
        set_transient('xvidspam_license_cache_status', 'invalid', 6 * HOUR_IN_SECONDS);
        set_transient('xvidspam_license_cache_msg', $msg, 6 * HOUR_IN_SECONDS);
        return ['status' => 'invalid', 'message' => $msg];
    }
}

// =========================================================================
// 2. REGISTRAR SHORTCODE OPCIONAL [xvidspro_comentarios]
// =========================================================================
add_shortcode( 'xvidspro_comentarios', 'xvidspro_render_comentarios_shortcode' );

function xvidspro_render_comentarios_shortcode() {
    $post_id = get_the_ID();
    if ( ! $post_id ) return '';

    ob_start();
    echo '<div class="xvidspro-procomments-wrapper">';
    if ( function_exists( 'xvidspro_mostrar_formulario_comentarios' ) ) {
        echo xvidspro_mostrar_formulario_comentarios( $post_id );
    }
    if ( function_exists( 'xvidspro_mostrar_lista_comentarios' ) ) {
        echo xvidspro_mostrar_lista_comentarios( $post_id );
    }
    echo '</div>';
    return ob_get_clean();
}

// =========================================================================
// 3. INTEGRACIÓN AUTOMÁTICA (Compatible con WP-Script)
// =========================================================================
add_filter( 'comments_template', 'xvidspro_reemplazar_comentarios_nativos' );

function xvidspro_reemplazar_comentarios_nativos( $theme_template ) {
    if ( is_singular() ) {
        $estado_actual = get_option( 'xvidspro_comments_status', 'on' );
        if ( $estado_actual === 'on' ) {
            return XVIDSPAM_PATH . 'templates/comments-area.php';
        }
    }
    return $theme_template;
}
