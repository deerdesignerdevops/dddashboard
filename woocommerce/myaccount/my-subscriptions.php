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
$elementorPopupID = $siteUrl === 'http://localhost/deerdesignerdash' ? 2776 : 1201;

//ARRAY OF SUBSCRIPTION NAMES
$activeSubscriptionsGroup = [];
$allSubscriptionsGroup = [];

//CREATE NEW SUBSCRIPTIONS ARRAY TO SHOW THE ACTIVES FIRST IN THE LIST
$activeSubscriptions = [];
$inactiveSubscriptions = [];
foreach($subscriptions as $sub){
	$status = $sub->get_status();
	if($status === "active"){
		$activeSubscriptions[] = $sub;
	}else if($status !== "active" && $status !== "cancelled"){
		$inactiveSubscriptions[] = $sub;
	}
}

$sortedSubscriptions = array_merge($activeSubscriptions, $inactiveSubscriptions);


function defineAddDesignerLinkProductID($parentProducts){;
	foreach($parentProducts as $parentProduct){
		if(str_contains($parentProduct, 'Business') !== false){
			return '1134';
		}else if(str_contains($parentProduct, 'Agency') !== false){
			return '1141';
		}else{
			return '1130';
		}
	}
}

function formatSubscriptionStatusLabel($status){
	switch ($status){
		case 'on-hold':
			return 'paused';
			break;
		case 'pending-cancel':
			return 'pending-cancellation';
			break;

		default:
			return $status;
	}
}

$invoicesPageNumber = isset($_GET["invoices_page"]) ? $_GET["invoices_page"] : 1;
$invoicesLimit = 5;

$currentUserOrders = wc_get_orders(array(
	'customer_id' => get_current_user_id(),
	'status' => array('wc-completed'),
	'limit' => $invoicesLimit,
	'paginate' => true,
	'paged' => $invoicesPageNumber
));

function generateInvoicePdfUrl($orderId){
	$pdfUrl = wp_nonce_url( add_query_arg( array(
	'action'        => 'generate_wpo_wcpdf',
	'document_type' => 'invoice',
	'order_ids'     => $orderId,
	'my-account'    => true,
	), admin_url( 'admin-ajax.php' ) ), 'generate_wpo_wcpdf' );

	return $pdfUrl;
}

$userCurrentAddons = [];

$allProductAddons = wc_get_products(['category' => get_term_by('slug', 'add-on', 'product_cat')->slug]);


