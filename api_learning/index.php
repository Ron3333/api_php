<?php

declare(strict_types=1);

	$host = "localhost";
	$dbname = "restaurante";
	$user = "root";
	$pass = "";

	$db = new PDO("mysql:host=$host;dbname=$dbname",$user,$pass);


require_once 'flight/Flight.php';
require 'productController.php'; //ESTO
// require 'flight/autoload.php';

$productController = new ProductController($db); //esto

Flight::route('/', function () use ($db) {

    if($db == true) {
	 	echo "db connected";
	 } else {
	 	die("smth is wrong");
	 }
});

Flight::route('GET /productos2', [ $productController, 'list' ]); //esto

Flight::route('GET /productos2/@id', [ $productController, 'products' ]); //esto

Flight::route('GET /productos', function () use ($db)  {

      $data = $db->query("SELECT * FROM foods");
      $all = $data->fetchAll(PDO::FETCH_ASSOC);
      flight::json($all);

});

Flight::route('GET /productos/@id', function (string $id) use ($db)  {

     // echo "ID,  ($id)!";

      $data = $db->query("SELECT * FROM foods WHERE id = $id");
      $row = $data->fetchAll(PDO::FETCH_ASSOC);
     
      flight::json($row);

});

Flight::route('POST /productos', function () use ($db)  {

    $request = Flight::request();
    $name = $request->data->name ?? null;
    $price = $request->data->price ?? null;
    $description = $request->data->description ?? null;
    $meal_id = intval( $request->data->meal_id ?? null );

    $query = "INSERT INTO foods (name, price, description, meal_id, image) VALUES (:name, :price, :description, :meal_id, :image)";
    $params = [
        ':name' => $name,
        ':price' => $price,
        ':description' => $description,
        ':meal_id' => $meal_id,
        ':image' => "imagen"
    ];

    $register_user = $db->prepare($query);
    $register_user->execute($params);

     Flight::json([
            'status' => 'success',
            'message' => 'Food creado',
            'ID'=>$db->lastInsertId()
        ], 201);


});

Flight::route('PUT /productos', function () use ($db)  {
    $request = Flight::request();
    $id = intval($request->data->id ?? null); //nuevo
    $name = $request->data->name ?? null;
    $price = $request->data->price ?? null;
    $description = $request->data->description ?? null;
    $meal_id = intval( $request->data->meal_id ?? null );
    $query = "UPDATE foods SET name = :name, price = :price, description = :description, meal_id = :meal_id, image = :image WHERE id = :id";
    $params = [
        ':name' => $name,
        ':price' => $price,
        ':description' => $description,
        ':meal_id' => $meal_id,
        ':image' => "image",
        ':id' => $id
    ];
    $update_user = $db->prepare($query);
    $update_user->execute($params);
    Flight::json([
            'status' => 'success',
            'message' => 'Food creado',
            'ID'=>$id
        ], 201);
});

Flight::route('DELETE /productos/@id', function (string $id) use ($db)  {

    $id = intval($id);
    $query = "DELETE FROM foods WHERE id = :id";
    $params = ['id' => $id];
    $delete_product = $db->prepare($query);
    $delete_product->execute($params);

      Flight::json([
            'status' => 'success',
            'message' => 'Food delete',
            'ID'=>$id
        ], 201);

});




Flight::start();
