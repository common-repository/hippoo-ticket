<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly     
?>

<style type="text/css">
.ticket{
    width:100%;
}
.ticket tr{
    border-bottom: 1px solid gray;
}
.ticket td{
    padding: 10px;
}
.ticket ul{
    column-count: 2;
}
.ticket li img{
    /* TODO */
    /* width:100%; */
    height: 240px;
}
.ticket li{
    text-decoration: none;
    list-style: none;
}
</style>
<h2 style="color: green; text-align: center; padding: 5px;"><?php echo esc_html($msg); ?></h2><br />

<form method="post" enctype="multipart/form-data">
<table class="ticket">
    <thead>
        <tr>
            <th>
                <p><strong>Ticket Name:</strong> <?php echo esc_html($ticket_name); ?></p>
                <p><strong>Tracking Number:</strong> #<?php echo esc_html($ticket_number); ?></p>
                <p><strong>Ticket Status:</strong> <?php echo wp_kses_post($ticket_status); ?></p>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)) : ?>
            <tr>
                <td>
                    <p>No History Found.</p>
                </td>
            </tr>
        <?php else : foreach ($rows as $row) : ?>
            <tr>
                <td>
                    <p><strong><?php echo wp_kses_post($row->type); ?> :</strong><br/><?php echo wp_kses_post($row->content); ?></p>
                    <ul><?php echo wp_kses_post($row->media_urls_html); ?></ul>
                    <br/>
                    <p><strong>Date : </strong><bdi><?php echo wp_kses_post($row->date); ?></bdi></p>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td>
                <?php wp_editor('', 'tcontent', ['media_buttons' => false, 'textarea_rows' => 5, 'quicktags' => false]); ?>
                <br />
                <input type="hidden" name="csrf_token" value="<?php echo esc_attr( wp_create_nonce('csrf_token_save') ); ?>" />
                <table class="upload_list">
                    <thead>
                        <tr>
                            <td>
                                <input type="button" value="Add Image" class="add_img" />
                            </td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="color: red;">Image size should not exceed 350KB</td>
                        </tr>
                    </tfoot>
                </table>
                <br />
                <input type="submit" name="save" value="Save" class="button btn button-primary" />
            </td>
        </tr>
    </tfoot>
</table>
</form>
<script type="text/javascript">
    jQuery('.add_img').click(function(){
        if(jQuery('.upload').length<5) {
            let str = '<tr><td><input type="file" name="file[]" class="upload" accept="image/*" size="20" /></td><td><i class="fa fa-window-close del" style="font-size:30px;color:red;cursor:pointer;"></i></td></tr>';
            jQuery('.upload_list').append(str);
        }
    });


    jQuery('body').on('change','.upload',function(){
    let file = this.files[0];
    if(file.size > 358400)
        this.value = '';
    });

    jQuery('body').on('click','.del',function(){
        if(confirm('Do you want to remove the image?'))
            jQuery(this).closest('tr').remove();
    });


    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href);
    }
</script>