$dates_to_display = apply_filters( 'wcs_subscription_details_table_dates_to_display', array(
	'start_date'              => _x( 'Start date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'last_order_date_created' => _x( 'Last payment', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'next_payment'            => _x( 'Next payment', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'end'                     => _x( 'End date', 'customer subscription table header', 'woocommerce-subscriptions' ),
	'trial_end'               => _x( 'Trial end date', 'customer subscription table header', 'woocommerce-subscriptions' ),
) );


function addNewActiveTaskToCurrentSubscription($subscriptionId, $subscriptionPlan){	
	$subscriptionObj = wcs_get_subscription($subscriptionId);
	$qty = 1;
	$product = wc_get_product(3040);
	$tax = ($product->get_price_including_tax()-$product->get_price_excluding_tax())*$qty;
	$activeTaskProductDisctount = 0;

	if(str_contains($subscriptionPlan, 'Business') || str_contains($subscriptionPlan, 'Agency')){
		$activeTaskProductDisctount = 50;
	}

	$price = $product->get_price() - $activeTaskProductDisctount;

	$subscriptionObj->add_product($product, $qty, array(
		'totals' => array(
			'subtotal'     => $price,
			'subtotal_tax' => $tax,
			'total'        => $price,
			'tax'          => $tax,
			'tax_data'     => array( 'subtotal' => array(1=>$tax), 'total' => array(1=>$tax) )
		)
	));
	$subscriptionObj->calculate_totals();
	$subscriptionObj->save();
	
	wp_redirect(site_url() . "/subscriptions");
	exit;
}

if(isset($_GET["additional-active-task"])){
	$subscriptionId = $_GET["subscription_id"];	
	$subscriptionPlan = $_GET["plan"];	
	addNewActiveTaskToCurrentSubscription($subscriptionId, $subscriptionPlan);
}

?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />


<div class="dd__loading_screen">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
</div>


<?php if(isset($_GET["change-your-plan"])){ wc_add_notice('Your request to switch plan has been sent. We\'ll get in touch soon!', 'success'); } ?>


<section class="dd__bililng_portal_section">
    <div style="max-width: 1140px; margin: auto">
        <a href="/" class="dd__bililng_portal_back"><i class="fa-solid fa-chevron-left"></i> Back to Dashboard</a>
        <h1 class="myaccount__page_title">Billing Portal</h1>

		<div class="woocommerce_account_subscriptions">
			<?php if ( ! empty( $subscriptions ) ) : ?>
				<div class="dd__subscriptions_sumary">            
					<div class="dd__subscription_container">
						<h2 class="cart__header__title">You have</h2>
				
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

							if(sizeof($activeSubscriptionsGroup)){ ?>
								<?php foreach(array_unique($activeSubscriptionsGroup) as $activeSubscriptionsGroupItem){ 
									$subscriptionItemCount = array_count_values($activeSubscriptionsGroup)[$activeSubscriptionsGroupItem];
								?>
									<span class="dd__subscriptions_sumary_name"><?php echo $activeSubscriptionsGroupItem; ?> <strong><?php echo $subscriptionItemCount; ?></strong></span>
								<?php } ?>								
							<?php } ?>

						<?php foreach ( $sortedSubscriptions as $subscription_id => $subscription ) :?>
							<?php if($subscription->get_status() !== "cancelled"){ ?>				
								<div class="dd__subscription_card <?php 
									foreach($subscription->get_items() as $subsItem){
										echo ' ' . strtok(strtolower($subsItem['name']), ' ');
									}

									echo ' ' . esc_attr($subscription->get_status());
									
									?>">
									<div class="dd__subscription_details">                        

										<div class="dd__subscription_header">
											<span class="dd__subscription_id <?php echo esc_attr( $subscription->get_status() ); ?>"><?php echo "Subscription ID: $subscription->id"; ?> | <strong><?php echo  formatSubscriptionStatusLabel($subscription->get_status()) ?></strong></span>
										</div>

										<?php 
										$subscriptionProductNames = [];
										$currentSubscriptionPlan = "";

										foreach ( $subscription->get_items() as  $item ){
											$currentCat =  strip_tags(wc_get_product_category_list($item['product_id']));

											if($currentCat === "Plan"){
												$currentSubscriptionPlan = $item['name'];
											}
											
											
											if(!in_array($item['name'], $subscriptionProductNames)){
												$itemName = $item['name'];
												$subscriptionProductNames[] = $itemName;												
											?>
									
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
											<a href="<?php echo $siteUrl; ?>/subscriptions/?additional-active-task=true&<?php echo "subscription_id=$subscription->id&plan=$currentSubscriptionPlan"; ?>" class="dd__add_designer_btn active-tasks">Get More Active Tasks</a>

											<a href="<?php echo $siteUrl; ?>/subscriptions/?change-your-plan=true" data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>" class="change_plan_btn change">Change Plan</a>	
										<?php } ?>

										<?php do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $subscription, false ); ?>
										
										<?php $actions = wcs_get_all_user_actions_for_subscription( $subscription, get_current_user_id() ); 
										
										if(sizeof($subscriptions) > 1){ 
											unset($actions['suspend']);
										}
										
										?>
												<?php if ( ! empty( $actions ) ) { ?>
													<div class="dd__subscriptions_buttons_wrapper">						
														<?php foreach ( $actions as $key => $action ) :?>															
															<a href="<?php echo esc_url( $action['url'] ); ?>" data-plan="<?php echo $currentSubscriptionPlan; ?>" data-subscription-id="<?php echo $subscription->id; ?>" data-button-type=<?php echo esc_html( $action['name'] ) . '_' . $subscription->id; ?> data-subscription-status="<?php echo $subscription->get_status(); ?>" class="dd__subscription_cancel_btn <?php echo str_replace(' ', '-', strtolower($item['name']));  ?> <?php echo sanitize_html_class( $key ) ?>"><?php echo esc_html( $action['name'] ); ?></a>
														<?php endforeach; ?>
													</div>
												<?php }; ?>
									</div>
								</div>
							<?php } ?>
						<?php endforeach; ?>
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

			<?php else : ?>
				<div class="dd__subscription_card"> 
					<div class="dd__subscription_details">
						<span class="dd__subscription_warning">You have no active subscriptions!</span>
					</div>

					<a href="https://deerdesigner.com/pricing" class="dd__add_designer_btn">See Pricing</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="user__invoices_section">
	<h2 class="cart__header__title">Your Invoices</h2>
	<div class="user__invoices_wrapper">
		<?php foreach($currentUserOrders->orders as $order){ ?>
			<div class="user__invoice_row">
				<span>#<?php echo $order->id; ?> - Invoice from <?php echo wc_format_datetime($order->get_date_completed()); ?></span>
				
				<a target="_blank" href="<?php echo generateInvoicePdfUrl($order->id); ?>">Download Invoice</a>
			</div>
		<?php } ?>
	</div>


	<?php if($currentUserOrders->max_num_pages > 1){ ?>
		<div class="user__invoices_pagination">
			<?php $prevUrl = "$siteUrl/subscriptions/?invoices_page=" . $invoicesPageNumber - 1; ?>
			<?php $nextUrl = "$siteUrl/subscriptions/?invoices_page=" . $invoicesPageNumber + 1; ?>
			
			<a href="<?php echo $prevUrl; ?>" class="user__invoices_pagination_btn <?php echo $invoicesPageNumber > 1 ? 'btn_active' : 'btn_inactive'; ?>">Prev</a>
		
			<span><?php echo $invoicesPageNumber; ?></span>

			<a href="<?php echo $nextUrl; ?>" class="user__invoices_pagination_btn <?php echo $invoicesPageNumber < $currentUserOrders->max_num_pages ? 'btn_active' : 'btn_inactive'; ?>">Next</a>
		</div>
	<?php } ?>
</section>

<?php echo do_shortcode('[elementor-template id="1201"]'); print_r($activeSubscriptionsGroup);?>

<style>
	.welcome-h1, .dash__menu, .woocommerce-MyAccount-navigation{
		display: none !important;
	}

	.form_subscription_update_disclaimer{
		display: <?php echo sizeof($sortedSubscriptions) > 1 ? 'none' : 'block';  ?>;
	}

	.premium-stock-photos.suspend {
		display: none;
	}
</style>

<script>
//THIS SCRIPT STOP THE DEFUALT WOOCOMMERCE REDIRECT FOR THE SUBSCRIPTION ACTIONS AND ASK THE USER IN ORDER TO PREVENT ACCIDENTAL CANCELLATIONS
//IT CHANGES THE POPUP TEXT AND LINK BASED ON THE BUTTON CLICKED

document.addEventListener("DOMContentLoaded", function(){
	const subscriptionsActionsBtns = Array.from(document.querySelectorAll(".dd__subscription_actions_form a"));
	const loadingSpinner = document.querySelector(".dd__loading_screen");
	function closePopup(){
		const elementorPopups = Array.from(document.querySelectorAll(".elementor-popup-modal"))
		elementorPopups.map((popup) => {
			popup.style.display = "none"
		})
	}
	
	let popupMsgNewText = ""

	function cancelFlow(currentPlan, currentLink, currentSubscriptionId){
		document.querySelector(".form_subscription_update_disclaimer").style.display = "none"
		document.querySelector(".update_plan_form form button").innerText = "Confirm Cancellation"
		document.querySelector(".popup_buttons").style.display = "none"
		document.querySelector(".update_plan_form").classList.add("show_form")
		document.querySelector(".update_plan_form form").elements['form_subscription_plan'].value = currentPlan
		document.querySelector(".update_plan_form form").elements['form_subscription_update_url'].value = currentLink
		document.querySelector(".update_plan_form form").elements['subscription_url'].value = `<?php echo $siteUrl; ?>/wp-admin/post.php?post=${currentSubscriptionId}&action=edit`
	}

	function pauseFlow(currentSubscriptionId){
		document.querySelector("#pause_popup .popup_msg h3").innerHTML = "ARE YOU SURE YOU WANT TO <br><span>PAUSE THIS SUBSCRIPTION?</span>";
		document.querySelector(".form_subscription_update_disclaimer").innerText = "When you pause your subscription, we'll keep your designs, tickets and communication saved until you reactivate your account. Your design team is still available until the end of your current billing period."
		document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes, pause it"
		document.querySelector(".cancel_btn .elementor-button-text").innerText = "Keep it active"
		confirmBtn = document.querySelector(".confirm_btn a")
		
		let newConfirmBtn = confirmBtn.cloneNode(true);
		confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
		
		newConfirmBtn.addEventListener("click", function(e){
			e.preventDefault()
			location.href = document.querySelector(`[data-button-type='Pause_${currentSubscriptionId}']`).href
			closePopup()
			loadingSpinner.style.display = "flex"
		})

		document.querySelector(".cancel_btn").addEventListener("click", function(e){
			e.preventDefault()
			closePopup()
		})
	}

	subscriptionsActionsBtns.map((btn) => {
		btn.addEventListener("click", function(e){
			e.preventDefault();
			const currentSubscriptionId = e.currentTarget.dataset.subscriptionId
			const currentSubscriptionStatus = e.currentTarget.dataset.subscriptionStatus
			const currentPlan = e.currentTarget.dataset.plan
			const currentUpdatePlanUrl = e.currentTarget.href
			const enablePauseFlow = <?php echo sizeof($subscriptions); ?>;
			const changePlanOptionsText = () => {
				if(currentPlan.includes('Standard')){
					return "Business Plan and Agency Plan"
				}else if(currentPlan.includes('Business')){
					return "Standard Plan and Agency Plan"
				}else{
					return "Standard Plan and Business Plan"
				}				
			}
			
			elementorProFrontend.modules.popup.showPopup( {id:<?php echo $elementorPopupID; ?>}, event);
			let confirmBtn = document.querySelector(".confirm_btn a");

			document.querySelector(".update_plan_form form").elements["btn_keep"].addEventListener("click", function(e){
				e.preventDefault()
				elementorProFrontend.modules.popup.closePopup( {}, event);
			})

			if(e.currentTarget.classList.contains("suspend")){
				confirmBtn.href = currentUpdatePlanUrl;
				popupMsgNewText = "ARE YOU SURE YOU WANT TO <br><span>PAUSE THIS SUBSCRIPTION?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerText = "	When you pause your subscription, we'll keep your designs, tickets and communication saved until you reactivate your account. Your design team is still available until the end of your current billing period."

				confirmBtn.addEventListener("click", function(e){
					closePopup()
					loadingSpinner.style.display = "flex"
				})
				
				document.querySelector(".cancel_btn").addEventListener("click", function(e){
					e.preventDefault()
					closePopup()
				})
			}
			else if(e.currentTarget.classList.contains("reactivate")){
				confirmBtn.href = currentUpdatePlanUrl;
				popupMsgNewText = "REACTIVATE <span>THIS SUBSCRIPTION?</span>";
				document.querySelector(".form_subscription_update_disclaimer").style.display = "none"
				document.querySelector(".confirm_btn .elementor-button-text").innerText = "Confirm"
				document.querySelector(".cancel_btn .elementor-button-text").innerText = "Cancel"

				confirmBtn.addEventListener("click", function(e){
					closePopup()
					loadingSpinner.style.display = "flex"
				})
				
				document.querySelector(".cancel_btn").addEventListener("click", function(e){
					e.preventDefault()
					closePopup()
				})
			}
			else if(e.currentTarget.classList.contains("cancel")){
				popupMsgNewText = "ARE YOU SURE YOU WANT TO <br><span>CANCEL THIS SUBSCRIPTION?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = "<span><strong>ATTENTION:</strong> When you cancel your subscription, you'll lose access to all your designs, tickets and communication. Your design team is still available until the end of your current billing period.</span>"
				document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes, cancel it"
				
				confirmBtn.addEventListener("click", function(e){
					e.preventDefault()
					cancelFlow(currentPlan, currentUpdatePlanUrl, currentSubscriptionId)
				})

				if(enablePauseFlow === 1 && currentSubscriptionStatus !== 'on-hold'){
					document.querySelector(".cancel_btn .elementor-button-text").innerText = "Pause it instead"
					document.querySelector(".cancel_btn").addEventListener("click", function(e){
						e.preventDefault()
						document.querySelector(".cancel_btn .elementor-button-text").innerText = "Cancel"
						pauseFlow(currentSubscriptionId)
					})
				}else{
					document.querySelector(".cancel_btn").addEventListener("click", function(e){
						e.preventDefault()
						closePopup();
					})
				}
			}
			else if(e.currentTarget.classList.contains("active-tasks")){
				confirmBtn.href = currentUpdatePlanUrl;
				popupMsgNewText = "Are you sure you want add <br><span>more active tasks?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = "<span><strong>ATTENTION:</strong> Your subscription will be increased by R$649.</span>"
				document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes, give more active tasks"
				document.querySelector(".cancel_btn .elementor-button-text").innerText = "Cancel"

				document.querySelector(".cancel_btn").addEventListener("click", function(e){
					e.preventDefault()
					closePopup()
				})
			}
			else{
				confirmBtn.href = currentUpdatePlanUrl;
				popupMsgNewText = "Which plan would you<br> <span>like to switch to?</span>";
				document.querySelector(".popup_buttons").style.display = "none"
				document.querySelector(".update_plan_form").classList.add("show_form")
				document.querySelector(".form_subscription_update_message_field label").style.display = "none"
				document.querySelector(".update_plan_form form button").innerText = "Request Change"
				document.querySelector(".update_plan_form form textarea").placeholder = `Choose between ${changePlanOptionsText()}`
				document.querySelector(".update_plan_form form").elements["btn_keep"].innerText = "Keep my plan"
				document.querySelector(".update_plan_form form").elements['form_subscription_plan'].value = currentPlan
				document.querySelector(".update_plan_form form").elements['form_subscription_update_url'].value = currentUpdatePlanUrl
				document.querySelector(".update_plan_form form").elements['subscription_url'].value = `<?php echo $siteUrl; ?>/wp-admin/post.php?post=${currentSubscriptionId}&action=edit`
				document.querySelector(".form_subscription_update_disclaimer").style.display = "none"
			}
 
			document.querySelector("#pause_popup .popup_msg h3").innerHTML = popupMsgNewText
				
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

<script>
	document.addEventListener("DOMContentLoaded", function(){
		const closeNoticesPopupBtn = document.querySelector(".dd__notices_popup_wrapper .dd__subscription_cancel_btn")
		
		if(closeNoticesPopupBtn){
			closeNoticesPopupBtn.addEventListener("click", function(e){;
				e.preventDefault()
				document.querySelector(".dd__notices_popup_wrapper").style.display = "none";
			})
		}
	})
</script>
