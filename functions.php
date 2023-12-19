<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	// Dynamically get version number of the parent stylesheet (lets browsers re-cache your stylesheet when you update your theme)
	$theme   = wp_get_theme( 'HelloElementorChild' );
	$version = rand(0, 999);

	// CSS
	wp_enqueue_style( "dd-custom-style-$version", get_stylesheet_directory_uri() . '/style.css', array( 'hello-elementor-theme-style' ), $version );
	wp_enqueue_style( "slick-$version", get_stylesheet_directory_uri() . '/libs/slick/css/slick.css', $version );
	wp_enqueue_style( "slick-theme-$version", get_stylesheet_directory_uri() . '/libs/slick/css/slick-theme.css', $version );

	//JS
	wp_enqueue_script("custom-jquery-$version", get_stylesheet_directory_uri() . '/libs/jquery/jquery.js', $version);
	wp_enqueue_script("slick-$version", get_stylesheet_directory_uri() . '/libs/slick/js/slick.min.js', $version);
	wp_enqueue_script("dd-custom-scripts-$version", get_stylesheet_directory_uri() . '/dd-custom-scripts.js', $version);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );


// function removeScriptVersionNumberFromQuery($src)
// {
//     $parts = explode('?ver', $src);
//     return $parts[0];
// }
// add_filter('script_loader_src', 'removeScriptVersionNumberFromQuery', 15, 1);
// add_filter('style_loader_src', 'removeScriptVersionNumberFromQuery', 15, 1);


require_once('stripe/init.php');
require_once('custom-email-notifications.php');



function logoutWhitoutConfirm($action, $result)
{
    if ($action == "log-out" && !isset($_GET['_wpnonce'])) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : home_url();
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));
        header("Location: $location");
        die;
    }
}
add_action('check_admin_referer', 'logoutWhitoutConfirm', 10, 2);





//STRIPE ENDPOINT FOR WEBHOOKS
function stripeInvoiceGenerationWebhook($req){
	$invoiceId = $req['data']['object']['id'];
	$response_data_arr = file_get_contents('php://input');	
	file_put_contents("wp-content/uploads/stripe_webhooks_logs/stripe_response_".date('Y_m_d')."_".$invoiceId.".log", $response_data_arr);
}

add_action( 'rest_api_init', function () {
  register_rest_route( '/stripe/v1','invoicegenerated', array(
    'methods' => 'POST',
    'callback' => 'stripeInvoiceGenerationWebhook',
  ) );
} );



function sendStripeNotificationPaymentUpdatedToSlack($customerName, $customerEmail, $customerPlan){
	$slackMessageBody = [
		'text'  => 'We have a new subscription, <!channel> :smiling_face_with_3_hearts:
*Client:* ' . $customerName . ' ' . $customerEmail . '
*Plan:* ' . $customerPlan . '
Let\'s wait for the onboarding rocket :muscle::skin-tone-2:',
		'username' => 'Marcus',
	];

	slackNotifications($slackMessageBody);
}


function sendWelcomeEmailAfterStripePayment($customerName, $customerEmail, $customerUrl){
	$body = "<p style='font-family: Helvetica, Arial, sans-serif; font-size: 15px;line-height: 1.5em;font-weight: bold;'>Let's get you on board!</p>
<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi there, Thanks for signing up! 😍</p>
<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>To confirm your email and start onboarding, please click the button below:</p>
<br>
<a rel='noopener' target='_blank' href='$customerUrl' style='background-color: #43b5a0; font-size: 15px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; text-decoration: none; padding: 10px 20px; color: #ffffff; border-radius: 50px; display: inline-block; mso-padding-alt: 0;'>
    <!--[if mso]>
    <i style='letter-spacing: 25px; mso-font-width: -100%; mso-text-raise: 30pt;'>&nbsp;</i>
    <![endif]-->
    <span style='mso-text-raise: 15pt;'>Fill out onboarding form</span>
    <!--[if mso]>S
    <i style='letter-spacing: 25px; mso-font-width: -100%;'>&nbsp;</i>
    <![endif]-->
</a>
<br><br>
<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>For your first access use these credentials below:<br>
username: $customerEmail <br>
password: change_123
</p>
<br><br>
<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>As soon as you complete the onboarding form, we'll create your profile and match you with a designer (up to 1 business day). Feel free to log in and send your first request.</p>
<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Thanks,<br> Deer Designer Team</p>
    <a href='https://deerdesigner.com'><img src='https://deerdesigner.com/wp-content/uploads/logo-horizontal.png' style='width:150px' alt=''></a>";

	
	$subject = "Start your onboarding process now!";

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: Wanessa <help@deerdesigner.com>',
    );

	wp_mail($customerEmail, $subject, $body, $headers);
}



