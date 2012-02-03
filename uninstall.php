<?php
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}
delete_option('foliamaptool');

//debug - remove when ready
//mail("cj@folia.dk","Foliamaptool deactivated",$_SERVER["SERVER_NAME"]);
?>
