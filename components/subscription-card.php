<?php 
function subscriptionCardComponent($subscription, $currentProductId){ 
    $siteUrl = site_url();
    $lastOrderPaidDate = getOrderPaymentDate($subscription);
    $activeTasksProductId = 1600;
    $standardPlanMonthlyPrice = wc_get_product( 1589 )->get_price();
    $activeTaskProductPrice = wc_get_product( $activeTasksProductId )->get_price();
    $activeTaskProductName = wc_get_product( $activeTasksProductId )->get_name();
    $currencySymbol = get_woocommerce_currency_symbol(apply_filters('wcml_price_currency', NULL ));
    $subscriptionStatus = $subscription->get_status();    
    $pausedPlanBillingPeriodEndingDate = calculateBillingEndingDateWhenPausedOrCancelled($subscription);
    $showReactivateButton = time() > strtotime($pausedPlanBillingPeriodEndingDate) ? true : false;
    $subscriptionPauseDate = "";
    $formatedSubscriptionPrice = str_replace('.00', '', $subscription->get_formatted_order_total());
    $showVat = "";
    $subscriptionSubtotal = $subscription->get_subtotal();
    $subscriptionPriceAndVat = " ($currencySymbol" . "$subscriptionSubtotal + VAT)";

    function removeSpacesInPriceString($stringToBeChanged){
        if(str_contains($stringToBeChanged, " / month")){
            return str_replace(" / month", "/month", $stringToBeChanged);
        }else if(str_contains($stringToBeChanged, " / year")){
            return str_replace(" / year", "/year", $stringToBeChanged);
        }else{
            return $stringToBeChanged;
        }
    }

    $currentUser = get_user_by('id', $subscription->data['customer_id']);

    if($currentUser->billing_country === 'GB'){
        $showVat = $subscriptionPriceAndVat;
    }else{
        $showVat = "";
    }

    $subscriptionRelatedNotes = wc_get_order_notes(array(
        'order_id' => $subscription->id,
        'type' => 'system_status_change',
        'orderby' => 'date_created',
        'order' => 'DESC',
    ));

    foreach($subscriptionRelatedNotes as $subscriptionRelatedNote){
        if(str_contains($subscriptionRelatedNote->content, 'Status changed from Active to Paused.')){
            $subscriptionPauseDate = $subscriptionRelatedNote->date_created->format('F j, Y');
            break;
        }
    };


    $reactivateUrl = get_permalink( wc_get_page_id( 'myaccount' ) ) . "subscriptions/?reactivate_plan=true";
    $reactivateUrlWithNonce = add_query_arg( '_wpnonce', wp_create_nonce( 'action' ), $reactivateUrl );

    if(isset($_GET['reactivate_plan']) && isset($_GET['_wpnonce'])){
        if(wp_verify_nonce($_GET['_wpnonce'], 'action')){
            do_action('redirectToCheckoutWhenReactivateSubscriptionAfterBillingDateHook', $subscription);
        }
    }
    
    ?>
    <div class="dd__subscription_card <?php 
        foreach($subscription->get_items() as $subsItem){
            echo ' ' . strtok(strtolower($subsItem['name']), ' ');
        }

        echo ' ' . esc_attr($subscriptionStatus);
        
        ?>">
        <div class="dd__subscription_details">                        
                <div class="dd__subscription_header">
                        <span class="dd__subscription_id <?php echo esc_attr( $subscriptionStatus ); ?>">Status: <strong><?php echo  do_action('callNewSubscriptionsLabel', $subscriptionStatus, $showReactivateButton);  ?>
                        <br> </strong>
                        <?php
                        if($subscriptionStatus !== 'active' && !$showReactivateButton && $lastOrderPaidDate){ ?>
                             Your Deer Designer team is still available until <?php echo $pausedPlanBillingPeriodEndingDate; ?></span>
                        <?php } ?> 
                </div>
            <?php 
            $currentSubscriptionPlan = "";
            
            foreach ( $subscription->get_items() as $subsItemId =>  $item ){
                $currentCat =  strip_tags(wc_get_product_category_list($item['product_id']));
                
                if($currentCat === "Plan"){
                    $currentSubscriptionPlan = $item['name'];
                    $activeTaskProductPrice = str_contains($currentSubscriptionPlan, 'Standard') ? $standardPlanMonthlyPrice : $activeTaskProductPrice;
                }										
                ?>
        
                <span class="dd__subscription_title">	
                    <?php echo $item['name'];?>
													
                    <?php if(sizeof($subscription->get_items()) > 1 && $subscriptionStatus === 'active') { 
                        if(!has_term('plan', 'product_cat', $item['product_id'])){ ?>
                            <span class="remove_item">
                                <?php if ( wcs_can_item_be_removed( $item, $subscription ) ) : ?>
                                    <?php $confirm_notice = apply_filters( 'woocommerce_subscriptions_order_item_remove_confirmation_text', __( 'Are you sure you want remove this item from your subscription?', 'woocommerce-subscriptions' ), $item, $_product, $subscription );?>
                                    <a href="<?php echo esc_url( WCS_Remove_Item::get_remove_url( $subscription->get_id(), $subsItemId ) );?>" class="remove" onclick="return confirm('<?php printf( esc_html( $confirm_notice ) ); ?>');">&times;</a>
                                <?php endif; ?>
                            </span>
                        <?php }
                        ?>
                    <?php } ?>
                </span>
                                
            <?php } ?>
            <span class="dd__subscription_price">
                <?php echo removeSpacesInPriceString($formatedSubscriptionPrice); ?>   
            </span>

            <?php if($showVat){ ?>
                <span class="dd__subscription_price_vat">
                    <?php echo $subscriptionPriceAndVat;?>
                </span>
            <?php } ?>

            <span class="dd__subscription_payment">Start date: <?php echo esc_html( $subscription->get_date_to_display( 'start_date' ) ); ?></span>	

            <?php if($lastOrderPaidDate) { ?>
                <span class="dd__subscription_payment">Last payment: <?php echo $lastOrderPaidDate; ?></span>
            <?php } ?>
            
            <?php if($subscriptionStatus === "active"){ ?>
                <span class="dd__subscription_payment">Next payment: <?php echo esc_html( $subscription->get_date_to_display( 'next_payment' ) ); ?></span>	
            <?php } ?>

            <?php if($subscriptionStatus === "on-hold" && $subscriptionPauseDate){ ?>
                <span class="dd__subscription_payment">Pause date: <?php echo $subscriptionPauseDate; ?></span>
            <?php } ?>
        </div>


        <div>
            <!-- <?php if($subscriptionStatus === 'active'){ ?>
                <div class="btn__wrapper">
                    <a href='<?php echo "$siteUrl/?add-to-cart=$activeTasksProductId"; ?>' data-plan="<?php echo $currentSubscriptionPlan; ?>" class="dd__primary_button active-tasks one__click_purchase" data-product-price="<?php echo $currencySymbol . $activeTaskProductPrice; ?>" data-product-name="<?php echo $activeTaskProductName; ?>">Add New Designer</a>
                </div>
            <?php } ?> -->

            <div class="dd__subscription_actions_form">
                <!--REACTIVATE BUTTON WITH ONE CLICK PURCHASE THAT APPEARS ONLY WHEN A PAUSED SUBSCRIPION HAS PASSED IT'S BILLING PERIOD / START-->
                <?php if($showReactivateButton && $subscriptionStatus === 'on-hold'){ ?>    
                    <a href="<?php echo $reactivateUrlWithNonce; ?>" data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>" class="dd__primary_button reactivate rebill" data-product-price='<?php echo get_woocommerce_currency_symbol($subscription->get_currency()) . str_replace('.00', '', $subscription->get_total()) . $showVat; ?>'>Reactivate</a>
                <?php } ?>
                <!--REACTIVATE BUTTON WITH ONE CLICK PURCHASE THAT APPEARS ONLY WHEN A PAUSED SUBSCRIPION HAS PASSED IT'S BILLING PERIOD / END-->

                <?php if($subscriptionStatus === "active"){ ?>
                    <a href="<?php echo get_permalink( wc_get_page_id( 'myaccount' ) ); ?>subscriptions/?change-plan=true" data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>" class="dd__primary_button change">Change Plan</a>	
                <?php } ?>

                <!--SUBSCRIPTION ACTIONS-->
                <?php $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); 
            
                if($showReactivateButton && $subscriptionStatus === 'on-hold'){
                    unset($actions['reactivate']);
                }
                
                ?>
                    <?php if ( ! empty( $actions ) ) { ?>
                        <div class="dd__subscriptions_buttons_wrapper">						
                            <?php foreach ( $actions as $key => $action ) : ?>															
                                <a href="<?php echo esc_url( $action['url'] ); ?>" data-product-cat="plan" data-request-type=<?php echo $action['name']; ?> data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>"  data-button-type=<?php echo esc_html( $action['name'] ) . '_' . $subscription->id; ?> data-subscription-status="<?php echo $subscriptionStatus; ?>" class="dd__subscription_cancel_btn <?php echo str_replace(' ', '-', strtolower($item['name']));  ?> <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php }; ?>
            </div>
        </div>
    </div>
<?php } ?>

 
<?php add_action('subscriptionCardComponentHook', 'subscriptionCardComponent', 10, 2); ?>







