<?php
global $currentTime;
$currentTime = date('Y-m-d');

function createProjectInClockify($requestBody){
	global $currentTime;
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/clockify';

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
		file_put_contents("$uploadsDir/clockify_api_response_log_post_project_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function createTaskInClockify($projectId, $requestBody){
	global $currentTime;
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects/$projectId/tasks";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/clockify';

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
		file_put_contents("$uploadsDir/clockify_api_response_log_post_task_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function updateProjectInClockify($projectId, $requestBody){
	global $currentTime;
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects/$projectId";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/clockify';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Api-Key: $apiKey",
        "Content-Type: application/json"
    ));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/clockify_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/clockify_api_response_log_put_project_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function updateTaskInClockify($projectId, $taskId, $requestBody){
	global $currentTime;
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects/$projectId/tasks/$taskId";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/clockify';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Api-Key: $apiKey",
        "Content-Type: application/json"
    ));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/clockify_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/clockify_api_response_log_put_task_project_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function getProjectByName($projectName){
	global $currentTime;
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects?name=$projectName";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/clockify';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Api-Key: $apiKey",
        "Content-Type: application/json"
    ));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/clockify_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/clockify_api_response_log_get_project_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



function getTaskByProjectId($projectId){
	global $currentTime;
    $apiKey = CLOCKFY_API_KEY;
    $workspaceId = CLOCKFY_WORKSPACE_ID;
	$apiUrl= "https://api.clockify.me/api/v1/workspaces/$workspaceId/projects/$projectId/tasks";
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/clockify';

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Api-Key: $apiKey",
        "Content-Type: application/json"
    ));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message, 3, "$uploadsDir/clockify_api_error_log.txt");
		$response = false;
	} else {
		file_put_contents("$uploadsDir/clockify_api_response_log_get_task_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}


function updateProjectAndTaskNameInClockify($userId){
	$currentUser = get_user_by('id', $userId);
	$companyName = $currentUser->billing_company;

	$response = getProjectByName($companyName);

	if($response){
		$requestBody = [
            "name" => $_POST['billing_company'],
			"isPublic" => true
        ];

		updateProjectInClockify($response[0]['id'], $requestBody);
		$task = getTaskByProjectId($response[0]['id']);

		if($task){
			updateTaskInClockify($response[0]['id'], $task[0]['id'], $requestBody);
		}
	}
}


function prepareDataToClockify($entryId, $formData, $form){
    if($form->id === 3){
        $currentUser = wp_get_current_user();
        $requestBody = [
            "name" => "$currentUser->billing_company",
			"isPublic" => true
        ];

        $clockifyProject = createProjectInClockify($requestBody);

		if($clockifyProject['id']){
			update_user_meta( $currentUser->id, 'clockify_project_id', $clockifyProject['id'] );
            createTaskInClockify($clockifyProject['id'], $requestBody);
		}

    }
}
add_action( 'fluentform/submission_inserted', 'prepareDataToClockify', 10, 3);
