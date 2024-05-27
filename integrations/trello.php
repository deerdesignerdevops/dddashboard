<?php
global $currentTime;
$currentTime = date('Y-m-d');

function searchTrelloCardByTicketNumber(){
	global $currentTime;

	if(isset($_GET['ticket_number'])){
		$ticketNumber = $_GET['ticket_number'];
		$uploadsDir = wp_upload_dir()['basedir'] . '/integrations-api-logs/trello';
		$trelloApiKey = TRELLO_API_KEY;
		$trelloToken = TRELLO_TOKEN;
		$trelloBoardId = '5a9042e34d72ec3d5b79a502';
		$moosendApiUrl = "https://api.trello.com/1/search/?query=$ticketNumber&idBoards=$trelloBoardId&modelTypes=cards&card_members=true&member_fields=fullName&key=$trelloApiKey&token=$trelloToken";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $moosendApiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
		]);
	
		$response = curl_exec($ch);
	
		if (curl_errno($ch)) {
			$error_message = 'Error: ' . curl_error($ch);
			echo $error_message;
			error_log($error_message, 3, "$uploadsDir/trello_api_error_log_$currentTime.txt");
			$response = false;
		} else {
			file_put_contents("$uploadsDir/trello_api_response_log_get_request_$currentTime.txt", $response . PHP_EOL, FILE_APPEND);
			$response = json_decode($response, true);
		}
	
		curl_close($ch);
		
		$ticketmembers = $response['cards'][0]['members'];
		$ticketmembersNames = [];
		foreach($ticketmembers as $member){
			$ticketmembersNames[] = $member['fullName'];
		}

		$ticketmembersNames = implode(", ", $ticketmembersNames);
		return $ticketmembersNames;
	}

}

function fillTicketMembers($form){
	if($form->id == 5){
		$ticketmembersNames = searchTrelloCardByTicketNumber();

		if($ticketmembersNames){
			echo "<script>
				document.addEventListener('DOMContentLoaded', function(){
					document.querySelector('[data-name=\"hidden_ticket_members\"]').value='$ticketmembersNames'
				})
			</script>";
		}
	}
}

add_action('fluentform/after_form_render', 'fillTicketMembers');
