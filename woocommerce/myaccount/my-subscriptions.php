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

require_once get_stylesheet_directory() . '/components/subscription-card.php';
require_once get_stylesheet_directory() . '/components/tasks-addons-card.php';
require_once get_stylesheet_directory() . '/components/addons-carousel.php';
require_once get_stylesheet_directory() . '/components/invoices.php';


$siteUrl = site_url();
$elementorPopupID = 1570;

//ARRAY OF SUBSCRIPTION NAMES
$otherSubscriptionsGroup = [];
$allSubscriptionsGroup = [];

//CREATE NEW SUBSCRIPTIONS ARRAY TO SHOW THE ACTIVES FIRST IN THE LIST
$otherSubscriptions = [];
$inotherSubscriptions = [];
$userCurrentPlans = [];
$userCurrentAddons = [];
$userCurrentActiveTasks = [];
$activePlanSubscriptions = [];

//CREATE NEW SUBSCRIPTIONS ARRAY TO SHOW THE ACTIVES FIRST IN THE LIST;
foreach($subscriptions as $sub){
	$status = $sub->get_status();
	$subItems = $sub->get_items();

	foreach($subItems as $subItem){
		$terms = get_the_terms( $subItem['product_id'], 'product_cat' );

		if($terms[0]->slug === 'add-on'){ 
			$userCurrentAddons[] = $subItem['product_id'];
		}
		else if($terms[0]->slug === 'active-task'){ 
			$userCurrentActiveTasks[] = $subItem['product_id'];
		}
		else if($terms[0]->slug === 'plan'){ 
			$userCurrentPlans[] = $subItem['product_id'];
		}
	}

	foreach($subItems as $item){
		if(has_term('plan', 'product_cat', $item['product_id'])){
			$activePlanSubscriptions[] = $sub;
		}else{
			$otherSubscriptions[] = $sub;
		}
	}
	
}


$allProductAddons = wc_get_products([
   'category' => get_term_by('slug', 'add-on', 'product_cat')->slug,
   'exclude' => $userCurrentAddons,
   'status' => 'publish'
]);


$sortedSubscriptions = array_merge($activePlanSubscriptions, $otherSubscriptions);


if(isset($_GET['change-plan'])){
	wc_add_notice('switch', 'success');
	wp_redirect(get_permalink( wc_get_page_id( 'myaccount' ) ) . '/subscriptions');
	exit;
}
?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />


<div class="loading__spinner_wrapper" style="display: none">
	<div class="loading-spinner"></div>
</div>

<section>
	<a href="/" class="dd__bililng_portal_back"><i class="fa-solid fa-chevron-left"></i> Back to Dashboard</a>
	<h1 class="myaccount__page_title">Billing Portal</h1>
</section>

