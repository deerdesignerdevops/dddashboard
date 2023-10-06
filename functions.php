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
	wp_enqueue_script('jquery', get_stylesheet_directory_uri() . '/libs/jquery/jquery.js', $version);
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



function addFirstAccessUserMetaToNewUsers($user_id) { 
   add_user_meta( $user_id, 'isFirstAccess', 1 );
}
add_action( 'user_register', 'addFirstAccessUserMetaToNewUsers');



//***************CUSTOM CODES FOR WOOCOMMERCE

//function removeWooMenuLinks( $menu_links ){	
// 	$menu_links[ 'subscriptions' ] = 'Billing Portal';
// 	unset( $menu_links[ 'dashboard' ] );
// 	unset( $menu_links[ 'customer-logout' ] );
// 	unset( $menu_links[ 'orders' ] );
// 	unset( $menu_links[ 'downloads' ] );
// 	unset( $menu_links[ 'edit-address' ] );
// 	unset( $menu_links[ 'edit-account' ] );
// 	unset( $menu_links[ 'subscriptions' ] );
// 	return $menu_links;	
// }
// add_filter( 'woocommerce_account_menu_items', 'removeWooMenuLinks' );


// function createNewLinksInWooDashMenu( $menu_links ){
// 	$new = array( 
// 		'request-design' => 'Request a Design', 
// 		'view-tickets' => 'View my Tickets',
// 		'design-brief-checklist' => 'Design Brief Checklist',
// 		'feedback' => 'Give Feedback',
// 		'deer-insights' => 'Deer Insights',
// 		'deer-help' => 'Need Help?',
// 		'subscriptions' => 'Billing Portal'
// 	);

// 	//$menu_links = $new + array_slice( $menu_links, 0, 8, true ) + array_slice( $menu_links, 1, 8, true );

// 	return $new;
// }
// add_filter ( 'woocommerce_account_menu_items', 'createNewLinksInWooDashMenu' );


// function customWooEndpointUrl( $url, $endpoint ){ 
// 	if( 'request-design' === $endpoint ) { 
// 		$url = 'https://deerdesigner.freshdesk.com/support/tickets/new';
// 	}

// 	else if( 'view-tickets' === $endpoint ) { 
// 		$url = 'https://deerdesigner.freshdesk.com/support/tickets'; 
// 	}

// 	else if( 'design-brief-checklist' === $endpoint ) { 
// 		$url = 'https://deerdesigner.box.com/v/design-brief-questions'; 
// 	}

// 	else if( 'feedback' === $endpoint ) { 
// 		$url = 'https://feedback.deerdesigner.com/'; 
// 	}

// 	else if( 'deer-insights' === $endpoint ) { 
// 		$url = 'https://deerdesigner.com/deer-insights/'; 
// 	}

// 	else if( 'deer-help' === $endpoint ) { 
// 		$url = 'https://help.deerdesigner.com/'; 
// 	}
// 	return $url; 
// }
// add_filter( 'woocommerce_get_endpoint_url', 'customWooEndpointUrl', 10, 4 );

// function filter_wc_stripe_payment_metadata($metadata, $order) {
// 	$order_data = $order->get_data();
	
// 	$metadata += ['first_name' => $order_data['billing']['first_name']];
// 	$metadata += ['last_name' => $order_data['billing']['last_name']];
// 	//$metadata += ['billing_company' => $order_data['billing']['company']];
// 	//$metadata += ['billing_phone' => $order_data['billing']['phone']];
// 	$metadata += ['billing_address_1' => $order_data['billing']['address_1']];
// 	//$metadata += ['billing_address_2' => $order_data['billing']['address_2']];
// 	//$metadata += ['billing_city' => $order_data['billing']['city']];
// 	//$metadata += ['billing_state' => $order_data['billing']['state']];
// 	$metadata += ['billing_country' => $order_data['billing']['country']];
// 	//$metadata += ['billing_postcode' => $order_data['billing']['postcode']];

//     return $metadata;
// }
// add_filter('wc_stripe_payment_metadata', 'filter_wc_stripe_payment_metadata', 10, 2);

// function custom_modify_stripe_customer_metadata($metadata) {
// 	$order_id = WC()->session->get('order_awaiting_payment');
// 	$order = new WC_Order($order_id);

//     $metadata['first_name'] = $order->get_billing_first_name();
// 	$metadata['last_name'] = $order->get_billing_last_name();
// 	//$metadata['billing_company'] = $order->get_billing_company();
// 	//$metadata['billing_phone'] = $order->get_billing_phone();
// 	$metadata['billing_address_1'] = $order->get_billing_address_1();
// 	//$metadata['billing_address_2'] = $order->get_billing_address_2();
// 	//$metadata['billing_city'] = $order->get_billing_city();
// 	//$metadata['billing_state'] = $order->get_billing_state();
// 	$metadata['billing_country'] = $order->get_billing_country();
// 	//$metadata['billing_postcode'] = $order->get_billing_postcode();

//     return $metadata;
// };
// add_filter('wc_stripe_customer_metadata', 'custom_modify_stripe_customer_metadata', 10, 1);

// function removeCheckoutFields( $fields ) {
// 	unset( $fields['billing']['billing_company'] );
// 	unset( $fields['billing']['billing_phone'] );
// 	unset( $fields['billing']['billing_state'] );
// 	unset( $fields['billing']['billing_address_2'] );
// 	unset( $fields['billing']['billing_city'] );
// 	unset( $fields['billing']['billing_postcode'] );
// 	unset( $fields['order']['order_comments'] );
// 	// unset( $fields['billing']['billing_email'] );
// 	// unset( $fields['billing']['billing_first_name'] );
// 	// unset( $fields['billing']['billing_last_name'] );
// 	// unset( $fields['billing']['billing_address_1'] );
// 	return $fields;
// }
// add_filter( 'woocommerce_checkout_fields', 'removeCheckoutFields' );

// function customThankyouPage( $order_id ) {
// 	$siteUrl = get_site_url();
// 	$order = wc_get_order( $order_id );
// 	if ( $order->get_billing_email() ) {
// 		wp_redirect( "$siteUrl/thanks" );
// 		exit;
// 	}
// }
// add_action( 'woocommerce_thankyou', 'customThankyouPage' );


// function changeOrderStatusToCompleteAfterPayment( $order_id ) {
//     $order = wc_get_order( $order_id );
//     $order->update_status( 'completed' );    
// }
// add_action( 'woocommerce_payment_complete', 'changeOrderStatusToCompleteAfterPayment' );






