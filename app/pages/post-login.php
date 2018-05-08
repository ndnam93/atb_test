<?php

$email = $_POST['email'];
$password = $_POST['password'];

$user = findUser($email);
if (!$user) {
    response_json([
        'success' => false,
        'error'   => 'Email does not exist',
    ]);
}
if (!password_verify($password, $user['password'])) {
    response_json([
        'success' => false,
        'error'   => 'Incorrect password',
    ]);
}

response_json([
    'success' => true,
    'token'   => generateJWT($user),
]);
