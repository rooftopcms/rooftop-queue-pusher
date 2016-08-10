<?php
require_once(dirname(__FILE__).'/../../../../../../config/application.php');
require_once('rooftop_job.php');

class PostSaved extends RooftopJob {
    function tearDown() {
    }

    public function perform() {
        echo "\n\nPerforming job...\n";

        try {
            $payload = $this->args;
            $body = $payload['body'];

            $url = parse_url( $payload['endpoint']['url'] );
            $host = $url['host'];
            $port = array_key_exists( 'port', $url ) ? $url['port'] : 80;

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
            error_log("Exception raised: '" . $e->getMessage() . "'");
        }
    }
}
?>