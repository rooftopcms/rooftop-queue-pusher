<?php
require_once('rooftop_job.php');

class PostSaved extends RooftopJob {
    public function setUp() {
    }
    public function tearDown() {
    }

    public function perform() {
        echo "\n\nPerforming job...";
        $url = $this->args['endpoint']['url'];
        $body = $this->args['body'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // if we don't have success with response, or success with no response - re-queue this message
        $attempts = array_key_exists('attempts', $body) ? $body['attempts']+=1 : 1;

        echo "\nStatus: $status \n";
        echo "Attempts: $attempts \n";
    }
}
?>