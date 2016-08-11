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
            $url = parse_url( $payload['endpoint']['url'] );
            $host_prefix = $url['scheme'] == 'https' ? 'ssl://' : '';
            $host = $host_prefix.$url['host'];
            $scheme_port = $url['scheme']=='https' ? 443 : 80;
            $port = array_key_exists( 'port', $url ) ? $url['port'] : $scheme_port;

            $request_socket = fsockopen( $host, $port, $errno, $errstr, 10 );
            $request_body = "POST " . $url['path'] . " HTTP/1.1\r\n";
            $request_body.= "Host: " . $url['host'] . "\r\n";
            $request_body.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $request_body.= "Content-Length: " . strlen( http_build_query( $body ) ) . "\r\n";
            $request_body.= "Connection: Close\r\n\r\n";
            $request_body.= http_build_query( $body );

            fwrite( $request_socket, $request_body );
            fclose( $request_socket );
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