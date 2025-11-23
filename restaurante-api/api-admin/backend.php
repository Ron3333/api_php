<?php
declare(strict_types=1);

// === CORS ===
header('Access-Control-Allow-Origin: http://restaurante-api.test');
header('Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../flight/Flight.php';
require_once '../config/config.php';
require_once '../libs/AppAPI.php';

$app = new AppAPI();

$authenticate = function () use ($app) {

    $headers = getallheaders();

   //var_dump($headers);
   /*
    Flight::json([
        'debug_headers' => $headers,
        'raw_server' => $_SERVER
    ], 200);
   
     exit();
 */
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    

    if (!$authHeader || !preg_match('/Bearer\s+([^\s]+)/', $authHeader, $matches)) {
        Flight::json([
            'status' => 'error',
            'message' => 'Token requerido'
        ], 401);
        return false;
    }

    $token = $matches[1];

    try {

        /*

        $decoded = $app->validateToken($token);
        // DEBUG: Ver qué tiene $decoded
        Flight::json([
            'status' => 'debug',
            'decoded' => $decoded,
            'sub_type' => gettype($decoded['sub'])
        ], 200);
       */
        
        $decoded = $app->validateToken($token);
        $query = "SELECT id, adminname, email FROM admins WHERE id = :id";
        $stmt = $app->link->prepare($query);

        $stmt->execute(['id' => $decoded['sub']]);
        $admin = $stmt->fetch(PDO::FETCH_OBJ);
       // var_dump($admin);
        //exit();


        if (!$admin) {
            Flight::json(['status' => 'error', 'message' => 'Acceso denegado: no eres admin'], 403);
            return false;
        }

        Flight::set('current_admin', $admin);
        return true;
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => 'Token inválido'], 401);
        return false;
    }
};

// 3. Dashboard Stats
Flight::route('GET /admin/dashboard', function () use ($app)  {
    //if (!$authenticate()) return;

    $stats = [
        'foods' => $app->link->query("SELECT COUNT(*) FROM foods")->fetchColumn(),
        'orders' => $app->link->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'bookings' => $app->link->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'admins' => $app->link->query("SELECT COUNT(*) FROM admins")->fetchColumn(),
    ];

    Flight::json([
        'status' => 'success',
        'data' => $stats
    ], 200);
});

// 4. Listar Admins
Flight::route('GET /admin/admins', function () use ($app, $authenticate) {
    if (!$authenticate()) return;

    $query = "SELECT id, adminname, email FROM admins";
    $admins = $app->selectAll($query);

    Flight::json([
        'status' => 'success',
        'data' => $admins ?: []
    ], 200);
});
// Delete admin
Flight::route('DELETE /admin/admins/@id', function ($id) use ($app, $authenticate) {
    if (!$authenticate()) return;

    $id = intval($id);
    if ($id <= 0) {
        Flight::json(['status' => 'error', 'message' => 'ID inválido'], 400);
        return;
    }

    $admin = Flight::get('current_admin');
    //var_dump($admin->id);
    //exit();

    if ($id == $admin->id) {
        Flight::json(['status' => 'error', 'message' => 'No puedes eliminarte a ti mismo'], 403);
        return;
    }

    $delete = $app->link->prepare("DELETE FROM admins WHERE id = :id");
    $delete->execute(['id' => $id]);

    if ($delete->rowCount() > 0) {
        Flight::json(['status' => 'success', 'message' => 'Admin eliminado'], 200);
    } else {
        Flight::json(['status' => 'error', 'message' => 'Admin no encontrado'], 404);
    }
});

// 1. Login Admin
Flight::route('POST /admin/login', function () use ($app) {
    $email = Flight::request()->data->email ?? null;
    $password = Flight::request()->data->password ?? null;

    if (!$email || !$password) {
        Flight::json(['status' => 'error', 'message' => 'Email y contraseña requeridos'], 400);
        return;
    }

    $query = "SELECT id, adminname, email, password FROM admins WHERE email = :email";
    $stmt = $app->link->prepare($query);
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $payload = [
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24),
            'sub' => $admin['id'],
            'email' => $admin['email'],
            'adminname' => $admin['adminname'],
            'role' => 'admin'
        ];
        $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

        Flight::json([
            'status' => 'success',
            'message' => 'Login exitoso',
            'data' => [
                'token' => $jwt,
                'admin' => [
                    'id' => $admin['id'],
                    'adminname' => $admin['adminname'],
                    'email' => $admin['email']
                ]
            ]
        ], 200);
    } else {
        Flight::json(['status' => 'error', 'message' => 'Credenciales inválidas'], 401);
    }
});

Flight::start();






