// Função para gerar o formulário
function cupom_personalizado_form() {
    ob_start();
    ?>
    <style>
        #apply-coupon {
            height: 50px;
            border-radius: 0 5px 5px 0 !important;
            background-color: var(--e-global-color-f5169dc) !important;
            color: #fff !important;
            top: 5px;
            position: relative;
        }
        .dd__remove_btn {
            display: inline-block;
            margin-top: 10px;
            color: #dc3545;
            text-decoration: none;
            cursor: pointer;
        }

    </style>
    <p>If you have a coupon code, please apply it below.</p>
    <div class="dd__coupon_wrapper">
        <form style="width:100%" method="post" class="cupom-personalizado-form">
            <p class="form-row form-row-first">
                <label for="coupon_code" class="screen-reader-text">Coupon:</label>
                <input style="height:50px; outline:none; border:0; position:relative; top:5px;" type="text" name="codigo_cupom" placeholder="Coupon Code">
            </p>
            <p class="form-row form-row-last" style="text-align: right;">
                <input type="submit" id="apply-coupon" name="aplicar_cupom" value="Apply coupon">
            </p>
        </form>
    </div>
    <div id="cupons-ativos">
    <?php
    // Verifica se há cupons aplicados
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (!empty($applied_coupons)) {
        foreach ($applied_coupons as $coupon) {
            echo '<div>';
            echo '<a style="margin-top: 10px; color: #dc3545; text-decoration: none; cursor: pointer;" class="dd__remove_btn" data-coupon="' . esc_attr($coupon) . '">Remove Coupon: ' . esc_html($coupon) . '</a>';
            echo '</div>';
        }
    }
    ?>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $(document).on('click', '.dd__remove_btn', function(e) {
            e.preventDefault();
            var coupon = $(this).data('coupon');
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'remover_cupom_ajax',
                    coupon: coupon
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cupom removido com sucesso!');
                        location.reload(); // Recarrega a página para atualizar o carrinho
                    } else {
                        alert('Error removing coupon. Please try again.');
                    }
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Função para processar o formulário
function processar_cupom_personalizado() {
    if (isset($_POST['aplicar_cupom']) && !empty($_POST['codigo_cupom'])) {
        $codigo_cupom = sanitize_text_field($_POST['codigo_cupom']);
        
        if (WC()->cart->apply_coupon($codigo_cupom)) {
            wc_add_notice(__('Coupon applied successfully!', 'seu-tema'), 'success');
        } else {
            wc_add_notice(__('Coupon invalid or expired.', 'seu-tema'), 'error');
        }
        
        // Redireciona para a página de sign-up
        wp_safe_redirect(home_url('/sign-up'));
        exit;
    }
}
add_action('init', 'processar_cupom_personalizado');

// Função AJAX para remover cupom
function remover_cupom_ajax() {
    if (isset($_POST['coupon'])) {
        $coupon = sanitize_text_field($_POST['coupon']);
        $removed = WC()->cart->remove_coupon($coupon);
        if ($removed) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    } else {
        wp_send_json_error();
    }
    wp_die();
}
add_action('wp_ajax_remover_cupom_ajax', 'remover_cupom_ajax');
add_action('wp_ajax_nopriv_remover_cupom_ajax', 'remover_cupom_ajax');

// Criação do shortcode
function cupom_personalizado_shortcode() {
    return cupom_personalizado_form();
}
add_shortcode('cupom_personalizado', 'cupom_personalizado_shortcode');

// Opcional: Remover o formulário padrão de cupom do WooCommerce
function remover_formulario_cupom_padrao() {
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    remove_action('woocommerce_before_cart', 'woocommerce_checkout_coupon_form', 10);
}
add_action('init', 'remover_formulario_cupom_padrao');