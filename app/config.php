<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly     
?>
<div class="wrap">
    <h1 class="wh-heading"><?php esc_html_e( 'Hippoo Settings', 'hippoo' ); ?></h1>
    
    <div class="settings">
        <?php
        if ( isset( $_POST['save'] ) ) {
            // Verify nonce for security
            if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'hippoo_nonce' ) ) {
                // Check user permissions here (if needed)
                if ( current_user_can( 'manage_options' ) ) {
                    $opt = [
                        'sms'        => sanitize_text_field( $_POST['sms'] ),
                        'email'      => sanitize_text_field( $_POST['email'] ),
                        'pg_ticket'  => sanitize_text_field( $_POST['pg_ticket'] ),
                    ];

                    // Update options only if the user has the required permissions
                    update_option( 'hippoo_ticket', $opt );
                    echo esc_html('<div class="updated"><p>' . __('Settings Saved.', 'hippoo') . '</p></div>');
                } else {
                    // Handle lack of permissions
                    echo esc_html('<div class="error"><p>' . __( 'Unauthorized Access.', 'hippoo' ) . '</p></div>');
                }
            } else {
                // Nonce verification failed
                echo esc_html('<div class="error"><p>' . __( 'Security check failed.', 'hippoo' ) . '</p></div>');
            }
        }



        $opt = get_option( 'hippoo_ticket', [] );
        ?>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'hippoo_nonce' ); ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th colspan="2"><?php esc_html_e( 'Settings', 'hippoo' ); ?></th>
                    </tr>
                </thead>
                <tr>
                    <td>SMS Content</td>
                    <td><input type="text" name="sms" size="70" value="<?php echo esc_attr( isset( $opt['sms'] ) ? $opt['sms'] : '' ); ?>" required="" /></td>
                </tr>
                <tr>
                    <td>Email Content</td>
                    <td>
                        <?php wp_editor( ( isset( $opt['email'] ) ? $opt['email'] : '' ), 'email', [ 'media_buttons' => false, 'textarea_rows' => 5 ] ); ?>
                        <p>
                            <small><?php esc_html_e( 'Use these values for Email %user%, Ticket Name %ticket%, Ticket URL %url%', 'hippoo' ); ?></small>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><?php esc_html_e( 'Pages', 'hippoo' ); ?></th>
                </tr>
                <tr>
                    <td>Ticket Page</td>
                    <td><?php wp_dropdown_pages( [ 'name' => 'pg_ticket', 'selected' => ( isset( $opt['pg_ticket'] ) ? $opt['pg_ticket'] : '' ) ] ); ?></td>
                </tr>
                <tfoot>
                    <tr>
                        <td colspan="2"><input type="submit" name="save" value="save" class="button-primary" /></td>
                    </tr>
                </tfoot>
            </table>
        </form>
        <br /><hr /><br />
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Shortcode List', 'hippoo' ); ?></th>
                </tr>
            </thead>
            <tr>
                <td><a href="javascript:void(0)">[hippoo_ticket]</a></td>
            </tr>
        </table>
    </div>


    <div class="short-desc">
        <div class="left">
            <p><?php esc_html_e( "Hippoo! is not just a shop management app, it's also a platform that enables you to extend its capabilities. With the ability to install extensions, you can customize your experience and add new features to the app. Browse and purchase WooCommerce plugins from our shop to enhance your store's functionality.", 'Hippoo-Ticket' ); ?></p>
            <a href="https://hippoo.app">
                <img class="hippoo-download" src="<?php echo wp_kses_post( hippoo_ticket_url . 'images/play-store.png' ); ?>" alt="Play Store">
            </a>
        </div>
    </div>
    
    <div class="extentions">
        <strong><?php esc_html_e( 'Hippoo extensions', 'hippoo' ); ?></strong>
        <p><?php esc_html_e( 'Customize Hippoo! with extensions! Browse and buy Hippoo plugins to add new features and enhance your experience. Download now and take your shop management to the next level!', 'hippoo' ); ?></p>

        <?php
        $response = wp_remote_get( 'https://hippoo.app/wp-json/wc/store/v1/products?category=57' );

        if ( is_wp_error( $response ) ) {
            // Handle errors here
        } else {

            $products = json_decode( wp_remote_retrieve_body( $response ), true );
            foreach ( $products as $product ) {
                $title       = wp_kses_post( $product['name'] );
                $description = wp_kses_post( $product['description'] );
                $price       = wp_kses_post( $product['prices']['price'] );
                $thumbnail   = wp_kses_post( $product['images'][0]['thumbnail'] );
                $permalink   = wp_kses_post( $product['permalink'] );

                $thumbnail_html = '';
                if ( ! empty( $thumbnail ) ) {
                    $thumbnail_html = '<img src="' . wp_kses_post( $thumbnail ) . '" alt="Thumbnail">';
                }
                
                if ($price == "0") {
                     $price = "Free";
                }

                $html = '
                <div class="item">
                    <div class="item-inner">
                        <strong>' . $title . '</strong>
                        <p>' . $description . '</p>
                        <span>Price: ' . $price . '</span>
                        <a href="' . $permalink . '" class="buy">Buy now</a>
                        '. $thumbnail_html . '
                    </div>
                </div>
                ';

                // echo wp_kses_post( $html );
                echo ( $html );
            }
        }
        ?>
    </div>

</div>
