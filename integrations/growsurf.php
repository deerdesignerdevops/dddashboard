<?php

//REFERRAL PROGRAM WITH GROWSURF
global $growSurfBaseUrl;
$growSurfBaseUrl = "https://api.growsurf.com/v2";



function addGrowSurfScript(){
    $campaignID = GROWSURF_CAMPAIGN_ID;
	echo "<script type='text/javascript'>
  (function(g,r,s,f){g.grsfSettings={campaignId:'$campaignID',version:'2.0.0'};s=r.getElementsByTagName('head')[0];f=r.createElement('script');f.async=1;f.src='https://app.growsurf.com/growsurf.js'+'?v='+g.grsfSettings.version;f.setAttribute('grsf-campaign', g.grsfSettings.campaignId);!g.grsfInit?s.appendChild(f):'';})(window,document);
</script>";
}
add_action('wp_head', 'addGrowSurfScript');



function postRequestToGrowSurf($requestBody){
    global $growSurfBaseUrl;
    $campaignID = GROWSURF_CAMPAIGN_ID;
    $apiUrl = "$growSurfBaseUrl/campaign/$campaignID/participant";
    $accessToken = GROWSURF_API_KEY;
    $uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/growsurf';
   
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Accept: application/json",
        "Authorization: Bearer $accessToken"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_message = 'Error: ' . curl_error($ch);
        echo $error_message;
        error_log($error_message, 3, "$uploadsDir/growsurf_api_error_log.txt");
        $response = false;
    } else {
        file_put_contents("$uploadsDir/growsurf_api_response_log_post_request.txt", $response . PHP_EOL, FILE_APPEND);
        $response = json_decode($response, true);
    }

    curl_close($ch);

    return $response;
}




function getUserByIdFromGrowSurf($participantId){
    global $growSurfBaseUrl;
    $campaignID = GROWSURF_CAMPAIGN_ID;
    $apiUrl = "$growSurfBaseUrl/campaign/$campaignID/participant/$participantId";
    $accessToken = GROWSURF_API_KEY;
    $uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/growsurf';
   
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Accept: application/json",
        "Authorization: Bearer $accessToken"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_message = 'Error: ' . curl_error($ch);
        echo $error_message;
        error_log($error_message, 3, "$uploadsDir/growsurf_api_error_log.txt");
        $response = false;
    } else {
        file_put_contents("$uploadsDir/growsurf_api_response_log_get_request.txt", $response . PHP_EOL, FILE_APPEND);
        $response = json_decode($response, true);
    }

    curl_close($ch);

    return $response;
}



function addNewParticipantToReferralProgram($orderId){
    if(!wcs_order_contains_renewal($orderId)){
        $referralId = get_post_meta($orderId, '_referral_id', true);
        $order = wc_get_order( $orderId );
        $currentUser = get_user_by('id', $order->data['customer_id']);

        $requestBody = [
            "email" => $currentUser->user_email,
            "referredBy" => $referralId,
            "firstName" => $currentUser->first_name,
            "lastName" => $currentUser->last_name,
        ];

        foreach($order->get_items() as $orderItem){
            if(has_term('plan', 'product_cat', $orderItem->get_product_id())){

                $response = postRequestToGrowSurf($requestBody);
                
                if($response['id']){
                    $referralId = $response['id'];
                    $growSurfParticipantUrl = "https://deerdesigner.com/?grsf=$referralId";
                    update_user_meta($currentUser->id, 'grow_surf_participant_id', $referralId);
                    update_user_meta($currentUser->id, 'grow_surf_participant_url', $growSurfParticipantUrl);
                }
                return;
            };	
        }
    }
}
add_action('woocommerce_payment_complete', 'addNewParticipantToReferralProgram');



function getUrlReferralParamsAndSaveCookie(){
    if(isset($_GET['grsf'])){
        $cookieName = "dd_referral_id";
        $cookieValue = $_GET['grsf'];
        setcookie($cookieName, $cookieValue, time() + (86400 * 30), "/");
    }
	else if(isset($_GET['sld'])){
        $cookieName = "dd_affiliate_id";
        $cookieValue = $_GET['sld'];
        setcookie($cookieName, $cookieValue, time() + (86400 * 30), "/");
    }
}
add_action('template_redirect', 'getUrlReferralParamsAndSaveCookie');



function getReferrerIdFromSubscriptionRenewal($orderId){
    if(wcs_order_contains_renewal($orderId)){
        $order = wc_get_order( $orderId );
        
        $orderSubscriptions = wcs_get_subscriptions_for_order($orderId, array('order_type' => 'any'));
        $subscription = $orderSubscriptions[array_key_first($orderSubscriptions)];
        $renewalCount = $subscription->get_payment_count( 'completed', 'renewal' );

        if($renewalCount <= 1){
            //GET GROWSURF USER ID
            $currentUserParticipantId = get_user_meta($order->data['customer_id'], 'grow_surf_participant_id', true );
        
            if($currentUserParticipantId){
                //GET USER DATA FROM GROW SURF
                $referralData = getUserByIdFromGrowSurf($currentUserParticipantId);
            
                if($referralData['referrer']['id']){
                    $userReferrerId = $referralData['referrer']['id'];
        
                    //GET USER FROM DATABASE WITH REFERRER ID
                    $referrer = get_users(array(
                        'meta_key' => 'grow_surf_participant_id',
                        'meta_value' => $userReferrerId
                    ));
        
                    if($referrer){
                        $referrerId = $referrer[0]->data->ID;
                        applyDiscountToReferrerNextRenewal($referrerId);
                        return;
                    }
                }
            }
        }
    }
}
add_action('woocommerce_payment_complete', 'getReferrerIdFromSubscriptionRenewal');



function applyDiscountToReferrerNextRenewal($referrerId){
    $userSubscriptions = wcs_get_users_subscriptions($referrerId);
    $couponCode = 'deerreferrer';
	
    foreach($userSubscriptions as $subscription){
		foreach($subscription->get_items() as $subItem){
			if(has_term('plan', 'product_cat', $subItem['product_id'])){
                $subscription->apply_coupon( $couponCode );
                $subscription->save();
                return;		
			}
		}
	}
}


function removeDiscountFromSubscriptionAfterPayment($subscription, $lastOrder){
    $couponCode = 'deerreferrer';
    $coupons = $subscription->get_used_coupons();
    
    if(in_array($couponCode, $coupons)){
        $subscription->remove_coupon( $couponCode );
        $subscription->save();
    }
}
add_action( 'woocommerce_subscription_renewal_payment_complete', 'removeDiscountFromSubscriptionAfterPayment', 10, 2 );
