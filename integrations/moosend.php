<?php
global $currentTime;
$currentTime = date('Y-m-d');

function postToMoosend($userName, $userEmail, $status, $moosendList){
	global $currentTime;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/moosend';
	$moosendApiKey = MOOSEND_API_KEY;
	$moosendListId = $moosendList === "news" ? MOOSEND_NEWS_LIST_ID : MOOSEND_ONBOARDING_LIST_ID;
	$moosendApiUrl = "https://api.moosend.com/v3/subscribers/$moosendListId/subscribe.json?apikey=$moosendApiKey";
	
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
		file_put_contents("$uploadsDir/moosend_api_response_log_post_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
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
		postToMoosend($userName, $userEmail, 'active', 'onboarding' );	
	}
}
add_action( 'fluentform/submission_inserted', 'subscribeUserToMoosendEmailList', 10, 3);



function scheduleMoosendUpdateStatus($subscriptionId, $newStatus, $userName, $userEmail, $status){
	$subscription = wcs_get_subscription($subscriptionId);
	
	if($subscription->get_status() === $newStatus){		
		foreach($subscription->get_items() as $subscritpionItem){
			if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
				postToMoosend($userName, $userEmail, $status, 'news');
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
					postToMoosend($userName, $userEmail, $moosendUserNewStatus, 'news');
				}
			}
		}
	}
}
add_action('woocommerce_subscription_status_updated', 'updateUserInMoosendBasedOnSubscriptionStatus', 30, 3);



function getUserByEmailFromMoosend($userEmail){	
	global $currentTime;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/moosend';
	$moosendApiKey = MOOSEND_API_KEY;
	$moosendOnboardingListId = MOOSEND_ONBOARDING_LIST_ID;
	$moosendApiUrlToGetUser = "https://api.moosend.com/v3/subscribers/$moosendOnboardingListId/view.json?apikey=$moosendApiKey&Email=$userEmail";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $moosendApiUrlToGetUser);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Accept: application/json',
	]);

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/moosend_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/moosend_api_response_log_get_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function updateUserEmailInMoosend($userId){
	$currentUserToUpdate = get_user_by('id', $userId);
	$userFirstName = $_POST['first_name'];
	$userLastName = $_POST['last_name'];
	$userEmail = $_POST['email'];

	$isUserExist = getUserByEmailFromMoosend(urlencode($currentUserToUpdate->user_email));

	if($isUserExist['Context']['ID']){
		global $currentTime;
		$moosendUserId = $isUserExist['Context']['ID'];
		$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/moosend';
		$moosendApiKey = MOOSEND_API_KEY;
		$moosendOnboardingListId = MOOSEND_ONBOARDING_LIST_ID;
		$moosendApiUrlToGetUser = "https://api.moosend.com/v3/subscribers/$moosendOnboardingListId/update/$moosendUserId.json?apikey=$moosendApiKey";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $moosendApiUrlToGetUser);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"Name\" : \"$userFirstName $userLastName\",\n    \"Email\" : \"$userEmail\",\n    \"HasExternalDoubleOptIn\": false}");
	
		$response = curl_exec($ch);
	
		if (curl_errno($ch)) {
			$error_message = 'Error: ' . curl_error($ch);
			echo $error_message;
			error_log($error_message, 3, "$uploadsDir/moosend_api_error_log.txt");
			$response = false;
		} else {
			file_put_contents("$uploadsDir/moosend_api_response_log_post_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
			$response = json_decode($response, true);
		}
	
		curl_close($ch);
	}
	
}



function updateUserInMoosendAfterNewPurchase($orderId){
	if(!wcs_order_contains_renewal($orderId)){
		$order = wc_get_order( $orderId );
		$currentUser = get_user_by('id', $order->data['customer_id']);
		$isUserOnboarded = get_user_meta($currentUser->id, 'is_user_onboarded', true);

		if($isUserOnboarded){
			foreach($order->get_items() as $orderItem){
				if(has_term('plan', 'product_cat', $orderItem->get_product_id())){
					$userName = "$currentUser->first_name $currentUser->last_name";
					$userEmail = $currentUser->user_email;	
					postToMoosend($userName, $userEmail, 'active', 'news' );	
					return;
				};	
			}
		}
	}
}
add_action( 'woocommerce_payment_complete', 'updateUserInMoosendAfterNewPurchase');

?>