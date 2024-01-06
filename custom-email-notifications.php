<?php 

global $headers;
$headers = array(
	'Content-Type: text/html; charset=UTF-8',
	'Reply-To: Deer Designer <help@deerdesigner.com>',
);

function emailTemplate($content){
	return '
	<table width="100%" style="background: #f7f7f7; padding: 40px;">
		<tr>
			<td width="600px">
				<div style="width: 600px; margin: auto;">
					<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="border-radius: 5px">
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="100%">
									<tr>
										<td align="center" valign="top">
											<!-- Header -->
											<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: #43B5A0">
												<tr>
													<td style="padding: 36px 48px; display: block; text-align: center; background-color: transparent; border: none; border-bottom: 1px solid #eee;">
														<p style="margin:0;">
														<img src="https://dash.deerdesigner.com/wp-content/uploads/2023/12/logo-email-header.png"/>
														</p>
													</td>
												</tr>
											</table>
											<!-- End Header -->
										</td>
									</tr>
									<tr>
										<td align="center" valign="top">
											<!-- Body -->
											<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" style="background: #fff">
												<tr>
													<td valign="top" id="body_content">
														<!-- Content -->
														<table border="0" cellpadding="20" cellspacing="0" width="100%">
															<tr>
																<td valign="top" style="padding:48px 48px 32px;background-color:transparent;border:none;border-bottom:1px solid #eee">
																	<div id="body_content_inner" style="font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;">' . $content . '</div>
																</td>
															</tr>
														</table>
														<!-- End Content -->
													</td>
												</tr>
											</table>
											<!-- End Body -->
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="center" valign="top">
								<!-- Footer -->
								<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
									<tr>
										<td valign="top">
											<table border="0" cellpadding="10" cellspacing="0" width="100%">
												<tr>
													<td colspan="2" valign="middle" id="credit">
														
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								<!-- End Footer -->
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	</table>
	';
}

function sendEmailToAdminAfterUserProfileUpdated($userId, $oldUserData, $userData){
	if(!is_admin()){
		global $wp, $headers;
		$editProfileUrl = site_url() . "/edit-account";
		
		if(home_url( $wp->request ) === $editProfileUrl){
			$user = get_userdata( $userId );
			$userFirstName = $user->first_name;
			$userLastName = $user->last_name;
			$userEmail = $user->user_email;
			$companyName = get_user_meta($userId, 'billing_company', true);

			$subject = "User Profile Updated";
			$toEmail = get_option( 'admin_email' );

			$message =  "							
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>User info:</p>
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>First name: $userFirstName</p>
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Last name: $userLastName</p>
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Email: $userEmail</p>
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Company: $companyName</p>
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'><strong>⚠️Make sure to update it on every platform</strong></p>
				";
		
			$body = emailTemplate($message);

			wp_mail($toEmail, $subject, $body, $headers);
		}
	}
}
add_action( 'profile_update', 'sendEmailToAdminAfterUserProfileUpdated', 10, 3);



function userUpdatedPaymentMethods($message){
	if(!is_admin()){
		if (str_contains($message, 'Payment method successfully added.')) {
			global $headers;
			$user = wp_get_current_user();
			$userName = "$user->first_name $user->last_name";
			$userEmail = $user->user_email;

			$subject = "Payment method updated";

			$emailMessage = "
		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your payment method was updated. If you believe this request was a mistake, please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a>.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br> The Deer Designer Team.</p>
		";

			$body = emailTemplate($emailMessage);
			wp_mail($userEmail, $subject, $body, $headers);
		}
	}

	return $message;
}
add_action('woocommerce_add_message', 'userUpdatedPaymentMethods');



