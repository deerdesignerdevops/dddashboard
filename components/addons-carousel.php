<?php 
function addonsCarouselComponent($allProductAddons){ ?>
    <div class="carousel__container">
        <form action="" method="post" enctype="multipart/form-data" class="addons__carousel_form">								
            <?php
                foreach($allProductAddons[0] as $addon){?>		
                        <div class="addon__card">
                            <div class="subscriptions__addons_img">
                                <?php echo get_the_post_thumbnail( $addon->id ); ?>
                                
                                <button type="submit" class="single_add_to_cart_button button alt" name="add-to-cart" value="<?php echo $addon->id; ?>"><?php echo $addon->name; ?></button>
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
        </form>
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