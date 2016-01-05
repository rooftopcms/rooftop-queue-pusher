<?php
require_once "../../../../../../vendor/autoload.php";
/**
 * Use Dotenv to set required environment variables and load .env file in root
 */
$dotenv = new Dotenv\Dotenv('../../../../../../');
if (file_exists('../../../../../../.env')) {
    $dotenv->load();
    $dotenv->required( [
        'REDIS_HOST',
        'REDIS_PORT',
        'REDIS_DB'
    ] );
}

$settings = [
    'REDIS_BACKEND'     => getenv('REDIS_HOST').":".getenv('REDIS_PORT'),    // Set Redis Backend Info
    'REDIS_BACKEND_DB'  => getenv('REDIS_DB'),                 // Use Redis DB 0
    'COUNT'             => '1',                 // Run 1 worker
    'INTERVAL'          => '5',                 // Run every 5 seconds
    'QUEUE'             => '*',                 // Look in all queues
    'PREFIX'            => 'rooftop',           // Prefix queues with rooftop
];

foreach ($settings as $key => $value) {
    putenv(sprintf('%s=%s', $key, $value));
}
?>
