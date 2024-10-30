<?php

class HippooTicketControllerWithAuth extends WC_REST_Customers_Controller {

    public function register_routes()
    {
        #
        $args_hippoo_ticket_insert = array(
            'methods'  => 'POST',
            'callback' => array( $this, 'hippoo_ticket_insert'),
            'permission_callback' => array( $this, 'hippo_edit_other_post_permissions_check' )

        );
        register_rest_route( 'wc-hippoo/v1', 'wp/tickets', $args_hippoo_ticket_insert);

        #
        $args_hippoo_ticket_list = array(
            'methods'  => 'GET',
            'callback' => array( $this, 'hippoo_ticket_list'),
            'permission_callback' => array( $this, 'hippo_edit_other_post_permissions_check' ),
            'args'                => array(
                                            'page' => array(
                                                'required'          => false
                                            ),
                                        )

        );
        register_rest_route( 'wc-hippoo/v1', 'wp/tickets', $args_hippoo_ticket_list);

        #
        $args_hippoo_ticket_get_ticket_info_by_ticket_id = array(
            'methods'  => 'GET',
            'callback' => array( $this, 'hippoo_ticket_get_ticket_info_by_ticket_id'),
            'permission_callback' => array( $this, 'hippo_edit_other_post_permissions_check' ),
            'args'                => array(
                                            'id' => array(
                                                'required' => true,
                                                'validate_callback' => function ($param, $request, $key) {
                                                    return is_numeric($param);
                                                }
                                            ),
                                            'page' => array(
                                                'required'          => false
                                            ),
                                        )
        );
        register_rest_route( 'wc-hippoo/v1', 'wp/tickets/(?P<id>\d+)', $args_hippoo_ticket_get_ticket_info_by_ticket_id );

        
        #
        $args_hippoo_ticket_get_ticket_info_by_order = array(
            'methods'  => 'GET',
            'callback' => array( $this, 'args_hippoo_ticket_get_ticket_info_by_order'),
            'permission_callback' => array( $this, 'hippo_edit_other_post_permissions_check' )

        );
        register_rest_route( 'wc-hippoo/v1', 'wp/tickets/order/(?P<id>\d+)', $args_hippoo_ticket_get_ticket_info_by_order );
        
        #
        $args_hippoo_ticket_delete = array(
            'methods'  => 'GET',
            'callback' => array( $this, 'hippoo_ticket_delete'),
            'permission_callback' => array( $this, 'hippo_edit_other_post_permissions_check' )

        );
        register_rest_route( 'wc-hippoo/v1', 'wp/tickets/(?P<id>\d+)/delete', $args_hippoo_ticket_delete);
        
        #
        $args_hippoo_ticket_update_status = array(
            'methods'  => 'POST',
            'callback' => array( $this, 'hippoo_ticket_update_status'),
            'permission_callback' => array( $this, 'hippo_edit_other_post_permissions_check' )

        );
        register_rest_route( 'wc-hippoo/v1', 'wp/tickets/(?P<id>\d+)/status', $args_hippoo_ticket_update_status);
        
        
        #
        $args_hippoo_ticket_count = array(
            'methods'  => 'GET',
            'callback' => array( $this, 'hippoo_ticket_count'),
            'permission_callback' => array( $this, 'hippo_edit_other_post_permissions_check' )

        );
        register_rest_route( 'wc-hippoo/v1', 'wp/tickets/count', $args_hippoo_ticket_count);
    }

    function hippo_edit_other_post_permissions_check(){
    	return current_user_can( 'edit_posts' );
    }

    function hippoo_ticket_delete($data){
       
        $ticket = hippoo_ticket_get_ticket_order($data['id']);
        
        if(empty($ticket))
            $response =  array(
                    'message' => 'No ticket found',
                );
            return new WP_REST_Response($response, 200);
            

        wp_delete_post($ticket->ID);
        $response =  array(
                    'message' => 'Ticket deleted',
                    );
        return new WP_REST_Response($response, 200);
    }

    function hippoo_ticket_update_status($data){
        $status = $data->get_json_params()['status'];
        $ticket = hippoo_ticket_get_ticket_order($data['id']);
        wp_update_post(['ID' => $ticket->ID,'post_status' => $status]);
        $response =  array(
                    'message' => 'Ticket status updated',
                    );
        return new WP_REST_Response($response, 200);
    }

    function hippoo_ticket_insert($data){
        global $wpdb,$hippoo_ticket_api_page;
        $arr = $data->get_json_params();

        $post_id    = $arr['post_id'];
        $content    = $arr['content'];
        $media_ids  = implode(',', $arr['media_ids']);
        $user_id    = get_post_meta($post_id, '_customer_user', true);
        $type       = 2;

        $hippoo_add_ticket_submmited = hippoo_add_ticket($post_id, $content, $media_ids, $type, $user_id);

        return new WP_REST_Response($hippoo_add_ticket_submmited, 200);
    }

