<?php
 define("ADMINURL", "http://restaurante-api.test/admin-panel"); 
 define("ADMINAPI", "http://restaurante-api.test"); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Admin - Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo ADMINURL ?>/index.php">Admin Panel</a>
        <div class="navbar-nav ms-auto">
          
            <a class="nav-link" href="<?php echo ADMINURL ?>/index.php">Dashboard</a>
            <a class="nav-link" href="<?php echo ADMINURL ?>/admins/admins.php">Usuarios</a>
            <a class="nav-link" href="<?php echo ADMINURL ?>/foods-admins/show-foods.php">Menús</a>
            <a class="nav-link" href="<?php echo ADMINURL ?>/admins/logout.php">Logout</a>
       
        </div>
    </div>
</nav>
<div class="container-fluid mt-3">