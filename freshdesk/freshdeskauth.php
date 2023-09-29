<?php
if (isset($_GET['authenticate'])) {
    $freshdeskAuthUrl = 'https://deerdesigner-626358737898751785.myfreshworks.com/login/auth/1695848017796?client_id=451979510707337272&redirect_uri=https%3A%2F%2Fdeerdesignerdevops.freshdesk.com%2Ffreshid%2Fcustomer_authorize_callback%3Fhd%3Ddeerdesignerdevops.freshdesk.com';
    header('Location: ' . $freshdeskAuthUrl);
    exit;
}

if (isset($_GET['callback_param'])) {
    header('Location: https://deerdesignerdevops.freshdesk.com/support/tickets');
    exit;
}
?>