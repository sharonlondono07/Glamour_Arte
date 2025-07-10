<?php
$host = "localhost";
$database = "prototipo";
$user = "root";
$password = "";
$port = 3306;

// Crear conexión con MySQLi
$conn = mysqli_connect($host, $user, $password, $database, $port);

// Verificar la conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>
