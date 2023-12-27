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
					<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>User info:</h2>
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
		<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

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
		$userName = "$user->first_name $user->last_name";
		$userEmail = $user->user_email;
		
		$currentDate = new DateTime($subscription->get_date_to_display( 'start' )); 
		$currentDate->add(new DateInterval('P1' . strtoupper($subscription->billing_period[0])));
		$billingDate = strtotime($currentDate->format('F j, Y'));
		$billingCycle = $currentDate->format('F j, Y');
		$tomorrowDate = date('F j, Y', strtotime('+1 days'));	
		$oneDayBeforeBillingPeriodEnds = strtotime('-1 day', $billingDate);
		
		$firstSentence = time() == $billingDate ? "Your account has now been put on Pause." : "Your account has been put on Pause";	
		$subject = "Your account is set to Pause";

		$messageA = "
		<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>$firstSentence. Your team will still be available to work with you until the end of your current billing cycle ($billingCycle).</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>To reactivate the account, just click on 'Reactivate' next to your plan and we will take care of it for you.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> if anything changes.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
		The Deer Designer Team.</p>
		";	

		$messageB = "
		<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Just a reminder that your Deer Designer account is scheduled to be paused tomorrow: $tomorrowDate.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If things changed and you'd like to keep your account active, just go to the Billing Portal and click on “Reactivate” next to your plan.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>I hope to see you again soon!.</p>

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
		The Deer Designer Team.</p>
		";

		if($billingDate == $tomorrowDate){
			wp_mail($userEmail, $subject, emailTemplate($messageB), $headers);
		}else if($billingDate == time()){
			wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);
		}else{
			wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);
			wp_schedule_single_event($oneDayBeforeBillingPeriodEnds, 'scheduleEmailToBeSentOnDayBeforeBillingDateEndsHook', array($userEmail, $subject, emailTemplate($messageB), $headers));
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
					$userName = "$user->first_name $user->last_name";
					$userEmail = $user->user_email;
					
					$currentDate = new DateTime($subscription->get_date_to_display( 'start' )); 
					$currentDate->add(new DateInterval('P1' . strtoupper($subscription->billing_period[0])));
					$billingDate = strtotime($currentDate->format('F j, Y'));
					$billingCycle = $currentDate->format('F j, Y');
					$tomorrowDate = date('F j, Y', strtotime('+1 days'));	
					$oneDayBeforeBillingPeriodEnds = strtotime('-1 day', $billingDate);
					
					$firstSentence = time() == $billingDate ? "Your account has now been Cancelled." : "Your account is set to be Cancelled.";	
					$subject = "Your account is set to Cancel";

					$messageA = "
					<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>$firstSentence. Your team will still be available to work with you until the end of your current billing cycle ($billingCycle).</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>After that date, you'll lose access to your tickets, communication, and designs.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you believe this request was a mistake, please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> before the account is cancelled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks for trusting us with your design work during this time.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

						$messageB = "
					<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Just a reminder that your Deer Designer account is scheduled to be cancelled tomorrow: $tomorrowDate.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you believe this request was a mistake, please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> before the account is cancelled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>I hope to see you again soon!.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

					if($billingDate == $tomorrowDate){
						wp_mail($userEmail, $subject, emailTemplate($messageB), $headers);
					}else if($billingDate == time()){
						wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);
					}else{
						wp_mail($userEmail, $subject, emailTemplate($messageA), $headers);
						wp_schedule_single_event($oneDayBeforeBillingPeriodEnds, 'scheduleEmailToBeSentOnDayBeforeBillingDateEndsHook', array($userEmail, $subject, emailTemplate($messageB), $headers));
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
					$userName = "$user->first_name $user->last_name";
					$userEmail = $user->user_email;
					
					$currentDate = new DateTime($subscription->get_date_to_display( 'start' )); 
					$currentDate->add(new DateInterval('P1' . strtoupper($subscription->billing_period[0])));
					$billingDate = strtotime($currentDate->format('F j, Y'));
					$billingCycle = $currentDate->format('F j, Y');
					
					$subject = "Your additional task is set to Cancel";

					$messageA = "
					<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your additional task is set to be canceled and it'll still be available until the end of its current billing cycle ($billingCycle).</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you believe this request was a mistake, please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> before the additional task is cancelled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

					$messageB = "
					<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your additional task has now been canceled.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If you believe this request was a mistake, please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a>.</p>

					<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
					The Deer Designer Team.</p>
					";

					if(time() == $billingDate){
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
				global $headers;
				$user = wp_get_current_user();
				$userName = "$user->first_name $user->last_name";
				$userEmail = $user->user_email;
				$productName = $subItem['name'];
				
				$subject = str_contains(strtolower($productName), 'task') ? "Your active task has been reactivated" : "Your account has been reactivated";

				$message = "
				<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userName</h2>

				<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your $productName has been reactivated!.</p>

				<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>If your previous designer is still free, we'll assign them to you. Otherwise, the team will select a designer who will read your profile, preferences, and past tickets, and they will be ready to start working on your requests as soon as possible.</p>

				<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>This process takes up to one business day, so feel free to log in and send a request! </p>

				<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Please reach out to help@deerdesigner.com if you need any additional help.</p>

				<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br>
				The Deer Designer Team.</p>
				";

				wp_mail($userEmail, $subject, emailTemplate($message), $headers);

			}
		}
	}

}
add_action('woocommerce_subscription_status_updated', 'sendEmailToUserWhenReactivateSubscription', 10, 3);



function sendEmailToAdminWhenReactivateSubscription($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to']) || isset($_GET['reactivate_plan'])){
		if($oldStatus !== 'pending' && $newStatus == 'active'){
			foreach($subscription->get_items() as $subItem){
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
add_action('woocommerce_subscription_status_updated', 'sendEmailToAdminWhenReactivateSubscription', 10, 3);



function customRetryPaymentRules( $default_retry_rules_array ) {
    return array(
            array(
                'retry_after_interval'            => 86400 /*24 HOURS*/,
                'email_template_customer'         => 'WCS_Email_Customer_Payment_Retry',
                'email_template_admin'            => 'WCS_Email_Payment_Retry',
                'status_to_apply_to_order'        => 'pending',
                'status_to_apply_to_subscription' => 'active',
            ),
            array(
                'retry_after_interval'            => 86400 /*24 HOURS*/,
                'email_template_customer'         => 'WCS_Email_Customer_Payment_Retry',
                'email_template_admin'            => 'WCS_Email_Payment_Retry',
                'status_to_apply_to_order'        => 'pending',
                'status_to_apply_to_subscription' => 'active',
            )
        );
}
add_filter( 'wcs_default_retry_rules', 'customRetryPaymentRules' );



function scheduleEmailToBeSentOnDayBeforeBillingDateEnds($userEmail, $subject, $body, $headers){
	wp_mail($userEmail, $subject, $body, $headers);
}
add_action('scheduleEmailToBeSentOnDayBeforeBillingDateEndsHook', 'scheduleEmailToBeSentOnDayBeforeBillingDateEnds');





?>