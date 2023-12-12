<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Products items list
 *
 * Override this template by copying it to yourtheme/automatewoo/email/product-grid-2-col.php
 *
 * @see https://automatewoo.com/docs/email/product-display-templates/
 *
 * @var \WC_Product[] $products
 * @var \WC_Order $order
 * @var Workflow $workflow
 * @var string $variable_name
 * @var string $data_type
 * @var string $data_field
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$n = 1;

?>

	<?php if ( is_array( $products ) ): ?>

		<style>
			/** don't inline this css - hack for gmail */
			.aw-product-grid .aw-product-grid-item-2-col img {
				height: auto !important;
			}
		</style>

		<table cellspacing="0" cellpadding="0" class="aw-product-grid">
			<tbody><tr><td style="padding: 0;"><div class="aw-product-grid-container">

				<?php if ( isset( $order ) ): ?>
					<?php $products = $order->get_items(); ?>
				<?php endif; ?>

				<?php foreach ( $products as $product ): ?>
					<?php $filtered_permalink_data    = automatewoo_email_template_product_permalink( $product ) ?>
					<?php $permalink                  = $filtered_permalink_data['permalink']; ?>
					<?php $filtered_product_name_data = automatewoo_email_template_product_name( $product ) ?>
					<?php $product_name               = $filtered_product_name_data['product_name']; ?>
					<?php $product                    = $filtered_product_name_data['product']; ?>

					<tr>
						<td class="td" style="text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
							<span><?php echo esc_html( $product_name ); ?></span>		
						</td>

						<td class="td" style="text-align: right; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
							<span class="price"><strong><?php echo $product->get_price_html(); ?></strong></span>
						</td>
					</tr>
										


				<?php $n++; endforeach; ?>

			</div></td></tr></tbody>
		</table>

	<?php endif; ?>
