<?php 
function tasksAddonsCardComponent($subscription, $cancelBtnLabel, $productCat){ 
    $subscriptionStatus = $subscription->get_status();

    ?>

    <?php if(sizeof($subscription->get_items()) <= 1){ ?>

        <div class="dd__subscription_addons_task_card <?php 
            foreach($subscription->get_items() as $subsItem){
                echo ' ' . strtok(strtolower($subsItem['name']), ' ');
            }

            echo ' ' . esc_attr($subscriptionStatus);
            
            ?>">
            <div class="dd__subscription_details"> 
                <div class="dd__subscription_header">
                    <?php if($productCat === "active-task" && $subscriptionStatus === "pending-cancel"){ ?>
                        <span class="dd__subscription_id <?php echo esc_attr( $subscriptionStatus ); ?>"><?php echo "Subscription ID: $subscription->id"; ?> | <strong><?php echo  do_action('callNewSubscriptionsLabel', $subscriptionStatus); ?> <br> </strong> Active task available until <?php echo esc_html( $subscription->get_date_to_display( 'end' ) ); ?></span>

                    <?php }else{ ?>
                           <span class="dd__subscription_id <?php echo esc_attr( $subscriptionStatus ); ?>"><?php echo "Subscription ID: $subscription->id"; ?> | <strong><?php echo  do_action('callNewSubscriptionsLabel', $subscriptionStatus); ?></strong></span>
                    <?php } ?>
                </div>


                <?php 
                    foreach ( $subscription->get_items() as $subsItemId => $item ){	
                        $terms = get_the_terms( $item['product_id'], 'product_cat' );
                    ?>
                    <span class="dd__subscription_title">														
                        <?php if(sizeof($subscription->get_items()) > 1 && $subscriptionStatus === 'active') { ?>
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

                <span class="dd__subscription_price">
                    <?php echo  str_replace('.00', '', $subscription->get_formatted_order_total()); ?>    
                </span>
                
                <span class="dd__subscription_payment">Start date: <?php echo esc_html( $subscription->get_date_to_display( 'start_date' ) ); ?></span>	
                <span class="dd__subscription_payment">Last payment: <?php echo esc_html( $subscription->get_date_to_display( 'last_order_date_created' ) ); ?></span>
                
                <?php if($subscriptionStatus === "active"){ ?>
                    <span class="dd__subscription_payment">Next payment: <?php echo esc_html( $subscription->get_date_to_display( 'next_payment' ) ); ?></span>	
                <?php } ?>

            </div>
            
            <?php 

            $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); 
            $actions['cancel']['name'] = __( $cancelBtnLabel, 'woocommerce-subscriptions' );
            unset($actions['suspend']);
           
            if($productCat == "add-on"){
                unset($actions['reactivate']);
            }

            if($subscriptionStatus == "pending-cancel"){
                unset($actions['cancel']);
            }

            ?>
            <?php if (!empty($actions)) { ?>
                <div class="dd__subscription_actions_form">
                    <?php foreach ( $actions as $key => $action ) : ?>															
                        <a href="<?php echo esc_url( $action['url'] ); ?>" data-product-cat=<?php echo $productCat; ?> data-request-type=<?php echo $action['name']; ?> data-subscription-id="<?php echo $subscription->id; ?>" data-plan="<?php echo $terms[0]->slug; ?>" data-button-type=<?php echo esc_html( $action['name'] ) . '_' . $subscription->id; ?> data-subscription-status="<?php echo $subscriptionStatus; ?>" class="dd__subscription_cancel_btn <?php echo str_replace(' ', '-', strtolower($item['name']));  ?> <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?> 
                    <?php echo $action['name'] === 'Downgrade' ? '<i class="fa-solid fa-caret-down"></i>' : ''; ?>
                    </a>
                    <?php endforeach; ?> 
                </div>
            <?php }; ?>
        </div>
    <?php } ?>
<?php } ?>

 
<?php add_action('tasksAddonsCardComponentHook', 'tasksAddonsCardComponent', 10, 3); ?>







