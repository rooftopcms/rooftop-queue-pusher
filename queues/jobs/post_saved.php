<?php
require_once('rooftop_job.php');

class PostSaved extends RooftopJob {
    private $retry_max = 3;

    public function perform() {
        echo "\n\nPerforming job...";

        $payload = $this->args;

        $url = $payload['endpoint']['url'];
        $body = $payload['body'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // if we don't have success with response, or success with no response - re-queue this message
        $attempts = array_key_exists('attempts', $body) ? $body['attempts']+=1 : 1;

        if( 200 !== $status && $attempts < $this->retry_max ) {
            $payload['body']['attempts'] = $attempts;

            $delay = 60 * $attempts * $attempts; // retry after 1, 8, 27 minutes before giving up
            error_log("RETRY (status $status) - ". $this->retry_max . " > $attempts - retry in $delay");

            ResqueScheduler::enqueueIn($delay, "content", "PostSaved", $payload);
        }else {
            error_log("Not re-running job. Status: $status reached after $attempts attempts.");
        }
    }
}
?>