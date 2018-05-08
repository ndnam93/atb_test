<?php
$user = authenticate();
$succeeded = updateUser( $_POST);

response_json([
    'success' => $succeeded,
]);