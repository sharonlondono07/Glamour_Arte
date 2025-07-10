<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $cita_id = $_GET['id'];
    $stmt = $conn->prepare("UPDATE citas SET estado = 'completada' WHERE id = ? AND id_estilista = ?");
    $stmt->bind_param("ii", $cita_id, $_SESSION['id']);
    if ($stmt->execute()) {
        header("Location: panelestilista.php");
    } else {
        echo "Error al actualizar el estado: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>