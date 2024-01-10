<?php

function subscribeUserToMoosendEmailList($entryId, $formData, $form){
	if($form->id === 3){
		$currentUser = wp_get_current_user();
		$userName = $currentUser->first_name . " " . $currentUser->last_name;
		$userEmail = $currentUser->user_email;		

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, MOOSEND_API_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"Name\" : \"$userName\",\n    \"Email\" : \"$userEmail\",\n    \"HasExternalDoubleOptIn\": false}");

		curl_exec($ch);

		curl_close($ch);
	}
}
add_action( 'fluentform/submission_inserted', 'subscribeUserToMoosendEmailList', 10, 3);


?>