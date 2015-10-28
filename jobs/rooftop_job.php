<?php
if( ! class_exists('RooftopJob') ):

    abstract class RooftopJob {

        function __construct() {
            echo "\nSetting up after perform event...\n";
            Resque_Event::listen('afterPerform', array('RooftopJob', 'afterPerform'));
        }

        public static function afterPerform($job) {
            echo "\n\n";
            echo "in afterPerform(). Perform finished!\n\n";

//            $args = $job->args;
//
//            $body = $args['body'];
//            $attempts = array_key_exists('attempts', $body) ? $body['attempts']+=1 : 1;
//            $body['attempts'] = $attempts;
//            $args['body'] = $body;
//
//            if( $attempts < 3 ) {
//                echo "\n\nRe-queue this job. Attempts: $attempts\n";
//            }

            echo "\n\n";
        }

        abstract public function perform();

//    protected function retry() {
//        echo "\n\nShould re-queue this job\n\n";
//        $url = $this->args['endpoint']['url'];
//        $body = $this->args['body'];
//
//        $attempts = array_key_exists('attempts', $body) ? $body['attempts']+=1 : 1;
//        $delay_in_minutes = $attempts*$attempts*$attempts;
//
//        if($attempts<3) {
//        }else {
//            error_log("\n\nnot retrying\n\n");
//        }
//    }
    }

endif;
?>