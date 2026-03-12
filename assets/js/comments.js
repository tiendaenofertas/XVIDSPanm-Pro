jQuery(document).ready(function($) {
    
    var xvidspro_is_human = false;
    $(document).on('mousemove touchstart keydown scroll click', function() {
        xvidspro_is_human = true;
    });

    // =========================================================================
    // CONTADOR DE CARACTERES EN VIVO
    // =========================================================================
    $('#xvidspro_comment').on('input', function() {
        var max_chars = 500;
        var current_chars = $(this).val().length;
        $('#xvidspro-char-current').text(current_chars);
        
        if (current_chars >= max_chars) {
            $('#xvidspro-char-counter').css('color', '#d63638'); // Rojo advertencia
        } else {
            $('#xvidspro-char-counter').css('color', '#888'); // Gris normal
        }
    });

    // =========================================================================
    // ENVIAR COMENTARIO
    // =========================================================================
    $('#xvidspro-comment-form').on('submit', function(e) {
        e.preventDefault(); 

        var form = $(this);
        var btn = $('#xvidspro_submit_btn');
        var responseDiv = $('#xvidspro-form-response');

        var post_id        = $('#xvidspro_post_id').val();
        var name           = $('#xvidspro_name').val();
        var comment        = $('#xvidspro_comment').val();
        var captcha_answer = $('#xvidspro_captcha_answer').val();
        var captcha_hash   = $('#xvidspro_captcha_hash').val();
        var honeypot       = $('#xvidspro_website_url').val();
        var time_start     = $('#xvidspro_time_start').val();
        var human_token    = xvidspro_is_human ? 'humano_ok_' + post_id : 'bot_detectado';

        // Validar límite antes de enviar
        if(comment.length > 500) {
            responseDiv.html('<p style="color: #d63638; font-weight: bold;">El comentario es demasiado largo (Máx 500 caracteres).</p>');
            return;
        }

        btn.prop('disabled', true).text('Publicando...');
        responseDiv.html('');

        $.ajax({
            url: xvidspro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'xvidspro_submit_comment',
                security: xvidspro_ajax.nonce,
                post_id: post_id,
                name: name,
                comment: comment,
                captcha_answer: captcha_answer,
                captcha_hash: captcha_hash,
                honeypot: honeypot,
                time_start: time_start,
                human_token: human_token 
            },
            success: function(response) {
                if (response.success) {
                    responseDiv.html('<p style="color: #9bd62c; font-weight: bold; margin-top: 10px;">' + response.data.message + '</p>');
                    form[0].reset();
                    $('#xvidspro-char-current').text('0'); // Resetear contador visual
                    $('#xvidspro-no-comments').remove();
                    if ($('#xvidspro-comments-list-container').length) {
                        $('#xvidspro-comments-list-container').prepend(response.data.html);
                    }
                } else {
                    responseDiv.html('<p style="color: #ff5252; font-weight: bold; margin-top: 10px;">' + response.data.message + '</p>');
                }
            },
            error: function() {
                responseDiv.html('<p style="color: #ff5252; margin-top: 10px;">Error de conexión.</p>');
            },
            complete: function() {
                btn.prop('disabled', false).text('Publicar');
                setTimeout(function(){ responseDiv.html(''); }, 5000);
            }
        });
    });

    // =========================================================================
    // ORDENAR Y ME GUSTA
    // =========================================================================
    $('#xvidspro-sort-comments').on('change', function() {
        var order_by = $(this).val();
        var post_id = $(this).data('post-id');
        var listContainer = $('#xvidspro-comments-list-container');
        listContainer.css('opacity', '0.5'); 
        $.ajax({
            url: xvidspro_ajax.ajax_url,
            type: 'POST',
            data: { action: 'xvidspro_sort_comments', security: xvidspro_ajax.nonce, post_id: post_id, order_by: order_by },
            success: function(response) {
                if (response.success) { listContainer.html(response.data.html); }
                listContainer.css('opacity', '1'); 
            }
        });
    });

    $(document).on('click', '.xvidspro-btn-like', function(e) {
        e.preventDefault();
        var btn = $(this);
        var comment_id = btn.data('id');
        if (btn.hasClass('xvidspro-liked')) { return; }
        $.ajax({
            url: xvidspro_ajax.ajax_url,
            type: 'POST',
            data: { action: 'xvidspro_like_comment', security: xvidspro_ajax.nonce, comment_id: comment_id },
            success: function(response) {
                if (response.success) {
                    btn.find('.xvidspro-likes-count').text(response.data.new_likes);
                    btn.addClass('xvidspro-liked');
                }
            }
        });
    });
});