<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly     


function hippoo_ticket_display_callback( $post ) {
    # Generate the table
    $ticket_id = $post->ID;
    $rows = hippoo_ticket_generate_tickets_table_body_html($ticket_id);
    include(plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . 'ticket_box_html_template.php');
}

add_action( 'save_post_hippoo_ticket', 'hippoo_ticket_save_meta_box' );
function hippoo_ticket_save_meta_box( $post_id ) {
    
    // Verify nonce for security
    if (!( isset( $_POST['hippoo_ticket_meta_box_nonce'] ) && wp_verify_nonce( $_POST['hippoo_ticket_meta_box_nonce'], 'hippoo_ticket_meta_box_nonce_action' ) )) {
        return;
    }

    // Check it's not an auto save routine
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if (!is_admin()) return;

    if (!empty($_POST['scontent'])) {
        global $wpdb;
        $pid = $wpdb->get_var(
            $wpdb->prepare("SELECT post_parent FROM $wpdb->posts WHERE ID = %d", $post_id)
        );

        if ($pid) {
            remove_action('save_post_hippoo_ticket', 'hippoo_ticket_save_meta_box');
            $scontent = isset( $_POST['scontent'] ) ? sanitize_text_field( $_POST['scontent'] ) : '';
            hippoo_add_ticket( $pid, $scontent, [], 2 );
            add_action( 'save_post_hippoo_ticket', 'hippoo_ticket_save_meta_box' );
            hippoo_ticket_sms($post_id);
            hippoo_ticket_email($post_id);
        }
    }
}

?>
