<?php
session_start(); // Iniciar la sesión

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al usuario a la página de login
header("Location: inicio.php");
exit();
?>