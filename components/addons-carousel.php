<?php 
function addonsCarouselComponent($allProductAddons){ 
    $siteUrl = site_url();
    $currencySymbol = get_woocommerce_currency_symbol(apply_filters('wcml_price_currency', NULL ));
    ?>
    <div class="carousel__container">
        <div class="addons__carousel_form glider">								
            <?php
                foreach($allProductAddons[0] as $addon){?>		
                        <div class="addon__card">
                            <div class="subscriptions__addons_img">
                                <?php echo get_the_post_thumbnail( $addon->id ); ?>
                                
                                <div class="btn__wrapper">
                                    <a href='<?php echo "$siteUrl/?buy-now=$addon->id&with-cart=0"; ?>' data-product-name="<?php echo $addon->name;?>" data-product-price="<?php echo $currencySymbol . $addon->price; ?>"  class="addons__button one__click_purchase <?php echo strtolower(str_replace(' ', '-', $addon->name)); ?> "><?php echo $addon->name; ?></a>
                                </div>
                            </div>
                            <div class="addon__card_info">
                                <span class="addon__title"><?php echo $addon->name; ?></span><br>
                                <span class="addon__price"><?php echo $currencySymbol  . "$addon->price / "; do_action('callAddonsPeriod', $addon->name); ?></span>
                                <div class="addon__description">
                                    <?php echo $addon->description; ?>
                                </div>
                            </div>
                        </div>	
                <?php } ?>
            </div>
    </div>


<style>
    .carousel__container{
        max-width: 1140px;
    }

    .subscriptions__addons_img{
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .subscriptions__addons_wrapper .addon__card{
        flex-direction: row;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
    }

    .subscriptions__addons_wrapper img{
        margin: 0;
    }

    .subscriptions__addons_wrapper .addon__card_info{
        flex: 1;
        text-align: left;
    }
</style>

<?php $carouselSlidesToShow = sizeof($allProductAddons[0]) > 1 ? 2 : 1; ?>

<script>
	new Glider(document.querySelector('.glider'), {
	slidesToShow: <?php echo $carouselSlidesToShow; ?>,
	slidesToScroll: 1,
	draggable: true,
	dots: '.glider__dots',
	arrows: {
		prev: '.glider-prev',
		next: '.glider-next'
	},
    dragVelocity: 3.3,
	});
</script>
<?php } ?>

<?php add_action('addonsCarouselHook', 'addonsCarouselComponent');