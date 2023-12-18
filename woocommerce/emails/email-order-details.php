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
$userSubscriptions = wcs_get_users_subscriptions($userId);


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
		$textBasedOnProduct = "You'll be able to book a call with our Creative Director right on your Dashboard.";

	}else if(str_contains(strtolower($itemName), 'task')){
		$textBasedOnProduct = "I'm glad to see that things are going great! <br><br> You've got an additional active task on your account, which means that you'll either get more time from your designer or an additional designer will also work on your requests every day. <br><br> Feel free to let your account manager know which tasks your team should prioritize.";

	}else if(str_contains(strtolower($itemName), 'assets')){
		$textBasedOnProduct = "We already let the team know, and they'll be using it on your requests starting today. ";

	}else{
		$textBasedOnProduct = "";
		$orderProductCat = "";
	}
}



$userName = $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'];
$userEmail = $orderData['billing']['email'];
$companyName = $orderData['billing']['company'];
$couponDiscount = 0;

?>

<h2>
	<?php
	if ( $sent_to_admin ) {
		$before = '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">';
		$after  = '</a>';
		$userDetailsForAdmin = '<p class="user__details"><strong>Receipt from:</strong>' . " $userName | $userEmail | $companyName" . '</p>';
	} else {
		$before = '';
		$after  = ' - Deer Designer Subscription';
		$userDetailsForAdmin = "";
	}
	/* translators: %s: Order ID. */
	echo wp_kses_post( $before . sprintf( __( 'Receipt #%s', 'woocommerce' ) . $after . ' <br><span>Paid on: <time datetime="%s">%s</time></span>', $order->get_order_number(), $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ) );
	?>
</h2>

<?php echo $userDetailsForAdmin; ?>

<?php if(!$sent_to_admin){ ?>
	<?php if($productCategory != 'plan'){ ?>
		<h3>Hi, <?php echo $userName; ?></h3>
	<?php } ?>

	<?php if($productCategory === 'add-on'){ ?>
		<p>Thank you for your purchase! You successfully added <?php echo implode(" | ", $orderItemsGroup); ?> to your subscription.</p>
	<?php } ?>

	<p><?php echo $textBasedOnProduct; ?></p>
<?php }else{ 
	 ?>
	 <?php if($productCategory !== 'add-on'){?>
		<p style="text-align: center;">Plan: <?php echo $userPlanName; ?>
			<?php if($currentSubscriptionStatus == 'on-hold' || $currentSubscriptionStatus == 'pending-cancel'){ ?> <span style="text-align: center;"> | Effective on: <?php echo $billingCycle; ?></span><?php } ?> 
		</p>
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
			echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
						<th class="td" colspan="5">Dicount</th>
						<td class="td" colspan="5" style="text-align: right;"><?php echo "-$couponDiscount%"?></td>
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

