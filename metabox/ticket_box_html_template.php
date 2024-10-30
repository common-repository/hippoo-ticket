<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
?>
<style type="text/css">
.ticket ul{
    column-count: 2;
}
.ticket li img{
    width:40%;
}
.ticket li{
    text-decoration: none;
    list-style: none;
}
</style>
<form method="post" action="">
    <table class="widefat striped ticket">
    <tr>
    	<td>
            <?php wp_editor('','scontent',['textarea_rows'=>5]); ?>
        </td>
    </tr>
    <?php 
    $nonce = wp_create_nonce( 'hippoo_ticket_meta_box_nonce' );
    wp_nonce_field( 'hippoo_ticket_meta_box_nonce_action', 'hippoo_ticket_meta_box_nonce' ); ?>
    <tbody>
        <?php if (empty($rows)) : ?>
            <tr>
                <td colspan="1">
                    <p>No History Found.</p>
                </td>
            </tr>
            <?php else : foreach ($rows as $row) : ?>
                <tr>
                    <td>
                        <p>
                            <strong>
                                <?php echo wp_kses_post($row->type); ?> :
                            </strong>
                            <br/>
                            <?php echo wp_kses_post($row->content); ?>
                        </p>
                        <ul><?php echo wp_kses_post($row->media_urls_html); ?></ul>
                        <br/>
                        <p>
                            <strong>Date : </strong>
                            <bdi>
                                <?php echo esc_html($row->date); ?>
                            </bdi>
                        </p>
                    </td>
                </tr>
            <?php endforeach; endif;?>
    </tbody>
    </table>
</form>
