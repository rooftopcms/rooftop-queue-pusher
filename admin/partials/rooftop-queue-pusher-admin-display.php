<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://errorstudio.co.uk
 * @since      1.0.0
 *
 * @package    Rooftop_Queue_Pusher
 * @subpackage Rooftop_Queue_Pusher/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<?php
function status_label($status) {
    switch($status) {
        case 200: return "Success"; break;
        case 500: return "Failed"; break;
        case 1: return "Waiting"; break;
        case 2: return "Running"; break;
        case 3: return "Failed"; break;
        case 4: return "Complete"; break;
        default: return $status;
    }
}
?>

<?php if( count($results) ):?>
    <br/>
    <hr/>

    <h3>
        Webhook calls
    </h3>

    <table class="wp-list-table widefat fixed striped pages">
        <thead>
        <tr>
            <th>Status</th>
            <th>Job type</th>
            <th>Post</th>
            <th>Message</th>
        </tr>
        </thead>

        <?php foreach($results as $result): ?>
            <tr>
                <td>
                    <?php echo status_label($result->status); ?>
                </td>
                <td>
                    <?php echo $result->job_class; ?>
                </td>
                <td>
                    <?php
                    $json = json_decode($result->payload);
                    $type = $json->body->type;
                    $id = $json->body->id;
                    ?>

                    <a href="/wp-admin/post.php?post=<?php echo $id;?>&action=edit" title="<?php echo preg_replace('/"/', '\'', $result->payload) ?>">/<?php echo $type;?>/<?php echo $id; ?></a>
                </td>
                <td>
                    <?php echo $result->message; ?>
                </td>
            </tr>
        <?php endforeach;?>
    </table>
<?php endif;?>
