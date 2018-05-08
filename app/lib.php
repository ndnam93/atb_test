<?php
use \Firebase\JWT\JWT;

const JWT_KEY = 'key';

function route() {
    $basePath = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
    $route = str_replace($basePath, '', $_SERVER['REDIRECT_URL']);
    $method = strtolower($_SERVER['REQUEST_METHOD']);
    $filePath = "pages\\$method-$route.php";
    if (!file_exists(__DIR__ . '\\' . $filePath)) {
        response_json([
            'success' => false,
            'error' => 'Resource not found',
        ], 404);
    }
    require $filePath;
}

function getDbConnection() {
    global $config;
    $dbConfig = @$config['database'];
    $conn = new mysqli(
        @$dbConfig['host'],
        @$dbConfig['username'],
        @$dbConfig['password'],
        @$dbConfig['database'],
        @$dbConfig['port']
    );
    if ($conn->connect_error) {
        throw new Exception('Cannot connect to database');
    }

    return $conn;
}

function findUser($email) {
    $db = getDbConnection();
    $statement = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $statement->bind_param('s', $email);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result->fetch_assoc();
    $db->close();
    return $user;
}

function updateUser($data) {
    global $user;
    $data = array_pick($data, ['name', 'address', 'phone', 'password']);
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    $data += findUser($user['email']);

    $db = getDbConnection();
    $statement = $db->prepare('UPDATE users SET name = ?, address = ?, phone = ?, password = ? WHERE email = ?');
    $statement->bind_param('sssss', $data['name'], $data['address'], $data['phone'], $data['password'], $user['email']);
    $succeeded = $statement->execute();
    $db->close();
    return $succeeded;
}

function response_json($payload, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($payload);
    die;
}

function generateJWT($user) {
    return JWT::encode([
        'email' => $user['email'],
    ], JWT_KEY);
}

/**
 * Pick elements of array
 *
 * @param array $array
 * @param array $allowed
 *
 * @return array
 */
function array_pick(array $array, array $allowed) {
    return array_intersect_key($array, array_flip($allowed));
}

/**
 * Authenticate via JWT. Return false if fail, return user data if success.
 *
 * @return bool|array
 */
function authenticate() {
    $token = str_replace('JWT ', '', $_SERVER['HTTP_AUTHORIZATION']);
    $payload = JWT::decode($token, JWT_KEY, ['HS256']);
    if (isset($payload->email) && $user = findUser($payload->email)) {
        unset($user['password']);
        $GLOBALS['user'] = $user;
        return  $user ;
    }
    response_json([
        'success' => false,
        'error' => 'unauthorized'
    ], 401);
}
