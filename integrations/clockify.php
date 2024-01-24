<?php

function createProjectInClockify($requestBody){
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Api-Key: $apiKey",
        "Content-Type: application/json"
    ));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/clockify_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/clockify_api_response_log_post_project_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function createTaskInClockify($projectId, $requestBody){
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects/$projectId/tasks";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Api-Key: $apiKey",
        "Content-Type: application/json"
    ));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/clockify_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/clockify_api_response_log_post_task_request.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function prepareDataToClockify($entryId, $formData, $form){
    if($form->id === 3){
        $currentUser = wp_get_current_user();
        $requestBody = [
            "name" => "$currentUser->billing_company",
        ];

        $clockifyProject = createProjectInClockify($requestBody);

		if($clockifyProject['id']){
			update_user_meta( $currentUser->id, 'clockify_project_id', $clockifyProject['id'] );
            createTaskInClockify($clockifyProject['id'], $requestBody);
		}

    }
}
add_action( 'fluentform/submission_inserted', 'prepareDataToClockify', 10, 3);
