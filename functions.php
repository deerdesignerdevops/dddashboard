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
	$version = rand(111,9999);

	// CSS
	wp_enqueue_style( 'custom', get_stylesheet_directory_uri() . '/style.css', array( 'hello-elementor-theme-style' ), $version );
	wp_enqueue_style( 'slick', get_stylesheet_directory_uri() . '/libs/slick/css/slick.css', $version );
	wp_enqueue_style( 'slick-theme', get_stylesheet_directory_uri() . '/libs/slick/css/slick-theme.css', $version );

	//JS
	wp_enqueue_script('custom-jquery', get_stylesheet_directory_uri() . '/libs/jquery/jquery.js', $version);
	wp_enqueue_script('slick', get_stylesheet_directory_uri() . '/libs/slick/js/slick.min.js', $version);
	wp_enqueue_script('custom', get_stylesheet_directory_uri() . '/scripts.js', $version);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );



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



//STRIPE API
require_once('stripe/init.php');

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
	$slackUrl = SLACK_WEBHOOK_URL;
	$slackMessageBody = [
		'text'  => 'We have a new subscription, <!channel> :smiling_face_with_3_hearts:
*Client:* ' . $customerName . ' ' . $customerEmail . '
*Plan:* ' . $customerPlan . '
Let\'s wait for the onboarding rocket :muscle::skin-tone-2:',
		'username' => 'Marcus',
	];


	wp_remote_post( $slackUrl, array(
		'body'        => wp_json_encode( $slackMessageBody ),
		'headers' => array(
			'Content-type: application/json'
		),
	) );
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

	$customerUrl = "https://dash.deerdesigner.com/signup/onboarding/?first_name=$customerName&last_name=&email=$customerEmail&city=$customerCity&country=$customerCountry&plan=$customerPlan";

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
			update_user_meta( $user->id, 'creative_calls', 4 );
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

	if($form->id == 3){
		echo "<script>
			document.addEventListener('DOMContentLoaded', function(){
				console.log('ok')
				document.querySelector('[data-name=\"plan\"]').value='$userPlan'
				document.querySelector('[data-name=\"city\"]').value='$userCity'
				document.querySelector('[data-name=\"country\"]').value='$userCountry'
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
	$url = home_url();
	$isUserOnboarded =  get_user_meta($user->ID, 'is_user_onboarded', true);

	if(is_page('dash') || is_page('dash-woo')){
		require_once(WP_PLUGIN_DIR  . '/fluentform/app/Api/FormProperties.php');

		if ( !in_array( 'administrator', $user->roles ) ) {
			$formApi = fluentFormApi('forms')->entryInstance($formId = 3);
			$atts = [
				'per_page' => 10,
				'page' => 1,
				'search' => $user->user_email,
			];
			
			$entries = $formApi->entries($atts , $includeFormats = false);
			if(!$entries["total"] && !$isUserOnboarded){
				$url = home_url() . "/signup/onboarding";
				wp_redirect($url);
				exit();
			}
		}
	
	}
}
add_action('template_redirect', 'checkIfCurrentUserIsOnboarded');



function sendStripePaymentFailedNotificationToSlack($req){
	$stripe = new \Stripe\StripeClient(STRIPE_API);
	$customer = $stripe->customers->retrieve($req['data']['object']['customer'],[]);
	$customerName = $customer->name;
	$customerEmail = $customer->email;
		
	$slackUrl = SLACK_WEBHOOK_URL;
	$slackMessageBody = [
		'text'  => '<!channel> Payment failed :x:
' . $customerName . ' - ' . $customerEmail . '
:arrow_right: AMs, work on their requests but don\'t send them until payment is resolved.',
		'username' => 'Marcus',
	];


	wp_remote_post( $slackUrl, array(
		'body'        => wp_json_encode( $slackMessageBody ),
		'headers' => array(
			'Content-type: application/json'
		),
	) );

	echo "Payment failed for: $customerName - $customerEmail";
}



add_action( 'rest_api_init', function () {
  register_rest_route( '/stripe/v1','paymentfailed', array(
    'methods' => 'POST',
    'callback' => 'sendStripePaymentFailedNotificationToSlack',
  ) );
} );



function displayCreativeCallsNumberOnAdminPanel( $user ) { 
    $isUserOnboarded = get_the_author_meta('creative_calls',$user->ID,true ); 
?>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th>Creative Calls:</th>
                <td>
                    <p><label>
						<input type="number" min="0" name="creative_calls" value="<?php echo $isUserOnboarded; ?>">
                    </label></p>
                </td>
            </tr>
        </tbody>
    </table>
<?php } 
add_action( 'show_user_profile', 'displayCreativeCallsNumberOnAdminPanel' );
add_action( 'edit_user_profile', 'displayCreativeCallsNumberOnAdminPanel' );



function updateUserCreativeCalls($user_id){
	update_user_meta( $user_id, 'creative_calls', $_POST['creative_calls'] );
}
add_action( 'personal_options_update', 'updateUserCreativeCalls' );
add_action( 'edit_user_profile_update', 'updateUserCreativeCalls' );



function displayUserOnboardedCheckboxOnAdminPanel( $user ) { 
    $isUserOnboarded = get_the_author_meta('is_user_onboarded',$user->ID,true ); 
?>
    <table class="form-table" role="presentation">
        <tbody>
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
add_action( 'show_user_profile', 'displayUserOnboardedCheckboxOnAdminPanel' );
add_action( 'edit_user_profile', 'displayUserOnboardedCheckboxOnAdminPanel' );


function updateIfUserIsOnboarded($user_id){
	if($_POST['is_user_onboarded']){
		update_user_meta( $user_id, 'is_user_onboarded', 1 );
	}else{
		update_user_meta( $user_id, 'is_user_onboarded', 0 );
	}
}
add_action( 'personal_options_update', 'updateIfUserIsOnboarded' );
add_action( 'edit_user_profile_update', 'updateIfUserIsOnboarded' );



function addFirstAccessUserMetaToNewUsers($user_id) { 
   add_user_meta( $user_id, 'is_first_access', 1 );
   add_user_meta( $user_id, 'is_user_onboarded', 0 );
}
add_action( 'user_register', 'addFirstAccessUserMetaToNewUsers');



function updateIsUserOnboardedAfterOnboardingForm(){
	update_user_meta( get_current_user_id(), 'is_user_onboarded', 1 );
}
add_action( 'fluentform/submission_inserted', 'updateIsUserOnboardedAfterOnboardingForm');



function sendUserOnboardedNotificationToSlack($entryId, $formData, $form){
	if($form->id === 3){
		$customerName = $formData['names']['first_name'] . " " . $formData['names']['last_name'];
		$customerEmail = $formData['email'];
		$customerCompany = $formData['company_name'];
		$customerCity = $formData['city'];
		$customerCountry = $formData['country'];

		$slackUrl = SLACK_WEBHOOK_URL;
		$slackMessageBody = [
			'text'  => '<!channel> :rocket:Onboarded: ' . $customerName . ' ( ' . $customerCompany . ' ) from ' . $customerCity . ', ' . $customerCountry,
			'username' => 'Marcus',
		];


		wp_remote_post( $slackUrl, array(
			'body'        => wp_json_encode( $slackMessageBody ),
			'headers' => array(
				'Content-type: application/json'
			),
		) );
	}
}
add_action( 'fluentform/submission_inserted', 'sendUserOnboardedNotificationToSlack', 10, 3);



function subscribeUserToMoosendEmailList($entryId, $formData, $form){
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
//add_action("wp_head", "googleTagManagerOnHead");



function googleTagManagerOnBody(){
	echo '
	<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W9B92LV"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
';
}
//add_action('wp_body_open', 'googleTagManagerOnBody');



function removePageTitleFromAllPages($return){
	return false;
}
add_filter('hello_elementor_page_title', 'removePageTitleFromAllPages');



function checkIfUserCanBookCreativeCall(){
	$canUserBookACreativeCall =  get_user_meta(get_current_user_id(), 'creative_calls', true);
	$canUserBookACreativeCall =  get_user_meta(get_current_user_id(), 'stripe_customer_plan', true);
	$remainingCalls =  get_user_meta(get_current_user_id(), 'creative_calls', true);

	if($canUserBookACreativeCall){
		echo "<style>.book_call_btn{display: flex !important;}</style>";
	}else{
		echo "<style>.book_call_btn{display: none !important;}</style>";
	}

}
add_action('template_redirect', 'checkIfUserCanBookCreativeCall');




//***************CUSTOM CODES FOR WOOCOMMERCE
function changeActionsButtonsLabel( $actions, $subscription ){
    if( isset( $actions['suspend'] ) ){
        $actions['suspend']['name'] = __( 'Pause', 'woocommerce-subscriptions' );
    }
    return $actions;
}
add_filter( 'wcs_view_subscription_actions', 'changeActionsButtonsLabel', 10, 2 );



function redirectUserAfterSubscriptionStatusUpdated(){
	$url = site_url() . "/subscriptions";

	if(is_user_logged_in() && is_wc_endpoint_url('view-subscription') ){
		wp_safe_redirect($url);
		exit;
	}
}
add_action('template_redirect', 'redirectUserAfterSubscriptionStatusUpdated');



function sendPaymentCompleteNotificationToSlack($order_id){
	$order = wc_get_order( $order_id );
	$orderData = $order->get_data();
	$orderItems = $order->get_items();
	$orderItemsGroup = [];

	foreach( $orderItems as $item_id => $item ){
		$itemName = $item->get_name();
		array_push($orderItemsGroup, $itemName);
	}

	$slackUrl = SLACK_WEBHOOK_URL;
	$customerName = $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'];
	$customerEmail = $orderData['billing']['email'];
	$slackMessageBody = [
		'text'  => 'We have a new subscription, <!channel> :smiling_face_with_3_hearts:
*Client:* ' . $customerName . ' ' . $customerEmail . '
*Plan:* ' . implode(" | ", $orderItemsGroup) . '
Let\'s wait for the onboarding rocket :muscle::skin-tone-2:',
		'username' => 'Marcus',
	];


	wp_remote_post( $slackUrl, array(
		'body'        => wp_json_encode( $slackMessageBody ),
		'headers' => array(
			'Content-type: application/json'
		),
	) );

}
add_action( 'woocommerce_payment_complete', 'sendPaymentCompleteNotificationToSlack');



function addCreativeCallToUserMetaAfterBuyCreativeCallProduct($order_id){
	$remainingCalls =  get_user_meta(get_current_user_id(), 'creative_calls', true);
	$order = wc_get_order( $order_id );
	$orderItems = $order->get_items();
	
	foreach( $orderItems as $item_id => $item ){
		if(strpos(strtolower($item->get_name()), 'call')){
			if($remainingCalls){
				update_user_meta(get_current_user_id(), 'creative_calls', $remainingCalls + 1);
			}else{
				add_user_meta(get_current_user_id(), 'creative_calls', 1);
			}
		}
	}
}
add_action( 'woocommerce_payment_complete', 'addCreativeCallToUserMetaAfterBuyCreativeCallProduct');




function sendUserOnboardedNotificationFromWooToSlack($entryId, $formData, $form){
	if($form->id === 3){
		$userName = $formData['names']['first_name'] . " " . $formData['names']['last_name'];
		$currentUser = wp_get_current_user(get_current_user_id());
		$companyName = $formData['company_name'];
		$userCity = $currentUser->billing_city;
		$userCountry = $currentUser->billing_country;

		$slackUrl = SLACK_WEBHOOK_URL;
		$slackMessageBody = [
			'text'  => '<!channel> :rocket:Onboarded: ' . $userName . ' (' . $companyName . ') ' . 'from ' . $userCity . ', ' . $userCountry,
			'username' => 'Marcus',
		];


		wp_remote_post( $slackUrl, array(
			'body'        => wp_json_encode( $slackMessageBody ),
			'headers' => array(
				'Content-type: application/json'
			),
		) );
	}
}
add_action( 'fluentform/submission_inserted', 'sendUserOnboardedNotificationFromWooToSlack', 10, 3);



function checkIfUserIsActive(){
	$user_id = get_current_user_id();
	$users_subscriptions = wcs_get_users_subscriptions($user_id);

	$product_id = "";
	$productsCategories = [];

	foreach ($users_subscriptions as $subscription){
		if ($subscription->has_status(array('active'))) {
			$subscription_products = $subscription->get_items();
			foreach ($subscription_products as $product) {			
                $product_id = $product->get_product_id();
				$terms = get_the_terms( $product_id, 'product_cat' );
				$productCategory = $terms[0]->slug;
				array_push($productsCategories, $productCategory);
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
	$order_id = WC()->session->get('order_awaiting_payment');
	$order = new WC_Order($order_id);

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



function redirectToOnboardingFormAfterCheckout( $order_id ) {
	 $user = wp_get_current_user();
	 $isUserOnboarded =  get_user_meta($user->id, 'is_user_onboarded', true);
     $url = site_url() . '/signup/onboarding';

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



function changeOrderStatusToCompleteAfterPayment( $order_id ) {
    $order = wc_get_order( $order_id );
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



function sendPaymentFailedNotificationToSlack($order_id){
	$order = wc_get_order( $order_id );
	$orderData = $order->get_data();

	$slackUrl = SLACK_WEBHOOK_URL;
	$customerName = $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'];
	$customerEmail = $orderData['billing']['email'];
	$slackMessageBody = [
		'text'  => '<!channel> Payment failed :x:
' . $customerName . ' - ' . $customerEmail . '
:arrow_right: AMs, work on their requests but don\'t send them until payment is resolved.',
		'username' => 'Marcus',
	];


	wp_remote_post( $slackUrl, array(
		'body'        => wp_json_encode( $slackMessageBody ),
		'headers' => array(
			'Content-type: application/json'
		),
	) );
}
add_action( 'woocommerce_order_status_failed', 'sendPaymentFailedNotificationToSlack');



function defineAddonPeriodToShowOnCards($addonName){
	if(str_contains($addonName, 'Photos')){
		echo 'year';
	}else if(str_contains($addonName, 'Call')){
		echo 'call';
	}else{
		echo 'month';
	}
}
add_action('callAddonsPeriod', 'defineAddonPeriodToShowOnCards');



function showBracketsAroundVariationName($name, $product) {
    if (strpos($name, '-') !== false) {
        $modified_name_last = substr($name, strrpos($name, '-') + 1);
        $modified_name_first = substr($name, 0, strrpos($name, "-"));
        $name = $modified_name_first . '( ' . $modified_name_last . ' )';
    }

    return $name;
}
add_filter('woocommerce_product_variation_get_name', 'showBracketsAroundVariationName', 10, 2);



function notificationToSlackWithSubscriptionUpdateStatus($subscription, $new_status, $old_status){
	if($old_status !== 'pending'){
		$subscriptionItems = $subscription->get_items();
		$currentUser = wp_get_current_user();
		$slackUrl = SLACK_WEBHOOK_URL;
		$customerName = $currentUser->display_name;
		$customerEmail = $currentUser->user_email;
		$subscriptionItemsGroup = [];

		foreach($subscriptionItems as $item){
			array_push($subscriptionItemsGroup, $item['name']);
		}

		$slackMessageBody = [
			'text'  => '<!channel> Subscription Updated :alert:
	*Client:* ' . $customerName . ' | ' . $customerEmail . '
	*Plan:* ' . implode(" | ", $subscriptionItemsGroup) . '
	:arrow_right: Client has changed his subscription to -> ' . "*$new_status*",
			'username' => 'Marcus',
		];


		wp_remote_post( $slackUrl, array(
			'body'        => wp_json_encode( $slackMessageBody ),
			'headers' => array(
				'Content-type: application/json'
			),
		) );
	}
}
add_action('woocommerce_subscription_status_updated', 'notificationToSlackWithSubscriptionUpdateStatus', 10, 3);



function notificationToSlackForSwitchSubscription($order){
	$orderItems = $order->get_items();
	$currentUser = wp_get_current_user();
	$slackUrl = SLACK_WEBHOOK_URL;
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


	wp_remote_post( $slackUrl, array(
		'body'        => wp_json_encode( $slackMessageBody ),
		'headers' => array(
			'Content-type: application/json'
		),
	) );
}
add_action('woocommerce_subscriptions_switch_completed', 'notificationToSlackForSwitchSubscription');



function moveCancelledSubscriptionsToTrash($subscription){

    if ($subscription && 'cancelled' === $subscription->get_status()) {
        wp_trash_post($subscription->id);
    }
}
add_action('woocommerce_subscription_status_cancelled', 'moveCancelledSubscriptionsToTrash');



//***************AFFILIATES AND REFERRAL PROGRAM
function applyCouponToSubscriptionBasedOnUserReferrals($referral_id){
	$referral_data = affwp_get_referral( $referral_id );
	$affiliate_data = affwp_get_affiliate( $referral_data->affiliate_id );

	$subscriptions = wcs_get_users_subscriptions($affiliate_data->user_id);

	$subscriptionsArray = [];

	foreach($subscriptions as $subscription){
		if ($subscription->has_status(array('active'))){
			array_push($subscriptionsArray, $subscription->id);
			$subscription->apply_coupon('dd10aff');
		}
	}


	$slackUrl = SLACK_WEBHOOK_URL;
	$slackMessageBody = [
		'text'  => implode(" | ", $subscriptionsArray),
		'username' => 'Marcus',
	];


	wp_remote_post( $slackUrl, array(
		'body'        => wp_json_encode( $slackMessageBody ),
		'headers' => array(
			'Content-type: application/json'
		),
	) );
}

add_action('affwp_insert_referral', 'applyCouponToSubscriptionBasedOnUserReferrals');