function createUserAfterStripePurchase($req){
	$stripe = new \Stripe\StripeClient(STRIPE_API);
	$customer = $stripe->customers->retrieve($req['data']['object']['customer'],[]);
	$invoiceId = $req['data']['object']['id'];
	$customerName = $customer->name;
	$customerEmail = $customer->email;
	$customerPlan = $req['data']['object']['items']['data'][0]['plan']['name'];
	$customerCity = $customer->address->city;
	$customerCountry = $customer->address->country;
	
	$response_data_arr = file_get_contents('php://input');
	
	file_put_contents("wp-content/uploads/stripe_webhooks_logs/stripe_response_".date('Y_m_d')."_".$invoiceId.".log", $response_data_arr);

	$customerUrl = "https://dash.deerdesigner.com/sign-up/onboarding/?first_name=$customerName&last_name=&email=$customerEmail&city=$customerCity&country=$customerCountry&plan=$customerPlan";

	if(empty(get_user_by('email', $customerEmail))){
		$newUserId = wp_create_user($customerEmail, 'change_123', $customerEmail);
		add_user_meta( $newUserId, 'stripe_customer_plan', $customerPlan );
		add_user_meta( $newUserId, 'stripe_customer_city', $customerCity );
		add_user_meta( $newUserId, 'stripe_customer_country', $customerCountry );
		sendWelcomeEmailAfterStripePayment($customerName, $customerEmail, $customerUrl);
		do_action('emailReminderHook', $customerEmail, $customerUrl);

		if(str_contains($customerPlan, 'Agency')){
			add_user_meta( $newUserId, 'creative_calls', 4 );
		}
	}else{
		if(str_contains($customerPlan, 'Agency')){
			$user = get_user_by('email', $customerEmail);
			$userCreativeCallsLeft =  get_user_meta($user->id, 'creative_calls', true);
			update_user_meta( $user->id, 'creative_calls', $userCreativeCallsLeft + 4 );
		}
	}

	sendStripeNotificationPaymentUpdatedToSlack($customerName, $customerEmail, $customerPlan);
	echo "Customer Name: $customerName, Customer Email: $customerEmail, Customer City: $customerCity, Customer Country: $customerCountry, Plan: $customerPlan";
}



add_action( 'rest_api_init', function () {
  register_rest_route( '/stripe/v1','paymentcheck', array(
    'methods' => 'POST',
    'callback' => 'createUserAfterStripePurchase',
  ) );
} );



function populateOnboardingFormHiddenFieldsWithUserMeta($form){
	$userPlan = get_user_meta(get_current_user_id(), 'stripe_customer_plan', true);
	$userCity = get_user_meta(get_current_user_id(), 'stripe_customer_city', true);
	$userCountry = get_user_meta(get_current_user_id(), 'stripe_customer_country', true);
	$currentUser = wp_get_current_user();
	$companyName = get_user_meta($currentUser->id, 'billing_company', true);

	if($form->id == 3){
		echo "<script>
			document.addEventListener('DOMContentLoaded', function(){
				document.querySelector('[data-name=\"plan\"]').value='$userPlan'
				document.querySelector('[data-name=\"city\"]').value='$userCity'
				document.querySelector('[data-name=\"country\"]').value='$userCountry'
				document.querySelector('[data-name=\"company_name\"]').value='$companyName'
			})
		</script>";
	}
}
add_action('fluentform/after_form_render', 'populateOnboardingFormHiddenFieldsWithUserMeta');



function hideAdminBarForNonAdminUser(){
	if(is_user_logged_in()){
		$currentUser = wp_get_current_user();
		$userRole = $currentUser->roles[0];

		if($userRole !== 'administrator' && $userRole !== 'editor' ){
			echo '<style>
			#wpadminbar{display: none !important;}</style>'	;		
		}	
	}
}
add_action('wp_head', 'hideAdminBarForNonAdminUser');


function redirectNonAdminUsersToHomepage(){
	if ( is_admin() && !current_user_can( 'administrator' ) && !current_user_can( 'editor' )) {
		wp_redirect( home_url() );
		exit;
	}
}
add_action('admin_head', 'redirectNonAdminUsersToHomepage');




function checkIfCurrentUserIsOnboarded(){
	$user = wp_get_current_user();
	$isUserOnboarded =  get_user_meta($user->ID, 'is_user_onboarded', true);

	if(!current_user_can('administrator')){
		if(is_page(array('dash', 'dash-woo'))){
			if(!$isUserOnboarded){
				$url = home_url() . "/sign-up/onboarding";
				wp_redirect($url);
				exit();
			}
		}
	}
	
}
add_action('template_redirect', 'checkIfCurrentUserIsOnboarded');



function checkIfUserASweredPlanPricingForm(){
	$user = wp_get_current_user();

	if(!current_user_can('administrator')){
		if(is_page(array('dash', 'dash-woo'))){
			require_once(WP_PLUGIN_DIR  . '/fluentform/app/Api/FormProperties.php');
			$formApi = fluentFormApi('forms')->entryInstance($formId = 4);
			$atts = [
				'search' => $user->user_email,
			];
			$entries = $formApi->entries($atts , $includeFormats = false);

			if($entries["total"]){
				echo "<style>.plans_pricing_popup{display: none !important;}</style>";
			}
			
		}else if(is_page('plans-and-pricing-form')){
			require_once(WP_PLUGIN_DIR  . '/fluentform/app/Api/FormProperties.php');
			$formApi = fluentFormApi('forms')->entryInstance($formId = 4);
			$atts = [
				'search' => $user->user_email,
			];
			$entries = $formApi->entries($atts , $includeFormats = false);		

			if($entries["total"] && !$isUserOnboarded){
				$url = home_url() . "/thanks-form-submited";
				wp_redirect($url);
				exit();
			}
		}
	}
}
add_action('template_redirect', 'checkIfUserASweredPlanPricingForm');



function sendStripePaymentFailedNotificationToSlack($req){
	$stripe = new \Stripe\StripeClient(STRIPE_API);
	$customer = $stripe->customers->retrieve($req['data']['object']['customer'],[]);
	$customerName = $customer->name;
	$customerEmail = $customer->email;
		
	$slackMessageBody = [
		'text'  => '<!channel> Payment failed :x:
' . $customerName . ' - ' . $customerEmail . '
:arrow_right: AMs, work on their requests but don\'t send them until payment is resolved.',
		'username' => 'Marcus',
	];

	slackNotifications($slackMessageBody);

	echo "Payment failed for: $customerName - $customerEmail";
}



add_action( 'rest_api_init', function () {
  register_rest_route( '/stripe/v1','paymentfailed', array(
    'methods' => 'POST',
    'callback' => 'sendStripePaymentFailedNotificationToSlack',
  ) );
} );



