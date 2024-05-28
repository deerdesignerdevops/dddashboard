<?php
function addNewDesignerCard($subscription){
    $siteUrl = site_url();
    $activeTasksProductId = 1600;
    $standardPlanMonthlyPrice = wc_get_product( 1589 )->get_price();
    $activeTaskProductPrice = wc_get_product( $activeTasksProductId )->get_price();
    $activeTaskProductName = wc_get_product( $activeTasksProductId )->get_name();
    $currencySymbol = get_woocommerce_currency_symbol(apply_filters('wcml_price_currency', NULL ));

    foreach($subscription->get_items() as $subscriptionItem){
        $currentSubscriptionPlan = $subscriptionItem['name'];
        $activeTaskProductPrice = str_contains($currentSubscriptionPlan, 'Standard') ? $standardPlanMonthlyPrice : $activeTaskProductPrice;
    }
?>
    <a class="dd__subscription_addons_task_card active-tasks one__click_purchase" href='<?php echo "$siteUrl/?add-to-cart=$activeTasksProductId"; ?>' data-plan="Standard" data-product-price="<?php echo $currencySymbol . $activeTaskProductPrice; ?>" data-product-name="<?php echo $activeTaskProductName; ?>">
        <div class="dd__subscription_addons_task_card_btn_new_wrapper">
            <div class="dd__subscription_addons_task_card_btn_new">
                <i class="fa-regular fa-square-plus"></i>
                <span>Add new designer</span>
            </div>
        </div>
    </a>
<?php
}
?>

<?php add_action('addNewDesignerCardHook', 'addNewDesignerCard'); ?>

