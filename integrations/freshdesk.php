<?php

function postRequestToFreshdesk($apiEndpoint, $requestBody){
	$apiUrl= "https://deerdesigner.freshdesk.com/api/v2/$apiEndpoint";
	$apiKey = FRESHDESK_API_KEY;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:X");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/freshdesk_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/freshdesk_api_response_log_post_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function putRequestToFreshdesk($freshdeskUserId, $requestBody){
	$apiUrl= "https://deerdesigner.freshdesk.com/api/v2/contacts/$freshdeskUserId";
	$apiKey = FRESHDESK_API_KEY;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:X");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/freshdesk_api_error_log.txt");
	} else {
		file_put_contents("$uploadsDir/freshdesk_api_response_log_put_request.txt", $response . PHP_EOL, FILE_APPEND);
	}

	curl_close($ch);
}



function createContactInFreshdesk($currentUser, $formData, $companyFreshdeskId){	
	$userName = "$currentUser->first_name $currentUser->last_name";
	$userEmail = $currentUser->user_email;
	$userAddress = $currentUser->billing_city ? "$currentUser->billing_city, $currentUser->billing_country" : "";
	$companyName = $currentUser->billing_company;
	$companyWebsite = $formData['url'];
	$userJobTitle = $formData['job_title'];
	$userCurrentPlan = '';

	$userSubscriptions = wcs_get_users_subscriptions($currentUser->id);

	foreach($userSubscriptions as $subscription){
		if($subscription->has_status(array('active'))){
			foreach($subscription->get_items() as $subItem){
				if(has_term('plan', 'product_cat', $subItem->get_product_id())){
					$userCurrentPlan = $subItem['name'];
				}
			}
		}
	}

	$requestBody = [
		"active" => true,
		"company_id" => $companyFreshdeskId,
		"name" => $userName,
		"email" => $userEmail,
		"address" => $userAddress,
		"description" => "Company: $companyName \n Website: $companyWebsite",
		"job_title" => $userJobTitle,
		"tags" => [$userCurrentPlan],
		"custom_fields" => [
				"registered_user" => true,
			]
	];

	$contactFreshdesk = postRequestToFreshdesk('contacts', $requestBody);

	if($contactFreshdesk['id']){
		update_user_meta( $currentUser->id, 'contact_freshdesk_id', $contactFreshdesk['id'] );
	}
}



function deleteContactFromFreshdesk($freshdeskUserId){
	$apiUrl= "https://deerdesigner.freshdesk.com/api/v2/contacts/$freshdeskUserId/hard_delete?force=true";
	$apiKey = FRESHDESK_API_KEY;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:X");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/freshdesk_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/freshdesk_api_response_log_delete_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);
}



function updateUserInFreshdeskBasedOnSubscriptionStatus($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to']) || isset($_GET['reactivate_plan'])){
		if($oldStatus !== 'pending' && $newStatus !== 'cancelled'){
			foreach($subscription->get_items() as $subscritpionItem){
				if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
					$freshdeskUserId = get_user_meta(get_current_user_id(), 'contact_freshdesk_id', true);
					
					$freshdeskContactStatus = [
						"registered_user" => "registered_user",
						"paused" => "paused",
						"cancelled" => "cancelled"
					];
					
					$status = "";

					switch($newStatus){
						case "active":
							$status = "registered_user";
							break;
						
						case "on-hold":
							$status = "paused";
							break;
						
						case "pending-cancel":
							$status = "cancelled";
							break;
						
						default:
							$status = "paused";
					}

					foreach($freshdeskContactStatus as $contactStatusValue){
						if($contactStatusValue === $status){
							 $freshdeskContactStatus[$contactStatusValue] = true;
						}else{
							 $freshdeskContactStatus[$contactStatusValue] = false;
						}					
					}

					$requestBody = [
						"custom_fields" => $freshdeskContactStatus
					];

					putRequestToFreshdesk($freshdeskUserId, $requestBody);	
				}
			}
		}
	}

}
add_action('woocommerce_subscription_status_updated', 'updateUserInFreshdeskBasedOnSubscriptionStatus', 10, 3);



function createCompanyInFreshdesk($entryId, $formData, $form){	
	if($form->id === 3){
		$currentUser = wp_get_current_user();
		$companyName = $currentUser->billing_company;
		$companyWebsite = $formData['url'];
		$city = $currentUser->billing_city;
		$country = $currentUser->billing_country;
		
		$requestBody = [
			"name" => $companyName,
			"description" => "Website: $companyWebsite \n City: $city, Country: $country",
			"domains" => [$companyWebsite]
		];

		$companyFreshdesk = postRequestToFreshdesk('companies', $requestBody);
		
		if($companyFreshdesk['id']){
			update_user_meta( $currentUser->id, 'company_freshdesk_id', $companyFreshdesk['id'] );
		}

		createContactInFreshdesk($currentUser, $formData, $companyFreshdesk['id']);
	}
}

add_action( 'fluentform/submission_inserted', 'createCompanyInFreshdesk', 10, 3);


?>