function sendEmailToUserWhenPausedPlan($subscription){
	if(isset($_GET['change_subscription_to'])){
		global $headers;

		$user = wp_get_current_user();
		$userName = $user->first_name;
		$userEmail = $user->user_email;

		$billingCycle = calculateBillingEndingDateWhenPausedOrCancelled($subscription);
		$tomorrowDate = date('F j, Y', strtotime('+1 days'));	
		$oneDayBeforeBillingPeriodEnds = strtotime('-1 day', strtotime($billingCycle));
		
		$subject = "Your account has been paused";

		$messageA = "
		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName,</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your account has been paused and you can still work with your team until your billing period ends on $billingCycle.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>To reactivate the account, just click on 'Reactivate' next to your plan and we will take care of it for you.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> if anything changes.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
		The Deer Designer Team.</p>
		";	

		$messageB = "
		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName,</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Just a reminder that your Deer Designer account is scheduled to be paused tomorrow: $billingCycle.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If things changed and you'd like to keep your account active, just go to the Billing Portal and click on “Reactivate” next to your plan.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>I hope to see you again soon!.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
		The Deer Designer Team.</p>
		";

		if(strtotime($billingCycle) == strtotime($tomorrowDate)){
			wp_mail($userEmail, $subject, emailTemplate($messageB), $headers);
		}else if(strtotime($billingCycle) == time()){
			wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);
		}else{
			wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);

			if(time() < $oneDayBeforeBillingPeriodEnds){
				wp_schedule_single_event($oneDayBeforeBillingPeriodEnds, 'scheduleEmailToBeSentOnDayBeforeBillingDateEndsHook', array($subscription->id, $userEmail, $subject, emailTemplate($messageB), $headers));
			}

		}
	}
}
add_action('woocommerce_subscription_status_on-hold', 'sendEmailToUserWhenPausedPlan', 10, 3);



function sendEmailToUserWhenCancelledPlan($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to'])){
		if($newStatus == 'pending-cancel'){
			foreach($subscription->get_items() as $subItem){
				if(has_term('plan', 'product_cat', $subItem['product_id'])){
					global $headers;
					$user = wp_get_current_user();
					$userName = $user->first_name;
					$userEmail = $user->user_email;
					
					$billingCycle = esc_html( $subscription->get_date_to_display( 'end' ) );
					$tomorrowDate = date('F j, Y', strtotime('+1 days'));	
					$oneDayBeforeBillingPeriodEnds = strtotime('-1 day', strtotime($billingCycle));
					
					$subject = "Your account is set to Cancel";

					$messageA = "
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName,</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your account has been canceled and you can still work with your team until your billing period ends on $billingCycle.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>After that date, you'll lose access to your tickets, communication, and designs.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you think this is a mistake, please email us at <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> before the account is cancelled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks for trusting us with your design work during this time.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

						$messageB = "
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName,</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Just a reminder that your Deer Designer account is scheduled to be cancelled tomorrow: $billingCycle.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you think this is a mistake, please email us at <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> before the account is cancelled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>I hope to see you again soon!.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

					if(strtotime($billingCycle) == strtotime($tomorrowDate)){
						wp_mail($userEmail, $subject, emailTemplate($messageB), $headers);
					}else if(strtotime($billingCycle) == time()){
						wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);
					}else{
						wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);

						if(time() < $oneDayBeforeBillingPeriodEnds){
							wp_schedule_single_event($oneDayBeforeBillingPeriodEnds, 'scheduleEmailToBeSentOnDayBeforeBillingDateEndsHook', array($subscription->id, $userEmail, $subject, emailTemplate($messageB), $headers));
						}
					}
				}
			}
			
		}
	}
}
add_action('woocommerce_subscription_status_updated', 'sendEmailToUserWhenCancelledPlan', 10, 3);



function sendEmailToUserWhenCancelledActiveTask($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to'])){
		if($newStatus == 'pending-cancel'){
			foreach($subscription->get_items() as $subItem){
				if(has_term('active-task', 'product_cat', $subItem['product_id'])){
					global $headers;
					$user = wp_get_current_user();
					$userName = $user->first_name;
					$userEmail = $user->user_email;

					$billingCycle = esc_html( $subscription->get_date_to_display( 'end' ) );
					
					$subject = "Your additional active task has been canceled";

					$messageA = "
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName,</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your additional active task has been canceled and it'll still be available until it's billing period ends on $billingCycle.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you think this is a mistake, please email us at <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> before this active task is cancelled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

					$messageB = "
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName,</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your additional task has now been canceled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you believe this request was a mistake, please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a>.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

					if(time() == strtotime($billingCycle)){
						wp_mail($userEmail, $subject, emailTemplate($messageB), $headers);
					}else{
						wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);
					}
				}
			}
			
		}
	}
}
add_action('woocommerce_subscription_status_updated', 'sendEmailToUserWhenCancelledActiveTask', 10, 3);



