<?php
/**
 * Customer payment retry email
 *
 * @author  Prospress
 * @package WooCommerce_Subscriptions/Templates/Emails
 * @version 1.0.0 - Migrated from WooCommerce Subscriptions v2.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: lowercase human time diff in the form returned by wcs_get_human_time_diff(), e.g. 'in 12 hours' */ ?>
<p><?php printf( esc_html_x( 'Your subscription renewal payment didn\'t go through. We\'ll try again in %s.', 'In customer renewal invoice email', 'woocommerce-subscriptions' ), esc_html( wcs_get_human_time_diff( $retry->get_time() ) ) ); ?></p>

<?php /* translators: %1$s %2$s: link markup to checkout payment url, note: no full stop due to url at the end */ ?>
<p><?php echo wp_kses( sprintf( _x( 'Your design team will wait for payment before continuing the work. If you need to update your credit card details, click on "Account Details" on the <a href="https://dash.deerdesigner.com/edit-account/">dashboard</a>. To avoid any interruption on the service, please log in and pay on your account page or click: %1$sPay Now &raquo;%2$s', 'In customer renewal invoice email', 'woocommerce-subscriptions' ), '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">', '</a>' ), array( 'a' => array( 'href' => true ) ) );?></p>

<?php
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
// if ( $additional_content ) {
// 	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
// }

do_action( 'woocommerce_email_footer', $email );
