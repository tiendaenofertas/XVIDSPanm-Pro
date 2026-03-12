<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

function xvidspro_registrar_estadistica_spam() {
    $hoy = date('Y-m-d');
    $fecha_guardada = get_option('xvidspro_spam_date', '');
    if ( $fecha_guardada !== $hoy ) {
        update_option('xvidspro_spam_today', 1);
        update_option('xvidspro_spam_date', $hoy);
    } else {
        update_option('xvidspro_spam_today', intval( get_option('xvidspro_spam_today', 0) ) + 1);
    }
    update_option('xvidspro_total_blocked', intval( get_option('xvidspro_total_blocked', 0) ) + 1);
}

// Generador de HTML de Insignias y Pines 
function xvidspro_generar_html_comentario($c) {
    // 1. Obtener ajustes dinámicos de la insignia
    $badge_status   = get_option( 'xvidspro_badge_status', 'on' );
    $badge_text     = get_option( 'xvidspro_badge_text', '👑 XVIDSPRO' );
    $badge_url      = get_option( 'xvidspro_badge_url', 'https://xvidspro.com/' );
    $badge_nofollow = get_option( 'xvidspro_badge_nofollow', 'on' ) === 'on' ? 'rel="nofollow"' : '';

    $pin_html = $c->is_pinned ? '<div style="color: #e2a829; font-size: 0.85rem; font-weight: bold; margin-bottom: 5px;">📌 Comentario Fijado</div>' : '';
    
    // 2. Construir Insignia si está activada
    $admin_html = '';
    if ( $c->is_admin && $badge_status === 'on' ) {
        $admin_html = '<a href="' . esc_url($badge_url) . '" target="_blank" ' . $badge_nofollow . ' style="background:#9bd62c; color:#111; font-size:0.75rem; padding:2px 6px; border-radius:3px; margin-left:8px; text-decoration:none; font-weight:bold; text-transform:uppercase;">' . esc_html($badge_text) . '</a>';
    }
    
    return '
    <div class="xvidspro-comment-item" style="border-bottom: 1px solid #eee; padding: 15px 0;">
        ' . $pin_html . '
        <div class="xvidspro-comment-meta" style="display: flex; align-items: center; margin-bottom: 8px;">
            <span class="emoji-avatar" style="font-size: 1.6rem; margin-right: 10px; line-height: 1;">' . esc_html($c->emoji) . '</span>
            <strong style="font-size: 1.1rem; color: #222;">' . esc_html($c->name) . '</strong>
            ' . $admin_html . '
            <span class="date" style="font-size: 0.85rem; color: #999; margin-left: 10px;">' . date('d/m/Y', strtotime($c->created_at)) . '</span>
        </div>
        <div class="xvidspro-comment-text" style="margin-bottom: 10px; color: #444; font-size: 1rem; line-height: 1.5;">
            <p style="margin: 0; padding: 0;">' . nl2br( esc_html($c->comment) ) . '</p>
        </div>
        <div class="xvidspro-comment-actions">
            <button class="xvidspro-btn-like" data-id="' . esc_attr($c->id) . '" style="background: #f8f8f8; border: 1px solid #ccc; border-radius: 20px; padding: 4px 15px; cursor: pointer; color: #333; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
                👍 <span class="xvidspro-likes-count">' . intval($c->likes) . '</span>
            </button>
        </div>
    </div>';
}

add_action( 'wp_ajax_xvidspro_submit_comment', 'xvidspro_ajax_submit_comment' );
add_action( 'wp_ajax_nopriv_xvidspro_submit_comment', 'xvidspro_ajax_submit_comment' );

