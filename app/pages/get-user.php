<?php
$user = authenticate();

response_json([
    'success' => true,
    'user' => $user,
]);

