<?php

// function getFolderFromBox($userName, $userEmail, $status, $moosendList){
// 	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/box';
// 	$moosendApiUrl = $moosendList === "news" ? MOOSEND_API_URL_NEWS : MOOSEND_API_URL;
// 	$ch = curl_init();
// 	curl_setopt($ch, CURLOPT_URL, $moosendApiUrl);
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
// 	curl_setopt($ch, CURLOPT_HTTPHEADER, [
// 		'Content-Type: application/json',
// 		'Accept: application/json',
// 	]);
// 	curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"Name\" : \"$userName\",\n    \"Email\" : \"$userEmail\",\n    \"HasExternalDoubleOptIn\": false,\n\"CustomFields\":[\n \"status=$status\"]}");

// 	$response = curl_exec($ch);

// 	if (curl_errno($ch)) {
// 		$error_message = 'Error: ' . curl_error($ch);
// 		echo $error_message;
// 		error_log($error_message, 3, "$uploadsDir/moosend_api_error_log.txt");
// 		$response = false;
// 	} else {
// 		file_put_contents("$uploadsDir/moosend_api_response_log_post_request.txt", $response . PHP_EOL, FILE_APPEND);
// 		$response = json_decode($response, true);
// 	}

// 	curl_close($ch);

// 	return $response;
// }