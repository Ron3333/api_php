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

// 5. Crear Admin

Flight::route('POST /admin/admins', function () use ($app, $authenticate) {
    if (!$authenticate()) return;

    // VOLVEMOS A USAR Flight::request()->data
    $adminname = Flight::request()->data->adminname ?? null;
    $email = Flight::request()->data->email ?? null;
    $password = Flight::request()->data->password ?? null;

    if (!$adminname || !$email || !$password) {
        Flight::json(['status' => 'error', 'message' => 'Todos los campos son obligatorios'], 400);
        return;
    }

    $check = $app->link->prepare("SELECT id FROM admins WHERE email = :email");
    $check->execute(['email' => $email]);
    if ($check->rowCount() > 0) {
        Flight::json(['status' => 'error', 'message' => 'Email ya registrado'], 409);
        return;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $insert = $app->link->prepare("INSERT INTO admins (adminname, email, password) VALUES (:adminname, :email, :password)");
    $insert->execute([
        'adminname' => $adminname,
        'email' => $email,
        'password' => $hashed
    ]);

    Flight::json([
        'status' => 'success',
        'message' => 'Admin creado',
        'data' => ['id' => $app->link->lastInsertId()]
    ], 201);
});

/****************************************** Foods **************************************************/

// === CRUD FOODS ===

// 1. Listar todos los foods
Flight::route('GET /admin/foods', function () use ($app, $authenticate) {
    if (!$authenticate()) return;

    $query = "SELECT f.*, 
                     CASE 
                       WHEN f.meal_id = 1 THEN 'Breakfast'
                       WHEN f.meal_id = 2 THEN 'Lunch'
                       WHEN f.meal_id = 3 THEN 'Dinner'
                       ELSE 'Unknown'
                     END as meal_name
              FROM foods f ORDER BY f.id DESC";
    $foods = $app->selectAll($query);

    Flight::json([
        'status' => 'success',
        'data' => $foods ?: []
    ], 200);
});

// 2. Obtener un food por ID
Flight::route('GET /admin/foods/@id', function ($id) use ($app, $authenticate) {
    if (!$authenticate()) return;

    $id = intval($id);
    $query = "SELECT * FROM foods WHERE id = :id";
    $food = $app->selectOne($query, ['id' => $id]);

    if (!$food) {
        Flight::json(['status' => 'error', 'message' => 'Food no encontrado'], 404);
        return;
    }

    Flight::json([
        'status' => 'success',
        'data' => $food
    ], 200);
});

// 3. Crear food (con subida de imagen)
Flight::route('POST /admin/foods', function () use ($app, $authenticate) {
    if (!$authenticate()) return;

    // === LEER DATOS DEL FORMULARIO ===
    $request = Flight::request();
    $name = $request->data->name ?? null;
    $price = $request->data->price ?? null;
    $description = $request->data->description ?? null;
    $meal_id = intval( $request->data->meal_id ?? null );



    if (!$name || !$price || !$description || !$meal_id) {
        Flight::json(['status' => 'error', 'message' => 'Faltan campos requeridos'], 400);
        return;
    }

    // === LEER ARCHIVO CON FLIGHT ===
    $uploadedFiles = $request->getUploadedFiles();
    if (!isset($uploadedFiles['image'])) {
        Flight::json(['status' => 'error', 'message' => 'Imagen requerida'], 400);
        return;
    }

    $file = $uploadedFiles['image'];
    if ($file->getError() !== UPLOAD_ERR_OK) {
        Flight::json(['status' => 'error', 'message' => 'Error al subir imagen: ' . $file->getError()], 400);
        return;
    }

    // Validar extensión
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        Flight::json(['status' => 'error', 'message' => 'Extensión no permitida'], 400);
        return;
    }

    // Generar nombre único
    $uniqueName = uniqid('food_', true) . '.' . $ext;
    $uploadDir = __DIR__ . "/foods-images/";
    $filePath = $uploadDir . $uniqueName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Mover archivo
    try {
        $file->moveTo($filePath);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => 'Error al guardar imagen'], 500);
        return;
    }

    // Insertar en DB
    $query = "INSERT INTO foods (name, price, description, meal_id, image) VALUES (:name, :price, :description, :meal_id, :image)";
    //var_dump( $meal_id);
   // exit();
    $params = [
        ':name' => $name,
        ':price' => $price,
        ':description' => $description,
        ':meal_id' => $meal_id,
        ':image' => $uniqueName
    ];

    //var_dump($params );
    //exit();

    try {
        $app->register($query, $params);
        Flight::json([
            'status' => 'success',
            'message' => 'Food creado',
            'data' => [
                'id' => $app->link->lastInsertId(),
                'image' => $uniqueName
            ]
        ], 201);
    } catch (Exception $e) {
        @unlink($filePath);
        error_log("DB Error: " . $e->getMessage());
        Flight::json(['status' => 'error', 'message' => 'Error en base de datos', 'DB' => $e->getMessage() ], 500);
    }
});

