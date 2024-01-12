<?php

function curlToMoosend($userName, $userEmail, $status){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, MOOSEND_API_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Accept: application/json',
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"Name\" : \"$userName\",\n    \"Email\" : \"$userEmail\",\n    \"HasExternalDoubleOptIn\": false,\n\"CustomFields\":[\n \"status=$status\"]}");

	curl_exec($ch);

	curl_close($ch);
}



function subscribeUserToMoosendEmailList($entryId, $formData, $form){
	if($form->id === 3){
		$currentUser = wp_get_current_user();
		$userName = "$currentUser->first_name $currentUser->last_name";
		$userEmail = $currentUser->user_email;	
		curlToMoosend($userName, $userEmail, 'active');	
	}
}
add_action( 'fluentform/submission_inserted', 'subscribeUserToMoosendEmailList', 10, 3);



function updateUserInMoosendBasedOnSubscriptionStatus($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to']) || isset($_GET['reactivate_plan'])){;
		if($oldStatus !== 'pending' && $newStatus !== 'cancelled'){
			foreach($subscription->get_items() as $subscritpionItem){
				if(has_term('plan', 'product_cat', $subscritpionItem['product_id'])){
					$userName = $subscription->data['billing']['first_name'] . " " . $subscription->data['billing']['last_name'];
					$userEmail = $subscription->data['billing']['email'];
					$status = "";

					switch($newStatus){
						case "active":
							$status = "active";
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

					curlToMoosend($userName, $userEmail, $status);	
				}
			}
		}
	}

}
add_action('woocommerce_subscription_status_updated', 'updateUserInMoosendBasedOnSubscriptionStatus', 10, 3);

?>