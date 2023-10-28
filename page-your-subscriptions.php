<?php get_header(); ?>

<?php
    require 'dd-subscriptions/vendor/autoload.php';

    use Automattic\WooCommerce\Client;
    use Automattic\WooCommerce\HttpClient\HttpClientException;

    global $woocommerce, $currentUserId, $siteUrl;

    $siteUrl = site_url();
    $currentUserId = get_current_user_id();
   
    $woocommerce = new Client(
        $siteUrl,
        'ck_7541a89ca4c8ac5ee7fbfe27e27515f1fd51c6b2',
        'cs_f76165184adc5c2a846f8bbe9a2d527b1ac2190e',
        [
            'version' => 'wc/v3',
        ]
    );


function updateSubscription($subscriptionId, $subscriptionStatus){
    global $currentUserId;

    $subscriptionData = [
        'customer_id'       => $currentUserId,
        'status'            => $subscription_status,
        'billing_period'    => $interval,
        'billing_interval'  => $interval_count,
        'start_date'        => $start_date,
        'next_payment_date' => $next_payment_date,
        'payment_method'    => 'stripe',
        'payment_details'   => [
            'post_meta' => [
                "_stripe_customer_id" => $stripe_customer_id,
                "_stripe_source_id"   => $stripe_source_id,
            ]
        ],
        'line_items' => [
            [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity'   => $quantity,                         
            ],
        ],
    ];
}


function changeSubscriptionStatus($subscriptionId, $subscriptionStatus){
    echo "<style>.dd__loading_screen{display:flex !important;}</style>";

    global $woocommerce;
    
    $subscriptionData = [
        'status' => $subscriptionStatus
    ];

    try{
        $woocommerce->put("subscriptions/$subscriptionId", $subscriptionData);

    }catch (HttpClientException $e) {
            echo '<pre><code>' . print_r($e->getMessage(), true) . '</code><pre>';
            echo '<pre><code>' . print_r($e->getRequest(), true) . '</code><pre>'; 
            echo '<pre><code>' . print_r($e->getResponse(), true) . '</code><pre>';
    }finally{
        header("Refresh: 0");
    }
}

function cancelSubscription($subscriptionId){
    echo "<style>.dd__loading_screen{display:flex !important;}</style>";

    global $woocommerce;

    try{
        $woocommerce->delete("subscriptions/$subscriptionId", ['force' => true]);

    }catch (HttpClientException $e) {
            echo '<pre><code>' . print_r($e->getMessage(), true) . '</code><pre>';
            echo '<pre><code>' . print_r($e->getRequest(), true) . '</code><pre>'; 
            echo '<pre><code>' . print_r($e->getResponse(), true) . '</code><pre>';
    }finally{
        header("Refresh: 0");
    }
}

    if(isset($_POST['pause_plan'])){
        changeSubscriptionStatus($_POST['subscription_id'], 'on-hold');

    }elseif(isset($_POST['cancel_plan'])){
        cancelSubscription($_POST['subscription_id']);

    }elseif(isset($_POST['reactivate_plan'])){
        changeSubscriptionStatus($_POST['subscription_id'], 'active');
    }

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<div class="dd__loading_screen">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
</div>
<section class="dd__bililng_portal_section">
    <div style="max-width: 1140px; margin: auto">
        <a href="/" class="dd__bililng_portal_back"><i class="fa-solid fa-chevron-left"></i> Back to Dashboard</a>
        <h1 class="myaccount__page_title">Billing Portal</h1>



