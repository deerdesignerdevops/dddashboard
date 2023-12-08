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


$siteUrl = site_url();
$elementorPopupID = $siteUrl === 'http://localhost/deerdesignerdash' ? 2776 : 1201;

//ARRAY OF SUBSCRIPTION NAMES
$activeSubscriptionsGroup = [];
$allSubscriptionsGroup = [];

//CREATE NEW SUBSCRIPTIONS ARRAY TO SHOW THE ACTIVES FIRST IN THE LIST
$activeSubscriptions = [];
$inactiveSubscriptions = [];
$userCurrentPlans = [];
$userCurrentAddons = [];
$userCurrentActiveTasks = [];

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

	if($status === "active"){
		$activeSubscriptions[] = $sub;
	}else if($status !== "active" && $status !== "cancelled"){
		$inactiveSubscriptions[] = $sub;
	}
}


$allProductAddons = wc_get_products([
   'category' => get_term_by('slug', 'add-on', 'product_cat')->slug,
   'exclude' => $userCurrentAddons,
]);


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

if(isset($_GET['change-plan'])){
	wc_add_notice('switch', 'success');
	wp_redirect(site_url() . '/subscriptions');
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
						<?php foreach ( $sortedSubscriptions as $subscription_index => $subscription ) :?>
							<?php if($subscription->get_status() !== "cancelled"){ 
								foreach($subscription->get_items() as $subItem){
									$terms = get_the_terms( $subItem['product_id'], 'product_cat' );
						
									if($terms[0]->slug === 'plan'){ 
										do_action('subscriptionCardComponentHook', $subscription, $userCurrentActiveTasks);
										}
								}								
								} ?>
						<?php endforeach; ?>
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
									$terms = get_the_terms( $subItem['product_id'], 'product_cat' );
						
									if($terms[0]->slug === 'active-task'){ 
										do_action('tasksAddonsCardComponentHook', $subscription, 'Downgrade');
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


	<?php if(!empty($userCurrentAddons)){ ?>
	<!--CURRENT ADDONS-->
	<section class="dd__bililng_portal_section">
		<div class="subscriptions__addons_wrapper">
			<div class="woocommerce_account_subscriptions">
				<h2 class="dd__billing_portal_section_title">Your current Add ons</h2>

				<div class="dd__subscription_container">
					<?php foreach ( $sortedSubscriptions as $subscription_index => $subscription ) :?>
						<?php if($subscription->get_status() !== "cancelled"){ 
							foreach($subscription->get_items() as $subItem){
								$terms = get_the_terms( $subItem['product_id'], 'product_cat' );
					
								if($terms[0]->slug === 'add-on'){ 
									do_action('tasksAddonsCardComponentHook', $subscription, 'Cancel Add On');
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
	<section class="dd__bililng_portal_section">
		<div class="subscriptions__addons_wrapper">
			<div class="woocommerce_account_subscriptions">
				<h2 class="dd__billing_portal_section_title">Available Add ons for you</h2>

				<?php do_action('addonsCarouselHook', array($allProductAddons)); ?>	
			</div>
		</div>
	</section>
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

<section class="user__invoices_section">
	<h2 class="dd__billing_portal_section_title">Your Invoices</h2>
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

<?php echo do_shortcode('[elementor-template id="1201"]'); ?>

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
	const loadingSpinner = document.querySelector(".loading__spinner_wrapper");
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
			const activeTaskProductDiscount = currentPlan.includes('Standard') ? 0 : 50
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
				document.querySelector(".form_subscription_update_disclaimer").innerHTML = `<span><strong>ATTENTION:</strong> Your subscription will be increased by R$${699 - activeTaskProductDiscount}.</span>`
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
