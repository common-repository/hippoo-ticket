<?php
    function hippoo_add_ticket($post_id, $content, $media_ids, $type, $user_id=0){

        # type:
        #    1 User
        #    2 Support
        $user_id = !empty($user_id) ? $user_id : get_current_user_id();

        // Check if $user_id is still empty or 0, then attempt to fetch author ID
        global $wpdb;
        if (empty($user_id)) {
            $author_id = $wpdb->get_var(
                $wpdb->prepare("SELECT post_author FROM $wpdb->posts WHERE ID = %d", esc_sql($post_id))
            );
            
            // Assign the retrieved author ID to $user_id only if it's not empty
            if ($author_id) {
                $user_id = $author_id;
            }
        }
        
        $post = get_post($post_id);
        if (empty($post))
            return array(
                "ticket_submited" => false,
                "message" => "The post_id is not found"
            );

        $ticket_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_type='hippoo_ticket' AND post_parent=%d",
                 esc_sql($post_id))
            );

        if(empty($ticket_id)){
            $args = [
                'post_title' =>"Ticket Number #$post_id",
                'post_author'=>$user_id,
                'post_type'  =>'hippoo_ticket',
                'post_status'=>'hippoo_waiting',
                'post_parent'=>$post_id
                ];
            $ticket_id = wp_insert_post($args);
        }

        if (empty($ticket_id))
            return array(
                "ticket_submited" => false,
                "message" => "Unable to insert ticket"
            );

        wp_update_post(['ID' => $ticket_id,'post_status' => ($type==1?'hippoo_waiting':'hippoo_answered')]);
		
		if( function_exists('parsidate') ){
			$date = parsidate('Y-m-d H:i:s','now','eng');
		} else {
			$date = date('Y-m-d H:i:s');
		}
		
        $args = [
            'pid'       =>$ticket_id,
            'uid'       =>$user_id,
            'type'      =>($type==1?'User':'Support'),
            'date'      =>$date,
            'content'   =>esc_sql($content),
            'media_ids' =>esc_sql($media_ids),
            'see'       => 0,
            ];
        
        $insert_result = $wpdb->insert("{$wpdb->prefix}hippoo_ticket", $args);
        if ($insert_result != true)
            return array(
                "ticket_submited" => false,
                "message" => "Unable to insert hippoo ticket"
        );

        return array(
                "ticket_submited" => true,
                "message" => "Ticket submited successfully"
        ); 
    }

    function hippoo_ticket_status($post_id, $item=3){
        $status = get_post_status($post_id);
        if(!in_array($status,['hippoo_waiting','hippoo_answered','hippoo_close']))
            return '';
        
        if($item == 1)
            return $status;
        
        $label = get_post_status_object($status)->label;
        
        if($item == 2)
            return $label;
        
        $color = $status=='hippoo_waiting'?'green':($status=='hippoo_close'?'red':'blue');
        return "<span style='color:$color'>$label</span>";
    }

    function hippoo_ticket_sms($ticket_id){
        $opt = get_option('hippoo_ticket',[]);
    }

    function hippoo_ticket_email($ticket_id){

        global $wpdb;
        $opt  = get_option('hippoo_ticket',[]);
        $url  = get_permalink($opt['pg_ticket'])."?oid=$ticket_id";
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT post_title, user_login, user_email FROM $wpdb->posts p JOIN $wpdb->users u ON u.ID = post_author AND p.ID = %d",
                $ticket_id
                )
            );
        $subj = 'Tickets reply URL '.home_url();
        $email= str_replace(['%user%','%ticket%','%url%'],[$row->user_login,$row->post_title,$url],$opt['email']);
        wp_mail($row->user_email,$subj,$email,['Content-Type: text/html; charset=UTF-8']);
    }

    function hippoo_ticket_get_ticket_order($order_id){
        global $wpdb;
        return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $wpdb->posts WHERE post_type='hippoo_ticket' AND post_parent=%d AND post_status IN ('hippoo_waiting', 'hippoo_answered', 'hippoo_close')",
                $order_id
            )
        );
    }


    function hippoo_ticket_get_ticket($ticket_id){
        global $wpdb;
        return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $wpdb->posts WHERE post_type='hippoo_ticket' AND ID = %d",
                    $ticket_id
                    )
                );
    }

    function hippoo_ticket_get_media_urls($media_ids) {
        $media_ids_array = explode(',', $media_ids);
        $media_urls = array();
        foreach ($media_ids_array as $media_id) {
            $attachment_metadata = wp_get_attachment_metadata($media_id);
            if ($attachment_metadata) {
                $media_url = wp_get_attachment_url($media_id);
                $media_urls[] = $media_url;
            }
        }
        return $media_urls;
    }
    
    function hippoo_ticket_get_media_urls_html($media_ids) {
        $html_img_template = "<li>
                                <a href='#URL' target='BLANK'>
                                    <img src='#URL' />
                                </a>
                             </li>";
        $html = "";
        $media_urls = hippoo_ticket_get_media_urls($media_ids);
        foreach ($media_urls as $media_url) {
            $html .= str_replace("#URL", $media_url, $html_img_template);
        }
        
        return $html;
    }

    function hippoo_ticket_generate_tickets_table_body_html($ticket_id){
        $rows = null;
        global $wpdb;
        $table_name = $wpdb->prefix . 'hippoo_ticket';
        if (!empty($ticket_id)) {
            $prepared_query = $wpdb->prepare(
                "SELECT * FROM $table_name
                WHERE pid = %d
                ORDER BY id DESC",
                $ticket_id
            );

            $rows = $wpdb->get_results($prepared_query);

            foreach ($rows as $row) {
                $row->media_urls_html = hippoo_ticket_get_media_urls_html($row->media_ids);
                $row->content = str_replace('\"', '"', $row->content);
            }
        }
        return $rows;
    }

    function hippoo_ticket_media_upload(){
    if (empty($_FILES['file'])) {
        return new WP_Error('invalid_file', 'Invalid file.', ['status' => 400]);
    }

    $attachment_ids = array();
    $files = isset($_FILES['file']) ? $_FILES['file'] : array();

    foreach ($files['name'] as $index => $name) {
        $file = array(
            'name' => $name,
            'type' => $files['type'][$index],
            'tmp_name' => $files['tmp_name'][$index],
            'error' => $files['error'][$index],
            'size' => $files['size'][$index]
        );

        // Check file size
        $file_size_limit = 350 * 1024; // 350 KB
        if ($file['size'] > $file_size_limit) {
            return new WP_Error('file_size_exceeded', 'File size exceeded the limit.', ['status' => 400]);
        }

        // Check file extension
        $allowed_extensions = array('png', 'jpg', 'jpeg');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            return new WP_Error('invalid_extension', 'Invalid file extension.', ['status' => 400]);
        }

        $upload = wp_upload_bits($file['name'], null, file_get_contents($file['tmp_name']));

        if (!$upload['error']) {
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($upload['file']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attachment_id = wp_insert_attachment($attachment, $upload['file']);

            if (!is_wp_error($attachment_id)) {
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
                $attachment_ids[] = $attachment_id;
            }
        }
    }
    $attachment_ids = implode(',', $attachment_ids);
    return $attachment_ids;
    }

?>