<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function xvidspro_mostrar_lista_comentarios( $post_id ) {
    
    // --- BLOQUEO DE LICENCIA ---
    $license_status = get_option('xvidspam_license_status', 'invalid');
    if ($license_status !== 'valid') {
        return ''; // Retornamos vacío para no mostrar dos veces el cartel rojo en la misma página
    }

    global $wpdb;
    $tabla = $wpdb->prefix . 'custom_comments';

    $comentarios = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $tabla WHERE post_id = %d AND status = 'approved' ORDER BY is_pinned DESC, created_at DESC LIMIT 8",
        $post_id
    ) );

    // Obtener los ajustes de la Insignia del panel
    $badge_status   = get_option( 'xvidspro_badge_status', 'on' );
    $badge_text     = get_option( 'xvidspro_badge_text', '👑 XVIDSPRO' );
    $badge_url      = get_option( 'xvidspro_badge_url', 'https://xvidspro.com/' );
    $badge_nofollow = get_option( 'xvidspro_badge_nofollow', 'on' ) === 'on' ? 'rel="nofollow"' : '';

    ob_start();
    ?>
    <div class="xvidspro-comments-list-wrapper" style="width: 100%; margin-top: 30px;">
        
        <div class="xvidspro-comments-header" style="display: flex !important; justify-content: space-between !important; align-items: center !important; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; width: 100%;">
            <h4 style="margin: 0 !important; font-size: 1.4rem !important; color: #111 !important; font-weight: bold !important;">Comentarios</h4>
            <select id="xvidspro-sort-comments" data-post-id="<?php echo esc_attr($post_id); ?>" style="margin-left: auto !important; padding: 5px 10px !important; background: #fff !important; color: #333 !important; border: 1px solid #ccc !important; border-radius: 4px !important; outline: none !important;">
                <option value="recent">🕒 Más recientes</option>
                <option value="oldest">📜 Más antiguos</option>
                <option value="likes">👍 Con más "Me gusta"</option>
            </select>
        </div>

        <div id="xvidspro-comments-list-container">
            <?php
            if ( $comentarios ) {
                foreach ( $comentarios as $c ) {
                    $pin_html = $c->is_pinned ? '<div style="color: #e2a829; font-size: 0.85rem; font-weight: bold; margin-bottom: 5px;">📌 Comentario Fijado</div>' : '';
                    
                    // Construir HTML de la Insignia dinámicamente
                    $admin_html = '';
                    if ( $c->is_admin && $badge_status === 'on' ) {
                        $admin_html = '<a href="' . esc_url($badge_url) . '" target="_blank" ' . $badge_nofollow . ' style="background:#9bd62c; color:#111; font-size:0.75rem; padding:2px 6px; border-radius:3px; margin-left:8px; text-decoration:none; font-weight:bold; text-transform:uppercase;">' . esc_html($badge_text) . '</a>';
                    }

                    ?>
                    <div class="xvidspro-comment-item" style="border-bottom: 1px solid #eee; padding: 15px 0;">
                        <?php echo $pin_html; ?>
                        <div class="xvidspro-comment-meta" style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span class="emoji-avatar" style="font-size: 1.6rem; margin-right: 10px; line-height: 1;"><?php echo esc_html( $c->emoji ); ?></span>
                            <strong style="font-size: 1.1rem; color: #222;"><?php echo esc_html( $c->name ); ?></strong>
                            <?php echo $admin_html; ?>
                            <span class="date" style="font-size: 0.85rem; color: #999; margin-left: 10px;"><?php echo date('d/m/Y', strtotime($c->created_at)); ?></span>
                        </div>
                        
                        <div class="xvidspro-comment-text" style="margin-bottom: 10px; color: #444; font-size: 1rem; line-height: 1.5;">
                            <p style="margin: 0; padding: 0;"><?php echo nl2br( esc_html( $c->comment ) ); ?></p>
                        </div>
                        
                        <div class="xvidspro-comment-actions">
                            <button class="xvidspro-btn-like" data-id="<?php echo esc_attr( $c->id ); ?>" style="background: #f8f8f8; border: 1px solid #ccc; border-radius: 20px; padding: 4px 15px; cursor: pointer; color: #333; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
                                👍 <span class="xvidspro-likes-count"><?php echo intval( $c->likes ); ?></span>
                            </button>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p id="xvidspro-no-comments" style="color: #666; font-weight: 500;">Aún no hay comentarios. ¡Sé el primero en comentar!</p>';
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
