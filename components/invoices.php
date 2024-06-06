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

            $firstInvoice = $stripeInvoices->data[0];
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
    
    ?>

    <section class="user__invoices_section" style="margin-top: 40px;">
        <div class="user__invoices_btn_wrapper">            
            <?php if(!empty($stripeInvoices->data)){ ?>
                <button class="user__invoices_btn" <?php echo !isset($_GET['invoices_page']) ? 'autofocus' : '';  ?> id="previous__invoices_btn">Previous Invoices</button>
            <?php } ?>

            <?php if(!empty($currentUserOrders->orders)){ ?>
                <button class="user__invoices_btn" <?php if(isset($_GET['invoices_page']) || empty($stripeInvoices->data)){ echo 'autofocus'; } ?> id="newer__invoices_btn">Your Invoices</button>
            <?php } ?>
        </div>

        <div class="user__invoices_container">
            <?php if(!empty($stripeInvoices->data)){ ?>
                <div class="user__invoices_col <?php echo isset($_GET['invoices_page']) ? '' : 'show__content'; ?>" id="previous__invoices">            
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


                    <?php if(sizeof($stripeInvoices) == 5){ ?>
                        <div class="user__invoices_pagination">
                            <?php $prevUrl =  "$siteUrl/subscriptions/?ending_before=$endingBefore&stripe_invoices_page=" . ($stripeInvoicesPageNumber - 1); ?>
                            <?php $nextUrl = "$siteUrl/subscriptions/?starting_after=$startingAfter&stripe_invoices_page=" . ($stripeInvoicesPageNumber + 1); ?>
                            
                            <a href="<?php echo $prevUrl; ?>" class="user__invoices_pagination_btn <?php echo $stripeInvoicesPageNumber > 1 ? 'btn_active' : 'btn_inactive'; ?>">Prev</a>
                        
                            <span><?php echo $stripeInvoicesPageNumber; ?></span>

                            <a href="<?php echo $nextUrl; ?>" class="user__invoices_pagination_btn <?php echo sizeof($stripeInvoices->data) == $invoicesLimit  ? 'btn_active' : 'btn_inactive'; ?>">Next</a>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if(!empty($currentUserOrders->orders)){ ?>
                <div class="user__invoices_col <?php echo isset($_GET['invoices_page']) ? 'show__content' : ''; ?>" id="newer__invoices">            
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
                    </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </section>


    <style>
        .user__invoices_col{
            display: <?php echo !empty($stripeInvoices->data) ? 'none' : 'block'; ?>;
        }

        .user__invoices_btn_wrapper{
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .user__invoices_btn{
            background-color: transparent !important;
            border: none !important;
            border-bottom: 2px solid transparent !important;
            border-radius: 0 !important;
            color: #aaa !important;
            padding: 10px 0 !important;            
        }

        .user__invoices_btn:focus{
            color: var(--e-global-color-text) !important;
            border-bottom: 2px solid var(--e-global-color-f5169dc) !important;
            outline: unset !important;
        }

        
    </style>


    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const previousInvoicesContent = document.querySelector('#previous__invoices')
            const newerInvoicesContent = document.querySelector('#newer__invoices')

            function showInvoiceContent(invoiceContentToShow, invoiceContentoHide){
                invoiceContentToShow.classList.add('show__content');
                invoiceContentoHide.classList.remove('show__content');
            }

            if(document.querySelector('#previous__invoices_btn')){
                document.querySelector('#previous__invoices_btn').addEventListener('click', function(){
                    showInvoiceContent(previousInvoicesContent, newerInvoicesContent)
                });
            
                document.querySelector('#newer__invoices_btn').addEventListener('click', function(){
                    showInvoiceContent(newerInvoicesContent, previousInvoicesContent)
                });
            }
        })

    </script>
<?php }


add_action('currentUserInvoicesComponentHook', 'currentUserInvoicesComponent');


?>