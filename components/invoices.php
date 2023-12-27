<?php
function currentUserInvoicesComponent(){
    $invoicesPageNumber = isset($_GET["invoices_page"]) ? $_GET["invoices_page"] : 1;

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
        'limit' => 5,
        'paginate' => true,
        'paged' => $invoicesPageNumber
    ));
    ?>

    <section class="user__invoices_section" style="margin-top: 40px;">
        <h2 class="dd__billing_portal_section_title">Your Invoices</h2>
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
                <?php $prevUrl = get_permalink( wc_get_page_id( 'myaccount' ) ) . "subscriptions/?invoices_page=" . $invoicesPageNumber - 1; ?>
                <?php $nextUrl = get_permalink( wc_get_page_id( 'myaccount' ) ) . "subscriptions/?invoices_page=" . $invoicesPageNumber + 1; ?>
                
                <a href="<?php echo $prevUrl; ?>" class="user__invoices_pagination_btn <?php echo $invoicesPageNumber > 1 ? 'btn_active' : 'btn_inactive'; ?>">Prev</a>
            
                <span><?php echo $invoicesPageNumber; ?></span>

                <a href="<?php echo $nextUrl; ?>" class="user__invoices_pagination_btn <?php echo $invoicesPageNumber < $currentUserOrders->max_num_pages ? 'btn_active' : 'btn_inactive'; ?>">Next</a>
            </div>
        <?php } ?>
    </section>
<?php }


add_action('currentUserInvoicesComponentHook', 'currentUserInvoicesComponent');


?>