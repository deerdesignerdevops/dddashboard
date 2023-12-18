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
<h2><?php printf( esc_html__( 'Hi %s,', 'woocommerce-subscriptions' ), esc_html( $order->get_billing_first_name() ) ); ?></h2>

<p>It looks like the payment for your subscription did not go through successfully. We understand that occasional hiccups can happen with payments, and there's no need to worry.</p>

<p>Here's the alert we received: [payment issue].</p>

<p>To ensure uninterrupted access to our design services, please review and update your payment information via the link below:</p>

<p>CTA: <a href='<?php esc_url( $order->get_checkout_payment_url() ); ?>'>Update Billing Information</a> </p>

<p>Please reach out to <a href='mailto:billing@deerdesigner.com'>billing@deerdesigner.com</a> if you need any additional help.</p>

<p>The Deer Designer Team.</p>

<?php
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
