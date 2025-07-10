<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

$correo = $_SESSION['correo'];
$servicio = null;
$error = null;

if (isset($_GET['id'])) {
    $id_servicio = $_GET['id'];
    $stmt_select = $conn->prepare("SELECT id, nombre_servicio, descripcion, precio, duracion FROM servicios WHERE id = ?");
    $stmt_select->bind_param("i", $id_servicio);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $servicio = $result->fetch_assoc();
    $stmt_select->close();

    if (!$servicio) {
        $error = "Servicio no encontrado.";
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nuevo_nombre = $_POST['nombre_servicio'];
        $nueva_descripcion = $_POST['descripcion'];
        $nuevo_precio = $_POST['precio'] ?? null;
        $nueva_duracion = $_POST['duracion'] ?? null;

        $stmt_update = $conn->prepare("UPDATE servicios SET nombre = ?, descripcion = ?, precio = ?, duracion = ? WHERE id = ?");
        $stmt_update->bind_param("sssdi", $nuevo_nombre, $nueva_descripcion, $nuevo_precio, $nueva_duracion, $id_servicio);
        if ($stmt_update->execute()) {
            header("Location: gestoreservicios.php");
        } else {
            $error = "Error al actualizar: " . $conn->error;
        }
        $stmt_update->close();
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
    <title>Glamour y Arte - Editar Servicio</title>
    <link rel="stylesheet" href="../estilo/gestoresti.css">
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
                <h1>Editar Servicio</h1>
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
                    <?php elseif ($servicio): ?>
                        <form method="POST">
                            <label>Nombre:</label><input type="text" name="nombre" value="<?php echo htmlspecialchars($servicio['nombre']); ?>" required><br>
                            <label>Descripción:</label><textarea name="descripcion" required><?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?></textarea><br>
                            <label>Precio:</label><input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($servicio['precio'] ?? ''); ?>" required><br>
                            <label>Duración (minutos):</label><input type="number" name="duracion" value="<?php echo htmlspecialchars($servicio['duracion'] ?? ''); ?>" required><br>
                            <button type="submit" class="btn-add">Guardar Cambios</button>
                        </form>
                    <?php endif; ?>
                    <a href="gestorservicios.php" class="btn-add">Volver</a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>