<?php
global $currentTime;
$currentTime = date('Y-m-d');

function postRequestToFreshdesk($apiEndpoint, $requestBody){
	global $currentTime;
	$apiUrl= "https://deerdesigner.freshdesk.com/api/v2/$apiEndpoint";
	$apiKey = FRESHDESK_API_KEY;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/freshdesk';

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
		file_put_contents("$uploadsDir/freshdesk_api_response_log_post_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function putRequestToFreshdesk($freshdeskUserId, $requestBody){
	global $currentTime;
	$apiUrl= "https://deerdesigner.freshdesk.com/api/v2/contacts/$freshdeskUserId";
	$apiKey = FRESHDESK_API_KEY;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/freshdesk';

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
		file_put_contents("$uploadsDir/freshdesk_api_response_log_put_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
	}

	curl_close($ch);
}



function updateCompanyNameInFreshdesk($companyFreshdeskId, $requestBody){
	global $currentTime;
	$apiUrl= "https://deerdesigner.freshdesk.com/api/v2/companies/$companyFreshdeskId";
	$apiKey = FRESHDESK_API_KEY;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/freshdesk';

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
		file_put_contents("$uploadsDir/freshdesk_api_response_log_put_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
	}

	curl_close($ch);
}



function getContactFromFreshdesk($teamMember){
	global $currentTime;
	$teamMemberEmail = urlencode($teamMember->user_email);
	$apiUrl= "https://deerdesigner.freshdesk.com/api/v2/contacts/?email=$teamMemberEmail";
	$apiKey = FRESHDESK_API_KEY;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/freshdesk';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:X");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/freshdesk_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/freshdesk_api_response_log_get_user_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
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
		"view_all_tickets" => true,
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



function createTeamMemberInFreshDesk($accountOwner, $teamMember, $formData, $companyFreshdeskId){	
	$userName = "$teamMember->first_name $teamMember->last_name";
	$userEmail = $teamMember->user_email;
	$userAddress = $teamMember->billing_city ? "$teamMember->billing_city, $teamMember->billing_country" : "";
	$companyName = $teamMember->billing_company;
	$companyWebsite = $formData['url'];
	$userJobTitle = $formData['job_title'];
	$userCurrentPlan = '';

	$userSubscriptions = wcs_get_users_subscriptions($accountOwner->id);

	foreach($userSubscriptions as $subscription){
		if($subscription->has_status(array('active'))){
			foreach($subscription->get_items() as $subItem){
				if(has_term('plan', 'product_cat', $subItem->get_product_id())){
					$accountOwnerSubscriptionStatus = $subscription->get_status();
				}
			}
		}
	}

	$isContactAlreadyExistInFreshdesk = getContactFromFreshdesk($teamMember);

	if($isContactAlreadyExistInFreshdesk){
		update_user_meta( $teamMember->id, 'contact_freshdesk_id', $isContactAlreadyExistInFreshdesk[0]['id'] );
		update_user_meta( $teamMember->id, 'company_freshdesk_id', $isContactAlreadyExistInFreshdesk[0]['company_id'] );
		
		$requestBody = [
			"custom_fields" => buildCustomFieldsToUpdateFreshdeskContact($accountOwnerSubscriptionStatus)
		];

		putRequestToFreshdesk($isContactAlreadyExistInFreshdesk[0]['id'], $requestBody);
		
	}else{	
		$requestBody = [
			"active" => true,
			"company_id" => $companyFreshdeskId,
			"view_all_tickets" => true,
			"name" => $userName,
			"email" => $userEmail,
			"address" => $userAddress,
			"description" => "Company: $companyName \n Website: $companyWebsite",
			"job_title" => $userJobTitle,
			"tags" => [$userCurrentPlan],
			"custom_fields" => buildCustomFieldsToUpdateFreshdeskContact($accountOwnerSubscriptionStatus)
		];
	
		$contactFreshdesk = postRequestToFreshdesk('contacts', $requestBody);
	
		if($contactFreshdesk['id']){
			update_user_meta( $teamMember->id, 'contact_freshdesk_id', $contactFreshdesk['id'] );
			update_user_meta( $teamMember->id, 'company_freshdesk_id', $contactFreshdesk['company_id'] );
		}
	}
}



function buildCustomFieldsToUpdateFreshdeskContact($currentPlanStatus){
	$freshdeskContactStatus = [
		"registered_user" => "registered_user",
		"paused" => "paused",
		"cancelled" => "cancelled"
	];
	
	$status = "";

	switch($currentPlanStatus){
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

	return $freshdeskContactStatus;
}


function synchronizeFreshdeskContactWithSubscription($subscriptionId, $newStatus, $accountOwnerId){
	$subscription = wcs_get_subscription($subscriptionId);

	if($subscription->get_status() === $newStatus){		
		foreach($subscription->get_items() as $subscritpionItem){
			if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){

				$requestBody = [
					"custom_fields" => 	buildCustomFieldsToUpdateFreshdeskContact($newStatus)
				];
	
				updateFreshdeskCompanyMembersBasedOnSubscriptionStatus($accountOwnerId, $requestBody);			
			}
		}

	}
}
add_action('synchronizeFreshdeskContactWithSubscriptionHook', 'synchronizeFreshdeskContactWithSubscription', 10, 3);



function updateFreshdeskCompanyMembersBasedOnSubscriptionStatus($accountOwnerId, $requestBody){	
	$groupsUser = new Groups_User( $accountOwnerId );

	foreach($groupsUser->groups as $group){
		if($group->name !== "Registered"){
			$groupId = $group->group_id;
			$group = new Groups_Group( $groupId );

			foreach($group->users as $groupUser){
				$freshdeskUserId = get_user_meta($groupUser->id, 'contact_freshdesk_id', true);
				putRequestToFreshdesk($freshdeskUserId, $requestBody);
			}
		}
	}
}



function scheduleFreshdeskUpdateStatus($subscription, $newStatus, $oldStatus){
	if($oldStatus !== 'pending' && $newStatus !== 'cancelled'){
		foreach($subscription->get_items() as $subscritpionItem){
			if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
				$currentUserId = $subscription->data['customer_id'];
				$billingPeriodEndingDate =  strtotime(calculateBillingEndingDateWhenPausedOrCancelled($subscription));

				if(time() < $billingPeriodEndingDate){
					wp_schedule_single_event($billingPeriodEndingDate, 'synchronizeFreshdeskContactWithSubscriptionHook', array($subscription->id, $newStatus, $currentUserId));
				}else{
					synchronizeFreshdeskContactWithSubscription($subscription->id, $newStatus, $currentUserId);
				}
			}
		}
	}
}
add_action('woocommerce_subscription_status_updated', 'scheduleFreshdeskUpdateStatus', 20, 3);




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
			createContactInFreshdesk($currentUser, $formData, $companyFreshdesk['id']);
		}

	}
}