    function hippoo_ticket_list($data){
        global $wpdb,$hippoo_ticket_api_page;

        if (!empty($data['page'])) {
            $page = esc_sql($data['page']);
        } else {
            $page = "1";
        }
        $page = --$page * $hippoo_ticket_api_page;

        $hippoo_ticket_table_name = $wpdb->prefix . 'hippoo_ticket';
        $query = $wpdb->prepare(
            "SELECT p.*, (
                SELECT content FROM $hippoo_ticket_table_name WHERE pid = p.id ORDER BY id DESC LIMIT 1
            ) AS latest
            FROM $wpdb->posts p 
            WHERE p.post_type = 'hippoo_ticket' AND p.post_status IN ('hippoo_waiting', 'hippoo_answered', 'hippoo_close')
            ORDER BY p.ID DESC 
            LIMIT %d, %d",
            $page,
            $hippoo_ticket_api_page
        );
        $rows = $wpdb->get_results($query);
        if(empty($rows))
        {
            $response =  array();
            return new WP_REST_Response($response, 200);
        }
        $out = [];
        foreach($rows as $ticket){

            $out[] = [
                'ticket_id'=> $ticket->ID,
                'order_id' => $ticket->post_parent,
                'customer_name' => get_post_meta($ticket->post_parent,'_billing_first_name',true).' '.get_post_meta($ticket->post_parent,'_billing_last_name',true),
                'customer_phone'=> get_post_meta($ticket->post_parent,'_billing_phone',true),
                'customer_email'=> get_post_meta($ticket->post_parent,'_billing_email',true),
                'date'         => $ticket->post_date,
                'latest_reply' => $ticket->latest,
                'status'       => $ticket->post_status
            ];
        }
        return new WP_REST_Response( $out, 200 );
    }

    function hippoo_ticket_get_ticket_info_by_order_id($data){

        $order   = wc_get_order( $data['id'] );
        if(empty($order))
        {
            $response =  array();
            return new WP_REST_Response($response, 200);
        }

        $ticket  = hippoo_ticket_get_ticket_order($data['id']);
        return $this->hippoo_ticket_get_tickets_and_replies($ticket);
    }

    function hippoo_ticket_get_ticket_info_by_ticket_id($data){

        if (empty($data['id'])) {
            $response =  array(
                    'message' => 'ticket_id not found',
            );
            return new WP_REST_Response($response, 200);
        }
        $ticket_id = $data['id'];
        $ticket = hippoo_ticket_get_ticket($ticket_id);

        if (empty($ticket)) {
            $response =  array();
            return new WP_REST_Response($response, 200);
        }
        return $this->hippoo_ticket_get_tickets_and_replies($ticket,$data);
    }

    function hippoo_ticket_get_tickets_and_replies($ticket, $data){
        global $wpdb,$hippoo_ticket_api_page;

        if (!empty($data['page'])) {
            $page = esc_sql($data['page']);
        } else {
            $page = "1";
        }
        $page = --$page * $hippoo_ticket_api_page;
        $query = $wpdb->prepare(
            "SELECT id, date, type as uby, content as value, media_ids 
            FROM {$wpdb->prefix}hippoo_ticket 
            WHERE pid = %d 
            ORDER BY id DESC 
            LIMIT %d, %d",
            $ticket->ID,
            $page,
            $hippoo_ticket_api_page
        );
        $replies = $wpdb->get_results($query);
        foreach($replies as $i=>$rep){
            if (property_exists($rep, 'media_ids') ) {
                $media_urls =  hippoo_ticket_get_media_urls($rep->media_ids);
                $replies[$i]->media_urls = $media_urls;
            }
        }
        $out = [
            'ticket_id'=> $ticket->ID,
            'order_id' => $ticket->post_parent,
            'customer_name' =>get_post_meta($ticket->post_parent,'_billing_first_name',true).' '.get_post_meta($ticket->post_parent,'_billing_last_name',true),
            'customer_phone'=>get_post_meta($ticket->post_parent,'_billing_phone',true),
            'customer_email'=>get_post_meta($ticket->post_parent,'_billing_email',true),
            'status'  =>$ticket->post_status,
            'replies' =>$replies
        ];
        return new WP_REST_Response( $out, 200 );
    }

    function hippoo_ticket_count($data){
        global $wpdb,$hippoo_ticket_api_page;
        $cnt = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = %s AND post_status = %s",
                'hippoo_ticket',
                'hippoo_waiting'
            )
        );
        return new WP_REST_Response($cnt, 200);
    }
    }
