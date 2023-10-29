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

<div class="dd__loading_screen">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
</div>



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
							<a href="https://deerdesigner.com/pricing" class="dd__add_designer_btn">Add a Designer</a>
						</div>
					</div>
				</div>



			<?php /** @var WC_Subscription $subscription */ ?>
			<?php foreach ( $subscriptions as $subscription_id => $subscription ) :
				//print_r($subscription);
				$switchVariationID = 0;
				?>
				
				
				<div class="dd__subscription_card <?php echo esc_attr( $subscription->get_status() ); ?>">
					<div class="dd__subscription_details">                        

						<div class="dd__subscription_header">
							<span class="dd__subscription_id <?php echo esc_attr( $subscription->get_status() ); ?>"><?php echo "Subscription ID: $subscription->id"; ?> | <strong><?php echo esc_attr( $subscription->get_status() ); ?></strong></span>
						</div>

						<?php foreach ( $subscription->get_items() as $item_id => $item ){ 
							$switchVariationID = $item['variation_id'];
							?>
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
						<?php if($subscription->get_status() === "active" || $subscription->get_status() !== "cancelled" ){ ?>

						<?php do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $subscription, false ); ?>
						<?php $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); ?>
								<?php if ( ! empty( $actions ) ) { ?>
									<div class="dd__subscriptions_buttons_wrapper">						
										<?php foreach ( $actions as $key => $action ) : ?>
											<a href="<?php echo esc_url( $action['url'] ); ?>" class="dd__subscription_cancel_btn <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?></a>
										<?php endforeach; ?>
									</div>
								<?php };
						 } ?>
					</div>
				</div>
			<?php endforeach; ?>
				
			<?php else : ?>
				<div class="dd__subscription_card"> 
					<div class="dd__subscription_details">
						<span class="dd__subscription_warning">You have no active subscriptions!</span>
					</div>

					<div class="dd__subscription_actions_form">
						<a href="https://deerdesigner.com/pricing" class="dd__add_designer_btn">See Pricing</a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<script>
const subscriptionsActionssBtns = Array.from(document.querySelectorAll('.dd__subscription_actions_form a'));
const loadingSpinner = document.querySelector(".dd__loading_screen");
subscriptionsActionssBtns.map((btn) => {
	btn.addEventListener("click", function(e){
		e.preventDefault();
		if (confirm("Are you sure?") == true) {
			loadingSpinner.style.display = "flex";
			location.href = e.currentTarget.href
		} else {
			return false;
		};
	})
})


</script>