function displayCompanyFieldOnAdminPanel($contactmethods){
	$newFieldsArray = array(
	'company_name'   => __('Company Name'),
	);

	$contactmethods = $newFieldsArray + $contactmethods;

	return $contactmethods;
}
add_filter('user_contactmethods', 'displayCompanyFieldOnAdminPanel');



function displayAdditionalUserDataOnAdminPanel( $user ) { 
    $userCreativeCallsLeft = get_the_author_meta('creative_calls',$user->ID,true ); 
	$isUserOnboarded = get_the_author_meta('is_user_onboarded',$user->ID,true );

?>
<h2>Additional Data</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th>Creative Calls Left:</th>
                <td>
                    <p><label>
						<input type="number" min="0" name="creative_calls" value="<?php echo $userCreativeCallsLeft; ?>">
                    </label></p>
                </td>
            </tr>

			<tr>
                <th>Is User Onboarded:</th>
                <td>
                    <p><label>
                        <input type="checkbox" <?php echo $isUserOnboarded ? "checked" : ""; ?> name="is_user_onboarded" value="1">
                    </label></p>
                </td>
            </tr>
        </tbody>
    </table>
<?php } 
add_action( 'show_user_profile', 'displayAdditionalUserDataOnAdminPanel' );
add_action( 'edit_user_profile', 'displayAdditionalUserDataOnAdminPanel' );



function updateAditionalUserDataOnAdminPanel($user_id){
	update_user_meta( $user_id, 'creative_calls', $_POST['creative_calls'] );
	update_user_meta( $user_id, 'is_user_onboarded', $_POST['is_user_onboarded'] );
}
add_action( 'personal_options_update', 'updateAditionalUserDataOnAdminPanel' );
add_action( 'edit_user_profile_update', 'updateAditionalUserDataOnAdminPanel' );



function addFirstAccessUserMetaToNewUsers($user_id) { 
   add_user_meta( $user_id, 'is_first_access', 1 );
   add_user_meta( $user_id, 'is_user_onboarded', 0 );
}
add_action( 'user_register', 'addFirstAccessUserMetaToNewUsers');



function updateIsUserOnboardedAfterOnboardingForm($entryId, $formData, $form){
	if($form->id === 3){
		update_user_meta( get_current_user_id(), 'is_user_onboarded', 1 );
	}
}
add_action( 'fluentform/submission_inserted', 'updateIsUserOnboardedAfterOnboardingForm', 10 ,3);



function sendUserOnboardedNotificationToSlack($entryId, $formData, $form){
	if($form->id === 3){
		$customerName = $formData['names']['first_name'] . " " . $formData['names']['last_name'];
		$customerCompany = $formData['company_name'];
		$customerCity = $formData['city'];
		$customerCountry = $formData['country'];

		$slackMessageBody = [
			'text'  => '<!channel> :rocket:Onboarded: ' . $customerName . ' ( ' . $customerCompany . ' ) from ' . $customerCity . ', ' . $customerCountry,
			'username' => 'Marcus',
		];

		slackNotifications($slackMessageBody);
	}
}
add_action( 'fluentform/submission_inserted', 'sendUserOnboardedNotificationToSlack', 10, 3);



function subscribeUserToMoosendEmailList($entryId, $formData, $form){
	if($form->id === 3){
		$user_name = $formData['names']['first_name'] . " " . $formData['names']['last_name'];
		$user_email = $formData['email'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, MOOSEND_API_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"Name\" : \"$user_name\",\n    \"Email\" : \"$user_email\",\n    \"HasExternalDoubleOptIn\": false}");

		curl_exec($ch);

		curl_close($ch);
	}
}
add_action( 'fluentform/submission_inserted', 'subscribeUserToMoosendEmailList', 10, 3);



function googleTagManagerOnHead(){
	echo "
	<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-W9B92LV');</script>
<!-- End Google Tag Manager -->
";
}
add_action("wp_head", "googleTagManagerOnHead");



function googleTagManagerOnBody(){
	echo '
	<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W9B92LV"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
';
}
add_action('wp_body_open', 'googleTagManagerOnBody');



function removePageTitleFromAllPages($return){
	return false;
}
add_filter('hello_elementor_page_title', 'removePageTitleFromAllPages');



function checkIfUserCanBookCreativeCall(){
	$userCreativeCallsLeft =  get_user_meta(get_current_user_id(), 'creative_calls', true);

	if($userCreativeCallsLeft){
		echo "<style>.book_call_btn{display: flex !important;}</style>";
	}else{
		echo "<style>.book_call_btn{display: none !important;}</style>";
	}

}
add_action('template_redirect', 'checkIfUserCanBookCreativeCall');



function checkIfGroupCanBookCreativeCall(){
	if(is_page(array('dash', 'dash-woo'))){
		$userSubscriptions = wcs_get_users_subscriptions(get_current_user_id());
		$userCurrentProducts = [];
		$groups_user = new Groups_User( get_current_user_id() );	
		$groupCreativeCallsLeft =  0;

		foreach($groups_user->groups as $item){
			$groupCreativeCallsLeft += $item->group->creative_calls;
		}

		foreach ($userSubscriptions as $subscription){
			if ($subscription->has_status(array('active'))) {
				$subscription_products = $subscription->get_items();

				foreach($subscription_products as $product){
					array_push($userCurrentProducts, $product['name']);
				}	
			}
		}

		if(in_array('Creative Director', $userCurrentProducts)){
			echo "<style>.group_book_call_btn{display: flex !important;}</style>";
			echo "<script>
			document.addEventListener('DOMContentLoaded', function(){
				const creativeCallsNumber = document.querySelector('.creative_calls_number span')
				creativeCallsNumber.innerText = '∞';
			})
			</script>";

		}else if($groupCreativeCallsLeft){
			echo "<style>.group_book_call_btn{display: flex !important;}</style>";
			echo "<script>
			document.addEventListener('DOMContentLoaded', function(){
				const creativeCallsNumber = document.querySelector('.creative_calls_number span')
				creativeCallsNumber.innerText = $groupCreativeCallsLeft;
			})
			</script>";
		}
		else{
			echo "<style>.group_book_call_btn{display: none !important;}</style>";
		}
	}

}
add_action('template_redirect', 'checkIfGroupCanBookCreativeCall');



