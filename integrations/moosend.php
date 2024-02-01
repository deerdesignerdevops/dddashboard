<?php

function curlToMoosend($userName, $userEmail, $status, $moosendList){
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/moosend';
	$moosendApiUrl = $moosendList === "news" ? MOOSEND_API_URL_NEWS : MOOSEND_API_URL;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $moosendApiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Accept: application/json',
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"Name\" : \"$userName\",\n    \"Email\" : \"$userEmail\",\n    \"HasExternalDoubleOptIn\": false,\n\"CustomFields\":[\n \"status=$status\"]}");

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/moosend_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/moosend_api_response_log_post_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function subscribeUserToMoosendEmailList($entryId, $formData, $form){
	if($form->id === 3){
		$currentUser = wp_get_current_user();
		$userName = "$currentUser->first_name $currentUser->last_name";
		$userEmail = $currentUser->user_email;	
		curlToMoosend($userName, $userEmail, 'active', 'onboarding' );	
	}
}
add_action( 'fluentform/submission_inserted', 'subscribeUserToMoosendEmailList', 10, 3);



function scheduleMoosendUpdateStatus($subscriptionId, $newStatus, $userName, $userEmail, $status){
	$subscription = wcs_get_subscription($subscriptionId);
	
	if($subscription->get_status() === $newStatus){		
		foreach($subscription->get_items() as $subscritpionItem){
			if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
				curlToMoosend($userName, $userEmail, $status, 'news');
			}
		}
	}
}
add_action('scheduleMoosendUpdateStatusHook', 'scheduleMoosendUpdateStatus', 10, 5);



function updateUserInMoosendBasedOnSubscriptionStatus($subscription, $newStatus, $oldStatus){
	if($oldStatus !== 'pending' && $newStatus !== 'cancelled'){
		foreach($subscription->get_items() as $subscritpionItem){
			if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
				$billingPeriodEndingDate =  strtotime(calculateBillingEndingDateWhenPausedOrCancelled($subscription));
				$userName = $subscription->data['billing']['first_name'] . " " . $subscription->data['billing']['last_name'];
				$userEmail = $subscription->data['billing']['email'];
				$moosendUserNewStatus = "";

				switch($newStatus){
					case "active":
						$moosendUserNewStatus = "active";
						break;
					
					case "on-hold":
						$moosendUserNewStatus = "paused";
						break;
					
					case "pending-cancel":
						$moosendUserNewStatus = "cancelled";
						break;
					
					default:
						$moosendUserNewStatus = "paused";
				}

				
				if(time() < $billingPeriodEndingDate){
					wp_schedule_single_event($billingPeriodEndingDate, 'scheduleMoosendUpdateStatusHook', array($subscription->id, $newStatus, $userName, $userEmail, $moosendUserNewStatus));
				}else{
					curlToMoosend($userName, $userEmail, $moosendUserNewStatus, 'news');
				}
			}
		}
	}
}
add_action('woocommerce_subscription_status_updated', 'updateUserInMoosendBasedOnSubscriptionStatus', 10, 3);

?>