<!--PLANS-->
<?php if ( ! empty( $subscriptions ) ) { ?>
	<section class="dd__bililng_portal_section">
		<div style="max-width: 1140px; margin: auto">
			<h2 class="dd__billing_portal_section_title">Plans</h2>
			
			<?php if(!empty($userCurrentPlans)){ ?>
				<div class="woocommerce_account_subscriptions">	
					<div class="dd__subscription_container">
						<?php if($activePlanSubscriptions[0]->get_status() !== "cancelled"){ 
							foreach($activePlanSubscriptions[0]->get_items() as $subItem){
								if(has_term('plan', 'product_cat', $subItem['product_id'])){ 
									$currentProductId = $subItem['variation_id'] ? $subItem['variation_id'] : $subItem['product_id'];
									do_action('subscriptionCardComponentHook', $activePlanSubscriptions[0], $currentProductId);
								}
							}								
							} ?>
					</div>
			</div>
			<?php }else{ ?>
					<div class="dd__subscription_card"> 
						<div class="dd__subscription_details">
							<span class="dd__subscription_warning">You have no active subscriptions!</span>
						</div>

						<a href="https://deerdesigner.com/pricing" class="dd__primary_button">See Pricing</a>
					</div>

			<?php } ?>
		</div>
	</section>

	
	<!--ACTIVE TASKS-->
	<section class="dd__bililng_portal_section">
		<div style="max-width: 1140px; margin: auto">

			<h2 class="dd__billing_portal_section_title">Additional Active Tasks</h2>

			<?php if(!empty($userCurrentActiveTasks)){ ?>
				<div class="woocommerce_account_subscriptions">
					<div class="dd__subscription_container">
						<?php foreach ( $sortedSubscriptions as $subscription_index => $subscription ) :?>
							<?php if($subscription->get_status() !== "cancelled"){ 
								foreach($subscription->get_items() as $subItem){
									if(has_term('active-task', 'product_cat', $subItem['product_id'])){ 
										do_action('tasksAddonsCardComponentHook', $subscription, 'Downgrade', 'active-task', $activePlanSubscriptions[0]->get_status());
									}
								}								
								} ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php }else{ ?>
				<h3 class="dd__billing_portal_no_subscriptions_found">You have no addtional active tasks at the moment!</h3>
			<?php }?>
		</div>
	</section>


	<?php if(!empty($userCurrentAddons) && current_user_can('administrator')){ ?>
	<!--CURRENT ADDONS-->
	<section class="dd__bililng_portal_section">
		<div class="subscriptions__addons_wrapper">
			<div class="woocommerce_account_subscriptions">
				<h2 class="dd__billing_portal_section_title">Your current Add ons</h2>

				<div class="dd__subscription_container">
					<?php foreach ( $sortedSubscriptions as $subscription_index => $subscription ) :?>
						<?php if($subscription->get_status() !== "cancelled"){ 
							foreach($subscription->get_items() as $subItem){					
								if(has_term('add-on', 'product_cat', $subItem['product_id'])){ 
									do_action('tasksAddonsCardComponentHook', $subscription, 'Cancel Add On', 'add-on', $activePlanSubscriptions[0]->get_status());
								}
							}								
							} ?>
					<?php endforeach; ?>
				</div>	
			</div>
		</div>
	</section>
	<?php } ?>

	<!--AVAILABLE ADDONS-->
	<?php if($activePlanSubscriptions[0]->get_status() === 'active' && !empty($allProductAddons) && current_user_can('administrator')){ ?>
		<section class="dd__bililng_portal_section">
			<div class="subscriptions__addons_wrapper">
				<div class="woocommerce_account_subscriptions">
					<h2 class="dd__billing_portal_section_title">Available Add ons for you</h2>

					<?php do_action('addonsCarouselHook', array($allProductAddons)); ?>	
				</div>
			</div>
		</section>
	<?php } ?>
<?php }

else{ ?>
	<section>
		<div class="dd__subscription_card"> 
			<div class="dd__subscription_details">
				<span class="dd__subscription_warning">You have no active subscriptions!</span>
			</div>

			<div class="">
				<a href="https://deerdesigner.com/pricing" class="dd__primary_button">See Pricing</a>
			</div>

		</div>
	</section>
<?php } ?>


<?php 
	$stripeCustomerId = get_post_meta($activePlanSubscriptions[0]->id, '_stripe_customer_id', true);
	do_action('currentUserInvoicesComponentHook', $stripeCustomerId);
?>


<?php echo do_shortcode('[elementor-template id="1201"]'); ?>

<style>
	.welcome-h1, .dash__menu, .woocommerce-MyAccount-navigation, .dd-checklist, .dd-insights, .dd-cta{
		display: none !important;
	}

</style>

<script>
//THIS SCRIPT STOP THE DEFUALT WOOCOMMERCE REDIRECT FOR THE SUBSCRIPTION ACTIONS AND ASK THE USER IN ORDER TO PREVENT ACCIDENTAL CANCELLATIONS
//IT CHANGES THE POPUP TEXT AND LINK BASED ON THE BUTTON CLICKED