//***************CUSTOM CODES FOR WOOCOMMERCE
function slackNotifications($slackMessageBody){
	wp_remote_post( SLACK_WEBHOOK_URL, array(
		'body'        => wp_json_encode( $slackMessageBody ),
		'headers' => array(
			'Content-type: application/json'
		),
	) );
}

function changeActionsButtonsLabel( $actions, $subscription ){
    if( isset( $actions['suspend'] ) ){
        $actions['suspend']['name'] = __( 'Pause', 'woocommerce-subscriptions' );
    }
    return $actions;
}
add_filter( 'wcs_view_subscription_actions', 'changeActionsButtonsLabel', 10, 2 );



function redirectUserAfterSubscriptionStatusUpdated(){
	$url = site_url() . "/subscriptions";

	if(is_user_logged_in() && is_wc_endpoint_url('view-subscription')){
		wp_safe_redirect($url);
		exit;
	}
	else if(is_user_logged_in() && is_wc_endpoint_url('payment-methods')){
		wp_safe_redirect(site_url() . '/edit-account');
		exit;
	}
}
add_action('template_redirect', 'redirectUserAfterSubscriptionStatusUpdated');



function sendPaymentCompleteNotificationToSlack($orderId){
	if(!wcs_order_contains_renewal($orderId)){
		$order = wc_get_order( $orderId );
		$orderData = $order->get_data();
		$orderItems = $order->get_items();
		$orderItemsGroup = [];
		$productType = "";
		$notificationFinalMsg = "";

		foreach( $orderItems as $item_id => $item ){
			$itemName = $item->get_name();
			$orderItemsGroup[] = $itemName;

			if(has_term('active-task', 'product_cat', $item->get_product_id())){
				$productType = 'Product';
			}else if(has_term('add-on', 'product_cat', $item->get_product_id())){
				$productType = 'Add on';
			}else{
				$productType = 'Plan';
				$notificationFinalMsg = 'Let\'s wait for the onboarding rocket :muscle::skin-tone-2:';
			}
		}

		$customerName = $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'];
		$customerEmail = $orderData['billing']['email'];
		$slackMessageBody = [
			'text'  => 'We have a new subscription, <!channel> :smiling_face_with_3_hearts:
	*Client:* ' . $customerName . ' ' . $customerEmail . '
	*' . $productType . ':* ' . implode(" | ", $orderItemsGroup) . '
	' . $notificationFinalMsg . '',
			'username' => 'Marcus',
		];

		slackNotifications($slackMessageBody);

	}
}
add_action( 'woocommerce_payment_complete', 'sendPaymentCompleteNotificationToSlack');



function sendRenewalCompleteNotificationToSlack($subscription){
	$subscriptionProducts = $subscription->get_items();
	
	foreach ($subscriptionProducts as $product) {					
		$userProductsNames[] = $product['name'];
	}

	$customerName = $subscription->data['billing']['first_name'] . " " . $subscription->data['billing']['last_name'];
	$customerEmail = $subscription->data['billing']['email'];
	
	$slackMessageBody = [
			'text'  => 'We have a subscription renewal, <!channel> :smiling_face_with_3_hearts:
	*Client:* ' . $customerName . ' ' . $customerEmail . '
	*Plan:* ' . implode(" | ", $userProductsNames),
			'username' => 'Marcus',
		];

	slackNotifications($slackMessageBody);

}
add_action( 'woocommerce_subscription_renewal_payment_complete', 'sendRenewalCompleteNotificationToSlack');



function sendUserOnboardedNotificationFromWooToSlack($entryId, $formData, $form){
	if($form->id === 3){
		$userName = $formData['names']['first_name'] . " " . $formData['names']['last_name'];
		$currentUser = wp_get_current_user(get_current_user_id());
		$companyName = $formData['company_name'];
		$userCity = $currentUser->billing_city;
		$userCountry = $currentUser->billing_country;

		$slackMessageBody = [
			'text'  => '<!channel> :rocket:Onboarded: ' . $userName . ' (' . $companyName . ') ' . 'from ' . $userCity . ', ' . $userCountry,
			'username' => 'Marcus',
		];

		slackNotifications($slackMessageBody);
	}
}
add_action( 'fluentform/submission_inserted', 'sendUserOnboardedNotificationFromWooToSlack', 10, 3);



function checkIfUserIsActive(){
	$user_id = get_current_user_id();
	$userSubscriptions = wcs_get_users_subscriptions($user_id);

	$product_id = "";
	$productsCategories = ['plan'];

	foreach ($userSubscriptions as $subscription){
		if ($subscription->has_status(array('active'))) {
			$subscription_products = $subscription->get_items();
			foreach ($subscription_products as $product) {			
                $product_id = $product->get_product_id();
				$terms = get_the_terms( $product_id, 'product_cat' );
				$productCategory = $terms[0]->slug;
				$productsCategories[] = $productCategory;
            }

		}
	}

	if(!in_array("plan", $productsCategories)){
		echo "<style>
			.paused__user_btn{display: none !important}
			.dd__dashboard_navbar_item{width: 25% !important}
		</style>";
	}else{
		echo "<style>
			.paused__user_banner{display: none !important}
			.dd__dashboard_navbar_item{width: 33% !important}
		</style>";
	}

}
add_action('template_redirect', 'checkIfUserIsActive');



