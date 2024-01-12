<?php

function createContactInFreshdesk($currentUser, $formData, $companyFreshdeskId){	
	$currentUser = wp_get_current_user();
	$userName = "$currentUser->first_name $currentUser->last_name";
	$userEmail = $currentUser->user_email;
	$userAddress = "$currentUser->billing_city, $currentUser->billing_country";
	$companyName = $currentUser->billing_company;
	$companyWebsite = $formData['url'];
	$userJobTitle = $formData['job_title'];
	$userCurrentPlan = '';
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs';

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
				"active" => true,
			]
	];

	$apiEndpoint = 'https://mafreitasfd.freshdesk.com/api/v2/contacts';
	$apiKey = FRESHDESK_API_KEY;
	
	$ch = curl_init($apiEndpoint);

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
	} else {
		file_put_contents("$uploadsDir/freshdesk_api_response_log.txt", $response . PHP_EOL, FILE_APPEND);
	}

	curl_close($ch);
}


function createCompanyInFreshdesk($entryId, $formData, $form){	
	if($form->id === 3){
		$currentUser = wp_get_current_user();
		$companyName = $currentUser->billing_company;
		$companyWebsite = $formData['url'];
		$city = $currentUser->billing_city;
		$country = $currentUser->billing_country;

		$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs';
		
		$requestBody = [
			"name" => $companyName,
			"description" => "Website: $companyWebsite \n City: $city, Country: $country",
			"domains" => [$companyWebsite]
		];

		$apiEndpoint = 'https://mafreitasfd.freshdesk.com/api/v2/companies';
		$apiKey = FRESHDESK_API_KEY;
		
		$ch = curl_init($apiEndpoint);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:X");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			$error_message = 'Error: ' . curl_error($ch);
			echo "Error: $error_message";
			file_put_contents("$uploadsDir/freshdesk_api_error_log.txt", $error_message . PHP_EOL, FILE_APPEND);
		} else {
			echo "contact created";
			$companyFreshdeskId = json_decode($response, true);
			
			if($companyFreshdeskId['id']){
				update_user_meta( $currentUser->id, 'company_freshdesk_id', $companyFreshdeskId['id'] );
			}

			createContactInFreshdesk($currentUser, $formData, $companyFreshdeskId['id']);
			file_put_contents("$uploadsDir/freshdesk_api_response_log.txt", $response . PHP_EOL, FILE_APPEND);
		}

		curl_close($ch);
	}

}

add_action( 'fluentform/submission_inserted', 'createCompanyInFreshdesk', 10, 3);


function updateUserInFreshdeskBasedOnSubscriptionStatus($subscription, $new_status, $old_status){
	if($old_status !== 'pending' && $new_status !== 'cancelled'){
		foreach($subscription->get_items() as $subscritpionItem){
			if(has_term('plan', 'product_cat', $subscritpionItem->id)){
				$customerName = $subscription->data['billing']['first_name'] . " " . $subscription->data['billing']['last_name'];
				$customerEmail = $subscription->data['billing']['email'];
				$customerCompany = $subscription->data['billing']['company'];
				$companyFreshdeskId = get_user_meta( get_current_user_id(), 'company_freshdesk_id' );
			}
		}
	}
}
//add_action('woocommerce_subscription_status_updated', 'updateUserInFreshdeskBasedOnSubscriptionStatus', 10, 3);



?>