// 4. Actualizar food (RESTful + imagen opcional)

Flight::route('PUT /admin/foods/@id', function ($id) use ($app, $authenticate) {
    if (!$authenticate()) return;

    $id = intval($id);
    $request = Flight::request();

    // === SOLO application/json → Flight::request()->data funciona perfecto ===
    $name = trim($request->data->name ?? '');
    $price = trim($request->data->price ?? '');
    $description = trim($request->data->description ?? '');
    $meal_id = trim($request->data->meal_id ?? '');
    $image = trim($request->data->image ?? ''); // ← URL de la imagen

    if ($name === '' || $price === '' || $description === '' || $meal_id === '') {
        Flight::json(['status' => 'error', 'message' => 'Campos requeridos'], 400);
        return;
    }

    $price = floatval($price);
    $meal_id = intval($meal_id);

    // === OBTENER IMAGEN ACTUAL (si no se envía nueva) ===
    if ($image === '') {
        $current = $app->selectOne("SELECT image FROM foods WHERE id = :id", ['id' => $id]);
        if (!$current) {
            Flight::json(['status' => 'error', 'message' => 'Food no encontrado'], 404);
            return;
        }
        $image = $current->image;
    } else {
        // Validar que la imagen exista en el servidor
        $uploadDir = __DIR__ . "/foods-images/";
        if (!file_exists($uploadDir . $image)) {
            Flight::json(['status' => 'error', 'message' => 'Imagen no encontrada'], 400);
            return;
        }
    }

    // === ACTUALIZAR ===
    $query = "UPDATE foods SET name = :name, price = :price, description = :description, meal_id = :meal_id, image = :image WHERE id = :id";
    $params = [
        ':name' => $name,
        ':price' => $price,
        ':description' => $description,
        ':meal_id' => $meal_id,
        ':image' => $image,
        ':id' => $id
    ];

    try {
        $app->update($query, $params);
        Flight::json(['status' => 'success', 'message' => 'Food actualizado'], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => 'Error DB', 'DB' => $e->getMessage()], 500);
    }
});

Flight::route('POST /admin/upload', function () use ($app, $authenticate) {
    if (!$authenticate()) return;

    $uploadedFiles = Flight::request()->getUploadedFiles();
    if (!isset($uploadedFiles['image']) || $uploadedFiles['image']->getError() !== UPLOAD_ERR_OK) {
        Flight::json(['status' => 'error', 'message' => 'Imagen requerida'], 400);
        return;
    }

    $file = $uploadedFiles['image'];
    $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        Flight::json(['status' => 'error', 'message' => 'Extensión no permitida'], 400);
        return;
    }

    $uniqueName = uniqid('food_', true) . '.' . $ext;
    $uploadDir = __DIR__ . "/foods-images/";
    $filePath = $uploadDir . $uniqueName;

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    try {
        $file->moveTo($filePath);
        Flight::json([
            'status' => 'success',
            'message' => 'Imagen subida',
            'data' => ['image' => $uniqueName]
        ], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => 'Error al guardar'], 500);
    }
});

// 5. Eliminar food
Flight::route('DELETE /admin/foods/@id', function ($id) use ($app, $authenticate) {
    if (!$authenticate()) return;

    $id = intval($id);
    $query = "SELECT image FROM foods WHERE id = :id";


    $food = $app->selectOne($query, ['id' => $id]);


    if (!$food) {
        Flight::json(['status' => 'error', 'message' => 'Food no encontrado'], 404);
        return;
    }

    @unlink(__DIR__ . "/foods-images/" . $food->image);

    $query = "DELETE FROM foods WHERE id = :id";
    $app->delete($query, ['id' => $id]);

    Flight::json(['status' => 'success', 'message' => 'Food eliminado'], 200);
});

Flight::start();








