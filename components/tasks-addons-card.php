<?php 
function tasksAddonsCardComponent($subscription, $cancelBtnLabel){ 

    $dates_to_display = apply_filters( 'wcs_subscription_details_table_dates_to_display', array(
	'start_date'              => _x( 'Start date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'last_order_date_created' => _x( 'Last payment', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'next_payment'            => _x( 'Next payment', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'end'                     => _x( 'End date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'trial_end'               => _x( 'Trial end date', 'customer subscription table header', 'woocommerce-subscriptions' ),
    ) );

    ?>

    <?php if(sizeof($subscription->get_items()) <= 1){ ?>

        <div class="dd__subscription_addons_task_card <?php 
            foreach($subscription->get_items() as $subsItem){
                echo ' ' . strtok(strtolower($subsItem['name']), ' ');
            }

            echo ' ' . esc_attr($subscription->get_status());
            
            ?>">
            <div class="dd__subscription_details">                        
                <div class="dd__subscription_header">
                    <span class="dd__subscription_id <?php echo esc_attr( $subscription->get_status() ); ?>"><?php echo "Subscription ID: $subscription->id"; ?> | <strong><?php echo  do_action('callNewSubscriptionsLabel', $subscription->get_status()); ?></strong></span>
                </div>

                <?php 
                    foreach ( $subscription->get_items() as $subsItemId => $item ){	
                    ?>
                    <span class="dd__subscription_title">														
                        <?php if(sizeof($subscription->get_items()) > 1 && $subscription->get_status() === 'active') { ?>
                                <span class="remove_item">
                                    <?php if ( wcs_can_item_be_removed( $item, $subscription ) ) : ?>
                                        <?php $confirm_notice = apply_filters( 'woocommerce_subscriptions_order_item_remove_confirmation_text', __( 'Are you sure you want remove this item from your subscription?', 'woocommerce-subscriptions' ), $item, $_product, $subscription );?>
                                        <a href="<?php echo esc_url( WCS_Remove_Item::get_remove_url( $subscription->get_id(), $subsItemId ) );?>" class="remove" onclick="return confirm('<?php printf( esc_html( $confirm_notice ) ); ?>');">&times;</a>
                                    <?php endif; ?>
                                </span>
                        <?php } ?>
                        <?php echo $item['name'];?>
                    </span>
                                    
                <?php } ?>
                <span class="dd__subscription_price"><?php echo wp_kses_post( $subscription->get_formatted_order_total() ); ?></span>

                <?php foreach ( $dates_to_display as $date_type => $date_title ) : ?>
                    <?php $date = $subscription->get_date( $date_type ); ?>
                    <?php if ( ! empty( $date ) ) : ?>
                        <span class="dd__subscription_payment"><?php echo esc_html( $date_title ); ?>: <?php echo esc_html( $subscription->get_date_to_display( $date_type ) ); ?></span>							
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php 

            $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); 
            $actions['cancel']['name'] = __( $cancelBtnLabel, 'woocommerce-subscriptions' );
            unset($actions['suspend']);
            unset($actions['reactivate']);

            if($subscription->get_status() == "pending-cancel"){
                unset($actions['cancel']);
            }

            ?>
            <?php if (!empty($actions)) { ?>
                <div class="dd__subscription_actions_form">
                    <?php foreach ( $actions as $key => $action ) :?>															
                        <a href="<?php echo esc_url( $action['url'] ); ?>" data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>" data-button-type=<?php echo esc_html( $action['name'] ) . '_' . $subscription->id; ?> data-subscription-status="<?php echo $subscription->get_status(); ?>" class="dd__subscription_cancel_link_btn <?php echo str_replace(' ', '-', strtolower($item['name']));  ?> <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?></a>
                    <?php endforeach; ?> 
                </div>
            <?php }; ?>
        </div>
    <?php } ?>
<?php } ?>

 
<?php add_action('tasksAddonsCardComponentHook', 'tasksAddonsCardComponent', 10, 2); ?>







