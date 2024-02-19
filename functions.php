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
	$version = rand(111,999);

	// CSS
	wp_enqueue_style( "dd-custom-style", get_stylesheet_directory_uri() . '/style.css', array( 'hello-elementor-theme-style' ), $version );
	wp_enqueue_style( "glider-styles", get_stylesheet_directory_uri() . '/libs/glider/glider.min.css', array(), $version );

	//JS
	wp_enqueue_script("glider-scripts", get_stylesheet_directory_uri() . '/libs/glider/glider.min.js', array(), $version);
	wp_enqueue_script("dd-custom-scripts", get_stylesheet_directory_uri() . '/dd-custom-scripts.js', array(), $version);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );



add_theme_support( 'admin-bar', array( 'callback' => '__return_false' ) );



require_once('stripe/init.php');
require_once('custom-email-notifications.php');
require_once('integrations/freshdesk.php');
require_once('integrations/moosend.php');
require_once('integrations/clockify.php');
require_once('integrations/appbox.php');


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



function populateOnboardingFormHiddenFieldsWithUserMeta($form){
	$currentUser = wp_get_current_user();
	$userCity = $currentUser->billing_city;
	$userCountry = $currentUser->billing_country;
	$companyName = get_user_meta($currentUser->id, 'billing_company', true);
	$userPlan = "";

	$userSubscriptions = wcs_get_users_subscriptions($currentUser->id);

	foreach($userSubscriptions as $sub){
		foreach($sub->get_items() as $subItem){
			if(has_term('plan', 'product_cat', $subItem['product_id'])){
				$userPlan = $subItem['name'];
			}
		}
	}


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



function populateCSATFormHiddenFieldsWithUserMeta($form){
	$currentUser = wp_get_current_user();
	$companyName = get_user_meta($currentUser->id, 'billing_company', true);

	if($form->id == 5){
		echo "<script>
			document.addEventListener('DOMContentLoaded', function(){
				document.querySelector('[data-name=\"hidden_company_name\"]').value='$companyName'
			})
		</script>";
	}
}
add_action('fluentform/after_form_render', 'populateCSATFormHiddenFieldsWithUserMeta');




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



function displayAdditionalUserDataOnAdminPanel( $user ) { 
	$isUserOnboarded = get_the_author_meta('is_user_onboarded',$user->ID,true );
	$companyFreshdeskId = get_the_author_meta('company_freshdesk_id',$user->ID,true );
	$contactFreshdeskId = get_the_author_meta('contact_freshdesk_id',$user->ID,true );
	$boxFolderId = get_the_author_meta('company_folder_box_id',$user->ID,true );
	$userVatNumber = get_the_author_meta('user_vat_number',$user->ID,true );

?>
<h2>Additional Data</h2>
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

			<tr>
            	<th>Freshdesk Company ID:</th>
                <td>
                    <p><label>
                        <input type="text" name="company_freshdesk_id" value="<?php echo $companyFreshdeskId; ?>">
                    </label></p>
                </td>
            </tr>

			<tr>
            	<th>Freshdesk Contact ID:</th>
                <td>
                    <p><label>
                        <input type="text" name="contact_freshdesk_id" value="<?php echo $contactFreshdeskId; ?>">
                    </label></p>
                </td>
            </tr>
			<tr>
            	<th>Box Folder ID:</th>
                <td>
                    <p><label>
                        <input type="text" name="company_folder_box_id" value="<?php echo $boxFolderId; ?>">
                    </label></p>
                </td>
            </tr>
			<tr>
            	<th>VAT:</th>
                <td>
                    <p><label>
                        <input type="text" name="user_vat_number" value="<?php echo $userVatNumber; ?>">
                    </label></p>
                </td>
            </tr>
        </tbody>
    </table>
<?php } 
add_action( 'show_user_profile', 'displayAdditionalUserDataOnAdminPanel' );
add_action( 'edit_user_profile', 'displayAdditionalUserDataOnAdminPanel' );



function updateAditionalUserDataOnAdminPanel($userId){
	update_user_meta( $userId, 'is_user_onboarded', $_POST['is_user_onboarded'] );
	update_user_meta( $userId, 'company_freshdesk_id', $_POST['company_freshdesk_id'] );
	update_user_meta( $userId, 'contact_freshdesk_id', $_POST['contact_freshdesk_id'] );
	update_user_meta( $userId, 'company_folder_box_id', $_POST['company_folder_box_id'] );
	update_user_meta( $userId, 'user_vat_number', $_POST['user_vat_number'] );

	//UPDATE USER IN THIRD PARTY PLATFORMS
	updateUserInFreshdeskByWordpressProfileUpdate($userId);
	updateFolderNameInBoxByWpProfileUpdate($userId);
	updateUserEmailInMoosend($userId);
}
add_action( 'personal_options_update', 'updateAditionalUserDataOnAdminPanel' );
add_action( 'edit_user_profile_update', 'updateAditionalUserDataOnAdminPanel' );



function addFirstAccessUserMetaToNewUsers($user_id) { 
   add_user_meta( $user_id, 'is_first_access', 1 );
   add_user_meta( $user_id, 'is_user_onboarded', 0 );
}
add_action( 'user_register', 'addFirstAccessUserMetaToNewUsers');



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
function slackNotifications($slackMessageBody, $slackWebHook = SLACK_WEBHOOK_URL){
	wp_remote_post( $slackWebHook, array(
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
	$url = get_permalink( wc_get_page_id( 'myaccount' ) ) . "subscriptions";

	if(is_user_logged_in() && is_wc_endpoint_url('view-subscription')){
		wp_safe_redirect($url);
		exit;
	}
	else if(is_user_logged_in() && is_wc_endpoint_url('payment-methods')){
		wp_safe_redirect(get_permalink( wc_get_page_id( 'myaccount' ) ) . 'edit-account');
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
		$orderItemsGroup = implode(" | ", $orderItemsGroup);

		$slackMessageBody = [
			"text" => "We have a new subscription, <!channel> :smiling_face_with_3_hearts:\n*Client:* $customerName | $customerEmail\n*$productType:* $orderItemsGroup\n$notificationFinalMsg",
			"username" => "Marcus"
		];


		slackNotifications($slackMessageBody);

	}
}
add_action( 'woocommerce_payment_complete', 'sendPaymentCompleteNotificationToSlack');



function updateCreativeCallsNumberAfterPaymentComplete($orderId){
	$order = wc_get_order( $orderId );
	$currentUser = get_user_by('id', $order->data['customer_id']);
	$groupName = preg_replace('/[^\w\s]/', '', $currentUser->billing_company);
	$groupName = strtolower(str_replace(' ', '_', $groupName));
	$companyName = $currentUser->billing_company;
	$creativeCalls = updateCreativeCallsNumberBasedOnActiveSubscriptions($currentUser->id);		

	createNewGroupAfterOnboarding($groupName, $companyName, $creativeCalls);
}
add_action( 'woocommerce_payment_complete', 'updateCreativeCallsNumberAfterPaymentComplete');



function sendUserOnboardedNotificationFromWooToSlack($entryId, $formData, $form){
	if($form->id == 3){
		$weekInSeconds = 7 * 24 * 60 * 60;
		$fiveMin = 5 * 60;

		$currentUser = wp_get_current_user();
		$userName = $currentUser->first_name . " " . $currentUser->last_name;
		$userEmail = $currentUser->user_email;
		$companyName = $formData['company_name'];
		$userCity = $currentUser->billing_city;
		$userCountry = $currentUser->billing_country;
		update_user_meta( get_current_user_id(), 'is_user_onboarded', 1 );

		$slackMessageBody = [
			"text" => "<!channel> :rocket:Onboarded: $userName ($companyName) from $userCity, $userCountry",
			"username" => "Marcus",
		];

		slackNotifications($slackMessageBody);
		wp_schedule_single_event(time() + $fiveMin, 'sendWelcomeEmailAfterOnboardingFormHook', array($userName, $userEmail));
		wp_schedule_single_event(time() + $weekInSeconds, 'sendWelcomeEmailAfterOnboardingFormOneWeekLaterHook', array($userName, $userEmail));

	}
}
add_action( 'fluentform/submission_inserted', 'sendUserOnboardedNotificationFromWooToSlack', 10, 3);



function checkIfUserIsActive($currentUser){
	$userSubscriptions = wcs_get_users_subscriptions($currentUser->id);
	$currentUserSubscriptionStatus = '';

	foreach ($userSubscriptions as $subscription){
		foreach ($subscription->get_items() as $product) {	
			if(has_term('plan', 'product_cat', $product['product_id'])){
				$currentUserSubscriptionStatus = $subscription->get_status();
			}		
		}
	}

	switch($currentUserSubscriptionStatus){
		case 'on-hold':
			echo "<style>
				.paused__user_btn, .paused__user_banner{display: none !important}
			</style>";
			break;
		
		case 'pending-cancel':
			echo "<style>
				.paused__user_btn, .paused__user_banner{display: none !important}
			</style>";
			break;
		
		case 'active':
			echo "<style>
				.paused__user_banner{display: none !important}
				.paused__user_btn{display: flex !important}
			</style>";
			break;
		
		default:
			echo "<style>
				.paused__user_btn{display: none !important}
			</style>";
	}
}



function getCurrentTeamMemberAccountOwner($currentUser){
	$groupsUser = new Groups_User( $currentUser->id );
	$rolesToCheck = ['administrator', 'subscriber', 'paused'];

	foreach($groupsUser->groups as $group){
		if($group->name !== "Registered"){
			$currentUserGroup = new Groups_Group( $group->group_id );
		}
	}
	
	foreach($currentUserGroup->users as $group){		
		if (!empty(array_intersect($group->user->roles, $rolesToCheck))) {
			checkIfUserIsActive($group->user);
			return;
		}
	}
}


function getCurrentUserRole(){
	$currentUser = wp_get_current_user();

	if(in_array('team_member', $currentUser->roles)){
		echo "<style>
			.paused__user_btn, .btn__billing, .paused__user_banner{display:none !important;}
			.account_details__section{width: 50%; margin: auto;}
			.account__details_col{width: 100% !important;}
		</style>";
		getCurrentTeamMemberAccountOwner($currentUser);
	}else{
		checkIfUserIsActive($currentUser);
	}
}

add_action('template_redirect', 'getCurrentUserRole');


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
		$url = get_permalink( wc_get_page_id( 'myaccount' ) ) . "subscriptions";
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
								wp_redirect(get_permalink( wc_get_page_id( 'myaccount' ) ) . "subscriptions");
								exit;
							}
						}
					}
				}
			}
		}
	}	
}
//add_action('template_redirect', 'preventUserHaveMultiplePlansAtTheSameTime');


