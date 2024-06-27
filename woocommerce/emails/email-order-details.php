<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); 
$orderData = $order->get_data();
$orderItems = $order->get_items();
$orderSubscriptions = wcs_get_subscriptions_for_order($order->get_id());
$userId = $orderData['customer_id'];
$currentUser = get_user_by('id', $userId);
$userSubscriptions = wcs_get_users_subscriptions($userId);
$userPlanName = '';
$userName = $currentUser->first_name;
$userEmail = $currentUser->user_email;
$companyName = $currentUser->billing_company;
$couponDiscount = 0;
$userDetailsForAdmin = '';

if($order instanceof WC_Subscription){
	foreach($order->get_items() as $subItem){
		$userPlanName = $subItem['name'];
		$currentDate = new DateTime($order->get_date_to_display( 'start' )); 
		$currentDate->add(new DateInterval('P1' . strtoupper($order->billing_period[0])));
		$billingCycle = $currentDate->format('F j, Y');
		$billingPeriodEndingDate =  str_contains($order->get_date_to_display( 'end' ), 'Not') ? $currentDate->format('F j, Y') : $order->get_date_to_display( 'end' );
		$currentSubscriptionStatus = $order->get_status();
	}
}else{
	foreach($userSubscriptions as $sub){
		foreach($sub->get_items() as $subItem){
			if(has_term('plan', 'product_cat', $subItem['product_id'])){
				$userPlanName = $subItem['name'];
				$currentDate = new DateTime($sub->get_date_to_display( 'start' )); 
				$currentDate->add(new DateInterval('P1' . strtoupper($sub->billing_period[0])));
				$billingCycle = $currentDate->format('F j, Y');
				$currentSubscriptionStatus = $sub->get_status();
			}
		}
	}
}


foreach( $orderItems as $item_id => $item ){
	$itemName = $item->get_name();
	$orderItemsGroup[] = $itemName;
	$itemData = $item->get_data();
	$terms = get_the_terms( $itemData['product_id'], 'product_cat' );
	$productCategory = $terms[0]->slug;

	if(str_contains(strtolower($itemName), 'director')){
		$textBasedOnProduct = "You've added the Creative Director add-on to your account. From now on, you'll be able to book as many calls with our Creative Director as you want! Feel free to do so right from the Dashboard.";

	}else if(str_contains(strtolower($itemName), 'designer')){
		$textBasedOnProduct = "You've added an additional designer to your account. This means you'll get either more time with your designer or an another designer to work on your requests every day. Feel free to let your account manager know which tasks your team should prioritize.";

	}else if(str_contains(strtolower($itemName), 'assets')){
		$textBasedOnProduct = "You've added the Premium Stock Assets add-on to your account. We've sent a notification to your design team, and they'll be using it on your requests starting today. You can read more the terms and conditions <a href='https://help.deerdesigner.com/article/show/170385-use-of-premium-stock-assets'>here</a>.";

	}else{
		$textBasedOnProduct = "";
		$orderProductCat = "";
	}
}

?>

<h2>
	<?php
	if ( $sent_to_admin ) {
		$userDetailsForAdmin = '<h2 class="user__details"><strong>Receipt from:</strong></h2>' . "<p style='text-align:center; font-weight:bold;'>$userName | $userEmail | $companyName</p>";
	}
	
	if($email->id === 'customer_completed_order'){
		echo wp_kses_post( sprintf( __( 'Receipt #%s - Deer Designer Subscription', 'woocommerce' ). ' <br><span>Paid on: <time datetime="%s">%s</time></span>', $order->get_order_number(), $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ) );
	} ?>

</h2>

<?php echo $userDetailsForAdmin; ?>

<?php if(!$sent_to_admin && $email->id === 'customer_completed_order'){ ?>
	<?php if($productCategory != 'plan'){ ?>
		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Hi <?php echo $userName; ?>,</p>
	<?php } ?>

	<p><?php echo $textBasedOnProduct; ?></p>
<?php }else{ 
	 ?>
	 <?php if($productCategory !== 'add-on' && $sent_to_admin){?>
		<p style="text-align: center;">Plan: <?php echo $userPlanName; ?></p>
	 <?php }
} ?>

<div style="margin-bottom: 40px;">
	<h3 class="order__email_sumary">Sumary</h3>
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" colspan="5" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Plans & Add-ons', 'woocommerce' ); ?></th>
				<th class="td" scope="col" colspan="5" style="text-align:right;"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			echo wc_get_email_order_items(
				$order,
				array(
					'show_sku'      => $sent_to_admin,
					'show_image'    => false,
					'image_size'    => array( 32, 32 ),
					'plain_text'    => $plain_text,
					'sent_to_admin' => $sent_to_admin,
				)
			);
			?>

			<?php 
				foreach($order->get_coupons() as $couponItem){
						$coupon_code   = $couponItem->get_code();
						$coupon = new WC_Coupon($coupon_code);
						$couponDiscount = $coupon->amount;
				}

				if($couponDiscount){ ?>
					<tr>
						<th class="td" colspan="5">Discount</th>
						<td class="td" colspan="5" style="text-align: right;"><?php echo get_woocommerce_currency()."-$couponDiscount"?></td>
					</tr>
			<?php }
			?>


		</tbody>
		<tfoot>
			<?php
			$item_totals = $order->get_order_item_totals();

			if ( $item_totals ) {
				$i = 0;
				foreach ( $item_totals as $total ) {
					$i++;
					?>
					<tr>
						<th class="td" colspan="5" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['label'] ); ?></th>
						<td class="td" colspan="5" style="text-align:right; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
				}
			}
			?>
		</tfoot>
	</table>
</div>