function sendWooMetadataToStripePaymentMetadata($metadata, $order) {
	$order_data = $order->get_data();
	
	$metadata += ['first_name' => $order_data['billing']['first_name']];
	$metadata += ['last_name' => $order_data['billing']['last_name']];
	$metadata += ['billing_company' => $order_data['billing']['company']];
	//$metadata += ['billing_phone' => $order_data['billing']['phone']];
	$metadata += ['billing_address_1' => $order_data['billing']['address_1']];
	//$metadata += ['billing_address_2' => $order_data['billing']['address_2']];
	//$metadata += ['billing_city' => $order_data['billing']['city']];
	//$metadata += ['billing_state' => $order_data['billing']['state']];
	$metadata += ['billing_country' => $order_data['billing']['country']];
	//$metadata += ['billing_postcode' => $order_data['billing']['postcode']];

    return $metadata;
}
add_filter('wc_stripe_payment_metadata', 'sendWooMetadataToStripePaymentMetadata', 10, 2);



function sendWooMetadataToStripeCustomerMetadata($metadata) {
	$orderId = WC()->session->get('order_awaiting_payment');
	$order = new WC_Order($orderId);

    $metadata['first_name'] = $order->get_billing_first_name();
	$metadata['last_name'] = $order->get_billing_last_name();
	$metadata['billing_company'] = $order->get_billing_company();
	//$metadata['billing_phone'] = $order->get_billing_phone();
	$metadata['billing_address_1'] = $order->get_billing_address_1();
	//$metadata['billing_address_2'] = $order->get_billing_address_2();
	//$metadata['billing_city'] = $order->get_billing_city();
	//$metadata['billing_state'] = $order->get_billing_state();
	$metadata['billing_country'] = $order->get_billing_country();
	//$metadata['billing_postcode'] = $order->get_billing_postcode();

    return $metadata;
};
add_filter('wc_stripe_customer_metadata', 'sendWooMetadataToStripeCustomerMetadata', 10, 1);



function removeCheckoutFields( $fields ) {
	//unset( $fields['billing']['billing_company'] );
	$fields['billing']['billing_company']['required'] = true;
	unset( $fields['billing']['billing_phone'] );
	//unset( $fields['billing']['billing_state'] );
	unset( $fields['billing']['billing_address_2'] );
	//unset( $fields['billing']['billing_city'] );
	//unset( $fields['billing']['billing_postcode'] );
	unset( $fields['order']['order_comments'] );
	// unset( $fields['billing']['billing_email'] );
	// unset( $fields['billing']['billing_first_name'] );
	// unset( $fields['billing']['billing_last_name'] );
	// unset( $fields['billing']['billing_address_1'] );
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'removeCheckoutFields' );



function redirectToOnboardingFormAfterCheckout( $orderId ) {
	$user = wp_get_current_user();
	$isUserOnboarded =  get_user_meta($user->id, 'is_user_onboarded', true);
    $url = site_url() . '/sign-up/onboarding';
	$order = wc_get_order( $orderId );
	$confirmationAlertMsg = "";
	
	foreach( $order->get_items() as $item_id => $item ){
		$itemName = $item->get_name();
		$orderItems[] = $itemName;
	}

	$productNames = implode(" | ", array_unique($orderItems));

	wc_add_notice("Your $productNames was added to your account! <p>$confirmationAlertMsg</p>", 'success');

	if($isUserOnboarded || current_user_can('administrator')){
		$url = site_url() . "/subscriptions";
		wp_redirect( $url );
        exit;  
	}else{
		do_action('emailReminderHook', $user->user_email, $url);
		wp_redirect( $url );
        exit;  
	} 
}
add_action( 'woocommerce_thankyou', 'redirectToOnboardingFormAfterCheckout', 10, 1 );



function moveCheckoutEmailFieldToTop( $address_fields ) {
    $address_fields['billing_email']['priority'] = 20;
    return $address_fields;
}
add_filter( 'woocommerce_billing_fields', 'moveCheckoutEmailFieldToTop' );



function changeOrderStatusToCompleteAfterPayment( $orderId ) {
    $order = wc_get_order( $orderId );
    $order->update_status( 'completed' ); 
	
	
}
add_action( 'woocommerce_payment_complete', 'changeOrderStatusToCompleteAfterPayment' );



