<?php
/**
 * Plugin Name: Hippoo Ticket
 * Version: 1.0.1
 * Plugin URI: https://Hippoo.app/
 * Description: A Free WooCommerce Plugin for Seamless Customer Support and support ticket.
 * Author: Hippoo team
 * Text Domain: hippoo-ticket
 * Domain Path: /languages
 * License: GPL3
 *
 * Hippoo! is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
* Hippoo! is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Hippoo!.
**/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly     

define('hippoo_ticket_path',dirname(__file__).DIRECTORY_SEPARATOR);
define('hippoo_ticket_url',plugins_url('hippoo-ticket').'/assets/');
global $hippoo_ticket_api_page;
$hippoo_ticket_api_page = 10;

require_once(ABSPATH."wp-admin/includes/image.php");

include_once(hippoo_ticket_path.'app'.DIRECTORY_SEPARATOR.'utils.php');
include_once(hippoo_ticket_path.'app'.DIRECTORY_SEPARATOR.'hooks.php');
include_once(hippoo_ticket_path.'app'.DIRECTORY_SEPARATOR.'web_api.php');
include_once(hippoo_ticket_path.'shortcode'.DIRECTORY_SEPARATOR.'ticket.php');
include_once(hippoo_ticket_path.'metabox'.DIRECTORY_SEPARATOR.'ticket_box.php');

register_activation_hook(__file__,'hippoo_ticket_register_hook');

function hippoo_ticket_register_hook(){
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    global $wpdb;
    $hippoo_ticket_table_name = $wpdb->prefix . 'hippoo_ticket';
    $sql = "CREATE TABLE IF NOT EXISTS {$hippoo_ticket_table_name} (
        id int(11) NOT NULL AUTO_INCREMENT,
        pid int(11) NOT NULL,
        uid int(11) NOT NULL,
        type varchar(7) NOT NULL,
        date datetime DEFAULT NULL,
        content text NOT NULL,
        media_ids text NOT NULL,
        see int(1) DEFAULT NULL,
        PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $wpdb->query($sql);

    $pg_ticket = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_content LIKE %s",
            'page',
            '%[hippoo_ticket]%'
        )
    );
    if(empty($pg_ticket)){
        $args = [
        'post_title'  => 'Hippoo Ticket',
        'post_type'   => 'page',
        'post_content'=> '[hippoo_ticket]',
        'post_status' =>'publish',
        'post_author' => get_current_user_id(),];
        $pg_ticket = wp_insert_post($args);
    }
    $args = [
    'sms'      => 'A message is sent to you.',
    'email'    => '<p>Dear User %user%</p>
    <p>A message is sent to you.</p>
    <p><a url="%url%">%ticket%</a></p>',
    'pg_ticket'=> $pg_ticket,
    ];
    update_option('hippoo_ticket',$args);
}

function hippoo_ticket_admin_menu(){

    // add_submenu_page('edit.php?post_type=hippoo_ticket','Settings','Settings','administrator','hippoo_ticket_con_ticket','hippoo_ticket_con_ticket');
    add_menu_page(
		__( 'Hippoo Ticket', 'hippoo-ticket' ),
		__( 'Hippoo Ticket', 'hippoo-ticket' ),
		'manage_options',
		'hippoo_ticket_con_ticket',
		'hippoo_ticket_con_ticket',
		( HIPPOO_POPUP_URL . '/images/icon.svg' )
	);

    global $menu,$wpdb;
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = %s AND post_status = %s",
            'hippoo_ticket',
            'hippoo_waiting'
        )
    );
    if(!empty($count)){
        foreach($menu as $i=>$men){
            if($men[2] == 'edit.php?post_type=hippoo_ticket'){
                $menu[$i][0] .= " <span class='update-plugins count-$count'><span class='plugin-count'>$count</span></span>";
            }
        }
    }
}
add_action('admin_menu','hippoo_ticket_admin_menu');

function hippoo_ticket_con_ticket(){
    include_once(hippoo_ticket_path.'app'.DIRECTORY_SEPARATOR.'config.php');
}


function hippoo_ticket_textdomain() {
    load_theme_textdomain( 'hippoo', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'hippoo_ticket_textdomain' );

function hippoo_ticket_page_style( $hook ) {
    if ( in_array( $hook, array( 'hippoo_ticket_page_hippoo_ticket_con_ticket' ) ) ) {
        wp_enqueue_style( 'hippoo_ticket_page_style', 
            hippoo_ticket_url . "css/style.css", null, 1.0);
    }
}

add_action( 'admin_enqueue_scripts', 'hippoo_ticket_page_style' );

/**
 * list of metaboxes
 */

function hippoo_ticket_register_meta_boxes() {
	add_meta_box( 'hippoo_ticket_metabox_1', 'Display Ticket', 'hippoo_ticket_display_callback', 'hippoo_ticket' );
}

add_action( 'add_meta_boxes', 'hippoo_ticket_register_meta_boxes');
?>