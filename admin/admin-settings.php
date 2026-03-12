<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function xvidspro_comentarios_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // 1. Guardar ajustes y VALIDAR LICENCIA
    if ( isset( $_POST['xvidspro_comments_submit'] ) && check_admin_referer( 'xvidspro_guardar_ajustes' ) ) {
        // Guardar configuraciones básicas
        update_option( 'xvidspro_comments_status', sanitize_text_field( $_POST['xvidspro_status'] ) );
        update_option( 'xvidspro_btn_bg', sanitize_hex_color( $_POST['xvidspro_btn_bg'] ) );
        update_option( 'xvidspro_btn_text', sanitize_hex_color( $_POST['xvidspro_btn_text'] ) );
        update_option( 'xvidspro_btn_hover', sanitize_hex_color( $_POST['xvidspro_btn_hover'] ) );
        update_option( 'xvidspro_badge_status', sanitize_text_field( $_POST['xvidspro_badge_status'] ) );
        update_option( 'xvidspro_badge_text', sanitize_text_field( $_POST['xvidspro_badge_text'] ) );
        update_option( 'xvidspro_badge_url', esc_url_raw( $_POST['xvidspro_badge_url'] ) );
        update_option( 'xvidspro_badge_nofollow', sanitize_text_field( $_POST['xvidspro_badge_nofollow'] ) );
        
        // --- SISTEMA DE LICENCIAS XVIDSPRO ---
        $license_key = sanitize_text_field($_POST['xvidspam_license_key']);
        update_option('xvidspam_license_key', $license_key);

        if (!empty($license_key)) {
            // Borrar caché para forzar comprobación inmediata al guardar/validar
            delete_transient('xvidspam_license_cache_status');
            delete_transient('xvidspam_license_cache_msg');

            // Limpiar dominio para la validación
            $domain = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);
            $api_url = 'https://xvidspro.com/api/verify_license.php'; 

            $response = wp_remote_post($api_url, [
                'body' => json_encode(['license_key' => $license_key, 'domain' => $domain]),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 15
            ]);

            if (is_wp_error($response)) {
                update_option('xvidspam_license_status', 'error');
                echo '<div class="notice notice-error is-dismissible"><p>❌ Error de conexión con el servidor de licencias XvidsPro.</p></div>';
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if ($body && isset($body['status']) && $body['status'] === 'success') {
                    update_option('xvidspam_license_status', 'valid');
                    echo '<div class="notice notice-success is-dismissible"><p>✅ Licencia validada correctamente. El plugin ha sido activado para este dominio.</p></div>';
                } else {
                    update_option('xvidspam_license_status', 'invalid');
                    $msg = $body['message'] ?? 'Licencia inválida o dominio no autorizado.';
                    echo '<div class="notice notice-error is-dismissible"><p>❌ ' . esc_html($msg) . '</p></div>';
                }
            }
        } else {
            update_option('xvidspam_license_status', 'invalid');
            echo '<div class="notice notice-warning is-dismissible"><p>⚠️ La licencia está vacía. El plugin permanecerá inactivo.</p></div>';
        }
    }

    // 2. Botón de limpieza de estadísticas
    if ( isset( $_POST['xvidspro_reset_stats'] ) && check_admin_referer( 'xvidspro_limpiar_stats' ) ) {
        update_option( 'xvidspro_spam_today', 0 ); 
        update_option( 'xvidspro_total_blocked', 0 );
        update_option( 'xvidspro_approved_today', 0 ); 
        update_option( 'xvidspro_approved_total', 0 );
        update_option( 'xvidspro_rejected_today', 0 ); 
        update_option( 'xvidspro_rejected_total', 0 );
        echo '<div class="notice notice-success is-dismissible"><p>🧹 Las estadísticas han sido puestas a cero.</p></div>';
    }

    // Obtener valores actuales 
    $estado_actual  = get_option( 'xvidspro_comments_status', 'on' );
    $btn_bg         = get_option( 'xvidspro_btn_bg', '#9bd62c' );
    $btn_text       = get_option( 'xvidspro_btn_text', '#111111' );
    $btn_hover      = get_option( 'xvidspro_btn_hover', '#a8e533' );
    
    $badge_status   = get_option( 'xvidspro_badge_status', 'on' );
    $badge_text     = get_option( 'xvidspro_badge_text', '👑 XVIDSPRO' );
    $badge_url      = get_option( 'xvidspro_badge_url', 'https://xvidspro.com/' );
    $badge_nofollow = get_option( 'xvidspro_badge_nofollow', 'on' );
    
    $current_license = get_option('xvidspam_license_key', '');
    $license_status  = get_option('xvidspam_license_status', 'invalid');

    // Lógica para estadísticas
    $hoy = date('Y-m-d');
    $fecha_guardada = get_option('xvidspro_spam_date', '');
    if ( $fecha_guardada !== $hoy ) {
        $spam_today = 0; $approved_today = 0; $rejected_today = 0;
    } else {
        $spam_today     = intval( get_option( 'xvidspro_spam_today', 0 ) );
        $approved_today = intval( get_option( 'xvidspro_approved_today', 0 ) );
        $rejected_today = intval( get_option( 'xvidspro_rejected_today', 0 ) );
    }
    $total_blocked       = intval( get_option( 'xvidspro_total_blocked', 0 ) );
    $approved_total      = intval( get_option( 'xvidspro_approved_total', 0 ) );
    $rejected_total      = intval( get_option( 'xvidspro_rejected_total', 0 ) );
    $total_spam_attempts = $total_blocked + $rejected_total;
    ?>
    <div class="wrap xvidspam-admin-wrapper" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <div class="xvidspam-admin-header" style="display: flex; align-items: center; justify-content: space-between; padding: 20px 0; border-bottom: 2px solid #ddd; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="https://xvidspro.com/" target="_blank" style="display: block; width: 60px; height: 60px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <img src="<?php echo XVIDSPAM_URL . 'assets/images/XvidsPam.png'; ?>" alt="XvidsPam Logo" style="max-width: 100%; height: auto; display: block;">
                </a>
                <h1 style="font-size: 1.8rem; font-weight: bold; color: #111; margin: 0;">Ajustes de XvidsPam</h1>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 1rem; color: #666; font-weight: 600;">Versión 1.0.3</p>
                <p style="margin: 0; font-size: 0.9rem; color: #999;">Blindaje Anti-Spam de Elite</p>
            </div>
        </div>

        <div class="xvidspam-stats-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <div style="background: #fff; border-left: 4px solid #9bd62c; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
                <h3 style="margin-top: 0; color: #646970; font-size: 13px; text-transform: uppercase; font-weight: bold;">Aprobados</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 10px 0 0 0; color: #9bd62c; line-height: 1;"><?php echo $approved_today; ?> <span style="font-size: 1rem; color: #777; font-weight: normal;">hoy</span></p>
                <p style="font-size: 0.9rem; color: #777; margin: 5px 0 0 0;">Total: <strong><?php echo $approved_total; ?></strong></p>
            </div>
            <div style="background: #fff; border-left: 4px solid #e2a829; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
                <h3 style="margin-top: 0; color: #646970; font-size: 13px; text-transform: uppercase; font-weight: bold;">Rechazados</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 10px 0 0 0; color: #e2a829; line-height: 1;"><?php echo $rejected_today; ?> <span style="font-size: 1rem; color: #777; font-weight: normal;">hoy</span></p>
                <p style="font-size: 0.9rem; color: #777; margin: 5px 0 0 0;">Total: <strong><?php echo $rejected_total; ?></strong></p>
            </div>
            <div style="background: #fff; border-left: 4px solid #d63638; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
                <h3 style="margin-top: 0; color: #646970; font-size: 13px; text-transform: uppercase; font-weight: bold;">Bots Bloqueados</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 10px 0 0 0; color: #d63638; line-height: 1;"><?php echo $spam_today; ?> <span style="font-size: 1rem; color: #777; font-weight: normal;">hoy</span></p>
                <p style="font-size: 0.9rem; color: #777; margin: 5px 0 0 0;">Total: <strong><?php echo $total_blocked; ?></strong></p>
            </div>
            <div style="background: #2271b1; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px; color: #fff;">
                <h3 style="margin-top: 0; color: rgba(255,255,255,0.8); font-size: 13px; text-transform: uppercase; font-weight: bold;">Actividad Total</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 10px 0 0 0; color: #fff; line-height: 1;"><?php echo $approved_total + $total_spam_attempts; ?></p>
                <p style="font-size: 0.9rem; color: rgba(255,255,255,0.8); margin: 5px 0 0 0;">Cazados: <strong><?php echo $total_spam_attempts; ?></strong></p>
            </div>
        </div>

        <form method="POST" action="" style="margin-bottom: 40px; display: flex; justify-content: flex-end;">
            <?php wp_nonce_field( 'xvidspro_limpiar_stats' ); ?>
            <input type="submit" name="xvidspro_reset_stats" class="button" value="🧹 Poner Contadores a Cero" onclick="return confirm('¿Seguro que deseas reiniciar los contadores?');">
        </form>
        
        <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 30px;">
        
        <form method="POST" action="">
            <?php wp_nonce_field( 'xvidspro_guardar_ajustes' ); ?>

            <div style="background: <?= $license_status === 'valid' ? 'rgba(0,255,136,0.05)' : 'rgba(255,59,59,0.05)' ?>; border: 1px solid <?= $license_status === 'valid' ? '#00ff88' : '#ff3b3b' ?>; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h2 style="margin-top:0; display:flex; align-items:center; gap:10px;">
                    🔐 Licencia Oficial XvidsPro
                    <?php if($license_status === 'valid'): ?>
                        <span style="background:#00ff88; color:#000; font-size:12px; padding:3px 8px; border-radius:10px; font-weight:bold;">VERIFICADA</span>
                    <?php else: ?>
                        <span style="background:#ff3b3b; color:#fff; font-size:12px; padding:3px 8px; border-radius:10px; font-weight:bold;">NO AUTORIZADO</span>
                    <?php endif; ?>
                </h2>
                <p>Para que el sistema funcione, este dominio (<strong><?= $_SERVER['HTTP_HOST'] ?></strong>) debe estar enlazado a una membresía activa de XvidsPro.</p>
                
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="text" name="xvidspam_license_key" value="<?= esc_attr($current_license) ?>" class="regular-text" placeholder="XVIDS-XXXX-XXXX-XXXX" style="width: 100%; max-width: 350px; padding: 8px; font-family: monospace; font-size: 15px; text-transform: uppercase;">
                    <input type="submit" name="xvidspro_comments_submit" class="button button-primary" value="Validar Licencia" style="padding: 4px 20px; font-weight: bold;">
                </div>
            </div>

            <h2 style="font-size: 1.2rem; color: #111; font-weight: 600; margin-bottom: 10px;">1. Estado General</h2>
            <table class="form-table">
                <tr>
                    <th scope="row" style="font-weight: 600;">Sistema de Comentarios</th>
                    <td>
                        <label style="margin-right: 15px; font-weight: bold; color: green;"><input type="radio" name="xvidspro_status" value="on" <?php checked( $estado_actual, 'on' ); ?>> Activado</label>
                        <label style="font-weight: bold; color: red;"><input type="radio" name="xvidspro_status" value="off" <?php checked( $estado_actual, 'off' ); ?>> Desactivado</label>
                    </td>
                </tr>
            </table>

            <h2 style="font-size: 1.2rem; color: #111; font-weight: 600; margin-top: 30px; margin-bottom: 10px;">2. Diseño del Botón "Publicar"</h2>
            <table class="form-table">
                <tr>
                    <th scope="row" style="font-weight: 600;">Color de Fondo</th>
                    <td><input type="color" name="xvidspro_btn_bg" value="<?php echo esc_attr($btn_bg); ?>"></td>
                </tr>
                <tr>
                    <th scope="row" style="font-weight: 600;">Color del Texto</th>
                    <td><input type="color" name="xvidspro_btn_text" value="<?php echo esc_attr($btn_text); ?>"></td>
                </tr>
                <tr>
                    <th scope="row" style="font-weight: 600;">Color Hover</th>
                    <td><input type="color" name="xvidspro_btn_hover" value="<?php echo esc_attr($btn_hover); ?>"></td>
                </tr>
            </table>

            <h2 style="font-size: 1.2rem; color: #111; font-weight: 600; margin-top: 30px; margin-bottom: 10px;">3. Insignia de Administrador</h2>
            <p style="color: #666; font-size: 0.9rem;">Personaliza la etiqueta que aparece al lado de tu nombre cuando respondes a un comentario.</p>
            <table class="form-table">
                <tr>
                    <th scope="row" style="font-weight: 600;">Estado de la Insignia</th>
                    <td>
                        <label style="margin-right: 15px;"><input type="radio" name="xvidspro_badge_status" value="on" <?php checked( $badge_status, 'on' ); ?>> ✅ Activado</label>
                        <label><input type="radio" name="xvidspro_badge_status" value="off" <?php checked( $badge_status, 'off' ); ?>> ❌ Desactivado</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row" style="font-weight: 600;">Texto de la Insignia</th>
                    <td><input type="text" name="xvidspro_badge_text" value="<?php echo esc_attr($badge_text); ?>" class="regular-text" placeholder="Ej: 👑 XVIDSPAM"></td>
                </tr>
                <tr>
                    <th scope="row" style="font-weight: 600;">URL del Enlace</th>
                    <td><input type="url" name="xvidspro_badge_url" value="<?php echo esc_url($badge_url); ?>" class="regular-text" placeholder="https://tu-sitio.com"></td>
                </tr>
                <tr>
                    <th scope="row" style="font-weight: 600;">Atributo SEO (Nofollow)</th>
                    <td>
                        <label style="margin-right: 15px;"><input type="radio" name="xvidspro_badge_nofollow" value="on" <?php checked( $badge_nofollow, 'on' ); ?>> Activado (Recomendado)</label>
                        <label><input type="radio" name="xvidspro_badge_nofollow" value="off" <?php checked( $badge_nofollow, 'off' ); ?>> Desactivado</label>
                    </td>
                </tr>
            </table>

            <p class="submit" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <input type="submit" name="xvidspro_comments_submit" class="button button-primary" value="Guardar Todos los Ajustes">
            </p>
        </form>
    </div>
    <?php
}