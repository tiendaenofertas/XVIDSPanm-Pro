<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function xvidspro_comentarios_manager_page() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'custom_comments';

    // Procesar Acciones (Aprobar, Rechazar, Eliminar, Fijar)
    if ( isset( $_GET['action'], $_GET['id'], $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'accion_comentario_' . $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        $accion = sanitize_text_field( $_GET['action'] );
        
        if ( $accion === 'delete' ) {
            $wpdb->delete( $tabla, array( 'id' => $id ) );
            echo '<div class="notice notice-success is-dismissible"><p>🗑 Comentario eliminado permanentemente.</p></div>';
        } elseif ( $accion === 'approve' ) {
            $wpdb->update( $tabla, array( 'status' => 'approved' ), array( 'id' => $id ) );
            echo '<div class="notice notice-success is-dismissible"><p>✅ Comentario aprobado.</p></div>';
        } elseif ( $accion === 'reject' ) {
            $wpdb->update( $tabla, array( 'status' => 'rejected' ), array( 'id' => $id ) );
            echo '<div class="notice notice-warning is-dismissible"><p>❌ Comentario rechazado.</p></div>';
        } elseif ( $accion === 'pin' ) {
            // Desfijar todos los de ese post primero, y fijar el seleccionado
            $post_id_c = $wpdb->get_var( "SELECT post_id FROM $tabla WHERE id = $id" );
            $wpdb->query( $wpdb->prepare("UPDATE $tabla SET is_pinned = 0 WHERE post_id = %d", $post_id_c) );
            $wpdb->update( $tabla, array( 'is_pinned' => 1 ), array( 'id' => $id ) );
            echo '<div class="notice notice-success is-dismissible"><p>📌 Comentario Fijado en la parte superior.</p></div>';
        } elseif ( $accion === 'unpin' ) {
            $wpdb->update( $tabla, array( 'is_pinned' => 0 ), array( 'id' => $id ) );
            echo '<div class="notice notice-success is-dismissible"><p>Comentario desfijado.</p></div>';
        }
    }

    $per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : ( isset( $_GET['per_page'] ) ? intval( $_GET['per_page'] ) : 20 );
    $paged    = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset   = ( $paged - 1 ) * $per_page;
    
    $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $tabla" );
    $total_pages = ceil( $total_items / $per_page );
    
    $comentarios = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tabla ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Gestor de Comentarios Moderado</h1>
        
        <form method="POST" style="margin: 15px 0; display:flex; align-items:center; gap: 10px;">
            <label><strong>Mostrar por página:</strong></label>
            <select name="per_page" onchange="this.form.submit()">
                <option value="10" <?php selected($per_page, 10); ?>>10 comentarios</option>
                <option value="20" <?php selected($per_page, 20); ?>>20 comentarios</option>
                <option value="50" <?php selected($per_page, 50); ?>>50 comentarios</option>
            </select>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">Estado</th>
                    <th style="width: 150px;">Autor / IP</th>
                    <th>Comentario</th>
                    <th style="width: 150px;">Fecha</th>
                    <th style="width: 320px;">Acciones de Moderación</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $comentarios ) : foreach ( $comentarios as $c ) : 
                    $post_link  = get_permalink( $c->post_id );
                    
                    // CORRECCIÓN: Usamos la nueva ruta xvidspam-manager
                    $base_url   = admin_url( 'admin.php?page=xvidspam-manager&paged='.$paged.'&per_page='.$per_page );
                    $url_aprob  = wp_nonce_url( $base_url . '&action=approve&id=' . $c->id, 'accion_comentario_' . $c->id );
                    $url_rechaz = wp_nonce_url( $base_url . '&action=reject&id=' . $c->id, 'accion_comentario_' . $c->id );
                    $url_elim   = wp_nonce_url( $base_url . '&action=delete&id=' . $c->id, 'accion_comentario_' . $c->id );
                    $url_pin    = wp_nonce_url( $base_url . '&action=pin&id=' . $c->id, 'accion_comentario_' . $c->id );
                    $url_unpin  = wp_nonce_url( $base_url . '&action=unpin&id=' . $c->id, 'accion_comentario_' . $c->id );
                ?>
                    <tr>
                        <td>
                            <?php if($c->status == 'approved') echo '<span style="color:green; font-weight:bold;">✅ Aprobado</span>'; 
                                  elseif($c->status == 'rejected') echo '<span style="color:orange; font-weight:bold;">❌ Rechazado</span>'; 
                                  else echo '<span style="color:gray;">Pendiente</span>'; ?>
                            <br>
                            <?php if($c->is_pinned) echo '<span style="color:#e2a829; font-weight:bold;">📌 Fijado</span>'; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html( $c->emoji . ' ' . $c->name ); ?></strong>
                            <?php if($c->is_admin) echo ' <span style="background:#9bd62c; color:#111; font-size:10px; padding:2px 4px; border-radius:3px;">👑 Admin</span>'; ?>
                            <br><span style="font-size:11px; color:#777;">IP: <?php echo esc_html($c->ip_address); ?></span>
                        </td>
                        <td>
                            <p style="margin-top: 0; font-size:14px;"><?php echo nl2br( esc_html( $c->comment ) ); ?></p>
                            <a href="<?php echo esc_url($post_link); ?>" target="_blank" style="font-size:12px;">Ver Publicación</a>
                        </td>
                        <td><?php echo date( 'd/m/Y h:i A', strtotime( $c->created_at ) ); ?></td>
                        <td>
                            <a href="<?php echo $url_aprob; ?>" class="button" style="border-color: green; color: green;">Aprobar</a>
                            <a href="<?php echo $url_rechaz; ?>" class="button" style="border-color: orange; color: orange;">Rechazar</a>
                            
                            <?php if ( $c->is_pinned ) : ?>
                                <a href="<?php echo $url_unpin; ?>" class="button" style="border-color: #555; color: #555;">Quitar Pin</a>
                            <?php else : ?>
                                <a href="<?php echo $url_pin; ?>" class="button button-primary">📌 Fijar</a>
                            <?php endif; ?>
                            
                            <a href="<?php echo $url_elim; ?>" class="button" style="border-color: red; color: red;" onclick="return confirm('¿Eliminar permanentemente?');">🗑</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5">Aún no hay comentarios publicados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 15px; display:flex; gap: 10px;">
            <?php if ( $paged > 1 ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=xvidspam-manager&paged='.($paged-1).'&per_page='.$per_page ); ?>" class="button">« Anterior</a>
            <?php endif; ?>
            <?php if ( $paged < $total_pages ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=xvidspam-manager&paged='.($paged+1).'&per_page='.$per_page ); ?>" class="button">Siguiente »</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}