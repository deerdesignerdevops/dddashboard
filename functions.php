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
	$version = $theme->get( 'Version' );

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



//SLACK NOTIFICATIONS
function sendPaymentCompleteNotificationToSlack($order_id){
	$order = wc_get_order( $order_id );
	$orderData = $order->get_data();
	$orderItems = $order->get_items();
	$orderItemsGroup = [];

	foreach( $orderItems as $item_id => $item ){
		$itemName = $item->get_name();
		array_push($orderItemsGroup, $itemName);
	}

	$slackUrl = SLACK_WEBHOOK_URL_MARCUS;
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



function sendPaymentFailedNotificationToSlack($order_id){
	$order = wc_get_order( $order_id );
	$orderData = $order->get_data();

	$slackUrl = SLACK_WEBHOOK_URL_MARCUS;
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



function sendUserOnboardedNotificationToSlack($entryId, $formData, $form){
	if($form->id === 3){
		$userName = $formData['names']['first_name'] . " " . $formData['names']['last_name'];
		$currentUser = wp_get_current_user(get_current_user_id());
		$companyName = $formData['company_name'];
		$userCity = $currentUser->billing_city;
		$userCountry = $currentUser->billing_country;

		$slackUrl = SLACK_WEBHOOK_URL_MARCUS;
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
add_action( 'fluentform/submission_inserted', 'sendUserOnboardedNotificationToSlack', 10, 3);



function createUserAfterStripePurchase($req){
	$stripe = new \Stripe\StripeClient(STRIPE_API);
	$customer = $stripe->customers->retrieve($req['data']['object']['customer'],[]);
	$invoiceId = $req['data']['object']['id'];
	$customerName = $customer->name;
	$customerEmail = $customer->email;
	$customerPlan = $req['data']['object']['items']['data'][0]['plan']['nickname'];
	$customerCity = $customer->address->city;
	$customerCountry = $customer->address->country;
	
	$response_data_arr = file_get_contents('php://input');
	
	file_put_contents("wp-content/uploads/stripe_webhooks_logs/stripe_response_".date('Y_m_d')."_".$invoiceId.".log", $response_data_arr);

	$customerUrl = "https://dash.deerdesigner.com/signup/onboarding/?first_name=$customerName&last_name=&email=$customerEmail&city=$customerCity&country=$customerCountry&plan=$customerPlan";

	// if(empty(get_user_by('email', $customerEmail))){
	// 	wp_create_user($customerEmail, 'change_123', $customerEmail);
	// 	sendWelcomeEmailAfterStripePayment($customerName, $customerEmail, $customerUrl);
	// }
	
	//sendPaymentCompleteNotificationToSlack($customerName, $customerEmail, $customerPlan);
	echo "Customer Name: $customerName, Customer Email: $customerEmail, Customer City: $customerCity, Customer Country: $customerCountry, Plan: $customerPlan";
}



add_action( 'rest_api_init', function () {
  register_rest_route( '/stripe/v1','paymentcheck', array(
    'methods' => 'POST',
    'callback' => 'createUserAfterStripePurchase',
  ) );
} );



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

	if(is_page('dash')){
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
				$url = home_url() . "/onboarding";
				wp_redirect($url);
				exit();
			}
		}
	
	}
}
add_action('template_redirect', 'checkIfCurrentUserIsOnboarded');



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

	if($product_id === 939 || !in_array("default-plans", $productsCategories)){
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
	//$metadata += ['billing_company' => $order_data['billing']['company']];
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
	//$metadata['billing_company'] = $order->get_billing_company();
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
	unset( $fields['billing']['billing_company'] );
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
	$isUserOnboarded =  get_user_meta($user->ID, 'is_user_onboarded', true);
    $order = wc_get_order( $order_id );

    $url = site_url() . '/signup/onboarding';

	if($isUserOnboarded){
		$url = site_url() . "/subscriptions";
	}

    if (!$order->has_status( 'failed' )) {
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



function redirectUserIfCartIsEmpty(){
	$url = site_url() . '/subscriptions';
	wp_redirect( $url );
	exit;  
}
add_action('woocommerce_cart_is_empty', 'redirectUserIfCartIsEmpty');



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