function sendEmailToUserWhenReactivateSubscription($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to']) || isset($_GET['reactivate_plan'])){
		if($oldStatus !== 'pending' && $newStatus == 'active'){
			foreach($subscription->get_items() as $subItem){

				if(has_term('plan', 'product_cat', $subItem['product_id'])){
					global $headers;
					$user = wp_get_current_user();
					$userName = "$user->first_name $user->last_name";
					$userEmail = $user->user_email;
					$productName = $subItem['name'];
					
					$subject = "Your account has been reactivated";

					$message = "
					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi $userName,</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your account has been reactivated on the plan $productName!.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If your previous designer is available, they'll be reassigned to you. If not, we'll pick someone new who'll review your profile and past requests and be ready within a business day. Feel free to log in and send requests anytime!</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Please reach out to help@deerdesigner.com if you need any additional help.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

					wp_mail($userEmail, $subject, emailTemplate($message), $headers);
				}

			}
		}
	}

}
add_action('woocommerce_subscription_status_updated', 'sendEmailToUserWhenReactivateSubscription', 10, 3);



function sendEmailToAdminWhenReactivateSubscription($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to']) || isset($_GET['reactivate_plan'])){
		if($oldStatus !== 'pending' && $newStatus == 'active'){
			foreach($subscription->get_items() as $subItem){
				if(has_term('plan', 'product_cat', $subItem['product_id'])){
					global $headers;
					$user = wp_get_current_user();
					$userName = "$user->first_name $user->last_name";
					$userEmail = get_option( 'admin_email' );
					$productName = $subItem['name'];
					$companyName = get_user_meta(get_current_user_id(), 'billing_company', true);

					$currentDate = new DateTime($subscription->get_date_to_display( 'start' )); 
					$currentDate->add(new DateInterval('P1' . strtoupper($subscription->billing_period[0])));
					$billingCycle = $currentDate->format('F j, Y');
					
					$subject = str_contains(strtolower($productName), 'task') ? "Active Task reactivated" : "Account reactivated";

					$message = "
					<p class='user__details'><strong>Account reactivated by: </strong>$userName | $userEmail | $companyName</p>
					<p>Plan: $productName | $billingCycle</p>
					";

					wp_mail($userEmail, $subject, emailTemplate($message), $headers);
				}

			}
		}
	}

}
add_action('woocommerce_subscription_status_updated', 'sendEmailToAdminWhenReactivateSubscription', 10, 3);



function customRetryPaymentRules( $default_retry_rules_array ) {
    return array(
            array(
                'retry_after_interval'            => 86400 /*24 HOURS*/,
                'email_template_customer'         => 'WCS_Email_Customer_Payment_Retry',
                'email_template_admin'            => 'WCS_Email_Payment_Retry',
                'status_to_apply_to_order'        => 'pending',
                'status_to_apply_to_subscription' => 'on-hold',
            ),
            array(
                'retry_after_interval'            => 86400 /*24 HOURS*/,
                'email_template_customer'         => 'WCS_Email_Customer_Payment_Retry',
                'email_template_admin'            => 'WCS_Email_Payment_Retry',
                'status_to_apply_to_order'        => 'pending',
                'status_to_apply_to_subscription' => 'on-hold',
            )
        );
}
add_filter( 'wcs_default_retry_rules', 'customRetryPaymentRules' );



function scheduleEmailToBeSentOnDayBeforeBillingDateEnds($subscriptionId, $userEmail, $subject, $body, $headers){
	$subscription = wcs_get_subscription($subscriptionId);

	if($subscription->get_status() === "on-hold" || $subscription->get_status() === "pending-cancel"){
		wp_mail($userEmail, $subject, $body, $headers);

		if($subscription->get_status() === "pending-cancel"){
			$user = get_user_by( 'email', $userEmail );
			$customerName = $user->first_name . " " . $user->last_name;
			$customerCompany = get_user_meta($user->id, 'billing_company', true);
			
			$slackMessageBody = [
					'text'  => '<!channel> Subscription Cancelled :alert:' . '
			*Client:* ' . $customerName . " ($customerCompany)'s " . 'account cancels tomorrow.
			Only work on their designs until today.',
					'username' => 'Marcus',
				];


			slackNotifications($slackMessageBody);
		}
	}
}
add_action('scheduleEmailToBeSentOnDayBeforeBillingDateEndsHook', 'scheduleEmailToBeSentOnDayBeforeBillingDateEnds', 10, 5);




