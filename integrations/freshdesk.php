<?php
global $currentTime;
$currentTime = date('Y-m-d');

function postRequestToFreshdesk($apiEndpoint, $requestBody) {
	global $currentTime;
	$apiUrl = "https://deerdesigner.freshdesk.com/api/v2/$apiEndpoint";
	$apiKey = FRESHDESK_API_KEY;

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
		error_log($error_message); // Log no arquivo debug.log do WordPress
		$response = false;
	} else {
		error_log("POST Response: " . $response); // Log no arquivo debug.log do WordPress
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}

function putRequestToFreshdesk($freshdeskUserId, $requestBody) {
	global $currentTime;
	$apiUrl = "https://deerdesigner.freshdesk.com/api/v2/contacts/$freshdeskUserId";
	$apiKey = FRESHDESK_API_KEY;

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
		error_log($error_message); // Log no arquivo debug.log do WordPress
	} else {
		error_log("PUT Response: " . $response); // Log no arquivo debug.log do WordPress
	}

	curl_close($ch);
}

function updateCompanyNameInFreshdesk($companyFreshdeskId, $requestBody) {
	global $currentTime;
	$apiUrl = "https://deerdesigner.freshdesk.com/api/v2/companies/$companyFreshdeskId";
	$apiKey = FRESHDESK_API_KEY;

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
		error_log($error_message); // Log no arquivo debug.log do WordPress
	} else {
		error_log("PUT Response: " . $response); // Log no arquivo debug.log do WordPress
	}

	curl_close($ch);
}

function getContactFromFreshdesk($teamMember) {
	global $currentTime;
	$teamMemberEmail = urlencode($teamMember->user_email);
	$apiUrl = "https://deerdesigner.freshdesk.com/api/v2/contacts/?email=$teamMemberEmail";
	$apiKey = FRESHDESK_API_KEY;

	$ch = curl_init($apiUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:X");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		echo $error_message;
		error_log($error_message); // Log no arquivo debug.log do WordPress
		$response = false;
	} else {
		error_log("GET Response: " . $response); // Log no arquivo debug.log do WordPress
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}

?>