function changeActiveTaskPriceInCartBasedOnUserPlan() {;
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
    return;

	$standardPlanMonthlyPrice = wc_get_product( 1589 )->get_price();
	$activeTaskProductPrice = get_current_user_id() === 970 ? 0 : wc_get_product( 1600 )->get_price();

	$cart = WC()->cart->get_cart();
	$currentUserSubscriptionPlan = "";


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

	if($cart){
		foreach ( $cart as $cart_item_key => $values) {
			$terms = get_the_terms( $values['data']->id, 'product_cat' );
			$activeTaskFinalPrice = str_contains($currentUserSubscriptionPlan, 'Standard' ) ? $standardPlanMonthlyPrice : $activeTaskProductPrice;

			if($terms[0]->slug === 'active-task'){
				$values['data']->set_price($activeTaskFinalPrice);

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
	
	foreach( $order->get_items() as $item_id => $item ){
		$itemName = $item->get_name();
		$orderItems[] = $itemName;
	}

	$productNames = implode(" | ", array_unique($orderItems));

	if(wcs_order_contains_renewal($orderId)){
		$slackMessageBody = [
			"text" => "<!channel> Payment failed :x:\n$customerName | $customerEmail\n:arrow_right: AMs, work on their requests but don't send them until payment is resolved.\n *Plan:* $productNames.",
			"username" => "Marcus"
		];
	}else{
		$slackMessageBody = [
			"text" => "<!channel>\n*New client:* Payment failed :x:\n*Who:* $customerName | $customerEmail\n:arrow_right: CS, if they don't sign up in the next 15 minutes, get in touch and see if they need help.\n *Plan:* $productNames.",
			"username" => "Marcus"
		];
	}

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



function notificationToSlackWithSubscriptionUpdateStatus($subscription, $newStatus, $oldStatus){
	if(isset($_GET['change_subscription_to']) || isset($_GET['reactivate_plan'])){
		if($oldStatus !== 'pending' && $newStatus !== 'cancelled'){
			$currentUser = wp_get_current_user();
			$subscriptionItems = $subscription->get_items();
			$customerName = $currentUser->first_name . " " . $currentUser->last_name;
			$customerEmail = $currentUser->user_email;
			$customerCompany = $currentUser->billing_company;
			$subscriptionItemsGroup = [];
			$billingMsg = '';
			$billingPeriodEndingDate =  calculateBillingEndingDateWhenPausedOrCancelled($subscription);
			$requestMotive = "";

			foreach($subscriptionItems as $item){
				$subscriptionItemsGroup[] = $item['name'];
			}

			$subscriptionItemsGroup = implode(" | ", array_unique($subscriptionItemsGroup));
			
			if($newStatus === "on-hold"){
				$requestMotive = get_post_meta($subscription->id, 'pause_cancel_motive', true);
				$messageTitle = 'Pause Request :warning:';
				$billingMsg = " requested to Pause. Their billing date is on: $billingPeriodEndingDate\n*Motive:* $requestMotive";

				if(time() < strtotime($billingPeriodEndingDate)){
					wp_schedule_single_event(strtotime($billingPeriodEndingDate), 'scheduleSlackNotificationForSubscriptionStatusUpdateHook', array($newStatus, $customerName, $customerEmail, $subscriptionItemsGroup, $subscription->id));
				}

			}else if($newStatus === "pending-cancel"){
				$requestMotive = get_post_meta($subscription->id, 'pause_cancel_motive', true);
				$messageTitle = 'Cancellation Request :warning:';
				$billingMsg = " requested to Cancel. Their billing date is on: $billingPeriodEndingDate\n*Motive:* $requestMotive";

				if(str_contains($subscriptionItemsGroup, 'Active Task')){
					$messageTitle = 'Downgrade Request :warning:';
					$billingMsg = " requested to Downgrade. Their billing date is on: $billingPeriodEndingDate\n*Motive:* $requestMotive";
				}

				if(time() < strtotime($billingPeriodEndingDate)){
					wp_schedule_single_event(strtotime($billingPeriodEndingDate), 'scheduleSlackNotificationForSubscriptionStatusUpdateHook', array($newStatus, $customerName, $customerEmail, $subscriptionItemsGroup, $subscription->id));
				}


			}else if($oldStatus === "pending-cancel" && $newStatus === "active"){
				$messageTitle = 'Subscription Reactivated :white_check_mark:';
				$billingMsg = "'s account will not be 'canceled' anymore. Keep the work going";

			}else{
				$messageTitle = 'Subscription Reactivated :white_check_mark:';

			}

			$slackMessageBody = [
				"text" => "<!channel> $messageTitle\n*Client:* $customerName | $customerEmail ($customerCompany)$billingMsg\n*Plan:* $subscriptionItemsGroup",
				"username" => "Marcus"
			];

			slackNotifications($slackMessageBody);

			if($newStatus === 'active'){
				wc_add_notice("Your $subscriptionItemsGroup has been reactivated.", 'success');
			}
		}
	}
	
}
add_action('woocommerce_subscription_status_updated', 'notificationToSlackWithSubscriptionUpdateStatus', 10, 3);


function sendPauseCancelMotiveToSubscriptionPostMeta($entryId, $formData, $form){
	if($form->id == 6){
		$subscriptionId = $formData['form_subscription_id'];
		$requestMotive = $formData['form_subscription_update_message'];

		update_post_meta($subscriptionId, 'pause_cancel_motive', $requestMotive);
	}

}
add_action('fluentform/submission_inserted', 'sendPauseCancelMotiveToSubscriptionPostMeta', 10, 3);



function scheduleSlackNotificationForSubscriptionStatusUpdate($status, $customerName, $customerEmail, $orderItemsGroup, $subscriptionId){
	$subscription = wcs_get_subscription($subscriptionId);

	if($subscription->get_status() === $status){
		$subscriptionStatus = $status === "on-hold" ? "Subscription will be Paused Today:double_vertical_bar:" : "Subscription will be Cancelled Today:alert:";
		
		$slackMessageBody = [
			"text" => "<!channel> $subscriptionStatus \n*Client:* $customerName | $customerEmail\n*Plan:* $orderItemsGroup",
			"username" => "Marcus"
		];
	
		slackNotifications($slackMessageBody);
	}
}
add_action('scheduleSlackNotificationForSubscriptionStatusUpdateHook', 'scheduleSlackNotificationForSubscriptionStatusUpdate', 10, 5);



function wooNoticesMessageBasedOnProduct($subscription, $newStatus, $oldStatus){
	if(!is_admin()){
		if($newStatus == 'pending-cancel'){
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
}
add_action('woocommerce_subscription_status_updated', 'wooNoticesMessageBasedOnProduct', 10, 3);



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



function moveCancelledSubscriptionsToTrash($subscription){

    if ($subscription && 'cancelled' === $subscription->get_status()) {
        wp_trash_post($subscription->id);
    }
}
add_action('woocommerce_subscription_status_cancelled', 'moveCancelledSubscriptionsToTrash');



function renameSubscriptionStatus($subscription_statuses){
    $subscription_statuses['wc-on-hold']      = _x( 'Paused', 'Subscription status', 'woocommerce-subscriptions' );

    return $subscription_statuses;
}
add_filter( 'wcs_subscription_statuses', 'renameSubscriptionStatus');



function redirectUserToCheckoutAfterAddToCart( $url, $adding_to_cart ) {
    return wc_get_checkout_url();
}
add_filter ('woocommerce_add_to_cart_redirect', 'redirectUserToCheckoutAfterAddToCart', 10, 2 ); 



function updateCreativeCallsNumberBasedOnActiveSubscriptions($userId){
	$creativeCalls = 0;
	$userSubscriptions = wcs_get_users_subscriptions($userId);

	if($userSubscriptions){
		foreach($userSubscriptions as $subscription){
			if($subscription->get_status() === "active"){
				$subscriptionItems = $subscription->get_items();

				foreach( $subscriptionItems as $item_id => $item ){
					if(str_contains(strtolower($item->get_name()), 'call')){
						$creativeCalls = 1;
					}
					else if(str_contains(strtolower($item->get_name()), 'agency')){
						$creativeCalls = 4;
					}else{
						$creativeCalls = 0;
					}
				}
			}
		}
	}
	
	return $creativeCalls;
}



function prepareOrderDataToCreateTheUserGroupOnDataBase($entryId, $formData, $form){
	if($form->id === 3){
		$currentUser = wp_get_current_user();
		$groupName = preg_replace('/[^\w\s]/', '', $currentUser->billing_company);
		$groupName = strtolower(str_replace(' ', '_', $groupName));
		$companyName = $currentUser->billing_company;
		$creativeCalls = updateCreativeCallsNumberBasedOnActiveSubscriptions($currentUser->id);		

		createNewGroupAfterOnboarding($groupName, $companyName, $creativeCalls);
	}

}
add_action('fluentform/submission_inserted', 'prepareOrderDataToCreateTheUserGroupOnDataBase', 10, 3);



function zeroCreativeCallsOnRenewalFailed($subscription){
	global $wpdb;
	$user = get_user_by( 'id', $subscription->data['customer_id']);
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



function createNewGroupAfterOnboarding($groupName, $companyName, $creativeCalls) {
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



function formatSubscriptionStatusLabel($status, $isBillingDate = false){
	switch ($status){
		case 'on-hold':
			echo $isBillingDate ? 'paused' : 'pending-pause';
			break;
		case 'pending-cancel':
			echo $isBillingDate ? 'cancelled' : 'pending-cancel';
			break;

		default:
			echo $status;
	}
}
add_action('callNewSubscriptionsLabel', 'formatSubscriptionStatusLabel', 10, 2);


add_filter( 'wc_add_to_cart_message_html', '__return_false' );



function disableSubscriptionActions( $actions, $subscription ) {
	foreach ( $actions as $action_key => $action ) {
		switch ( $action_key ) {
			case 'change_payment_method':
				unset( $actions[ $action_key ] );
				break;
			default: 
				error_log( '-- $action = ' . print_r( $action, true ) );
				break;
		}
	}

	return $actions;
}
add_filter( 'wcs_view_subscription_actions', 'disableSubscriptionActions', 10, 2 );



function cancelActiveTasksByPausePlan($subscription, $newStatus, $oldStatus){
	$userSubscriptions = wcs_get_users_subscriptions(get_current_user_id());

	foreach($subscription->get_items() as $item){
		if(has_term( 'plan', 'product_cat', $item->get_product_id())){
			foreach ($userSubscriptions as $subs){		
				foreach ($subs->get_items() as $product) {			
					if ( !has_term( 'plan', 'product_cat', $product->get_product_id() ) ){
						if($newStatus === "on-hold" || $newStatus === "cancelled" || $newStatus === "pending-cancel"){
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
			$newSubject = "[$siteTitle]: Thanks for signing up! Here's your receipt.";

		}elseif(has_term('add-on', 'product_cat', $orderItem->get_product_id())){
			$newSubject = "[$siteTitle]: You've got a new Add on: $productName";

		}else{
			$newSubject = "[$siteTitle]: You've got an additional $productName";
		}
	}
    
    return $newSubject;
}
add_filter('woocommerce_email_subject_customer_completed_order', 'changeCompletedOrderEmailSubjectBasedOnProduct', 10, 2);



function chargeUserWhenReactivateSubscriptionAfterBillingDate($subscription){
	$renewalOrder = wcs_create_renewal_order($subscription);
	$paymentMethod = 'stripe';
	$renewalOrder->set_payment_method($paymentMethod);
	$renewalOrder->calculate_totals();
	
	do_action('woocommerce_order_action_wcs_retry_renewal_payment', $renewalOrder);

	wp_redirect(get_permalink( wc_get_page_id( 'myaccount' ) ) . 'subscriptions');
	exit;
}
add_action('chargeUserWhenReactivateSubscriptionAfterBillingDateHook', 'chargeUserWhenReactivateSubscriptionAfterBillingDate');



function showPaymentFailedNoticeToUserWhenReactivateSubscription($orderId){
	$accountDetailsUrl = get_permalink( wc_get_page_id( 'myaccount' ) ) . 'edit-account';
	if(isset($_GET['reactivate_plan'])){
		wc_add_notice("The plan was not reactivated because the payment has failed! Update your <a href='" . $accountDetailsUrl . "'>payment details</a> and try again.", 'error');
	}

}
add_action( 'woocommerce_order_status_failed', 'showPaymentFailedNoticeToUserWhenReactivateSubscription');



function calculateBillingEndingDateWhenPausedOrCancelled($subscription){
	
	if($subscription->get_status() === 'on-hold'){
		$newDateTime = new DateTime($subscription->get_date('next_payment'));
		$pausedPlanBillingPeriodEndingDate = $newDateTime->format('F d, Y');

	}else if($subscription->get_status() === 'pending-cancel'){
		$newDateTime = new DateTime($subscription->get_date('end'));
		$pausedPlanBillingPeriodEndingDate = $newDateTime->format('F d, Y');

	}else{
		$pausedPlanBillingPeriodEndingDate = 0;
	}

	return $pausedPlanBillingPeriodEndingDate;
}


function unserializedOnboardingFieldInUserProfilePage($user){
	$frequentRequests = get_the_author_meta('company_frequent_requests',$user->ID,true );
	$unserializedValue = unserialize($frequentRequests);
	$finalValue = $unserializedValue[0];

	echo "<script>
		document.querySelector('#frequent_requests input').value = '$finalValue'	
	</script>";
}

add_action('show_user_profile', 'unserializedOnboardingFieldInUserProfilePage');
add_action( 'edit_user_profile', 'unserializedOnboardingFieldInUserProfilePage' );




//TEAM MEMBERS FEATURE
function userCanAddMoreTeamMembers($numberOfTeamMembersFromForm){
	$userId = get_current_user_id();
	$userSubscriptions = wcs_get_users_subscriptions($userId );
	$groupsUser = new Groups_User( $userId  );
	$currentUserTeamMembers = [];
	$isCurrentUserCanAddMoreTeamMembers = false;

	foreach($groupsUser->groups as $group){
		if($group->name !== "Registered"){
			$groupId = $group->group_id;
			$group = new Groups_Group( $groupId );

			foreach($group->users as $groupUser){
				if(in_array('team_member', $groupUser->roles)){
					$currentUserTeamMembers[] = $groupUser;
				}
			}
		}
	}

	foreach ($userSubscriptions as $subscription){
		foreach ($subscription->get_items() as $product) {	
			if(has_term('plan', 'product_cat', $product['product_id'])){
				
				if(str_contains($product['name'], 'Business') && $numberOfTeamMembersFromForm + sizeof($currentUserTeamMembers) > 4 ){
					$isCurrentUserCanAddMoreTeamMembers = false;
				}else{
					$isCurrentUserCanAddMoreTeamMembers = true;
				}
			}		
		}
	}

	return $isCurrentUserCanAddMoreTeamMembers;
	
}



function createAdditionalUserBySubmitingForm($entryId, $formData, $form){
	if($form->id == 7){	
		$currentUser = wp_get_current_user();	
		$companyFreshdeskId = get_user_meta( get_current_user_id(), 'company_freshdesk_id', true );
		$companyWebsite = get_user_meta( get_current_user_id(), 'company_website', true );

		
		$userAdditionalData = [
			'url' => $companyWebsite,
			'job_title' => 'Team Member'
		];
		
		$additionalUsersAdded = [];

		$isUserCanAddMoreTeamMembers = userCanAddMoreTeamMembers(sizeof($formData['team_members_form']));

		if($isUserCanAddMoreTeamMembers){
			foreach($formData['team_members_form'] as $additionalUser){
				$additionalUserName = $additionalUser[0];
				$additionalUserEmail = $additionalUser[1];			
				$userAlreadyExists = get_user_by( 'email', $additionalUserEmail );
	
				if($userAlreadyExists){
					if(in_array('administrator', $userAlreadyExists->roles)){
						wc_add_notice("You can't add this user!", 'error');
	
					}else{
						$additionalUser = new WP_User($userAlreadyExists->id);
						$additionalUser->set_role('team_member');
						update_user_meta( $userAlreadyExists->id, 'is_user_onboarded', 1 );
						update_user_meta( $userAlreadyExists->id, 'is_first_access', 0 );
						update_user_meta( $userAlreadyExists->id, 'billing_company', $currentUser->billing_company );
						$additionalUsersAdded[] = "$additionalUserName ($additionalUserEmail)";
						addTeamMembersToCurrentUsersGroup($userAlreadyExists->id, $additionalUsersAdded);
						sendWelcomeEmailToAdditionalTeamMembers($additionalUserName, $additionalUserEmail, get_current_user_id());
						createTeamMemberInFreshDesk($currentUser, $additionalUser, $userAdditionalData, intval($companyFreshdeskId));
					}
	
				}else{
					$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					$passwordCharactersLength = 8;
					$newUserRandomPassword = substr(str_shuffle($characters), 0, $passwordCharactersLength);
					$newUserId = wp_create_user($additionalUserEmail, $newUserRandomPassword, $additionalUserEmail);
	
					if($newUserId){
						$additionalUser = new WP_User($newUserId);
						$additionalUser->set_role('team_member');
						wp_update_user(['ID' => $newUserId, 'first_name' => $additionalUserName]);
						update_user_meta( $newUserId, 'is_user_onboarded', 1 );
						update_user_meta( $newUserId, 'is_first_access', 0 );
						update_user_meta( $newUserId, 'billing_company', $currentUser->billing_company );
						$additionalUsersAdded[] = "$additionalUserName ($additionalUserEmail)";
						addTeamMembersToCurrentUsersGroup($newUserId, $additionalUsersAdded);
						sendWelcomeEmailToAdditionalTeamMembers($additionalUserName, $additionalUserEmail, get_current_user_id(), $newUserRandomPassword);
						createTeamMemberInFreshDesk($currentUser, $additionalUser, $userAdditionalData, intval($companyFreshdeskId));
					};
				}	
			}	
			
			if(!empty($additionalUsersAdded)){
				sendAdditionalusersNotificationToSlack($additionalUsersAdded);
				sendEmailToProductionWhenNewTeamMemberIsAdded(get_current_user_id(), $additionalUsersAdded);
				sendEmailToUserAboutAdditionalTeamMembers(get_current_user_id(), $additionalUsersAdded);
				wc_add_notice("The users " . implode(', ', $additionalUsersAdded) . "<br>were successfully added to your team!", 'success');
			}
		}else{
			wc_add_notice("Your additional team members limit is: 4. Upgrade your plan to add more!", 'error');
		}
	}	
}
add_action( 'fluentform/submission_inserted', 'createAdditionalUserBySubmitingForm', 10, 3 );



function addTeamMembersToCurrentUsersGroup($newUserId, $additionalUsersAdded){
	global $wpdb;
	$groupsUser = new Groups_User( get_current_user_id() );
	$tableName = _groups_get_tablename( 'group' );

	foreach($groupsUser->groups as $group){
		if($group->name !== "Registered"){
			$groupId = $group->group_id;
			$groupName = $group->name;
		}
	}


	$existingRow = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $tableName WHERE name = %s",
			$groupName,
		)
	);

	if($existingRow){
		Groups_User_Group::create( array( 'user_id' => $newUserId, 'group_id' => $groupId ) );
	}
}



function sendAdditionalusersNotificationToSlack($additionalUsersAdded){
	$slackWebHookUrl = site_url() === 'https://dash.deerdesigner.com' ? SLACK_CLIENT_MANAGEMENT_WEBHOOK_URL : SLACK_WEBHOOK_URL;
	$accountOwner = wp_get_current_user();
	$companyName = get_user_meta(get_current_user_id(), 'billing_company', true);

	$slackMessageBody = [
			'text'  => '<!channel> A client just added new team members to their account:  ' . '
	*Owner:* ' . $accountOwner->first_name . ' | ' . $accountOwner->user_email . " ($companyName)" . '
	*Team Members:* ' . implode(', ', $additionalUsersAdded),
			'username' => 'Marcus',
		];


	slackNotifications($slackMessageBody, $slackWebHookUrl);
}



function removeAdditionalUserFromDatabase($userId){
	$slackWebHookUrl = site_url() === 'https://dash.deerdesigner.com' ? SLACK_CLIENT_MANAGEMENT_WEBHOOK_URL : SLACK_WEBHOOK_URL;
	$userToBeDeleted = get_user_by( 'id', $userId);
	$freshdeskUserId = get_user_meta($userToBeDeleted->id, 'contact_freshdesk_id', true);
	$accountOwner = wp_get_current_user();
	$companyName = get_user_meta(get_current_user_id(), 'billing_company', true);
	$requestBody = [
		"custom_fields" => [
			"registered_user" => false,
			"paused" => false,
			"cancelled" => true
		]
	];

	if(in_array('administrator', $userToBeDeleted->roles)){
		wc_add_notice("You can't remove this user!", 'error');
	}else{
		wc_add_notice("The user was successfully removed from your account!", 'success');
		

		$slackMessageBody = [
			'text'  => '<!channel> A client just removed a team member from their account:  ' . '
	*Owner:* ' . $accountOwner->first_name . ' | ' . $accountOwner->user_email . " ($companyName)" . '
	*Team Member:* ' . $userToBeDeleted->first_name . " ($userToBeDeleted->user_email)" ,
			'username' => 'Marcus',
		];

		slackNotifications($slackMessageBody, $slackWebHookUrl);
		putRequestToFreshdesk($freshdeskUserId, $requestBody);
		wp_delete_user($userId);

	}

	wp_redirect(get_permalink(wc_get_page_id('myaccount')) . "edit-account");
	exit;
}

add_action('removeAdditionalUserFromDatabaseHook', 'removeAdditionalUserFromDatabase');




function sendNotificationToSlackWhenOrderChangeFromFailedToProcessing($orderId, $oldStatus, $newStatus, $order){
	if($newStatus === "completed"){
		if(wcs_order_contains_renewal($orderId)){
			$orderFailedBefore = false;
			$orderNotes = wc_get_order_notes(array(
				'order_id' => $orderId,
				'type' => 'system_status_change',
				'orderby' => 'date_created',
				'order' => 'DESC',
			));

			foreach ($orderNotes as $orderNote) {
				$notificationFinalMsg = $orderNote->content;
				if(str_contains($orderNote->content, 'Failed')){
					$orderFailedBefore = true;
					break;
				}
			}

			if($orderFailedBefore){
				$orderData = $order->get_data();
				$orderItems = $order->get_items();
				$orderItemsGroup = [];
				$productType = "";
				$notificationFinalMsg = 'Keep the work going.';
	
				foreach( $orderItems as $item_id => $item ){
					$itemName = $item->get_name();
					$orderItemsGroup[] = $itemName;
	
					if(has_term('active-task', 'product_cat', $item->get_product_id())){
						$productType = 'Product';
					}else if(has_term('add-on', 'product_cat', $item->get_product_id())){
						$productType = 'Add on';
					}else{
						$productType = 'Plan';
					}
				}
	
				$customerName = $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'];
				$customerEmail = $orderData['billing']['email'];
				$orderItemsGroup = implode(" | ", $orderItemsGroup);
	
				$slackMessageBody = [
					"text" => 
					"Payment was resolved, <!channel> :smiling_face_with_3_hearts:\n*Client:* $customerName | $customerEmail\n*$productType:* $orderItemsGroup\n$notificationFinalMsg",
	
					"username" => "Marcus"
				];
	
				slackNotifications($slackMessageBody);
			}
		}		
	}
}
add_action('woocommerce_order_status_changed', 'sendNotificationToSlackWhenOrderChangeFromFailedToProcessing', 10, 4);



function redirectUserToCheckoutIfHasFailedOrderOnFirstAccess(){
	if(is_user_logged_in() && is_page('onboarding')){
		$currentUserId = get_current_user_id();
		$isFirstAccess = get_user_meta($currentUserId, 'is_first_access', true);
		$mostRecentOrder = wc_get_orders([   
			'customer_id' => $currentUserId,
    		'limit' => 1]
		);

		if($mostRecentOrder){
			$orderStatus = $mostRecentOrder[0]->get_status();
			$orderKey = $mostRecentOrder[0]->get_order_key();
			$paymentUrl = wc_get_checkout_url() . 'order-pay/' . $mostRecentOrder[0]->id . '/?pay_for_order=true&key=' . $orderKey;
	
	
			if($isFirstAccess && $orderStatus !== 'completed'){
				wp_redirect($paymentUrl);
				exit;
			}
		}
	}
}
add_action('template_redirect', 'redirectUserToCheckoutIfHasFailedOrderOnFirstAccess');



function customEmailExistsMsg($msg, $email) {
    $loginUrl = site_url();
    $customMessage = "An account is already registered with this email ($email). <a href='$loginUrl'>Please log in</a> or use a different email address.";

    return $customMessage;
}
add_filter('woocommerce_registration_error_email_exists', 'customEmailExistsMsg', 10, 2);



function woocommerceNewCustomerDataSetRole( $customer_data ){
	$customer_data['role'] = 'subscriber';
	return $customer_data;
}
add_filter( 'woocommerce_new_customer_data', 'woocommerceNewCustomerDataSetRole' );


function getOrderPaymentDate($subscription){
	$subscriptionRelatedOrders = $subscription->get_related_orders();
	$lastOrderPaidDate = "";

	if($subscriptionRelatedOrders){
        $lastOrderId = array_key_first($subscriptionRelatedOrders);
        $lastOrderStatus = wc_get_order($lastOrderId)->get_status();
        $lastOrderPaidDate = wc_get_order($lastOrderId)->get_date_paid();

		if($lastOrderPaidDate){
			$lastOrderPaidDate = $lastOrderPaidDate->date('F d, Y');
		}
    }

	return $lastOrderPaidDate;
}




function sendNotificationToSlackAfterCSATFormSubmitted($entryId, $formData, $form){
	if($form->id == 5){
		$slackWebHookUrl = site_url() === 'https://dash.deerdesigner.com' ? SLACK_CSAT_WEBHOOK_URL : SLACK_WEBHOOK_URL;
		$companyName = $formData['hidden_company_name'];
		$ticketNumber = $formData['hidden_ticket_number'];

		function changeSlackIconBasedOnFeedback($ratingType){
			switch ($ratingType){
				case "Perfect":
					return ":large_green_circle:";
				
				case "Good":
					return ":large_yellow_circle:";

				default:
					return ":red_circle:";
			}
		}
		
		$ratingsNumberOne = $formData['csat_form_communication'] . " " . changeSlackIconBasedOnFeedback($formData['csat_form_communication']);
		$ratingsNumberTwo = $formData['csat_form_satisfaction'] . " " . changeSlackIconBasedOnFeedback($formData['csat_form_satisfaction']);
		$ratingsNumberThree = $formData['csat_form_time'] . " " . changeSlackIconBasedOnFeedback($formData['csat_form_time']);
		$feedback = $formData['description'];

		$ratings = [$ratingsNumberOne, $ratingsNumberTwo, $ratingsNumberThree];
		$notificationIcon = ":pencil:";

		if(in_array('Bad', $ratings)){
			$notificationIcon = ":alert:";
		}
		
		$slackMessageBody = [
			"text" => "<!channel>\n *CSAT Feedback* $notificationIcon\n *Company:* $companyName\n *Ticket Number:* $ticketNumber\n\n *Ratings:*\n • How was the team's communication: $ratingsNumberOne\n • Are you happy with the designs you received: $ratingsNumberTwo\n • The turnaround time met your expectations: $ratingsNumberThree\n\n*Feedback:*\n $feedback",
			"username" => "Marcus",
		];

		slackNotifications($slackMessageBody, $slackWebHookUrl);
	}
}
add_action( 'fluentform/submission_inserted', 'sendNotificationToSlackAfterCSATFormSubmitted', 10, 3);




function schedulePauseSubscriptionNotificationAfterPaymentFailed($orderId, $oldStatus, $newStatus, $order){
	if(wcs_order_contains_renewal($orderId) && $newStatus === "failed"){
		$orderFailedBefore = 0;
		$orderNotes = wc_get_order_notes(array(
			'order_id' => $orderId,
			'type' => 'system_status_change',
			'orderby' => 'date_created',
			'order' => 'DESC',
		));
	
		foreach ($orderNotes as $orderNote) {
			if(str_contains($orderNote->content, 'Order status changed from Pending payment to Failed.')){
				$orderFailedBefore = ++$orderFailedBefore;
			}
		}

		if($orderFailedBefore === 3){
			sendPauseNotificationAfterThreeFailedPaymentAtemptsOnRenewal(wcs_get_subscriptions_for_order($orderId, array('order_type' => 'any')));
		}
	}
}
add_action( 'woocommerce_order_status_changed', 'schedulePauseSubscriptionNotificationAfterPaymentFailed', 10, 4);



function sendPauseNotificationAfterThreeFailedPaymentAtemptsOnRenewal($orderSubscriptions){
	$subscription = $orderSubscriptions[array_key_first($orderSubscriptions)];

	if($subscription->get_status() === "on-hold"){
		$user = get_user_by('id', $subscription->data['customer_id']);
		$customerName = "$user->first_name $user->last_name";
		$customerEmail = $user->user_email;
		
		foreach( $subscription->get_items() as $item_id => $item ){
			$itemName = $item->get_name();
			$orderItems[] = $itemName;
		}

		$productNames = implode(" | ", array_unique($orderItems));

		$slackMessageBody = [
			"text" => "<!channel> Paused Subscription :double_vertical_bar:\n$customerName | $customerEmail\n:arrow_right: Subscription paused due to payment failed.\n *Plan:* $productNames.",
			"username" => "Marcus"
		];

		slackNotifications($slackMessageBody);
	}
}



function populateContactFormHiddenFieldsWithUserMeta($form){
	if($form->id == 8){
		$currentUser = wp_get_current_user();
		$companyName = $currentUser->billing_company;

		echo "<script>
			document.addEventListener('DOMContentLoaded', function(){
				document.querySelector('[data-name=\"hidden_company\"]').value='$companyName'
			})
		</script>";
	}
}
add_action('fluentform/after_form_render', 'populateContactFormHiddenFieldsWithUserMeta');



function deleteCancellationWarningAfterSixMonthsHookFromCronJobs($subscription){
	$sixMonthsAheadFormatedDate = get_post_meta($subscription->id, 'six_months_after_last_pause', true);
	wp_clear_scheduled_hook('cancellationWarningAfterSixMonthsHook', array($subscription->id, $sixMonthsAheadFormatedDate));
}
add_action('woocommerce_subscription_status_active', 'deleteCancellationWarningAfterSixMonthsHookFromCronJobs');