function sendWelcomeEmailAfterOnboardingForm($userName, $userEmail){
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Thiago <thiago@deerdesigner.com>',
		'Reply-To: Thiago <thiago@deerdesigner.com>',
	);

	$subject = "Hey $userName, Thiago here!😉";

	$message = "
		Hey $userName, welcome to the team!
		<br><br>

		I'm thrilled to have you on board! Every time someone joins Deer Designer, we ring our 🔔 and celebrate! 🎉🍾
		<br><br>

		Thank you for answering the onboarding questions. They help us understand your needs and create your client profile in our platform. This process will take around 1 business day and we'll email you as soon as we're ready for your first request.
		<br><br>

		In your first month with us, different members of our team will work with you and keep an eye on your requests. During this time, your feedback is more important than ever and will allow us to settle on the best account manager and designer to work with you in the long run. 
		<br><br>

		While you wait to send your first request, <a href='https://deerdesigner.com/our-processes' target='_blank' style='color: #54c1a2;'>here's what you should know about our processes</a>.
		<br><br>

		We also created a <a href='https://help.deerdesigner.com/' target='_blank' style='color: #54c1a2;'>Help Centre</a> where you can check some of our clients' frequently asked questions.
		<br><br>

		Speak soon,<br>
		Thiago<br><br>

		PS: If you'd like a quick onboarding call to get things started, please <a href='https://book.deer.tools/client-onboarding/' target='_blank' style='color: #54c1a2;'>book the best time that suits you here</a>.
	";

	wp_mail($userEmail, $subject, emailTemplate($message), $headers);
}


add_action('sendWelcomeEmailAfterOnboardingFormHook', 'sendWelcomeEmailAfterOnboardingForm', 10, 2);



function sendWelcomeEmailAfterOnboardingFormOneWeekLater($userName, $userEmail){
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Thiago <thiago@deerdesigner.com>',
		'Reply-To: Thiago <thiago@deerdesigner.com>',
	);

	$subject = "A week already?⚡";

	$message = "
		Hey $userName, Thiago here again.<br><br>
		It's been a week now since you've joined Deer Designer. It goes fast, hey? 🚀<br><br>

		I'd love to hear your thoughts on the service so far. Do you have any questions or ideas? <br><br>

		Just hit reply and let me know!<br><br>

		Cheers,<br>
		Thiago<br>
		Founder @ Deer Designer
	";

	wp_mail($userEmail, $subject, emailTemplate($message), $headers);
}


add_action('sendWelcomeEmailAfterOnboardingFormOneWeekLaterHook', 'sendWelcomeEmailAfterOnboardingFormOneWeekLater', 10, 2);


function sendWelcomeEmailToAdditionalTeamMembers($userName, $userEmail, $accountOwnerId, $userPassword = false){
	global $headers;
	$accountOwner = get_user_by( 'id', $accountOwnerId);
	$companyName = get_user_meta($accountOwnerId, 'billing_company', true);

	$subject = "Welcome to Deer Designer";

	$messageA = "
		Hey $userName,<br><br>
		$accountOwner->first_name from $companyName just added you to their group in our system!<br><br>

		Here is your credentials: <br><br>
		Login: $userEmail<br>
		Password: $userPassword<br>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
		The Deer Designer Team.</p>
	";

	$messageB = "
		Hey $userName,<br><br>
		$accountOwner->first_name from $companyName just added you to their group in our system!<br><br>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
		The Deer Designer Team.</p>
	";

	if($userPassword){
		$finalMessage = $messageA;
	}else{
		$finalMessage = $messageB;
	}

	wp_mail($userEmail, $subject, emailTemplate($finalMessage), $headers);
}



function sendEmailToProductionWhenNewTeamMemberIsAdded($accountOwnerId, $additionalUsersAdded){
	global $headers;
	$accountOwner = get_user_by( 'id', $accountOwnerId);
	$companyName = get_user_meta($accountOwnerId, 'billing_company', true);
	$productionEmail = 'production@deerdesigner.com';
	$additionalUsers = implode(', ', $additionalUsersAdded);

	$subject = "New Team Member Added";

	$message = "
		A client just added new team members to their account: ,<br><br>
		<strong>Owner:</strong> $accountOwner->first_name | $accountOwner->user_email ($companyName)<br>
		<strong>Team Members:</strong> $additionalUsers.
	";


	wp_mail($productionEmail, $subject, emailTemplate($message), $headers);
}
?>