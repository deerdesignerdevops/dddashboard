<?php
/**
 * Edit account form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

$user = wp_get_current_user();
$currentUserRoles = $user->roles;

$userCurrentProducts = [];
$groups_user = new Groups_User( get_current_user_id() );
$groupId = $groups_user->groups[1]->group_id;

$group = new Groups_Group( $groupId );
$membersOfCurrentUserGroup = $group->users;

//REMOVE ADDITIONAL USER FROM DATABASE
if(isset($_GET['remove_additional_user']) && isset($_GET['_wpnonce'])){
	if(wp_verify_nonce($_GET['_wpnonce'], 'action')){
		do_action('removeAdditionalUserFromDatabaseHook', $_GET['remove_additional_user']);
	}
}


?>

<style>
.dash__menu, .welcome-h1, .dd-checklist, .dd-insights, .dd-cta {
	display: none;
}

fieldset {
  border: 1px solid #ccc !important;
  border-radius: 5px !important;
  margin: 20px 0;
}
	
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<section>
	<a href="<?php echo get_permalink( wc_get_page_id( 'myaccount' )); ?>" class="dd__bililng_portal_back"><i class="fa-solid fa-chevron-left"></i> Back to Dashboard</a>
	
	<div class="account__details_row">
		<div class="account__details_col">
			<form class="woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >
				<?php do_action( 'woocommerce_edit_account_form_start' ); ?>
				<h2 class="myaccount__page_title">Your details</h2>

				<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
					<label for="account_first_name"><?php esc_html_e( 'First name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" />
				</p>
				<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
					<label for="account_last_name"><?php esc_html_e( 'Last name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" />
				</p>
				<div class="clear"></div>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="account_display_name"><?php esc_html_e( 'Display name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" /> <span><em><?php esc_html_e( 'This will be how your name will be displayed in the account section and in reviews', 'woocommerce' ); ?></em></span>
				</p>
				<div class="clear"></div>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="account_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
				</p>

				<fieldset>
					<legend><?php esc_html_e( 'Password change', 'woocommerce' ); ?></legend>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="off" />
					</p>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="off" />
					</p>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="password_2"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="off" />
					</p>
				</fieldset>
				<div class="clear"></div>

				<?php do_action( 'woocommerce_edit_account_form' ); ?>

				<p>
					<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
					<button type="submit" class="woocommerce-Button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> dd__primary_button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
					<input type="hidden" name="action" value="save_account_details" />
				</p>

				<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
			</form>

			<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
		</div>


		<?php if(!in_array('team_member', $currentUserRoles)){ ?>
			<div class="account__details_col">
				<div class="payment__methods">
					<?php 
					//PAYMENT METHODS
					$saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
					$has_methods   = (bool) $saved_methods;
					$types         = wc_get_account_payment_methods_types();

					do_action( 'woocommerce_before_account_payment_methods', $has_methods ); ?>
					
					<h2 class="myaccount__page_title">Payment Methods</h2>
					<?php if ( $has_methods ) : ?>							
						<?php foreach ( $saved_methods as $type => $methods ) :  ?>
							<?php foreach ( $methods as $method ) : ?>
								<div class="user__invoice_row">
										<?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
												<?php
													if ( has_action( 'woocommerce_account_payment_methods_column_' . $column_id ) ) {
														do_action( 'woocommerce_account_payment_methods_column_' . $column_id, $method );
													} elseif ( 'method' === $column_id ) { ?>
														<span>
															<?php
																if ( ! empty( $method['method']['last4'] ) ) {
																	echo sprintf( esc_html__( '%1$s ending in %2$s', 'woocommerce' ), esc_html( wc_get_credit_card_type_label( $method['method']['brand'] ) ), esc_html( $method['method']['last4'] ) );
																} else {
																	echo esc_html( wc_get_credit_card_type_label( $method['method']['brand'] ) );
																}
															?>
														
															<?php
															} elseif ( 'expires' === $column_id ) {
																echo ' - ' . esc_html( $method['expires'] );
															?>

														</span>
													<?php
													} elseif ( 'actions' === $column_id ) { ?>
														<div style="display: flex; gap: 12px;">

														<?php
														foreach ( $method['actions'] as $key => $action ) {
															echo '<a href="' . esc_url( $action['url'] ) . '" >' . esc_html( $action['name'] ) . '</a>';
														}
														?>
														</div>
													<?php }
												?>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
						<?php endforeach; ?>

					<?php else : ?>

						<?php wc_print_notice( esc_html__( 'No saved payment methods found.', 'woocommerce' ), 'notice' ); ?>

					<?php endif; ?>

					<?php do_action( 'woocommerce_after_account_payment_methods', $has_methods ); ?>

					<?php if ( WC()->payment_gateways->get_available_payment_gateways() ) : ?>
						<div style="margin-top: 20px;">
							<a class="woocommerce-Button button dd__primary_button" href="<?php echo esc_url( wc_get_endpoint_url( 'add-payment-method' ) ); ?>"><?php esc_html_e( 'Add payment method', 'woocommerce' ); ?></a>
						</div>
					<?php endif; ?>
				</div>

				<div class="team__members">
					<h2 class="myaccount__page_title">Additional Users</h2>

					<?php if(!empty($membersOfCurrentUserGroup) && sizeof($membersOfCurrentUserGroup) > 1){ ?>
						<div class="team__members_list">

							<?php foreach($membersOfCurrentUserGroup as $group){ ?>
								<?php if($group->user->id !== get_current_user_id()){ ?>
									
									<?php
									$removeAdditionalUserUrl = get_permalink( wc_get_page_id( 'myaccount' ) ) . "/edit-account/?remove_additional_user=" . $group->user->id;
									$removeAdditionalUserUrlWithNonce = add_query_arg( '_wpnonce', wp_create_nonce( 'action' ), $removeAdditionalUserUrl );
									?>

									<div class="team__members_row">
										<span><strong><?php echo $group->user->first_name; ?></strong></span>
										
										<span><?php echo $group->user->user_email; ?>
											<a href="<?php echo $removeAdditionalUserUrlWithNonce; ?>" onclick="return confirm('Are you sure?')"><i class="fa-solid fa-circle-minus"></i></a>
										</span>
										
									</div>
								<?php } ?>
							<?php } ?>
							
						</div>	
					<?php } ?>
					
					<?php echo do_shortcode('[fluentform id="7"]'); ?>
				</div>


			</div>
		<?php } ?>
	</div>


</section>





