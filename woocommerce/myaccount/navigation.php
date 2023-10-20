<?php
/**
 * My Account navigation
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_account_navigation' );
?>


<?php $siteUrl = get_site_url(); ?>

<nav class="woocommerce-MyAccount-navigation">
	<div class="dd__dashboard_navbar">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : 

		$imgUrl = "";

		if($endpoint == "request-design"){
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/request-a-design.svg";
		}

		else if($endpoint == "view-tickets"){
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/view-my-tickets-1.svg";
		}

		else if($endpoint == "design-brief-checklist"){
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/Design-Brief-Checklist.svg";
		}
		
		else if($endpoint == "feedback"){
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/give-feedback.svg";
		}

		else if($endpoint == "deer-insights"){
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/Deer-Insights.png";
		}

		else if($endpoint == "subscriptions"){
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/pause-or-cancel-anytime.svg";
		}

		else if($endpoint == "deer-help"){
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/need-help.svg";
		}
		
		else{
			$imgUrl = "$siteUrl/wp-content/uploads/2023/09/billing-portal.svg";
		}
			
			?>
			<div class="dd__dashboard_navbar_item <?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" target="_blank">
					<img class="dd__dashboard_navbar_icon" src=<?php echo $imgUrl ; ?> />
					<span> <?php echo esc_html( $label ); ?> </span>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
