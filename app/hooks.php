<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly     

/**
 * add column to account order
 *
 */
function hippoo_ticket_wc_add_my_account_orders_column( $columns ) {

    $columns['ticket'] = 'Support';
    $columns['ticket_status'] = 'Status';

    return $columns;
}
add_filter( 'woocommerce_account_orders_columns', 'hippoo_ticket_wc_add_my_account_orders_column' );


function hippoo_ticket_wc_custom_column_display( $order ) {
	global $wpdb;
	
	if(in_array($order->get_status(),['completed','cancelled'])){
		echo esc_html('-');
		return;
	}

   	$opt = get_option('hippoo_ticket', []);
	$url = esc_url( get_permalink( $opt['pg_ticket'] ) . '?oid=' . absint( $order->get_id() ) );
	$tid = absint( $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_type = 'hippoo_ticket' AND post_status IN ('hippoo_waiting','hippoo_answered','hippoo_close') AND post_parent = %d",
        absint( $order->get_id() )
    ) ) );

	echo wp_kses_post("<a href='" . esc_url($url) . "' class='woocommerce-button wp-element-button button' target='_blank'>Ticket</a>");

}
add_action( 'woocommerce_my_account_my_orders_column_ticket', 'hippoo_ticket_wc_custom_column_display' );

function hippoo_ticket_wc_custom_column_display_status( $order ) {
    global $wpdb;

    if ( in_array( $order->get_status(), ['completed', 'cancelled'] ) ) {
        echo esc_html('-');
        return;
    }

    $tid = absint( $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_type = 'hippoo_ticket' AND post_status IN ('hippoo_waiting','hippoo_answered','hippoo_close') AND post_parent = %d",
        absint( $order->get_id() )
    ) ) );

    echo wp_kses_post( hippoo_ticket_status( $tid ) );
}
add_action( 'woocommerce_my_account_my_orders_column_ticket_status', 'hippoo_ticket_wc_custom_column_display_status' );

/**
 * add column to ticket post type
 *
**/

add_filter('manage_hippoo_ticket_posts_columns','hippoo_ticket_columns');
function hippoo_ticket_columns( $columns ) {
    // this will add the column to the end of the array
    $columns['order_id'] = 'Order Id';
    $columns['ticket_id'] = 'Ticket Id';
    $columns['ticket_status'] = 'Status';
    return $columns;
}

add_action( 'manage_hippoo_ticket_posts_custom_column','hippoo_ticket_action_custom_columns_content', 10, 2 );

function hippoo_ticket_action_custom_columns_content( $column_id, $post_id ) {
    if ( $column_id == 'order_id' ) {
        $pid = absint( get_post_field( 'post_parent', $post_id ) );
        $url = esc_url( admin_url( "post.php?post=$pid&action=edit" ) );
        echo wp_kses_post("<a href='$url' target='_blank'>#$pid</a>");
    }

    if ( $column_id == 'ticket_id' )
        echo wp_kses_post( $post_id );

    if ( $column_id == 'ticket_status' )
        echo wp_kses_post( hippoo_ticket_status( $post_id ) );
}


/**
 * init
 */
add_action( 'init', 'hippoo_ticket_hook_init' );
function hippoo_ticket_hook_init() {
	$labels = array(
		'name'                  => 'Hippoo Tickets',
		'singular_name'         => 'Hippoo Ticket',
		'menu_name'             => 'Hippoo Tickets',
		'name_admin_bar'        => 'Hippoo Ticket',
		'add_new'               => 'Add Ticket',
		'add_new_item'          => 'Add New Ticket',
		'new_item'              => 'New Ticket',
		'edit_item'             => 'Edit Ticket',
		'view_item'             => 'View Ticket',
		'all_items'             => 'All Tickets',
		'search_items'          => 'Search Tickets',
	);

	$args = array(
		'labels'             => $labels,
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'ticket' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title','editor','author'),
	);

	register_post_type( 'hippoo_ticket', $args );

    register_post_status( 'hippoo_waiting', array(
		'label'                     => 'Wating for reply',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Waiting for reply <span class="count">(%s)</span>', 'Waiting for reply <span class="count">(%s)</span>' ),
	) );
    register_post_status( 'hippoo_answered', array(
		'label'                     => 'Answered',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Answered <span class="count">(%s)</span>', 'Answered <span class="count">(%s)</span>' ),
	) );
    register_post_status( 'hippoo_close', array(
		'label'                     => 'Closed',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>' ),
	) );
}