function limitProductQuantityToOne($cart_item_data, $product_id) {
	
    $cart = WC()->cart->get_cart();

    if ($cart) {
        foreach ($cart as $cart_item_key => $values) {
            if ($values['data']->get_id() == $product_id) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
    }

    return $cart_item_data;
}
add_filter('woocommerce_add_to_cart_validation', 'limitProductQuantityToOne', 10, 2);



function preventUserHaveMultiplePlansAtTheSameTime() {	
	if(is_page(array( 'cart', 'sign-up' ))){
		if(is_user_logged_in()){
			$userSubscriptions = wcs_get_users_subscriptions(get_current_user_id());
			$isCurrentUserHaveSubscriptionPlan = false;

			if($userSubscriptions){
				foreach($userSubscriptions as $subscription){
					foreach($subscription->get_items() as $subItem){
						$terms = get_the_terms( $subItem['product_id'], 'product_cat' );

						if($terms[0]->slug === "plan"){
							$isCurrentUserHaveSubscriptionPlan = true;
						}
					}
				}

				$cart = WC()->cart->get_cart();
				if($cart){
					foreach ($cart as $cart_item_key => $values) {					
						$terms = get_the_terms( $values['data']->id, 'product_cat' );
						
						if($terms[0]->slug === 'plan'){
							if($isCurrentUserHaveSubscriptionPlan){
								WC()->cart->remove_cart_item( $cart_item_key );
								wc_add_notice('You can\'t purchase this item! Please, use the Change Plan Button in your dashboard!', 'success', array('notice-type' => 'error'));
								wp_redirect(site_url() . '/subscriptions');
								exit;
							}
						}
					}
				}
			}
		}
	}	
}
add_action('template_redirect', 'preventUserHaveMultiplePlansAtTheSameTime');




function changeActiveTaskPriceInCartBasedOnUserPlan() {;
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
    return;

	$cart = WC()->cart->get_cart();

	if(is_user_logged_in()){
		$userSubscriptions = wcs_get_users_subscriptions(get_current_user_id());
		if($userSubscriptions){
			foreach($userSubscriptions as $subscription){
				foreach($subscription->get_items() as $subItem){
					$terms = get_the_terms( $subItem['product_id'], 'product_cat' );

					if($terms[0]->slug === "plan"){
						$currentUserSubscriptionPlan = $subItem['name'];
					}
				}
			}
		}
	
	}

	$activeTaskDiscount =  str_contains($currentUserSubscriptionPlan, 'Standard' ) ? 0 : 50;

	if($cart){
		foreach ( $cart as $cart_item_key => $values) {
			$terms = get_the_terms( $values['data']->id, 'product_cat' );
			$productPrice = $values['data']->get_price();
			
			if($terms[0]->slug === 'active-task'){
				$values['data']->set_price($productPrice - $activeTaskDiscount);

			}
			

		}
	}
}

add_action('woocommerce_before_calculate_totals', 'changeActiveTaskPriceInCartBasedOnUserPlan');



function customCheckoutCouponForm() {
    echo '<tr class="coupon-form"><td colspan="2">';
    
    wc_get_template(
        'checkout/form-coupon.php',
        array(
            'checkout' => WC()->checkout(),
        )
    );
    echo '</tr></td>';
}
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
add_action( 'woocommerce_review_order_after_cart_contents', 'customCheckoutCouponForm' );



function sendPaymentFailedNotificationToSlack($orderId){
	$order = wc_get_order( $orderId );
	$orderData = $order->get_data();
	$customerName = $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'];
	$customerEmail = $orderData['billing']['email'];
	$slackMessageBody = [
		'text'  => '<!channel> Payment failed :x:
' . $customerName . ' - ' . $customerEmail . '
:arrow_right: AMs, work on their requests but don\'t send them until payment is resolved.',
		'username' => 'Marcus',
	];

	slackNotifications($slackMessageBody);
}
add_action( 'woocommerce_order_status_failed', 'sendPaymentFailedNotificationToSlack');



function showBracketsAroundVariationName($name, $product) {
    if (str_contains($name, '-') !== false) {
        $modifiedNameLast = substr($name, strrpos($name, '-') + 1);
        $modifiedNameFirst = substr($name, 0, strrpos($name, '-'));
        $name = $modifiedNameFirst . '(' . trim($modifiedNameLast) . ')';
    }

    return $name;
}
add_filter('woocommerce_product_variation_get_name', 'showBracketsAroundVariationName', 10, 2);



function notificationToSlackWithSubscriptionUpdateStatus($subscription, $new_status, $old_status){
	if($old_status !== 'pending' && $new_status !== 'cancelled'){
		$subscriptionItems = $subscription->get_items();
		$customerName = $subscription->data['billing']['first_name'] . " " . $subscription->data['billing']['last_name'];
		$customerEmail = $subscription->data['billing']['email'];
		$subscriptionItemsGroup = [];
		
		switch ($new_status){
			case 'on-hold':
				$newStatusLabel = 'paused';
				$messageTitle = 'Subscription Paused :double_vertical_bar:';
				break;

			case 'pending-cancel':
				$newStatusLabel = 'pending-cancellation';
				$messageTitle = 'Subscription Cancelled :alert:';
				break;

			default:
				$newStatusLabel = $new_status;
				$messageTitle = 'Subscription Reactivated :white_check_mark:';
		}


		foreach($subscriptionItems as $item){
			$subscriptionItemsGroup[] = $item['name'];
		}

		if($new_status === 'active'){
			wc_add_notice('Your ' . implode(" | ", array_unique($subscriptionItemsGroup)) . ' has been reactivated.', 'success');
		}

		$slackMessageBody = [
				'text'  => '<!channel> ' . $messageTitle . '
		*Client:* ' . $customerName . ' | ' . $customerEmail . '
		*Plan:* ' . implode(" | ", array_unique($subscriptionItemsGroup)) . '
		:arrow_right: Client has changed his subscription to -> ' . "*$newStatusLabel*",
				'username' => 'Marcus',
			];


		slackNotifications($slackMessageBody);
	}
}
add_action('woocommerce_subscription_status_updated', 'notificationToSlackWithSubscriptionUpdateStatus', 10, 3);



function customSubscriptionNoticeText($message){

	if (str_contains($message, 'Your subscription has been cancelled.') || str_contains($message, 'Your subscription has been reactivated.')) {
		unset($message);
    }else if(str_contains($message, 'hold')){
		$message = 'Your account has been succesfully paused. Your Deer Designer team is still available until the end of your current billing period.';
	}else if(str_contains($message, 'switch')){
		$message = 'Your request to switch plan has been sent. We\'ll get in touch soon!';
	}

    return $message;

}
add_filter('woocommerce_add_message', 'customSubscriptionNoticeText');



function wooNoticesMessageBasedOnProduct($subscription, $new_status, $old_status){
	if($new_status == 'pending-cancel'){
		$message = "";

		foreach($subscription->get_items() as $item){
			if(has_term('active-task','product_cat', $item['product_id'])){
				$message = 'This active task has been succesfully cancelled and will still be available until the end of your current billing period.';
			}else if(has_term('add-on','product_cat', $item['product_id'])){
				$message = 'This add on has been succesfully cancelled and will still be available until the end of your current billing period.';
			}else{
				$message = 'Your account has been succesfully cancelled. Your Deer Designer team is still available until the end of your current billing period.';
			}
		}

		wc_add_notice($message, 'success');
	}
}
add_action('woocommerce_subscription_status_updated', 'wooNoticesMessageBasedOnProduct', 10, 3);




function notificationToSlackForSwitchSubscription($order){
	$orderItems = $order->get_items();
	$currentUser = wp_get_current_user();
	$customerName = $currentUser->display_name;
	$customerEmail = $currentUser->user_email;
	$orderItemsGroup = [];

	foreach($orderItems as $item){
		array_push($orderItemsGroup, $item['name']);
	}

	$slackMessageBody = [
		'text'  => '<!channel> Subscription Switched :alert:
*Client:* ' . $customerName . ' | ' . $customerEmail . '
:arrow_right: Client has switched his plan to ' . '*' . implode(" | ", $orderItemsGroup) . '*',
		'username' => 'Marcus',
	];


	slackNotifications($slackMessageBody);
}
add_action('woocommerce_subscriptions_switch_completed', 'notificationToSlackForSwitchSubscription');



function moveCancelledSubscriptionsToTrash($subscription){

    if ($subscription && 'cancelled' === $subscription->get_status()) {
        wp_trash_post($subscription->id);
    }
}
//add_action('woocommerce_subscription_status_cancelled', 'moveCancelledSubscriptionsToTrash');



function renameSubscriptionStatus($subscription_statuses){
    $subscription_statuses['wc-on-hold']      = _x( 'Paused', 'Subscription status', 'woocommerce-subscriptions' );

    return $subscription_statuses;
}
add_filter( 'wcs_subscription_statuses', 'renameSubscriptionStatus');







function redirectUserToCheckoutAfterAddToCart( $url, $adding_to_cart ) {
    return wc_get_checkout_url();
}
add_filter ('woocommerce_add_to_cart_redirect', 'redirectUserToCheckoutAfterAddToCart', 10, 2 ); 



function prepareOrderDataToCreateTheUserGroupOnDataBase($orderId){
	$order = wc_get_order( $orderId );
	$orderData = $order->get_data();
	$orderItems = $order->get_items();
	$groupName = strtolower(str_replace(' ', '_', $orderData['billing']['company']));
	$companyName = $orderData['billing']['company'];
	$creativeCalls = 0;
	
	foreach( $orderItems as $item_id => $item ){
		if(str_contains(strtolower($item->get_name()), 'call')){
			$creativeCalls = 1;
		}
		else if(str_contains(strtolower($item->get_name()), 'agency')){
			$creativeCalls = 4;
		}else{
			$creativeCalls = 0;
		}
	}

	createNewGroupAfterPurchase($groupName, $companyName, $creativeCalls);

}
add_action('woocommerce_payment_complete', 'prepareOrderDataToCreateTheUserGroupOnDataBase');



function zeroCreativeCallsOnRenewalFailed($subscription){
	global $wpdb;
	$user = get_user_by( 'email', $subscription->data['billing']['email']);
	$groupsUser = new Groups_User( $user->id );

	foreach($subscription->get_items() as $item){
		if(str_contains(strtolower($item['name']), 'agency')){
			$tableName = _groups_get_tablename( 'group' );

			foreach($groupsUser->groups as $group){
				$existingRow = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM $tableName WHERE name = %s",
						$group->name,
					)
				);

				if($existingRow){
						//UPDATE DATA
						$wpdb->update($tableName, array(
								'creative_calls' => 0,
							), array(
								'name' => $group->name
							)
						);
				}
			}
		}
	}
}
add_action('woocommerce_subscription_renewal_payment_failed', 'zeroCreativeCallsOnRenewalFailed');



