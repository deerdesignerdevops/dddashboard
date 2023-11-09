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
$activeSubscriptionsGroup = [];
$allSubscriptionsGroup = [];

echo "TEST GITHUB";
function defineAddDesignerLinkProductID($parentProducts){;
	foreach($parentProducts as $parentProduct){
		if(strpos($parentProduct, 'Business') !== false){
			return '1134';
		}else if(strpos($parentProduct, 'Agency') !== false){
			return '1141';
		}else{
			return '1130';
		}
	}
}

$userCurrentAddons = [];

$allProductAddons = wc_get_products(['category' => get_term_by('slug', 'add-on', 'product_cat')->slug]);


$dates_to_display = apply_filters( 'wcs_subscription_details_table_dates_to_display', array(
	'start_date'              => _x( 'Start date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'last_order_date_created' => _x( 'Last order date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'next_payment'            => _x( 'Next payment date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'end'                     => _x( 'End date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'trial_end'               => _x( 'Trial end date', 'customer subscription table header', 'woocommerce-subscriptions' ),
) );

?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />


<div class="dd__loading_screen">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
</div>


<?php if($_GET["change-your-plan"]){ wc_print_notice('Your request to switch plan has been sent. We\'ll get in touch soon!'); } ?>

<section class="dd__bililng_portal_section">
    <div style="max-width: 1140px; margin: auto">
        <a href="/" class="dd__bililng_portal_back"><i class="fa-solid fa-chevron-left"></i> Back to Dashboard</a>
        <h1 class="myaccount__page_title">Billing Portal</h1>

		<div class="woocommerce_account_subscriptions">
			<?php if ( ! empty( $subscriptions ) ) : ?>
				<div class="dd__subscriptions_sumary">            
					<div class="dd__subscription_details">				
						<?php
							foreach($subscriptions as $subscriptionItem){ 
								foreach($subscriptionItem->get_items() as $item_id => $item){
									array_push($allSubscriptionsGroup, $item['name']);
									
									foreach($allProductAddons as $productAddon){
										if($productAddon->id === $item["product_id"]){
											array_push($userCurrentAddons, $productAddon->id);
										}
									}
								}	

								if($subscriptionItem->has_status( 'active' )){
									foreach($subscriptionItem->get_items() as $item_id => $item){
										array_push($activeSubscriptionsGroup, $item['name']);
									}
								}
								
							}

							if(sizeof($activeSubscriptionsGroup) > 0){ ?>
								<h2 class="cart__header__title">You have:</h2>

								<?php foreach(array_unique($activeSubscriptionsGroup) as $activeSubscriptionsGroupItem){ 
									$subscriptionItemCount = array_count_values($activeSubscriptionsGroup)[$activeSubscriptionsGroupItem];
								?>
									<span class="dd__subscriptions_sumary_name"><?php echo $activeSubscriptionsGroupItem; ?> <strong><?php echo $subscriptionItemCount; ?></strong></span>
								<?php } ?>
						
								<div class="dd__subscriptions_buttons_wrapper" style="margin-top: 20px;">
									<a href="<?php echo $siteUrl; ?>/?add-to-cart=<?php echo defineAddDesignerLinkProductID($allSubscriptionsGroup); ?>" class="dd__add_designer_btn">Add a Designer</a>
								</div>
								
							<?php }else{ ?>
								<div class="dd__subscription_card"> 
									<div class="dd__subscription_details">
										<span class="dd__subscription_warning">You have no active subscriptions!</span>
									</div>

									<div class="dd__subscription_actions_form">
										<a href="https://deerdesigner.com/pricing" class="dd__add_designer_btn">See Pricing</a>
									</div>
								</div>
							<?php } ?>
				</div>

				<div class="subscriptions__addons_wrapper">
					<div class="cart__addons">
						<?php if(sizeof($allProductAddons) > 0){ ?>
							<h2 class="cart__header__title">Available Addons for you</h2>
						<?php } ?>

						<form action="" method="post" enctype="multipart/form-data" class="addons__carousel_form">								
							<?php
								foreach($allProductAddons as $addon){			
									if(!in_array($addon->id, $userCurrentAddons)){ ?>
										<div class="addon__card">
											<div class="addon__card_info">
												<?php echo get_the_post_thumbnail( $addon->id ); ?>
												<span class="addon__title"><?php echo $addon->name; ?></span><br>
												<span class="addon__title"><?php echo get_woocommerce_currency_symbol() . "$addon->price / "; do_action('callAddonsPeriod', $addon->name); ?></span>
												<div class="addon__description">
													<?php echo $addon->description; ?>
												</div>
											</div>
											<button type="submit" class="single_add_to_cart_button button alt" name="add-to-cart" value="<?php echo $addon->id; ?>"><?php echo $addon->name; ?></button>
										</div>	
									<?php } ?>															
								<?php } ?>
						</form>
					</div>
				</div>
		</div>



			<?php /** @var WC_Subscription $subscription */ //print_r($subscriptions[0]->get_items()); ?>
			<?php foreach ( $subscriptions as $subscription_id => $subscription ) : ?>
				
				<?php if($subscription->get_status() !== "cancelled"){ ?>				
					<div class="dd__subscription_card <?php echo esc_attr( $subscription->get_status() ); ?>">
						<div class="dd__subscription_details">                        

							<div class="dd__subscription_header">
								<span class="dd__subscription_id <?php echo esc_attr( $subscription->get_status() ); ?>"><?php echo "Subscription ID: $subscription->id"; ?> | <strong><?php echo $subscription->get_status() === "pending-cancel" ? 'pending-cancellation' : $subscription->get_status(); ?></strong></span>
							</div>

							<?php foreach ( $subscription->get_items() as $item_id => $item ){ ?>
						
								<span class="dd__subscription_title">														
									<?php if(sizeof($subscription->get_items()) > 1) { ?>
											<span class="remove_item">
												<?php if ( wcs_can_item_be_removed( $item, $subscription ) ) : ?>
													<?php $confirm_notice = apply_filters( 'woocommerce_subscriptions_order_item_remove_confirmation_text', __( 'Are you sure you want remove this item from your subscription?', 'woocommerce-subscriptions' ), $item, $_product, $subscription );?>
													<a href="<?php echo esc_url( WCS_Remove_Item::get_remove_url( $subscription->get_id(), $item_id ) );?>" class="remove" onclick="return confirm('<?php printf( esc_html( $confirm_notice ) ); ?>');">&times;</a>
												<?php endif; ?>
											</span>
									<?php } ?>
									<?php echo $item['name']; ?>
							</span>
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
							<?php if($subscription->get_status() === "active" && !in_array($item["product_id"], $userCurrentAddons)){ ?>
								<a href="<?php echo $siteUrl; ?>/subscriptions/?change-your-plan=true" data-plan="<?php echo $item['name']; ?>" data-subscription-id="<?php echo $subscription->id; ?>" class="dd__add_designer_btn">Change Plan</a>	
							<?php } ?>

							<?php do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $subscription, false ); ?>
							<?php $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); ?>
									<?php if ( ! empty( $actions ) ) { ?>
										<div class="dd__subscriptions_buttons_wrapper">						
											<?php foreach ( $actions as $key => $action ) : ?>
												<a href="<?php echo esc_url( $action['url'] ); ?>" data-plan="<?php echo $item['name']; ?>" data-subscription-id="<?php echo $subscription->id; ?>" class="dd__subscription_cancel_btn <?php echo str_replace(' ', '-', strtolower($item['name']));  ?> <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?></a>
											<?php endforeach; ?>
										</div>
									<?php }; ?>
						</div>
					</div>
				<?php } ?>
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


<style>
	.welcome-h1, .dash__menu, .woocommerce-MyAccount-navigation{
		display: none !important;
	}

	.suspend{
		display: <?php echo sizeof($activeSubscriptionsGroup) > 1 ? 'block' : 'none';  ?>;
	}

	.form_subscription_update_disclaimer{
		display: <?php echo sizeof($activeSubscriptionsGroup) > 1 ? 'none' : 'block';  ?>;
	}

	.premium-stock-photos.suspend {
		display: none;
	}
</style>


<?php echo do_shortcode('[elementor-template id="1201"]'); ?>

<script>
//THIS SCRIPT STOP THE DEFUALT WOOCOMMERCE REDIRECT FOR THE SUBSCRIPTION ACTIONS AND ASK THE USER IN ORDER TO PREVENT ACCIDENTAL CANCELLATIONS
//IT CHANGES THE POPUP TEXT AND LINK BASED ON THE BUTTON CLICKED

document.addEventListener("DOMContentLoaded", function(){
	const subscriptionsActionssBtns = Array.from(document.querySelectorAll(".dd__subscription_actions_form a"));
	const loadingSpinner = document.querySelector(".dd__loading_screen");
	
	

	subscriptionsActionssBtns.map((btn) => {
		btn.addEventListener("click", function(e){
			e.preventDefault();
			let popupID = 2776//1201
			let popupMsgNewText = ""
	

			elementorProFrontend.modules.popup.showPopup( {id:popupID}, event);

			document.querySelector(".update_plan_form form").elements["btn_keep"].addEventListener("click", function(e){
				e.preventDefault()
				elementorProFrontend.modules.popup.closePopup( {}, event);
			})

			if(e.currentTarget.classList.contains("suspend")){
				popupMsgNewText = "ARE YOU SURE YOU WANT TO <br><span>PAUSE THIS PLAN?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerText = "	When you pause your subscription, we'll keep your designs, tickets and communication saved until you reactivate your account. Your design team is still available until the end of your current billing period."
			}
			else if(e.currentTarget.classList.contains("reactivate")){
				popupMsgNewText = "REACTIVATE <span>THIS SUBSCRIPTION?</span>";
				document.querySelector(".form_subscription_update_disclaimer").style.display = "none"
			}
			else if(e.currentTarget.classList.contains("cancel")){
				popupMsgNewText = "ARE YOU SURE YOU WANT TO <br><span>CANCEL THIS PLAN?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerText = "When you cancel your subscription, you'll lose access to all your designs, tickets and communication. Your design team is still available until the end of your current billing period."
				document.querySelector(".update_plan_form form button").innerText = "Cancel Subscription"
				document.querySelector(".popup_buttons").style.display = "none"
				document.querySelector(".update_plan_form").classList.add("show_form")
				document.querySelector(".update_plan_form form").elements['form_subscription_plan'].value = e.currentTarget.dataset.plan
				document.querySelector(".update_plan_form form").elements['form_subscription_update_url'].value = e.currentTarget.href
				document.querySelector(".update_plan_form form").elements['subscription_url'].value = `<?php echo $siteUrl; ?>/wp-admin/post.php?post=${e.currentTarget.dataset.subscriptionId}&action=edit`
			}
			else{
				popupMsgNewText = "Which plan would you<br> <span>like to switch to?</span>";
				document.querySelector(".popup_buttons").style.display = "none"
				document.querySelector(".update_plan_form").classList.add("show_form")
				document.querySelector(".form_subscription_update_message_field label").innerText = "Which plan would you like to switch to?"
				document.querySelector(".update_plan_form form button").innerText = "Request Change"
				document.querySelector(".update_plan_form form").elements['form_subscription_plan'].value = e.currentTarget.dataset.plan
				document.querySelector(".update_plan_form form").elements['form_subscription_update_url'].value = e.currentTarget.href
				document.querySelector(".update_plan_form form").elements['subscription_url'].value = `<?php echo $siteUrl; ?>/wp-admin/post.php?post=${e.currentTarget.dataset.subscriptionId}&action=edit`
				document.querySelector(".form_subscription_update_disclaimer").style.display = "none"
			}
 
			document.querySelector(".pause_popup .popup_msg h3").innerHTML = popupMsgNewText

			let confirmBtn = document.querySelectorAll(".confirm_btn a");
			confirmBtn.forEach((popupBtn) => {			
				popupBtn.href = e.currentTarget.href;
				popupBtn.addEventListener("click", function(){
					loadingSpinner.style.display = "flex";
					document.querySelector(".elementor-popup-modal").style.display = "none"
				})
			});		
		})
	})
})
</script>

<script>
	$('.addons__carousel_form').slick({
		autoplay: true,
  		autoplaySpeed: 4000,
		infinite: true,
		speed: 300,
		slidesToShow: 1,
		responsive: [
			{
			breakpoint: 768,
			settings: {
				slidesToShow: 1,
				slidesToScroll: 1
			}
			},
			{
			breakpoint: 480,
			settings: {
				slidesToShow: 1,
				slidesToScroll: 1
			}
			}
  		]
	});
</script>