add_action('admin_footer-post.php', 'hippoo_ticket_append_post_status_list');

function hippoo_ticket_append_post_status_list(){
    global $post;

    if($post->post_type == 'hippoo_ticket'){
        $statuses = [
            'hippoo_waiting' => 'Waiting for reply',
            'hippoo_answered' => 'Answered',
            'hippoo_close' => 'Closed'
        ];

        $status_options = '';
        $label = '';

        foreach($statuses as $state => $text){
            $selected = selected( $post->post_status, $state, false );
            $status_options .= "<option value='" . esc_attr($state) . "' $selected>" . esc_html($text) . "</option>";

            if($post->post_status == $state){
                $label = $text;
            }
        }
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var statusSelect = document.querySelector("select#post_status");
                if (statusSelect) {
                    statusSelect.innerHTML = "<?php echo esc_html($status_options); ?>";
                }

                var statusDisplay = document.querySelector("#post-status-display");
                if (statusDisplay) {
                    statusDisplay.innerHTML += "<?php echo esc_html($label); ?>";
                }

                var publishButton = document.querySelector("#publish");
                if (publishButton) {
                    publishButton.name = "save";
                }
            });
        </script>
        <?php
    }
}


/**
 * manage ticket table list
 */
	function hippoo_ticket_remove_quick_edit( $actions, $post ) {
		if($post->post_type == 'hippoo_ticket') {
			unset($actions['inline hide-if-no-js']);
		}

		return $actions;
	}
    add_filter('post_row_actions','hippoo_ticket_remove_quick_edit',10,2);


    function hippoo_ticket_custom_bulk_actions($actions) {
        $actions['status_close']='Close Ticket';
        return $actions;
    }
    add_filter('bulk_actions-edit-hippoo_ticket','hippoo_ticket_custom_bulk_actions');

    add_filter('handle_bulk_actions-edit-hippoo_ticket', function($redirect_url, $action, $post_ids) {
    	if ($action == 'status_close') {
    		foreach ($post_ids as $post_id) {
    			wp_update_post([
    				'ID' => $post_id,
    				'post_status' => 'hippoo_close'
    			]);
    		}
    		$redirect_url = add_query_arg('status_close', count($post_ids), $redirect_url);
    	}
    	return $redirect_url;
    }, 10, 3);


    add_action( 'after_delete_post', 'hippoo_ticket_delete_ticket_hook', 10, 2 );
    function hippoo_ticket_delete_ticket_hook( $post_id, $post ) {
    	if ( 'hippoo_ticket' !== $post->post_type )
    		return;
        global $wpdb;
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}hippoo_ticket WHERE pid = %d", $post_id));
    }


    function hippoo_ticket_change_the_author( $name ) {
		if(is_admin()){
			if ( !function_exists( 'get_current_screen' ) ) {
			   require_once ABSPATH . '/wp-admin/includes/screen.php';
			}
			$screen = get_current_screen();
			if($screen->id != 'edit-hippoo_ticket')
				return $name;

			global $post;
			return get_post_meta($post->post_parent,'_billing_first_name',true).' '.get_post_meta($post->post_parent,'_billing_last_name',true);
		}
    }
    add_filter( 'the_author', 'hippoo_ticket_change_the_author', 10, 1);

	function hippoo_ticket_admin_footer(){
		if(is_admin() && isset($_GET['post_type']) && $_GET['post_type'] == 'hippoo_ticket'){
		?>
			<style type="text/css">
				{
					display: none;
				}
			</style>
			<script type="text/javascript">
				jQuery('a[href="post-new.php?post_type=hippoo_ticket"]').hide();
				jQuery('a.page-title-action').hide();
			</script>
		<?php
		}
	}
    add_action('admin_footer','hippoo_ticket_admin_footer');

?>