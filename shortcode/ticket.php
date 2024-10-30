<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly     

add_action('init', 'hippoo_ticket_output_buffer');
add_shortcode('hippoo_ticket','hippoo_short_ticket');

function hippoo_ticket_output_buffer() {
    ob_start();
}

function hippoo_short_ticket($atts=[]){

    if(defined('REST_REQUEST'))
        return;
        
    if( !is_admin() && (!is_user_logged_in() or empty($_GET['oid']) or !is_numeric($_GET['oid'])) ){
        wp_redirect(home_url());
        exit();
    }

    
    ob_start();
    
    global $wpdb;
    $order_id = absint($_GET['oid']); // Sanitize input
    $msg      = '';
    $order    = $wpdb->get_row(
                    $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $order_id)
                );
    $uid      = get_current_user_id();
    $cid      = get_post_meta($order_id, '_customer_user', true);
    

    $ticket_id = $wpdb->get_var(
                    $wpdb->prepare("SELECT ID FROM $wpdb->posts 
                                    WHERE post_type='hippoo_ticket' AND post_parent= %d", $order->ID)
                    );
   
   if(get_post_status($ticket_id) == 'trash'){
        wp_redirect(wc_get_page_permalink( 'myaccount' ) . '/orders');
        exit();
    }
    

    if (isset($_POST['save']) && isset($_POST['csrf_token'])) {
        if (wp_verify_nonce($_POST['csrf_token'], 'csrf_token_save')) {

            // Check if file upload was successful
            if (
                isset($_FILES['file']) &&
                is_array($_FILES['file'])
            ) {
                $media_ids = hippoo_ticket_media_upload();
            } else {
                $media_ids = ''; // No file uploaded or an error occurred
            }

            // Sanitize and validate content
            $tcontent = isset($_POST['tcontent']) ? sanitize_text_field($_POST['tcontent']) : '';

            if (!empty($tcontent)) {
                hippoo_add_ticket($order_id, $tcontent, $media_ids, 1);
                $msg = 'Ticket submitted.';
            } else {
                $msg = 'Error: Invalid ticket content.';
            }
        }
    }


    
    # Generate the table
    $rows = hippoo_ticket_generate_tickets_table_body_html($ticket_id);
    $ticket_name = sanitize_text_field( str_replace( "Private:", "", get_the_title( absint( $_GET['oid'] ) ) ) );
    $ticket_number = empty($ticket_id) ? '' : $ticket_id;
    $ticket_status = empty($ticket_id) ? 'New Ticket' : wp_kses_post(hippoo_ticket_status($ticket_id));
    include(plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . 'ticket_html_template.php');

    return ob_get_clean();
}
?>
