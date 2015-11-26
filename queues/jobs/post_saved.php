<?php
require_once(dirname(__FILE__).'/../../../../../../config/application.php');
require_once('rooftop_job.php');

class PostSaved extends RooftopJob {
    private $retry_max = 3;

    function setUp() {
        $this->mysql = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    }
    function tearDown() {
        echo "\n\ntearDown\n\n";
        $this->mysql->close();
        unset($this->mysql);
    }

    public function perform() {
        echo "\n\nPerforming job...\n";
        $inflector = ICanBoogie\Inflector::get('en');

        try {
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
            $attempt = array_key_exists('attempts', $body) ? $body['attempts']+=1 : 1;

            if( 200 !== $status && $attempt < $this->retry_max ) {
                $payload['body']['attempts'] = $attempt;

                $delay = (60 * $attempt * $attempt)*$attempt; // retry after 1, 8, 27 minutes before giving up
                error_log("RETRY (status $status) - ". $this->retry_max . " > $attempt - retry in $delay");

                ResqueScheduler::enqueueIn($delay, "content", "PostSaved", $payload);

                $this->saveJobStatus($status, "Job failed at $attempt attempt. Will retry...");
            }else {
                error_log("Not re-running job. Status: $status reached after $attempt attempts.");
                $this->saveJobStatus($status, "Job completed after $attempt attempts.");
            }
        }catch (Exception $e) {
            error_log("Not re-running job. Exception received: '" . $e->getMessage() . "'");
            $this->saveJobStatus(500, "Job failed. Not retrying.");
        }
    }

    private function saveJobStatus($status, $message) {
        try {
            $payload = $this->args;
            $prefix = $table_prefix = getenv('DB_PREFIX') ?: 'wp_';
            $table_name = $prefix . $payload['body']['blog_id']."_completed_jobs";
            $rooftop_job_id = $this->job->payload['args'][0]['rooftop_job_id'];

            $message = $this->mysql->real_escape_string($message);

            $sql = <<<EOSQL
UPDATE $table_name SET status = $status, message = '$message' WHERE rooftop_job_id = '$rooftop_job_id';
EOSQL;
            echo $sql . "\n\n";

            $this->mysql->query($sql);
        }catch (Exception $e) {

        }
    }
}
?>