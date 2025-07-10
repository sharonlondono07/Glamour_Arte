<xaiArtifact artifact_id="70d4e8e9-8040-4586-bc92-cef27263d2c7" artifact_version_id="2c55b66d-f421-4f4e-bb6a-26b0a2095f45" title="get_citas.php" contentType="text/php">


<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id']) || $_SESSION['id_rol'] != 2) {
    header("Location: routing.php");
    exit();
}

$id_estilista = $_SESSION['id'];
$date = $_GET['date'] ?? date('Y-m-d');

$stmt_citas = $conn->prepare("SELECT u.correo AS cliente, s.nombre_servicio, c.estado
                             FROM citas c
                             JOIN users u ON c.id_cliente = u.id
                             JOIN servicios s ON c.id_servicio = s.id
                             WHERE c.id_estilista = ? AND DATE(c.fecha_hora) = ?");
$stmt_citas->bind_param("is", $id_estilista, $date);
$stmt_citas->execute();
$result_citas = $stmt_citas->get_result();

$citas = [];
while ($row = $result_citas->fetch_assoc()) {
    $citas[] = $row;
}

echo json_encode($citas);
$stmt_citas->close();
$conn->close();
?>