function xvidspro_ajax_submit_comment() {
    check_ajax_referer( 'xvidspro_comment_nonce', 'security' );

    $post_id        = intval( $_POST['post_id'] );
    $name           = sanitize_text_field( $_POST['name'] );
    $comment        = sanitize_textarea_field( $_POST['comment'] );
    $captcha_answer = isset( $_POST['captcha_answer'] ) ? intval( $_POST['captcha_answer'] ) : 0;
    $captcha_hash   = isset( $_POST['captcha_hash'] ) ? sanitize_text_field( $_POST['captcha_hash'] ) : '';
    $honeypot       = isset( $_POST['honeypot'] ) ? sanitize_text_field( $_POST['honeypot'] ) : '';
    $time_start     = isset( $_POST['time_start'] ) ? intval( $_POST['time_start'] ) : 0;
    $human_token    = isset( $_POST['human_token'] ) ? sanitize_text_field( $_POST['human_token'] ) : '';
    $ip_address     = $_SERVER['REMOTE_ADDR'];

    if ( mb_strlen($comment) > 500 ) {
        wp_send_json_error( array( 'message' => 'El comentario supera los 500 caracteres.' ) );
    }

    $llave_esperada = 'humano_ok_' . $post_id;
    if ( $human_token !== $llave_esperada ) {
        xvidspro_registrar_estadistica_spam();
        wp_send_json_error( array( 'message' => '🛑 Bloqueo de Seguridad: Actividad sospechosa detectada.' ) );
    }

    if ( empty( $name ) || empty( $comment ) || empty( $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Todos los campos son obligatorios.' ) );
    }

    $expected_hash = wp_hash( $captcha_answer . 'xvidspro_seguridad', 'nonce' );
    if ( ! hash_equals( $expected_hash, $captcha_hash ) ) {
        xvidspro_registrar_estadistica_spam(); 
        wp_send_json_error( array( 'message' => '🤖 Error: Respuesta matemática incorrecta.' ) );
    }

    if ( function_exists('xvidspro_validar_seguridad_comentario') ) {
        $spam_error = xvidspro_validar_seguridad_comentario( $comment, $honeypot, $time_start, $ip_address );
        if ( $spam_error ) {
            xvidspro_registrar_estadistica_spam();
            wp_send_json_error( array( 'message' => $spam_error ) );
        }
    }

    $is_admin = ( is_user_logged_in() && current_user_can( 'manage_options' ) ) ? 1 : 0;
    $emoji = function_exists('xvidspro_generar_emoji_aleatorio') ? xvidspro_generar_emoji_aleatorio() : '😀';

    global $wpdb;
    $tabla = $wpdb->prefix . 'custom_comments';

    $insertado = $wpdb->insert(
        $tabla,
        array(
            'post_id'    => $post_id,
            'name'       => $name,
            'comment'    => $comment,
            'emoji'      => $emoji,
            'likes'      => 0,
            'status'     => 'approved',
            'ip_address' => $ip_address,
            'is_pinned'  => 0,
            'is_admin'   => $is_admin,
            'created_at' => current_time('mysql')
        )
    );

    if ( $insertado ) {
        $nuevo_id = $wpdb->insert_id;
        $c = $wpdb->get_row( "SELECT * FROM $tabla WHERE id = $nuevo_id" );
        $html = xvidspro_generar_html_comentario($c);

        wp_send_json_success( array( 'message' => '✅ Comentario publicado con éxito.', 'html' => $html ) );
    } else {
        wp_send_json_error( array( 'message' => '❌ Error al guardar.' ) );
    }
}

add_action( 'wp_ajax_xvidspro_sort_comments', 'xvidspro_ajax_sort_comments' );
add_action( 'wp_ajax_nopriv_xvidspro_sort_comments', 'xvidspro_ajax_sort_comments' );

function xvidspro_ajax_sort_comments() {
    check_ajax_referer( 'xvidspro_comment_nonce', 'security' );

    $post_id  = intval( $_POST['post_id'] );
    $order_by = sanitize_text_field( $_POST['order_by'] );
    
    global $wpdb;
    $tabla = $wpdb->prefix . 'custom_comments';

    $order_query = "ORDER BY is_pinned DESC, created_at DESC"; 
    if ( $order_by === 'oldest' ) {
        $order_query = "ORDER BY is_pinned DESC, created_at ASC"; 
    } elseif ( $order_by === 'likes' ) {
        $order_query = "ORDER BY is_pinned DESC, likes DESC, created_at DESC"; 
    }

    $comentarios = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tabla WHERE post_id = %d AND status = 'approved' $order_query LIMIT 8", $post_id ) );

    if ( $comentarios ) {
        $html = '';
        foreach ( $comentarios as $c ) {
            $html .= xvidspro_generar_html_comentario($c);
        }
        wp_send_json_success( array( 'html' => $html ) );
    } else {
        wp_send_json_success( array( 'html' => '<p id="xvidspro-no-comments" style="color:#666;">Aún no hay comentarios.</p>' ) );
    }
}

add_action( 'wp_ajax_xvidspro_like_comment', 'xvidspro_ajax_like_comment' );
add_action( 'wp_ajax_nopriv_xvidspro_like_comment', 'xvidspro_ajax_like_comment' );
function xvidspro_ajax_like_comment() {
    check_ajax_referer( 'xvidspro_comment_nonce', 'security' );
    $comment_id = intval( $_POST['comment_id'] );
    if ( !$comment_id ) wp_send_json_error();
    
    global $wpdb;
    $tabla = $wpdb->prefix . 'custom_comments';
    $wpdb->query( $wpdb->prepare( "UPDATE $tabla SET likes = likes + 1 WHERE id = %d", $comment_id ) );
    $new_likes = $wpdb->get_var( $wpdb->prepare( "SELECT likes FROM $tabla WHERE id = %d", $comment_id ) );
    wp_send_json_success( array( 'new_likes' => $new_likes ) );
}