document.addEventListener("DOMContentLoaded", function(){

	function closePopup(){
		const elementorPopups = Array.from(document.querySelectorAll(".elementor-popup-modal"))
		elementorPopups.map((popup) => {
			popup.style.display = "none"
		})
	}

	const addActiveTaskOrAddonBtn = Array.from(document.querySelectorAll('.one__click_purchase'))

	addActiveTaskOrAddonBtn.map((btn) => {
		btn.addEventListener('click', function(e){
			e.preventDefault()
			const addProductToCartLink = e.currentTarget.href
			const productPrice = e.currentTarget.dataset.productPrice
			const productName = e.currentTarget.dataset.productName
			elementorProFrontend.modules.popup.showPopup( {id:<?php echo $elementorPopupID; ?>}, event);
			document.querySelector("#pause_popup .popup_msg h3").innerHTML = "ARE YOU SURE YOU WANT TO <br><span> ADD THIS ITEM TO YOUR ACCOUNT?</span>";
			document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes"
			document.querySelector(".cancel_btn .elementor-button-text").innerText = "No"

			document.querySelector(".confirm_btn a").addEventListener('click', function(){
				location.href = addProductToCartLink
				loadingSpinner.style.display = 'flex'
				closePopup()
			})
			
			document.querySelector(".cancel_btn a").addEventListener('click', function(){
				closePopup()
			})

			if(btn.classList.contains('active-tasks')){
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = `For this ${productName}, starting today, we will charge <strong>$${productPrice}</strong> per month to the card on your account.`
			}else if(btn.classList.contains('creative-call')){
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = `We will charge <strong> $${productPrice} </strong> to the card on your account.`
			}else{
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = `For the ${productName}, starting today, we will charge <strong>$${productPrice}</strong> per month to the card on your account.`
			}
		})
	})

	const subscriptionsActionsBtns = Array.from(document.querySelectorAll(".dd__subscription_actions_form a"));
	const loadingSpinner = document.querySelector(".loading__spinner_wrapper");

	let popupMsgNewText = ""

	function cancelFlow(currentPlan, currentLink, currentSubscriptionId){
		document.querySelector(".form_subscription_update_disclaimer").style.display = "none"
		document.querySelector(".update_plan_form form button").innerText = "Confirm Cancellation"
		document.querySelector(".popup_buttons").style.display = "none"
		document.querySelector(".update_plan_form").classList.add("show_form")
		document.querySelector(".update_plan_form form").elements['form_subscription_plan'].value = currentPlan
		document.querySelector(".update_plan_form form").elements['form_subscription_update_url'].value = currentLink
		document.querySelector(".update_plan_form form").elements['subscription_url'].value = `<?php echo $siteUrl; ?>/wp-admin/post.php?post=${currentSubscriptionId}&action=edit`

		if(currentPlan === "active-task"){
			document.querySelector("#pause_popup .popup_msg h3").innerHTML = "WHY ARE YOU DOWNGRADING? <br><span>DID WE DO ANYTHING WRONG?</span>";
			document.querySelector(".update_plan_form form .ff-btn-submit").innerText = "Confirm Downgrade"
			document.querySelector(".update_plan_form form").elements['btn_keep'].innerText = "Keep Active Task"
		}

	}

	function pauseFlow(currentPlan, currentSubscriptionId){
		const currentLink = document.querySelector(`[data-button-type='Pause_${currentSubscriptionId}']`).href
		document.querySelector("#pause_popup .popup_msg h3").innerHTML = "ARE YOU SURE YOU WANT TO <br><span>PAUSE YOUR SUBSCRIPTION?</span>";
		document.querySelector(".update_plan_form").classList.add("show_form")
		document.querySelector(".update_plan_form form").elements['form_subscription_plan'].value = currentPlan
		document.querySelector(".update_plan_form form").elements['form_subscription_update_url'].value = currentLink
		document.querySelector(".popup_buttons").style.display = "none"
		document.querySelector(".form_subscription_update_disclaimer").innerText = "If you pause your plan with multiple active tasks, they will be automatically canceled."
		document.querySelector(".update_plan_form form").elements['subscription_url'].value = `<?php echo $siteUrl; ?>/wp-admin/post.php?post=${currentSubscriptionId}&action=edit`
		document.querySelector(".form_subscription_update_message_field label").innerText = "Why are you pausing? Did we do anything wrong?"
		document.querySelector(".form_subscription_update_message_field label").style.display = "block"
		document.querySelector(".update_plan_form form button").innerText = "Pause Subscription"
	}

	subscriptionsActionsBtns.map((btn) => {
		btn.addEventListener("click", function(e){
			e.preventDefault();
			const currentSubscriptionId = e.currentTarget.dataset.subscriptionId
			const currentSubscriptionStatus = e.currentTarget.dataset.subscriptionStatus
			const productPrice = e.currentTarget.dataset.productPrice
			const currentPlan = e.currentTarget.dataset.plan
			const currentUpdatePlanUrl = e.currentTarget.href
			const enablePauseFlow = <?php echo sizeof($subscriptions); ?>;
			
			let currentTypeOfRequest = ""

			if(e.currentTarget.dataset.productCat === 'active-task'){
				currentTypeOfRequest = 'Downgrade Active Task';
			}else{
				if(e.currentTarget.dataset.requestType === 'Pause'){
					currentTypeOfRequest = 'Pause Request'
				}else if(e.currentTarget.dataset.requestType === 'Cancel'){
					currentTypeOfRequest = 'Cancellation Request'
				}else{
					currentTypeOfRequest = 'Change Plan Request'
				}
			}
			
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
			document.querySelector(".update_plan_form form").elements['form_subscription_request_type'].value = currentTypeOfRequest

			document.querySelector(".update_plan_form form").elements["btn_keep"].addEventListener("click", function(e){
				e.preventDefault()
				elementorProFrontend.modules.popup.closePopup( {}, event);
			})

			document.querySelector(".update_plan_form form .dd__subscription_cancel_btn").addEventListener("click", function(e){
				closePopup()
				loadingSpinner.style.display = "flex"
			})



			if(e.currentTarget.classList.contains("suspend")){
				confirmBtn.href = currentUpdatePlanUrl;
				popupMsgNewText = "ARE YOU SURE YOU WANT TO <br><span>PAUSE YOUR SUBSCRIPTION?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerText = "If you pause your plan with multiple active tasks, they will be automatically canceled."
				document.querySelector(".form_subscription_update_message_field label").style.display = "none"
		
				confirmBtn.addEventListener("click", function(e){
					e.preventDefault()
					pauseFlow(currentPlan, currentSubscriptionId, currentTypeOfRequest)
				})
				
				document.querySelector(".cancel_btn").addEventListener("click", function(e){
					e.preventDefault()
					closePopup()
				})
			}
			else if(e.currentTarget.classList.contains("rebill")){
				confirmBtn.href = currentUpdatePlanUrl;
				popupMsgNewText = "WOULD YOU LIKE TO REACTIVATE <br><span>YOUR SUBSCRIPTION?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = `We will charge <strong> $${productPrice} </strong> to the card on your account.`
				document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes"
				document.querySelector(".cancel_btn .elementor-button-text").innerText = "No"

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
				popupMsgNewText = "WOULD YOU LIKE TO REACTIVATE <br><span>YOUR SUBSCRIPTION?</span>";
				document.querySelector(".form_subscription_update_disclaimer").style.display = "none"
				document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes"
				document.querySelector(".cancel_btn .elementor-button-text").innerText = "No"

				confirmBtn.addEventListener("click", function(e){
					closePopup()
					loadingSpinner.style.display = "flex"
				})
				
				document.querySelector(".cancel_btn").addEventListener("click", function(e){
					e.preventDefault()
					closePopup()
				})
			}
			else if(e.currentTarget.classList.contains("active-task")){
				popupMsgNewText = "ARE YOU SURE YOU WANT <br><span>TO REMOVE THIS ACTIVE TASK?</span>";
				document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes, remove it"
				document.querySelector(".cancel_btn .elementor-button-text").innerText = "No, keep it"
				document.querySelector(".form_subscription_update_message_field label").style.display = 'none'		
				document.querySelector(".form_subscription_update_disclaimer").style.display = 'none'
				
				confirmBtn.addEventListener("click", function(e){
					e.preventDefault()
					cancelFlow(currentPlan, currentUpdatePlanUrl, currentSubscriptionId)
				})

				document.querySelector(".cancel_btn").addEventListener("click", function(e){
					e.preventDefault()
					closePopup()
				})
			}
			else if(e.currentTarget.classList.contains("cancel")){
				popupMsgNewText = "Why are you cancelling? <br><span>Did we do anything wrong?</span>";
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = "<span>By cancelling your account you will lose access to all the data, design files, and ticket history. If you pause your account, we will store everything for you until you decide to reactivate it.</span>"
				document.querySelector(".confirm_btn .elementor-button-text").innerText = "Yes, cancel it"
				document.querySelector(".form_subscription_update_message_field label").style.display = 'none'
				
				if(currentPlan === 'add-on' || currentPlan === 'active-task'){
					document.querySelector(".form_subscription_update_disclaimer").style.display = 'none'
				}
	
				if(currentPlan === 'add-on'){
					confirmBtn.addEventListener("click", function(e){
						closePopup()
						loadingSpinner.style.display = "flex"
						location.href = currentUpdatePlanUrl
					})
				}
				
				confirmBtn.addEventListener("click", function(e){
					e.preventDefault()
					cancelFlow(currentPlan, currentUpdatePlanUrl, currentSubscriptionId)
				})

				if(enablePauseFlow && currentSubscriptionStatus !== 'on-hold' && currentPlan !== 'active-task' && currentPlan !== 'add-on'){
					document.querySelector(".cancel_btn .elementor-button-text").innerText = "Pause it instead"
					document.querySelector(".cancel_btn").addEventListener("click", function(e){
						e.preventDefault()
						document.querySelector(".cancel_btn .elementor-button-text").innerText = "Cancel"
						pauseFlow(currentPlan, currentSubscriptionId)
					})
				}else{
					document.querySelector(".cancel_btn").addEventListener("click", function(e){
						e.preventDefault()
						closePopup();
					})
				}
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