function createNewGroupAfterPurchase($groupName, $companyName, $creativeCalls) {
	global $wpdb;
    $tableName = _groups_get_tablename( 'group' );

    $data = array(
		'parent_id' => null,
		'creator_id' => 1, 
		'datetime' => date('Y-m-d H:i:s'),
        'name' => $groupName,
        'description' => 'Group for the company ' . $companyName,
		'creative_calls' => $creativeCalls
    );

    $existingRow = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $tableName WHERE name = %s",
            $groupName,
        )
    );

   if($existingRow){
		//UPDATE DATA
		$wpdb->update($tableName, array(
				'creative_calls' => $creativeCalls,
			), array(
				'name' => $groupName
			)
		);

		if ( $group = Groups_Group::read_by_name( $groupName ) ) {
			Groups_User_Group::create( array( "user_id"=>get_current_user_id(), "group_id"=>$group->group_id ) );
		}

   }else{
		//INSERT DATA AND ADD CURRENT USER TO THE GROUP
		$wpdb->insert($tableName, $data);
		$insertedId = $wpdb->insert_id;

		if($insertedId){
			if ( $group = Groups_Group::read_by_name( $groupName ) ) {
				Groups_User_Group::create( array( "user_id"=>get_current_user_id(), "group_id"=>$group->group_id ) );
			}
		}
   }
}



function createHTMLForCustomFieldOnGroupAdminPage($creativeCallsLeft = null){
	$html = "
		<label style='margin: 20px 0; display: flex; align-items: center; gap: 12px;'>Creative Calls Left
			<input type='number' value='$creativeCallsLeft' min='0' name='creative_calls' id='creative_calls'/>
		</label>
		";

	return $html;
}
add_filter('groups_admin_groups_add_form_after_fields', 'createHTMLForCustomFieldOnGroupAdminPage');



