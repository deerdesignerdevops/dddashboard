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
    $apiUrl = "$growSurfBaseUrl/campaign/1fk2cp/participant";
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



function addNewParticipantToReferralProgram($orderId){
    if(!wcs_order_contains_renewal($orderId)){
        $referralId = get_post_meta($orderId, '_referral_id', true);
        $order = wc_get_order( $orderId );
        $currentUser = get_user_by('id', $order->data['customer_id']);

        $requestBody = [
            "email" => $currentUser->user_email,
            "referredBy" => $referralId,
            "first_name" => $currentUser->first_name,
            "last_name" => $currentUser->last_name,
        ];

        foreach($order->get_items() as $orderItem){
            if(has_term('plan', 'product_cat', $orderItem->get_product_id())){

                $response = postRequestToGrowSurf($requestBody);
                if($response['id']){
                    update_user_meta($currentUser->id, 'grow_surf_participant_id', $response['id']);
                }
                return;
            };	
        }
    }
}
add_action('woocommerce_payment_complete', 'addNewParticipantToReferralProgram');
