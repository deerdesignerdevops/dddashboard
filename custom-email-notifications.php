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
																	<div id="body_content_inner">' . $content . '</div>
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
				<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>New info:</h2>
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
add_action( 'profile_update', 'sendEmailToAdminAfterUserProfileUpdated', 10, 3);


function userUpdatedPaymentMethods(){
	global $headers;
	$user = wp_get_current_user();
	$userFirstName = $user->first_name;
	$userLastName = $user->last_name;
	$userEmail = $user->user_email;

	$subject = "Payment method updated";

	$message = "
<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userFirstName $userLastName</h2>

<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your payment method was updated. If you believe this request was a mistake, please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a>.</p>

<p>Thanks,<br.
The Deer Designer Team.</p>
";

	$body = emailTemplate($message);
	wp_mail($userEmail, $subject, $body, $headers);
}
add_action('after_woocommerce_add_payment_method', 'userUpdatedPaymentMethods');





function sendEmailToUserWhenPausedPlan($subscription){
	global $headers;
	$user = wp_get_current_user();
	$userFirstName = $user->first_name;
	$userLastName = $user->last_name;
	$userEmail = $user->user_email;

	$subject = "Your account is set to 'Pause'";

	$message = "
<h2 style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi, $userFirstName $userLastName</h2>

<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Your account has been put on Pause. Your team will still be available to work with you until the end of your current billing cycle (Day Month, Year).</p>

<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>To reactivate the account, just click on 'Reactivate' next to your plan and we will take care of it for you.</p>

<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Please get in touch with <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> if anything changes.</p>

<p>Thanks,<br>
The Deer Designer Team.</p>
";

	$body = emailTemplate($message);
	wp_mail($userEmail, $subject, $body, $headers);

}
add_action('woocommerce_subscription_status_on-hold', 'sendEmailToUserWhenPausedPlan', 10, 3);

?>