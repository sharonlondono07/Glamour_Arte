<xaiArtifact artifact_id="cf1a5853-076b-4beb-8463-0dfcb64fa065" artifact_version_id="d4868fb7-d8e6-4c9b-a5f0-f88d87c40b2e" title="actualizar_cita.php" contentType="text/php">

<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id']) || $_SESSION['id_rol'] != 2) {
    header("Location: routing.php");
    exit();
}

$id_estilista = $_SESSION['id'];

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id_cita = intval($_GET['id']);
    $action = $_GET['action'];

    $nuevo_estado = '';
    switch ($action) {
        case 'completada':
            $nuevo_estado = 'completada';
            break;
        case 'cancelada':
            $nuevo_estado = 'cancelada';
            break;
        case 'pendiente':
            $nuevo_estado = 'pendiente';
            break;
        default:
            die("Acción no válida.");
    }

    $stmt_update = $conn->prepare("UPDATE citas SET estado = ? WHERE id_cita = ? AND id_estilista = ?");
    $stmt_update->bind_param("sii", $nuevo_estado, $id_cita, $id_estilista);
    if ($stmt_update->execute()) {
        $stmt_update->close();
        header("Location: panelestilista.php"); // Redirigir de vuelta al panel
        exit();
    } else {
        echo "Error al actualizar: " . $conn->error;
    }
    $stmt_update->close();
}

$conn->close();
?>