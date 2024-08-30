<?php
global $currentTime;
$currentTime = date('Y-m-d');

function getAccessTokenFromBox(){
	global $currentTime;
	$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/box';
    $apiUrl = 'https://api.box.com/oauth2/token';

    $requestBody = [
        "client_id" => BOX_CLIENT_ID,
        "client_secret"=> BOX_CLIENT_SECRET,
        "grant_type"=> "client_credentials",
        "box_subject_type"=>"enterprise",
        "box_subject_id"=>BOX_ENTERPRISE_ID
    ];

	error_log("[$currentTime] Iniciando requisição para obter o token de acesso na Box.", 0);

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
		error_log("[$currentTime] Erro ao obter o token de acesso: $error_message", 0);
		$response = false;
	} else {
		error_log("[$currentTime] Token de acesso obtido com sucesso: $response", 0);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}

function uploadFileToBoxFolder($accessToken, $filePath, $parentFolderId){
	global $currentTime;
    $apiUrl = 'https://upload.box.com/api/2.0/files/content';
    $boxUserId = BOX_USER_ID;

	error_log("[$currentTime] Iniciando upload do arquivo $filePath para a pasta $parentFolderId na Box.", 0);

	$fileName = basename($filePath);
	$fileType = mime_content_type($filePath);
	$fileObject = new CURLFile($filePath, $fileType, $fileName);
    
	$requestBody = [
		"attributes" => json_encode([
			"name" => $fileName,
			"parent" => [
				"id" => $parentFolderId
			]
		]),
		"file" => $fileObject
    ];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Content-Type: multipart/form-data",
		"Accept: application/json",
        "as-user: $boxUserId",
        "Authorization: Bearer $accessToken"
	]);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_message = 'Error: ' . curl_error($ch);
		error_log("[$currentTime] Erro ao fazer upload do arquivo: $error_message", 0);
		$response = false;
	} else {
		error_log("[$currentTime] Upload realizado com sucesso: $response", 0);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}

function postNewFolderInBox($accessToken, $folderName, $parentFolderId = BOX_CLIENT_FOLDER_ID){
	global $currentTime;
    $apiUrl = 'https://api.box.com/2.0/folders';
    $boxUserId = BOX_USER_ID;

	error_log("[$currentTime] Iniciando a criação da pasta $folderName na Box.", 0);

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
		error_log("[$currentTime] Erro ao criar a pasta: $error_message", 0);
		$response = false;
	} else {
		error_log("[$currentTime] Pasta $folderName criada com sucesso na Box: $response", 0);
		$response = json_decode($response, true);
	}

	curl_close($ch);

	return $response;
}



