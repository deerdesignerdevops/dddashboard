<?php ?>
<div class="payment__methods">
    <?php 
    //PAYMENT METHODS
    $saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
    $has_methods   = (bool) $saved_methods;
    $types         = wc_get_account_payment_methods_types();

    do_action( 'woocommerce_before_account_payment_methods', $has_methods ); ?>
    
    <h2 class="myaccount__page_title">Payment Methods</h2>
    <?php if ( $has_methods ) : ?>							
        <?php foreach ( $saved_methods as $type => $methods ) :  ?>
            <?php foreach ( $methods as $method ) : ?>
                <div class="user__invoice_row">
                        <?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
                                <?php
                                    if ( has_action( 'woocommerce_account_payment_methods_column_' . $column_id ) ) {
                                        do_action( 'woocommerce_account_payment_methods_column_' . $column_id, $method );
                                    } elseif ( 'method' === $column_id ) { ?>
                                        <span>
                                            <?php
                                                if ( ! empty( $method['method']['last4'] ) ) {
                                                    echo sprintf( esc_html__( '%1$s ending in %2$s', 'woocommerce' ), esc_html( wc_get_credit_card_type_label( $method['method']['brand'] ) ), esc_html( $method['method']['last4'] ) );
                                                } else {
                                                    echo esc_html( wc_get_credit_card_type_label( $method['method']['brand'] ) );
                                                }
                                            ?>
                                        
                                            <?php
                                            } elseif ( 'expires' === $column_id ) {
                                                echo ' - ' . esc_html( $method['expires'] );
                                            ?>

                                        </span>
                                    <?php
                                    } elseif ( 'actions' === $column_id ) { ?>
                                        <div style="display: flex; gap: 12px;">

                                        <?php
                                        foreach ( $method['actions'] as $key => $action ) {
                                            echo '<a href="' . esc_url( $action['url'] ) . '" >' . esc_html( $action['name'] ) . '</a>';
                                        }
                                        ?>
                                        </div>
                                    <?php }
                                ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
        <?php endforeach; ?>

    <?php else : ?>

        <?php wc_print_notice( esc_html__( 'No saved payment methods found.', 'woocommerce' ), 'notice' ); ?>

    <?php endif; ?>

    <?php do_action( 'woocommerce_after_account_payment_methods', $has_methods ); ?>

    <?php if ( WC()->payment_gateways->get_available_payment_gateways() ) : ?>
        <div style="margin-top: 20px;">
            <a class="woocommerce-Button button dd__primary_button" href="<?php echo esc_url( wc_get_endpoint_url( 'add-payment-method' ) ); ?>"><?php esc_html_e( 'Add payment method', 'woocommerce' ); ?></a>
        </div>
    <?php endif; ?>
</div>