<?php
//require_once '../vendor/autoload.php'; // Incluir el autoloader de Composer
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AppAPI
{
    public $host = HOST;
    public $dbname = DBNAME;
    public $user = USER;
    public $pass = PASS;
    public $link;

    public function __construct()
    {
        $this->connect();
    }

    public function connect()
    {
        $this->link = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->user, $this->pass);
        $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function selectAll($query, $params = [])
    {
        $stmt = $this->link->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

   public function selectOne($query, $params = [])
    {
        $stmt = $this->link->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row ?: false;
    }

    public function validateCart($q)
    {
        $row = $this->link->query($q);
        $row->execute();
        return $row->rowCount();
    }

    public function insert($query,$arr = [] )
    {
        if ($this->validate($arr) == "empty") {
            throw new Exception("Uno o más campos están vacíos");
        }
        $insert_record = $this->link->prepare($query);
        $insert_record->execute($arr);
        return true;
    }

    public function update($query, $arr = [])
    {
        if ($this->validate($arr) == "empty") {
            throw new Exception("Uno o más campos están vacíos");
        }
        $update_record = $this->link->prepare($query);
        $update_record->execute($arr);
        return true;
    }

    public function delete($query, $params = [])
    {
        $stmt = $this->link->prepare($query);
        return $stmt->execute($params);
    }

    public function validate($arr)
    {

        if (in_array("", $arr)) {
            return "empty";  
        }
        // var_dump($arr);
         //        exit();
        return false;
    }

    public function register($query, $arr)
    {
    
        if ($this->validate($arr) == "empty") {

            throw new Exception("Uno o más campos están vacíos");
        }

        $register_user = $this->link->prepare($query);
        $register_user->execute($arr);
        return true;
    }

    public function login($email, $password)
    {
        $query = "SELECT id, email, username, password FROM users WHERE email = :email";
        $stmt = $this->link->prepare($query);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $payload = [
                'iat' => time(),
                'exp' => time() + (60 * 60),
                'sub' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username']
            ];
            $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');
            return [
                'token' => $jwt,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'username' => $user['username']
                ]
            ];
        }
        throw new Exception("Correo o contraseña inválidos");
    }

    public function validateToken($token)
    {
        /*
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            throw new Exception("Token inválido: " . $e->getMessage());
        }
        */

       try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $decoded = (array) $decoded;

            // FORZAR sub como entero
            $decoded['sub'] = (int) ($decoded['sub'] ?? 0);

            return $decoded;
        } catch (Exception $e) {
            error_log("JWT Error: " . $e->getMessage());
            throw new Exception("Token inválido");
        }
    }
}