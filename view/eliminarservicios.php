<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

$correo = $_SESSION['correo'];

if (isset($_GET['id'])) {
    $id_servicio = $_GET['id'];

    // Verificar si el servicio tiene citas asociadas
    $stmt_check = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE id_servicio = ?");
    $stmt_check->bind_param("i", $id_servicio);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($result_check['total'] == 0) {
        $stmt = $conn->prepare("DELETE FROM servicios WHERE id = ?");
        $stmt->bind_param("i", $id_servicio);
        if ($stmt->execute()) {
            header("Location: gestorservicios.php");
        } else {
            $error = "Error al eliminar el servicio: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "No se puede eliminar el servicio porque está asociado a citas.";
    }
} else {
    $error = "ID de servicio no especificado.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Glamour y Arte - Eliminar Servicio</title>
    <link rel="stylesheet" href="../estilo/panelestilista.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Glamour y Arte</div>
            <div class="menu">
                <ul>
                    <li><a href="paneladmin.php"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="gestorestilistas.php"><span class="icon">👩‍💼</span> Estilistas</a></li>
                    <li><a href="gestorservicios.php"><span class="icon">✂️</span> Servicios</a></li>
                    <li><a href="gestionclientes.php"><span class="icon">👥</span> Clientes</a></li>
                    <li><a href="configadmin.php"><span class="icon">⚙️</span> Configuración</a></li>
                </ul>
            </div>
            <div class="logout">
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Eliminar Servicio</h1>
                <div class="user">
                    <div class="user-info">
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Administrador</span>
                    </div>
                </div>
            </header>
            <section class="dashboard">
                <div class="recent-appointments">
                    <?php if (isset($error)): ?>
                        <p style="color: red;"><?php echo $error; ?></p>
                        <a href="gestorservicios.php" class="btn-add">Volver</a>
                    <?php else: ?>
                        <p>El servicio ha sido eliminado exitosamente.</p>
                        <a href="gestorservicios.php" class="btn-add">Volver</a>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>