function displayCustomFieldToShowCreativeCallsOnEditGroupAdminPage($html, $group_id){
	global $wpdb;
		
	$tableName = _groups_get_tablename( 'group' );
	$existingRow = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $tableName WHERE group_id = %s",
            $group_id,
        )
    );
	
	$creativeCallsLeft = $existingRow->creative_calls;

	return createHTMLForCustomFieldOnGroupAdminPage($creativeCallsLeft);

}
add_filter('groups_admin_groups_edit_form_after_fields', 'displayCustomFieldToShowCreativeCallsOnEditGroupAdminPage', 10, 2);



function saveCreativeCallsInDataBase($group_id){
	global $wpdb;
	$tableName = _groups_get_tablename( 'group' );

	if(isset($_POST['creative_calls'])){
		$wpdb->update($tableName, array(
				'creative_calls' => $_POST['creative_calls'],
			), array(
				'group_id' => $group_id
			)
		);
	};
}
add_action('groups_admin_groups_add_submit_success', 'saveCreativeCallsInDataBase');
add_action('groups_admin_groups_edit_submit_success', 'saveCreativeCallsInDataBase');



function removeMobileMessagingFromWooEmails( $mailer ) {	   
	remove_action( 'woocommerce_email_footer', array( $mailer->emails['WC_Email_New_Order'], 'mobile_messaging' ), 9 );
}
add_action( 'woocommerce_email', 'removeMobileMessagingFromWooEmails' );



function formatSubscriptionStatusLabel($status){
	switch ($status){
		case 'on-hold':
			echo 'paused';
			break;
		case 'pending-cancel':
			echo 'cancelled';
			break;

		default:
			echo $status;
	}
}
add_action('callNewSubscriptionsLabel', 'formatSubscriptionStatusLabel');


add_filter( 'wc_add_to_cart_message_html', '__return_false' );



function removeMySubscriptionsButton( $actions, $subscription ) {
	foreach ( $actions as $action_key => $action ) {
		switch ( $action_key ) {
			case 'change_payment_method':	// Hide "Change Payment Method" button?
//			case 'change_address':		// Hide "Change Address" button?
//			case 'switch':			// Hide "Switch Subscription" button?
//			case 'resubscribe':		// Hide "Resubscribe" button from an expired or cancelled subscription?
//			case 'pay':			// Hide "Pay" button on subscriptions that are "on-hold" as they require payment?
//			case 'reactivate':		// Hide "Reactive" button on subscriptions that are "on-hold"?
//			case 'cancel':			// Hide "Cancel" button on subscriptions that are "active" or "on-hold"?
				unset( $actions[ $action_key ] );
				break;
			default: 
				error_log( '-- $action = ' . print_r( $action, true ) );
				break;
		}
	}

	return $actions;
}
add_filter( 'wcs_view_subscription_actions', 'removeMySubscriptionsButton', 100, 2 );



function cancelActiveTasksByPausePlan($subscription, $new_status, $old_status){
	$userSubscriptions = wcs_get_users_subscriptions(get_current_user_id());

	foreach($subscription->get_items() as $item){
		if(has_term( 'plan', 'product_cat', $item->get_product_id())){
			foreach ($userSubscriptions as $subs){		
				foreach ($subs->get_items() as $product) {			
					if ( !has_term( 'plan', 'product_cat', $product->get_product_id() ) ){
						if($new_status === "on-hold" || $new_status === "cancelled" || $new_status === "pending-cancel"){
							$subs->update_status('pending-cancel');
						}
					};	
				}
			}
		}
	}
	
}
add_action('woocommerce_subscription_status_updated', 'cancelActiveTasksByPausePlan', 10, 3);



function defineSubscriptionPeriod($productPrice){
	if(str_contains($productPrice, 'month') !== false){
		echo "/month";
	}else if(str_contains($productPrice, 'year') !== false){
		echo "/year";
	}else{
		echo "";
	}
}
add_action('defineSubscriptionPeriodHook', 'defineSubscriptionPeriod');



function defineAddonPeriodToShowOnCards($addonName){
	if(str_contains($addonName, 'Stock')){
		echo 'month';
	}else if(str_contains($addonName, 'Call')){
		echo 'call';
	}else{
		echo 'month';
	}
}
add_action('callAddonsPeriod', 'defineAddonPeriodToShowOnCards');



function changeNewOrderEmailSubjectBasedOnProduct($subject, $order) {
	$siteTitle = get_bloginfo( 'name' );
	$orderItems = $order->get_items();

	foreach($orderItems as $orderItem){
		$productName = $orderItem->get_name();

		if(has_term('plan', 'product_cat', $orderItem->get_product_id())){
			$newSubject = "[$siteTitle]: New Subscription";
		}else{
			$newSubject = "[$siteTitle]: New $productName";
		}
	}
    
    return $newSubject;
}
add_filter('woocommerce_email_subject_new_order', 'changeNewOrderEmailSubjectBasedOnProduct', 10, 2);



function changeCompletedOrderEmailSubjectBasedOnProduct($subject, $order) {
	$siteTitle = get_bloginfo( 'name' );
	$orderItems = $order->get_items();

	foreach($orderItems as $orderItem){
		$productName = $orderItem->get_name();

		if(has_term('plan', 'product_cat', $orderItem->get_product_id())){
			$newSubject = "[$siteTitle]: Thanks for joining Deer Designer - Receipt attached";

		}elseif(has_term('add-on', 'product_cat', $orderItem->get_product_id())){
			$newSubject = "[$siteTitle]: You've got a new Add on: $productName";

		}else{
			$newSubject = "[$siteTitle]: You've got an additional $productName";
		}
	}
    
    return $newSubject;
}
add_filter('woocommerce_email_subject_customer_completed_order', 'changeCompletedOrderEmailSubjectBasedOnProduct', 10, 2);
