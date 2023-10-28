<?php
/**
 * My Subscriptions section on the My Account page
 *
 * @author   Prospress
 * @category WooCommerce Subscriptions/Templates
 * @version  1.0.0 - Migrated from WooCommerce Subscriptions v2.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$siteUrl = site_url();
$activeSubscriptionsGroups = [];
$dates_to_display = apply_filters( 'wcs_subscription_details_table_dates_to_display', array(
	'start_date'              => _x( 'Start date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'last_order_date_created' => _x( 'Last order date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'next_payment'            => _x( 'Next payment date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'end'                     => _x( 'End date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'trial_end'               => _x( 'Trial end date', 'customer subscription table header', 'woocommerce-subscriptions' ),
), $subscription );
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
	.welcome-h1, .dash__menu, .woocommerce-MyAccount-navigation{
		display: none !important;
	}
</style>



<section class="dd__bililng_portal_section">
    <div style="max-width: 1140px; margin: auto">
        <a href="/" class="dd__bililng_portal_back"><i class="fa-solid fa-chevron-left"></i> Back to Dashboard</a>
        <h1 class="myaccount__page_title">Billing Portal</h1>

		<div class="woocommerce_account_subscriptions">
			<?php if ( ! empty( $subscriptions ) ) : ?>
				<div class="dd__subscriptions_sumary">            
					<div class="dd__subscription_details">  
						<h2>You have:</h2>   
						<?php
							foreach($subscriptions as $subscriptionItem){ 
								if($subscriptionItem->has_status( 'active' )){
									foreach($subscriptionItem->get_items() as $item_id => $item){
										array_push($activeSubscriptionsGroups, $item['name']);
									}
								}
							}

							foreach(array_unique($activeSubscriptionsGroups) as $activeSubscriptionsGroupsItem){ 
								$subscriptionItemCount = array_count_values($activeSubscriptionsGroups)[$activeSubscriptionsGroupsItem];
							?>
								<span class="dd__subscriptions_sumary_name"><?php echo $activeSubscriptionsGroupsItem; ?> <strong><?php echo $subscriptionItemCount; ?></strong></span>

							<?php }
						?>
						
						<div class="dd__subscriptions_buttons_wrapper" style="margin-top: 20px;">
							<a href="<?php echo $siteUrl; ?>/?add-to-cart=928" class="dd__add_designer_btn">Add a Designer</a>
						</div>
					</div>
				</div>



			<?php /** @var WC_Subscription $subscription */ ?>
			<?php foreach ( $subscriptions as $subscription_id => $subscription ) : ?>
				
				<div class="dd__subscription_card">
					<div class="dd__subscription_details">                        

						<div class="dd__subscription_header">
							<span class="dd__subscription_id <?php echo esc_attr( $subscription->get_status() ); ?>"><?php echo "Subscription ID: $subscription->id"; ?> | <strong><?php echo esc_attr( $subscription->get_status() ); ?></strong></span>
						</div>

						<?php foreach ( $subscription->get_items() as $item_id => $item ){ ?>
							<span class="dd__subscription_title"><?php echo $item['name']; ?></span>
						<?php } ?>
						<span class="dd__subscription_price"><?php echo wp_kses_post( $subscription->get_formatted_order_total() ); ?></span>

						<?php foreach ( $dates_to_display as $date_type => $date_title ) : ?>
							<?php $date = $subscription->get_date( $date_type ); ?>
							<?php if ( ! empty( $date ) ) : ?>
								<span class="dd__subscription_payment"><?php echo esc_html( $date_title ); ?>: <?php echo esc_html( $subscription->get_date_to_display( $date_type ) ); ?></span>							
							<?php endif; ?>
						<?php endforeach; ?>
					</div>

					<div class="dd__subscription_actions_form">
						<?php if($subscription->get_status() === "active"){ ?>
							<?php do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $subscription, false ); ?>
						<?php } ?>
						
						<?php $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); ?>
						<?php if ( ! empty( $actions ) ) : ?>

							<div class="dd__subscriptions_buttons_wrapper">						
								<?php foreach ( $actions as $key => $action ) : ?>
									<a href="<?php echo esc_url( $action['url'] ); ?>" class="dd__subscription_cancel_btn <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?></a>
								<?php endforeach; ?>
							</div>
						
					
						<?php endif; ?>
					</div>

					
				</div>
			<?php endforeach; ?>
				
			<?php else : ?>
				<p class="no_subscriptions woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
					<?php if ( 1 < $current_page ) :
						printf( esc_html__( 'You have reached the end of subscriptions. Go to the %sfirst page%s.', 'woocommerce-subscriptions' ), '<a href="' . esc_url( wc_get_endpoint_url( 'subscriptions', 1 ) ) . '">', '</a>' );
					else :
						esc_html_e( 'You have no active subscriptions.', 'woocommerce-subscriptions' );
						?>
						<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
							<?php esc_html_e( 'Browse products', 'woocommerce-subscriptions' ); ?>
						</a>
					<?php
				endif; ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</section>

<script>
const subscriptionsActionssBtns = Array.from(document.querySelectorAll('.dd__subscription_actions_form a'));
subscriptionsActionssBtns.map((btn) => {
	btn.addEventListener("click", function(e){
		e.preventDefault();
		if (confirm("Are you sure?") == true) {
			location.href = e.currentTarget.href
		} else {
			return false;
		};
	})
})


</script>
