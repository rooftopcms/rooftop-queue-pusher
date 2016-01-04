<?php
require_once "../../../../../../vendor/autoload.php";

$settings = [
    'REDIS_BACKEND'     => REDIS_HOST.":".REDIS_PORT,    // Set Redis Backend Info
    'REDIS_BACKEND_DB'  => REDIS_DB,                 // Use Redis DB 0
    'COUNT'             => '2',                 // Run 1 worker
    'INTERVAL'          => '5',                 // Run every 5 seconds
    'QUEUE'             => '*',                 // Look in all queues
    'PREFIX'            => 'rooftop',           // Prefix queues with rooftop
];

foreach ($settings as $key => $value) {
    putenv(sprintf('%s=%s', $key, $value));
}
?>
