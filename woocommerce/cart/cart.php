<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<style>
.coupon{
	display: none;
}
</style>

<?php
function defineSubscriptionPeriod($productPrice){
	if(str_contains($productPrice, 'month') !== false){
		return strstr($productPrice, '/ month');
	}else if(str_contains($productPrice, 'year') !== false){
		return strstr($productPrice, '/ year');
	}else{
		return "";
	}
}

?>



<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<div class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<div>
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php 
			$cartLength = sizeof(WC()->cart->get_cart());
			$couponDiscount = 0;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				
				if( count( WC()->cart->get_applied_coupons() ) > 0 ) {
					$couponsApplied = WC()->cart->get_applied_coupons();
					foreach($couponsApplied as $coupon){ 
						$currentCoupon= new WC_Coupon($coupon);
						$couponDiscount = $currentCoupon->amount;
					}
				}
				

				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$parent_product= wc_get_product($_product->get_parent_id());
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				$productPrice = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ));
				$product_description = $parent_product ? $parent_product->description : $_product->description;
				$productTerms = get_the_terms( $product_id, 'product_cat' );
				
				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					?>
					<div class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">


						<div class="cart__header">
							<h2 class="cart__header__title">Subscribe to <?php echo $product_name; ?></h2>
							<?php if($cartLength > 1){ 				
									foreach ($productTerms as $term) {
										$product_cat = $term->name;										
										?>
											<div class="product-remove">
												<?php
													echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
														'woocommerce_cart_item_remove_link',
														sprintf(
															'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
															esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
															/* translators: %s is the product name */
															esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
															esc_attr( $product_id ),
															esc_attr( $_product->get_sku() )
														),
														$cart_item_key
													)
												?>
											</div>
										<?php
									} ?>							
							<?php } ?>					
						</div>

						<span class="cart__header_price">
							<?php echo $productPrice; ?>
						</span>

						<div class="cart__content">						
							<?php
								echo $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
							?>						

							<div class="cart__product_details">

							<div class="cart__product_details_header">
								<span class="cart__product_name"><strong><?php echo $product_name; ?></strong></span>

								<!-- <div class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
										<?php
										if ( $_product->is_sold_individually() ) {
											$min_quantity = 1;
											$max_quantity = 1;
										} else {
											$min_quantity = 0;
											$max_quantity = $_product->get_max_purchase_quantity();
										}

										$product_quantity = woocommerce_quantity_input(
											array(
												'input_name'   => "cart[{$cart_item_key}][qty]",
												'input_value'  => $cart_item['quantity'],
												'max_value'    => $max_quantity,
												'min_value'    => $min_quantity,
												'product_name' => $product_name,
											),
											$_product,
											false
										);

										echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
										?>
								</div> -->
							</div>								
								<div class="cart__product_description">									
									<?php echo $product_description; ?>
								</div>

								<div class="cart__product_subtotal">
									<span>Subtotal</span>
										<?php
											echo get_woocommerce_currency_symbol() . $_product->get_price() . defineSubscriptionPeriod($productPrice);											
										?>
								</div>

								<?php
									if($couponDiscount){ ?>
										<div class="cart__product_subtotal">
											<span>Dicount: </span>
											<span>-<?php echo $couponDiscount; ?>% </span>
										</div>

										<div class="cart__product_subtotal">
											<span>Total: </span>
												<?php
													echo get_woocommerce_currency_symbol() . $_product->get_price() - ($_product->get_price() * ($couponDiscount / 100)) . defineSubscriptionPeriod($productPrice);
												?>
										</div>
									<?php }
								?>
							</div>
						</div>						
					</div>
					<?php
				}
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>

			<tr>
				<td colspan="6" class="actions">

					<?php if ( wc_coupons_enabled() ) { ?>
						<div class="coupon">
							<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
					<?php } ?>

<!-- 					<button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button> -->

					<?php do_action( 'woocommerce_cart_actions' ); ?>

					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</td>
			</tr>

			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</div>
	</div>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php
$user_id = get_current_user_id();
$userSubscriptions = wcs_get_users_subscriptions($user_id);
$currentUserProducts = [];

foreach ($userSubscriptions as $subscription){
	if (!$subscription->has_status(array('cancelled'))) {
		$subscription_products = $subscription->get_items();
		foreach ($subscription_products as $product) {
			$current_user_product_id = $product->get_product_id();
			array_push($currentUserProducts, $current_user_product_id);
		}
	}
}

$allProductAddons = wc_get_products([
   'category' => get_term_by('slug', 'add-on', 'product_cat')->slug,
   'exclude' => $currentUserProducts,
]);

?>

<?php if(sizeof($allProductAddons) > 0){ ?>
	<div class="cart__addons">
		<h2 class="cart__header__title">Available Addons</h2>
		<form action="" method="post" enctype="multipart/form-data" class="addons__carousel_form">								
			<?php
				foreach($allProductAddons as $addon){	?>		
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
		</form>
	</div>
<?php } ?>

<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

<div class="cart-collaterals">
	<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'woocommerce_cart_collaterals' );
	?>
</div>

<script>
	$('.addons__carousel_form').slick({
		autoplay: true,
  		autoplaySpeed: 4000,
		infinite: true,
		speed: 300,
		slidesToShow: 2,
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

<?php do_action( 'woocommerce_after_cart' ); ?>
