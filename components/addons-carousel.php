<?php 
function addonsCarouselComponent($allProductAddons){ 
    $siteUrl = site_url();
    ?>
    <div class="carousel__container">
        <div class="addons__carousel_form">								
            <?php
                foreach($allProductAddons[0] as $addon){?>		
                        <div class="addon__card">
                            <div class="subscriptions__addons_img">
                                <?php echo get_the_post_thumbnail( $addon->id ); ?>
                                
                                <div class="btn__wrapper">
                                    <a href='<?php echo "$siteUrl/?buy-now=$addon->id&with-cart=0"; ?>' data-product-name="<?php echo $addon->name;?>" data-product-price="<?php echo $addon->price; ?>"  class="addons__button one__click_purchase <?php echo strtolower(str_replace(' ', '-', $addon->name)); ?> "><?php echo $addon->name; ?></a>
                                </div>
                            </div>
                            <div class="addon__card_info">
                                <span class="addon__title"><?php echo $addon->name; ?></span><br>
                                <span class="addon__price"><?php echo get_woocommerce_currency_symbol() . "$addon->price / "; do_action('callAddonsPeriod', $addon->name); ?></span>
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

<?php $slidesToShow = sizeof($allProductAddons[0]) > 1 ? 2 : 1; ?>

<script>
	document.addEventListener("DOMContentLoaded", function(){
        $('.addons__carousel_form').slick({
		autoplay: false,
  		autoplaySpeed: 4000,
		infinite: true,
		speed: 300,
		slidesToShow: <?php echo $slidesToShow; ?>,
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
    })
</script>
<?php } ?>




<?php add_action('addonsCarouselHook', 'addonsCarouselComponent');