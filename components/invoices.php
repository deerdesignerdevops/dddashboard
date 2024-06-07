<?php
function currentUserInvoicesComponent($currentUserStripeCustomerId){
    $stripe = new \Stripe\StripeClient(STRIPE_API);
    $invoicesLimit = 5;
    $siteUrl = site_url();
    
    if($currentUserStripeCustomerId){
        try{
            $currentStripeCustomer = $stripe->customers->retrieve($currentUserStripeCustomerId, []);
            
            if($currentStripeCustomer){
                if(isset($_GET["starting_after"])){
                    $stripeInvoices = $stripe->invoices->all(['limit' => 5, 'customer' => $currentUserStripeCustomerId, 'status' => 'paid', 'starting_after' => $_GET["starting_after"]]);
                }else if(isset($_GET["ending_before"])){
                    $stripeInvoices = $stripe->invoices->all(['limit' => 5, 'customer' => $currentUserStripeCustomerId, 'status' => 'paid', 'ending_before' => $_GET["ending_before"]]);
                }else{
                    $stripeInvoices = $stripe->invoices->all(['limit' => $invoicesLimit, 'customer' => $currentUserStripeCustomerId, 'status' => 'paid']);
                }
            }

            $firstInvoice = $stripeInvoices->data ? $stripeInvoices->data[0] : 0;
            $lastInvoice = end($stripeInvoices->data);

        }catch(Exception $e){
            $errorMessage = $e->getMessage();
            echo "Error: $errorMessage";
        }
    }



    $invoicesPageNumber = isset($_GET["invoices_page"]) ? $_GET["invoices_page"] : 1;
    $stripeInvoicesPageNumber = isset($_GET["stripe_invoices_page"]) ? $_GET["stripe_invoices_page"] : 1;

  
    function generateInvoicePdfUrl($orderId){
        $pdfUrl = wp_nonce_url( add_query_arg( array(
        'action'        => 'generate_wpo_wcpdf',
        'document_type' => 'invoice',
        'order_ids'     => $orderId,
        'my-account'    => true,
        ), admin_url( 'admin-ajax.php' ) ), 'generate_wpo_wcpdf' );

        return $pdfUrl;
    }
    $currentUserOrders = wc_get_orders(array(
        'customer_id' => get_current_user_id(),
        'status' => array('wc-completed'),
        'limit' => $invoicesLimit,
        'paginate' => true,
        'paged' => $invoicesPageNumber
    ));

    function checkWooInvoicesUrlParamenter(){
        if(!isset($_GET['stripe_invoices_page']) && !isset($_GET['invoices_page'])){
            $autoFocus = "user__invoices_btn_autofocus";
        }else if(isset($_GET['invoices_page'])){
            $autoFocus = "user__invoices_btn_autofocus";
        }else{
            $autoFocus = false;
        }

        return $autoFocus;
    }
    
    ?>

    <section class="user__invoices_section" style="margin-top: 40px;">
        <div class="user__invoices_btn_wrapper"> 
            <button class="user__invoices_btn <?php echo checkWooInvoicesUrlParamenter();  ?>" id="newer__invoices_btn">Your Invoices</button>           
            
            <button class="user__invoices_btn <?php echo isset($_GET['stripe_invoices_page']) ? 'user__invoices_btn_autofocus' : '';  ?>" id="previous__invoices_btn">Previous Invoices</button>
        </div>

        <div class="user__invoices_container">
            <!--WOO INVOICES-->
            <div class="user__invoices_col <?php echo isset($_GET['stripe_invoices_page']) ? '' : 'show__content'; ?>" id="newer__invoices">            
                <?php if(!empty($currentUserOrders->orders)){ ?>
                    <div class="user__invoices_wrapper">
                        <?php foreach($currentUserOrders->orders as $order){ ?>
                            <div class="user__invoice_row">
                                <span>#<?php echo $order->id; ?> - Invoice from <?php echo wc_format_datetime($order->get_date_completed()); ?></span>
                                <a target="_blank" href="<?php echo generateInvoicePdfUrl($order->id); ?>">Download Invoice</a>
                            </div>
                        <?php } ?>
                    </div>


                    <?php if($currentUserOrders->max_num_pages > 1){ ?>
                        <div class="user__invoices_pagination">
                            <?php $prevUrl = "$siteUrl/subscriptions/?invoices_page=" . ($invoicesPageNumber - 1); ?>
                            <?php $nextUrl = "$siteUrl/subscriptions/?invoices_page=" . ($invoicesPageNumber + 1); ?>
                            
                            <a href="<?php echo $prevUrl; ?>" class="user__invoices_pagination_btn <?php echo $invoicesPageNumber > 1 ? 'btn_active' : 'btn_inactive'; ?>">Prev</a>
                        
                            <span><?php echo $invoicesPageNumber; ?></span>

                            <a href="<?php echo $nextUrl; ?>" class="user__invoices_pagination_btn <?php echo $invoicesPageNumber < $currentUserOrders->max_num_pages ? 'btn_active' : 'btn_inactive'; ?>">Next</a>
                        </div>
                    <?php } ?>
                <?php }else{ ?>
                    <div class="dd__subscription_details">
				        <span class="dd__subscription_warning">No invoices found!</span>
			        </div>
               <?php } ?>
            </div>

            <!--STRIPE INVOICES-->
            <div class="user__invoices_col <?php echo isset($_GET['stripe_invoices_page']) ? 'show__content' : ''; ?>" id="previous__invoices">            
                <?php if(!empty($stripeInvoices->data)){ ?>
                    <div class="user__invoices_wrapper">
                        <?php foreach($stripeInvoices->data as $stripeInvoice){ 
                            $startingAfter = $lastInvoice->id;
                            $endingBefore = $firstInvoice->id;
                            ?>
                            <div class="user__invoice_row">
                                <span>#<?php echo substr($stripeInvoice->id, -4); ?> - Invoice from <?php echo date('F j, Y', $stripeInvoice->created); ; ?></span>
                                <a target="_blank" href="<?php echo $stripeInvoice->invoice_pdf; ?>">Download Invoice</a>
                            </div>
                        <?php } ?>
                    </div>

                    <!--PAGINATION-->
                    <?php if(sizeof($stripeInvoices) == 5){ ?>
                        <div class="user__invoices_pagination">
                            <?php $prevUrl =  "$siteUrl/subscriptions/?ending_before=$endingBefore&stripe_invoices_page=" . ($stripeInvoicesPageNumber - 1); ?>
                            <?php $nextUrl = "$siteUrl/subscriptions/?starting_after=$startingAfter&stripe_invoices_page=" . ($stripeInvoicesPageNumber + 1); ?>
                            
                            <a href="<?php echo $prevUrl; ?>" class="user__invoices_pagination_btn <?php echo $stripeInvoicesPageNumber > 1 ? 'btn_active' : 'btn_inactive'; ?>">Prev</a>
                        
                            <span><?php echo $stripeInvoicesPageNumber; ?></span>

                            <a href="<?php echo $nextUrl; ?>" class="user__invoices_pagination_btn <?php echo sizeof($stripeInvoices->data) == $invoicesLimit  ? 'btn_active' : 'btn_inactive'; ?>">Next</a>
                        </div>
                    <?php } ?>
                <?php }else{ ?>
                    <div class="dd__subscription_details">
				        <span class="dd__subscription_warning">No invoices found!</span>
			        </div>
                <?php } ?>
            </div>
        </div>
    </section>


    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const previousInvoicesBtn = document.querySelector('#previous__invoices_btn');
            const previousInvoicesContent = document.querySelector('#previous__invoices');
            const newerInvoicesBtn = document.querySelector('#newer__invoices_btn');
            const newerInvoicesContent = document.querySelector('#newer__invoices')

            function showInvoiceContent(invoiceContentToShow, invoiceContentoHide, btnToShow, btnToHide){
                invoiceContentToShow.classList.add('show__content');
                invoiceContentoHide.classList.remove('show__content');
                btnToShow.classList.add('user__invoices_btn_autofocus');
                btnToHide.classList.remove('user__invoices_btn_autofocus');
            }

            if(newerInvoicesBtn && previousInvoicesBtn){
                document.querySelector('#previous__invoices_btn').addEventListener('click', function(){
                    showInvoiceContent(previousInvoicesContent, newerInvoicesContent, previousInvoicesBtn, newerInvoicesBtn)
                });
            
                document.querySelector('#newer__invoices_btn').addEventListener('click', function(){
                    showInvoiceContent(newerInvoicesContent, previousInvoicesContent, newerInvoicesBtn, previousInvoicesBtn)
                });
            }
        })

    </script>
<?php }


add_action('currentUserInvoicesComponentHook', 'currentUserInvoicesComponent');


?>