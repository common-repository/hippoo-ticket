<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly     

add_action('rest_api_init', function () {
    require_once __DIR__ . DIRECTORY_SEPARATOR .'web_api_auth.php';
    $controller = new HippooTicketControllerWithAuth();
    $controller->register_routes();
});
?>