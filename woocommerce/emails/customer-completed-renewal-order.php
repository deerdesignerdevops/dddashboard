<?php
/**
 * Customer completed renewal order email
 *
 * @author  Brent Shepherd
 * @package WooCommerce_Subscriptions/Templates/Emails
 * @version 1.0.0 - Migrated from WooCommerce Subscriptions v2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<p><?php esc_html_e( 'We have finished processing your subscription renewal order.', 'woocommerce-subscriptions' ); ?></p>

<?php
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
// if ( $additional_content ) {
// 	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
// }
?>

<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>You're receiving this email because you have <br>an active subscription with <a href="https://deerdesigner.com">Deer Designer.</a></p>

<?php
do_action( 'woocommerce_email_footer', $email );