<?php

    try{
        $subscriptions = $woocommerce->get('subscriptions', ['customer'=>$currentUserId]);
        $activeSubscriptions = $woocommerce->get('subscriptions', ['customer'=>$currentUserId, 'status' => 'active']);
    }catch (HttpClientException $e) {
            echo '<pre><code>' . print_r($e->getMessage(), true) . '</code><pre>';
            echo '<pre><code>' . print_r($e->getRequest(), true) . '</code><pre>'; 
            echo '<pre><code>' . print_r($e->getResponse(), true) . '</code><pre>';
    } 

    $activeSubscriptionsGroups = [];
    
    if(!empty($subscriptions)){
        ?>
        <div class="dd__subscriptions_sumary">            
            <div class="dd__subscription_details">  
                <h2>You have:</h2>   
                <?php
                    foreach($activeSubscriptions as $subscriptionItem){ 
                        array_push($activeSubscriptionsGroups, $subscriptionItem->line_items[0]->name);
                    }

                    foreach(array_unique($activeSubscriptionsGroups) as $activeSubscriptionsGroupsItem){ 
                        $subscriptionItemCount = array_count_values($activeSubscriptionsGroups)[$activeSubscriptionsGroupsItem];
                    ?>
                        <span class="dd__subscriptions_sumary_name"><?php echo $activeSubscriptionsGroupsItem; ?> <strong><?php echo $subscriptionItemCount; ?></strong></span>

                    <?php }
                ?>
                
                <div class="dd__subscriptions_buttons_wrapper" style="margin-top: 20px;">
                    <a href="<?php echo $siteUrl; ?>/?add-to-cart=928" class="dd__add_designer_btn">Add a Designer</a>
                </div>
            </div>
        </div>
        <?php


        foreach($subscriptions as $subscription){
            foreach ($subscription->meta_data as $meta) {
                if ($meta->key === '_stripe_customer_id') {
                    $customerStripeId = $meta->value;
                }elseif($meta->key === '_stripe_source_id'){
                    $customerStripePaymentId = $meta->value; 
                }
            }
            
                $productName = $subscription->line_items[0]->name;
                $productPrice = $subscription->line_items[0]->price;
                $productQuant = $subscription->line_items[0]->quantity;
                $nextPaymentDate = $subscription->next_payment_date_gmt ? date("l jS \of F Y", strtotime($subscription->next_payment_date_gmt)) : null;
                $cancelationDate = $subscription->cancelled_date_gmt ? date("l jS \of F Y", strtotime($subscription->cancelled_date_gmt)) : null;

                ?>
                    <div class="dd__subscription_card">
                        <div class="dd__subscription_details">                        

                            <div class="dd__subscription_header">
                                <span class="dd__subscription_id <?php echo $subscription->status; ?>"><?php echo "Subscription ID: $subscription->id | <strong> $subscription->status </strong>"; ?></span>
                            </div>

                            <span class="dd__subscription_title"><?php echo $productName; ?></span>
                            <span class="dd__subscription_price"><?php echo "$$productPrice,00"; ?></span>
                            <span class="dd__subscription_quantity"><?php echo "Quantity: $productQuant"; ?></span>
                            
                            <?php if($nextPaymentDate && $subscription->status !== 'cancelled'){ ?>
                                <span class="dd__subscription_payment"><?php echo "Your plan renews on: $nextPaymentDate"; ?></span>
                            <?php } ?>
                            
                            <?php if($cancelationDate && $subscription->status !== 'cancelled'){ ?>
                                <span class="dd__subscription_payment"><?php echo "Your plan will be cancelled on: $cancelationDate"; ?></span>
                            <?php   } ?>
                        </div>

                        <form class="dd__subscription_actions_form" method="POST" action="<?php echo $siteUrl; ?>/your-subscriptions/">
                                <?php if($subscription->status === 'active') { ?>
                                    <input type="submit" id="update_plan" name="update_plan" value="Update Plan" class="dd__subscription_update_btn" />
                                    <div class="dd__subscriptions_buttons_wrapper">
                                        <input type="submit" id="pause_plan" name="pause_plan" value="Pause Plan" class="dd__subscription_cancel_btn" />
                                        <input type="submit" id="cancel_plan" name="cancel_plan" value="Cancel Plan" class="dd__subscription_cancel_btn" />
                                    </div>

                                <?php  } else{ ?>
                                    <input type="submit" id="reactivate_plan" name="reactivate_plan" value="Reactivate Plan" class="dd__subscription_update_btn" />
                                    <input type="submit" id="cancel_plan" name="cancel_plan" value="Cancel Plan" class="dd__subscription_cancel_btn" />
                               <?php } ?>
                                
                            <input type="hidden" id="subscription_id" name="subscription_id" value="<?=$subscription->id ?>">

                        </form>
                    </div>
                <?php
        }
    }else{ ?>
        <div class="dd__subscription_card">
            <div class="dd__subscription_details">
                <p>No active subscriptions!</p>
            </div>

            <a href="https://deerdesigner.com/pricing" class="dd__bililng_portal_back">See Pricing</a>
            
        </div>
   <?php }

?>

    </div>
</section>


<script>
    const formSubmission = Array.from(document.querySelectorAll(".dd__subscription_actions_form"));
    formSubmission.forEach((form) => {
        form.addEventListener("submit", function(e){
            e.preventDefault()
            
            const result = confirm("Are you sure you want to submit this form?");

            if(result){
                e.submit();
            }
            return result;
        })
    })



</script>


<?php get_footer(); ?>