<?php

function getAccessTokenFromBox(){
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/box';
    $apiUrl = 'https://api.box.com/oauth2/token';
    
    $requestBody = [
        "client_id" => BOX_CLIENT_ID,
        "client_secret"=> BOX_CLIENT_SECRET,
        "grant_type"=> "client_credentials",
        "box_subject_type"=>"enterprise",
        "box_subject_id"=>BOX_ENTERPRISE_ID
    ];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Accept: application/json',
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/box_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/box_api_response_log_token_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function postNewFolderInBox($accessToken, $folderName, $parentFolderId = BOX_CLIENT_FOLDER_ID){
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/box';
    $apiUrl = 'https://api.box.com/2.0/folders';
    $boxUserId = BOX_USER_ID;
    
    $requestBody = [
        "name" => $folderName,
        "parent" => [
            "id" => $parentFolderId
        ]
    ];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Content-Type: application/json",
		"Accept: application/json",
        "as-user: $boxUserId",
        "Authorization: Bearer $accessToken"
	]);

	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/box_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/box_api_response_log_post_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function createCompanyFoldersInBox($entryId, $formData, $form){
    if($form->id === 3){;
        $currentUser = wp_get_current_user();
        $parentFolderName = $currentUser->billing_company;
        
        $companySubFolder = [
            'Best of', 'Brand Assets', 'Design Proposals', 'Requests', 'Sub-brands'
        ];
        
        $accessToken = getAccessTokenFromBox();
        $accessToken = $accessToken['access_token'];

        if($accessToken){
            $parentFolderId = postNewFolderInBox($accessToken, $parentFolderName);
            $parentFolderId = $parentFolderId['id'];
    
            if($parentFolderId){
                update_user_meta($currentUser->id, "company_folder_box_id", $parentFolderId);
                
                foreach($companySubFolder as $companySubFolder){
                    postNewFolderInBox($accessToken, $companySubFolder, $parentFolderId);
                }
            }
        }
    }
}
add_action( 'fluentform/submission_inserted', 'createCompanyFoldersInBox', 10, 3);



function updateFolderParentDirectory($folderName, $folderId, $newParentFolderId){
    $accessToken = getAccessTokenFromBox();
    $accessToken = $accessToken['access_token'];
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/box';
    $apiUrl = "https://api.box.com/2.0/folders/$folderId";
    $boxUserId = BOX_USER_ID;
    
    $requestBody = [
        "name" => $folderName,
        "parent" => [
            "id" => $newParentFolderId
        ]
    ];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Content-Type: application/json",
		"Accept: application/json",
        "as-user: $boxUserId",
        "Authorization: Bearer $accessToken"
	]);

	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/box_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/box_api_response_log_update_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function scheduleUpdateFolderParentDirectory($subscriptionId, $newStatus, $currentUserId){
	$subscription = wcs_get_subscription($subscriptionId);
    $user = get_user_by('id', $currentUserId);
    $folderName = $user->billing_company;
    $folderId = get_user_meta($currentUserId, "company_folder_box_id", true);
    $newParentFolderId = "";

    switch($newStatus){        
        case 'on-hold':
            $newParentFolderId = BOX_PAUSED_FOLDER_ID;
            break;
        
        case 'pending-cancel':
            $newParentFolderId = BOX_CANCELLED_FOLDER_ID;
            break;

        default:
            $newParentFolderId = BOX_CLIENT_FOLDER_ID;
            break;
    }

	if($subscription->get_status() === $newStatus){		
		foreach($subscription->get_items() as $subscritpionItem){
			if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
				updateFolderParentDirectory($folderName, $folderId, $newParentFolderId);		
			}
		}

	}
}
add_action('scheduleUpdateFolderParentDirectoryHook', 'scheduleUpdateFolderParentDirectory', 10, 3);



function moveCompanyFolderBasedOnSubscriptionStatus($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to']) || isset($_GET['reactivate_plan'])){
		if($oldStatus !== 'pending' && $newStatus !== 'cancelled'){
			foreach($subscription->get_items() as $subscritpionItem){
				if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
					$currentUserId = $subscription->data['customer_id'];
					$billingPeriodEndingDate =  strtotime(calculateBillingEndingDateWhenPausedOrCancelled($subscription));

					if(time() < $billingPeriodEndingDate){
						wp_schedule_single_event($billingPeriodEndingDate, 'scheduleUpdateFolderParentDirectoryHook', array($subscription->id, $newStatus, $currentUserId));
					}else{
						scheduleUpdateFolderParentDirectory($subscription->id, $newStatus, $currentUserId);
					}
				}
			}
		}
	}
}
add_action('woocommerce_subscription_status_updated', 'moveCompanyFolderBasedOnSubscriptionStatus', 40, 3);