add_action( 'fluentform/submission_inserted', 'createCompanyInFreshdesk', 10, 3);



function updateUserInFreshdeskByWordpressProfileUpdate($userId){
	$currentUserToUpdate = get_user_by('id', $userId);
	$isContactAlreadyExistInFreshdesk = getContactFromFreshdesk($currentUserToUpdate);

	if($isContactAlreadyExistInFreshdesk){;
		$companyFreshdeskId = get_the_author_meta('company_freshdesk_id',$userId,true );
		$contactFreshdeskId = get_the_author_meta('contact_freshdesk_id',$userId,true );
		
		$userFirstName = $_POST['first_name'];
		$userLastName = $_POST['last_name'];
		$userEmail = $_POST['email'];
		$companyName = $_POST['billing_company'];
	
		$requestBodyForUser = [
			"active" => true,
			"name" => "$userFirstName $userLastName",
			"email" => $userEmail,
		];

		$requestBodyForCompany = [
			"name" => $companyName,
		];
	
		putRequestToFreshdesk($contactFreshdeskId, $requestBodyForUser);
		updateCompanyNameInFreshdesk($companyFreshdeskId, $requestBodyForCompany);

	}
}



function updateUserInFreshdeskByWoocommerceUpdateCustomer($userId, $user){
	$contactFreshdeskId = get_user_meta($userId, 'contact_freshdesk_id', true );
	$userFirstName = $user->first_name;
	$userLastName = $user->last_name;
	$userEmail = $user->email;

	$requestBodyForUser = [
		"active" => true,
		"name" => "$userFirstName $userLastName",
		"email" => $userEmail,
	];

	putRequestToFreshdesk($contactFreshdeskId, $requestBodyForUser);

}
add_action( 'woocommerce_update_customer', 'updateUserInFreshdeskByWoocommerceUpdateCustomer', 20, 2);




function updateUserInFreshdeskAfterNewPurchase($orderId){
	if(!wcs_order_contains_renewal($orderId)){
		$order = wc_get_order( $orderId );
		$currentUser = get_user_by('id', $order->data['customer_id']);
		$isUserOnboarded = get_user_meta($currentUser->id, 'is_user_onboarded', true);

		if($isUserOnboarded){
			$requestBody = [
				"custom_fields" => 	buildCustomFieldsToUpdateFreshdeskContact("active")
			];

			foreach($order->get_items() as $orderItem){
				if(has_term('plan', 'product_cat', $orderItem->get_product_id())){
					updateFreshdeskCompanyMembersBasedOnSubscriptionStatus($currentUser->id, $requestBody);
					return;
				};	
			}
		}
	}
}
add_action( 'woocommerce_payment_complete', 'updateUserInFreshdeskAfterNewPurchase');


?>