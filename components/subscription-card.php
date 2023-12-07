<?php 
function subscriptionCardComponent($subscription, $userCurrentActiveTasks){ 
    $siteUrl = site_url();
    $activeTasksProductId = $siteUrl === 'http://localhost/deerdesignerdash' ? 3040 : 1389;

    $dates_to_display = apply_filters( 'wcs_subscription_details_table_dates_to_display', array(
	'start_date'              => _x( 'Start date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'last_order_date_created' => _x( 'Last payment', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'next_payment'            => _x( 'Next payment', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'end'                     => _x( 'End date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'trial_end'               => _x( 'Trial end date', 'customer subscription table header', 'woocommerce-subscriptions' ),
    ) );

    ?>
    <div class="dd__subscription_card <?php 
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
            $subscriptionProductNames = [];
            $currentSubscriptionPlan = "";
            
            foreach ( $subscription->get_items() as $subsItemId =>  $item ){
                $currentCat =  strip_tags(wc_get_product_category_list($item['product_id']));
                
                if($currentCat === "Plan"){
                    $currentSubscriptionPlan = $item['name'];
                }										
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

        <div class="dd__subscription_actions_form">
            <a href="<?php echo $siteUrl; ?>/?add-to-cart=<?php echo $activeTasksProductId; ?>" data-plan="<?php echo $currentSubscriptionPlan; ?>" class="dd__primary_button active-tasks">Get More Active Tasks</a>

            <?php if($subscription->get_status() === "active"){ ?>
                <a href="<?php echo $siteUrl; ?>/subscriptions/?change-plan=true" data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>" class="dd__primary_button change">Change Plan</a>	
            <?php } ?>

            
            
            <?php $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); 
            
            if(!empty($userCurrentActiveTasks)){ 
                unset($actions['suspend']);
                unset($actions['cancel']);
            }
            
            ?>
                    <?php if ( ! empty( $actions ) ) { ?>
                        <div class="dd__subscriptions_buttons_wrapper">						
                            <?php foreach ( $actions as $key => $action ) :?>															
                                <a href="<?php echo esc_url( $action['url'] ); ?>" data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>" data-button-type=<?php echo esc_html( $action['name'] ) . '_' . $subscription->id; ?> data-subscription-status="<?php echo $subscription->get_status(); ?>" class="dd__subscription_cancel_btn <?php echo str_replace(' ', '-', strtolower($item['name']));  ?> <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php }; ?>
        </div>
    </div>
<?php } ?>

 
<?php add_action('subscriptionCardComponentHook', 'subscriptionCardComponent', 10, 2); ?>







