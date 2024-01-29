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



function postNewFolderInBox($accessToken, $folderName, $parentFolderId = BOX_PARENT_FOLDER_ID){
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
