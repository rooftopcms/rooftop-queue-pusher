<?php
require_once(dirname(__FILE__).'/../../../../../../config/application.php');
require_once('rooftop_job.php');

class PostSaved extends RooftopJob {
    private $retry_max = 2;

    function tearDown() {
    }

    public function perform() {
        echo "\n\nPerforming job...\n";

        $payload = $this->args;
        $body = $payload['body'];

        try {
            $url = $payload['endpoint']['url'];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }catch (Exception $e) {
            // if we don't have success with response, or success with no response - re-queue this message
            $attempt = array_key_exists('attempts', $body) ? $body['attempts']+=1 : 1;

            if( $attempt <= $this->retry_max ) {
                $payload['body']['attempts'] = $attempt;
                $delay = ( 60 * $attempt * $attempt ) * $attempt; // retry after 1, 8, 27 minutes before giving up

                error_log("Exception raised: '" . $e->getMessage() . "' - Retrying job in {$delay}");
                ResqueScheduler::enqueueIn( $delay, "content", "PostSaved", $payload );
            }else {
                error_log("Exception raised: '" . $e->getMessage() . "' - Not retrying");
            }
        }
    }
}
?>