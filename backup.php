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







/////////


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
    <p style="font-size: 1rem; font-weight: 400; color: #aaa;">If you have a coupon code, please apply it below.</p>
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
    <div id="cupons-ativos" style="margin-top:20px;">
    <?php
    // Verifica se há cupons aplicados
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (!empty($applied_coupons)) {
        foreach ($applied_coupons as $coupon) {
            echo '<div>';
            echo '<a style="color: #e4572f"class="dd__remove_btn" data-coupon="' . esc_attr($coupon) . '">Remove Coupon: ' . esc_html($coupon) . '</a>';
            echo '</div>';
        }
    }
    ?>
    </div>
   <script type="text/javascript">
jQuery(document).ready(function($) {
    var originalText;

    // Função para aplicar a sobreposição de espera
    function showLoadingOverlay() {
        $('body').addClass('processing'); // Adiciona a classe de processamento
        $('.woocommerce-cart').css('opacity', '0.5'); // Aplica a opacidade
    }

    function hideLoadingOverlay() {
        $('body').removeClass('processing');
        $('.woocommerce-cart').css('opacity', '1'); // Remove a opacidade
    }

    $(document).on('click', '.dd__remove_btn', function(e) {
        e.preventDefault();
        
        var $this = $(this); // Armazena a referência ao elemento clicado
        var coupon = $this.data('coupon');
        originalText = $this.text(); // Armazena o texto original do link
		console.log($this.texto)
        $this.text('Removing...'); // Muda o texto para "Removing..."
        $this.css('pointer-events', 'none'); // Desativa o clique
        showLoadingOverlay(); // Exibe a sobreposição de espera

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'remover_cupom_ajax',
                coupon: coupon
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Recarrega a página para atualizar o carrinho
                } else {
                    alert('Error removing coupon. Please try again.');
                    $this.text(originalText); // Restaura o texto original em caso de erro
                    $this.css('pointer-events', 'auto'); // Reativa o clique
                    hideLoadingOverlay(); // Remove a sobreposição
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $this.text(originalText); // Restaura o texto original em caso de erro
                $this.css('pointer-events', 'auto'); // Reativa o clique
                hideLoadingOverlay(); // Remove a sobreposição
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
            wc_add_notice(__('Coupon successfully applied!', 'seu-tema'), 'success');
        } else {
            wc_add_notice(__('Invalid or expired coupon.', 'seu-tema'), 'error');
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



/////// copia 2


// Função para gravar o cookie ao detectar o query param
function gravar_cookie_por_query_param() {
    // Verifica se o parâmetro 'cupom' existe na URL
    if (isset($_GET['coupon'])) {
        // Obtém o valor do cupom
        $codigo_cupom = sanitize_text_field($_GET['coupon']);
        
        // Grava o cookie com o valor do cupom, com duração de 1 dia
        setcookie('coupon_deer', $codigo_cupom, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        
        // Aplica o cupom
       if (WC()->cart->apply_coupon($codigo_cupom)) {
    wc_add_notice(__('Coupon successfully applied via URL!', 'seu-tema'), 'success');
    // Redireciona para a página de sign-up após aplicar o cupom
    wp_safe_redirect(home_url('/sign-up'));
    exit;
} else {
    wc_add_notice(__('Invalid or expired coupon.', 'seu-tema'), 'error');
}
        
        // Agora, remova o parâmetro da URL e redirecione para evitar reaplicações
        wp_safe_redirect(remove_query_arg('coupon'));
        exit;
    }
}

add_action('init', 'gravar_cookie_por_query_param');

// Função para gerar o formulário
function cupom_personalizado_form() {
    ob_start();
    ?>
    <style>
		
		#coupon-input{
			border: 1px solid rgb(204, 204, 204)!important;
		}
        #apply-coupon {
        width:100%;
            border-radius: 0 5px 5px 0 !important;
            background-color: var(--e-global-color-f5169dc) !important;
            color: #fff !important;
           
        }

.dd__coupon_wrapper {
  border: none;
  height: auto!important;
}
        .dd__remove_btn {
            display: inline-block;
            margin-top: 10px;
            color: #dc3545;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
   <p style="font-size: 1rem; font-weight: 400; color: #aaa;">If you have a coupon code, please apply it below.</p>
    <div class="dd__coupon_wrapper">
        <form style="width:100%" method="post" class="cupom-personalizado-form">
          
                <label for="coupon_code" class="screen-reader-text">Coupon:</label>
                <input id="coupon-input" style="outline:none; border:0;" type="text" name="codigo_cupom" placeholder="Coupon Code">
          
       
                <input type="submit" id="apply-coupon" name="aplicar_cupom" value="Apply coupon">
           
        </form>
    </div>
    <div id="cupons-ativos" style="margin-top:20px;">
    <?php
    // Verifica se há cupons aplicados
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (!empty($applied_coupons)) {
        foreach ($applied_coupons as $coupon) {
            echo '<div>';
            echo '<a style="color: #e4572f"class="dd__remove_btn" data-coupon="' . esc_attr($coupon) . '">Remove Coupon: ' . esc_html($coupon) . '</a>';
            echo '</div>';
        }
    }
    ?>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var originalText;

        function showLoadingOverlay() {
            $('body').addClass('processing');
            $('.woocommerce-cart').css('opacity', '0.5');
        }

        function hideLoadingOverlay() {
            $('body').removeClass('processing');
            $('.woocommerce-cart').css('opacity', '1');
        }

        $(document).on('click', '.dd__remove_btn', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var coupon = $this.data('coupon');
            originalText = $this.text();
            $this.text('removing...');
            $this.css('pointer-events', 'none');
            showLoadingOverlay();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'remover_cupom_ajax',
                    coupon: coupon
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error removing coupon. Please try again.');
                        $this.text(originalText);
                        $this.css('pointer-events', 'auto');
                        hideLoadingOverlay();
                    }
                },
                error: function() {
                    $this.text(originalText);
                    $this.css('pointer-events', 'auto');
                    hideLoadingOverlay();
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
            wc_add_notice(__('Coupon applied successfully!.', 'seu-tema'), 'success');
        } else {
            wc_add_notice(__('Invalid or expired coupon.', 'seu-tema'), 'error');
        }

        // Redireciona para a página de sign-up
       // /wp_safe_redirect(home_url('/sign-up'));
        ///exit;
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


////////////////////3333333333 end en end

function gravar_cookie_por_query_param() {
    if (isset($_GET['coupon'])) {
        $codigo_cupom = sanitize_text_field($_GET['coupon']);
        
        // Verifica se o cupom já está aplicado
        if (!in_array($codigo_cupom, WC()->cart->get_applied_coupons())) {
            
            // Remove cupons aplicados anteriormente
            $applied_coupons = WC()->cart->get_applied_coupons();
            if (!empty($applied_coupons)) {
                foreach ($applied_coupons as $coupon) {
                    WC()->cart->remove_coupon($coupon);
                }
            }

            // Grava o novo cupom no cookie
            setcookie('coupon_deer', $codigo_cupom, 0, COOKIEPATH, COOKIE_DOMAIN);
            
            // Aplica o novo cupom
            if (WC()->cart->apply_coupon($codigo_cupom)) {
                wc_add_notice(__('Coupon successfully applied via URL!', 'seu-tema'), 'success');
                wp_safe_redirect('/sign-up');
                exit;
            } else {
                wc_add_notice(__('Invalid or expired coupon.', 'seu-tema'), 'error');
                // Remove o cookie se o cupom for inválido
                setcookie('coupon_deer', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
            }
        }

        // Remover o parâmetro da URL
        $redirect_url = remove_query_arg('coupon');
        if (isset($_GET['add-to-cart'])) {
            $redirect_url = add_query_arg('add-to-cart', $_GET['add-to-cart'], $redirect_url);
        }
        wp_safe_redirect($redirect_url);
        exit;
    }
}




add_action('init', 'gravar_cookie_por_query_param');

// Função para gerar o formulário
function cupom_personalizado_form() {
    ob_start();
    ?>
    <style>
		
		#coupon-input{
			border: 1px solid rgb(204, 204, 204)!important;
		}
        #apply-coupon {
        width:100%;
            border-radius: 0 5px 5px 0 !important;
            background-color: var(--e-global-color-f5169dc) !important;
            color: #fff !important;
            margin-top:20px!important;
           
        }

.dd__coupon_wrapper {
  border: none;
  height: auto!important;
}
        .dd__remove_btn {
            display: inline-block;
            margin-top: 10px;
            color: #dc3545;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
   <p style="font-size: 1rem; font-weight: 400; color: #aaa;">If you have a coupon code, please apply it below.</p>
    <div class="dd__coupon_wrapper">
        <form style="width:100%" method="post" class="cupom-personalizado-form">
          
                <label for="coupon_code" class="screen-reader-text">Coupon:</label>
                <input id="coupon-input" style="outline:none; border:0;" type="text" name="codigo_cupom" placeholder="Coupon Code">
          
       
                <input type="submit" id="apply-coupon" name="aplicar_cupom" value="Apply coupon">
           
        </form>
    </div>
    <div id="cupons-ativos" style="margin-top:20px;">
    <?php
    // Verifica se há cupons aplicados
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (!empty($applied_coupons)) {
        foreach ($applied_coupons as $coupon) {
            echo '<div>';
            echo '<a style="color: #e4572f"class="dd__remove_btn" data-coupon="' . esc_attr($coupon) . '">Remove Coupon: ' . esc_html($coupon) . '</a>';
            echo '</div>';
        }
    }
    ?>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var originalText;

        function showLoadingOverlay() {
            $('body').addClass('processing');
            $('.woocommerce-cart').css('opacity', '0.5');
        }

        function hideLoadingOverlay() {
            $('body').removeClass('processing');
            $('.woocommerce-cart').css('opacity', '1');
        }

        $(document).on('click', '.dd__remove_btn', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var coupon = $this.data('coupon');
            originalText = $this.text();
            $this.text('removing...');
            $this.css('pointer-events', 'none');
            showLoadingOverlay();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'remover_cupom_ajax',
                    coupon: coupon
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href =  window.location.href
                    } else {
                        alert('Error removing coupon. Please try again.');
                        $this.text(originalText);
                        $this.css('pointer-events', 'auto');
                        hideLoadingOverlay();
                    }
                },
                error: function() {
                    $this.text(originalText);
                    $this.css('pointer-events', 'auto');
                    hideLoadingOverlay();
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
            wc_add_notice(__('Coupon applied successfully!.', 'seu-tema'), 'success');
        } else {
            wc_add_notice(__('Invalid or expired coupon.', 'seu-tema'), 'error');
        }

        // Redireciona para a página de sign-up
       // /wp_safe_redirect(home_url('/sign-up'));
        ///exit;
    }
}
add_action('init', 'processar_cupom_personalizado');

// Função AJAX para remover cupom
function remover_cupom_ajax() {
    if (isset($_POST['coupon'])) {
        $coupon = sanitize_text_field($_POST['coupon']);
        $removed = WC()->cart->remove_coupon($coupon);
        
        if ($removed) {
            // Remove o cookie
            setcookie('coupon_deer', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
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

function verificar_e_remover_cookie_cupom() {
    if (isset($_COOKIE['coupon_deer'])) {
        $cookie_cupom = sanitize_text_field($_COOKIE['coupon_deer']);
        $applied_coupons = WC()->cart->get_applied_coupons();

        // Se o cupom do cookie não estiver aplicado no carrinho, remove o cookie
        if (!in_array($cookie_cupom, $applied_coupons)) {
            setcookie('coupon_deer', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}
add_action('wp', 'verificar_e_remover_cookie_cupom');

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
