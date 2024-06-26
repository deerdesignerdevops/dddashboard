<?php

//REFERRAL PROGRAM WITH GROWSURF
global $growSurfBaseUrl, $currentTime;
$currentTime = date('Y-m-d');
$growSurfBaseUrl = "https://api.growsurf.com/v2";


function postRequestToGrowSurf($requestBody){
    global $growSurfBaseUrl, $currentTime;
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
        file_put_contents("$uploadsDir/growsurf_api_response_log_post_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
        $response = json_decode($response, true);
    }

    curl_close($ch);

    return $response;
}




function getUserByIdFromGrowSurf($participantId){
    global $growSurfBaseUrl, $currentTime;
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
        file_put_contents("$uploadsDir/growsurf_api_response_log_get_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
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



function dynamicReferralCoupon($referrerId, $referralCount){
    $coupon = new WC_Coupon();
    $couponCode = "referrer_$referrerId" . "_$referralCount";
    $companyName = get_user_meta($referrerId, 'billing_company', true);

    $coupon->set_code($couponCode);
    $coupon->set_amount( 100 );
    $coupon->set_discount_type('recurring_fee');
    $coupon->set_description( "[REFERRAL] Coupon for $companyName." );
    $coupon->save();

    return $coupon->get_code();
}



function dynamicReferralCreditCoupon($referrerId, $amount){
    $coupon = new WC_Coupon();
    $couponCode = "referrer_subscription_credit_$referrerId";
    $companyName = get_user_meta($referrerId, 'billing_company', true);

    $coupon->set_code($couponCode);
    $coupon->set_amount( $amount );
    $coupon->set_discount_type('recurring_fee');
    $coupon->set_description( "[REFERRAL] Credit Coupon for $companyName." );
    $coupon->save();

    return $coupon->get_code();
}



function applyDiscountToReferrerNextRenewal($referrerId){
    $userSubscriptions = wcs_get_users_subscriptions($referrerId);
    $referralCount = 1;
	
    foreach($userSubscriptions as $subscription){
		foreach($subscription->get_items() as $subItem){
			if(has_term('plan', 'product_cat', $subItem['product_id'])){
                $coupons = $subscription->get_used_coupons();

                if($coupons){
                    foreach($coupons as $coupon){
                        $referralCount = $referralCount + 1;
                    }
                }

                $couponCode = dynamicReferralCoupon($referrerId, $referralCount);

                if($couponCode){
                    $subscription->apply_coupon( $couponCode );
                    $subscription->save();
                    return;		
                }               
			}
		}
	}
}



function removeDiscountFromSubscriptionAfterPayment($subscription, $lastOrder){
    $coupons = $subscription->get_used_coupons();
    $couponsAmount = 0;
    
    foreach($coupons as $coupon){
        if(str_contains($coupon, "referrer")){
            $currentCouponObj = new WC_Coupon($coupon);
            $couponsAmount = $couponsAmount + $currentCouponObj->amount;
            $subscription->remove_coupon( $coupon );
            $subscription->save();
            $currentCouponObj->delete(true);
        }
    }

    $referrerSubscriptionCredit = $subscription->get_subtotal() - $couponsAmount;

    if($referrerSubscriptionCredit < 0){
        $creditCouponCode = dynamicReferralCreditCoupon($subscription->data['customer_id'], abs($referrerSubscriptionCredit));
    
        if($creditCouponCode){
            $subscription->apply_coupon( $creditCouponCode );
            $subscription->save();
            return;		
        }   
    }
}
add_action( 'woocommerce_subscription_renewal_payment_complete', 'removeDiscountFromSubscriptionAfterPayment', 10, 2 );
