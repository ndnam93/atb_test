<?php
require '../vendor/autoload.php';
require '../app/lib.php';

set_error_handler(function($errno, $errstr ) {
    response_json([
        'success' => false,
        'error' => $errstr,
    ], 500);
});

$config = json_decode(file_get_contents('../config.json'), true);
route();
