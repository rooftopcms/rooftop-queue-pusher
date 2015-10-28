<?php
if( ! class_exists('RooftopJob') ):

    Resque_Event::listen('afterPerform', array('RooftopJob', 'afterPerform'));

    abstract class RooftopJob {
        public static function afterPerform($job) {
            // always perform this after perform() is called on a RooftopJob
        }

        abstract public function perform();
    }

endif;
?>