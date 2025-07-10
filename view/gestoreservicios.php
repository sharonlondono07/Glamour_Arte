<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

$correo = $_SESSION['correo'];

// Obtener lista de servicios
$stmt_servicios = $conn->prepare("SELECT id, nombre_servicio, descripcion, precio, duracion, imagen FROM servicios");
$stmt_servicios->execute();
$result_servicios = $stmt_servicios->get_result();

// Manejar edición
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$servicio_to_edit = null;
if ($edit_id > 0) {
    $stmt_edit = $conn->prepare("SELECT id, nombre_servicio, descripcion, precio, duracion, imagen FROM servicios WHERE id = ?");
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows > 0) {
        $servicio_to_edit = $result_edit->fetch_assoc();
    }
    $stmt_edit->close();
}

// Manejar agregar
$add_mode = isset($_GET['add']) && $_GET['add'] == 1;
$new_servicio = ['nombre_servicio' => '', 'descripcion' => '', 'precio' => '', 'duracion' => '', 'imagen' => ''];

// Procesar formulario de edición o agregar
$success = '';
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_servicio = $_POST['nombre_servicio'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $duracion = $_POST['duracion'];
    $imagen = $_FILES['imagen']['name'] ? $_FILES['imagen']['name'] : (isset($_POST['imagen_actual']) ? $_POST['imagen_actual'] : '');

    if (empty($nombre_servicio) || empty($precio) || empty($duracion)) {
        $error = "Nombre, precio y duración son obligatorios.";
    } else {
        // Manejar la subida de la imagen
        $target_dir = "uploads/servicios/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["imagen"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $new_image_name = $edit_id > 0 ? "servicio_" . $edit_id . "." . $imageFileType : "servicio_" . time() . "." . $imageFileType;

        if (!empty($_FILES["imagen"]["name"])) {
            $check = getimagesize($_FILES["imagen"]["tmp_name"]);
            if ($check === false) {
                $error = "El archivo no es una imagen.";
            } elseif ($_FILES["imagen"]["size"] > 5000000) { // 5MB límite
                $error = "La imagen es demasiado grande (máximo 5MB).";
            } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $error = "Solo se permiten archivos JPG, JPEG, PNG y GIF.";
            } else {
                if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_dir . $new_image_name)) {
                    $imagen = $new_image_name;
                } else {
                    $error = "Error al subir la imagen.";
                }
            }
        }

        if (!$error) {
            if (isset($_POST['edit_id'])) {
                // Editar servicio
                $edit_id = intval($_POST['edit_id']);
                $stmt_update = $conn->prepare("UPDATE servicios SET nombre_servicio = ?, descripcion = ?, precio = ?, duracion = ?, imagen = ? WHERE id = ?");
                $stmt_update->bind_param("ssdssi", $nombre_servicio, $descripcion, $precio, $duracion, $imagen, $edit_id);

                if ($stmt_update->execute()) {
                    $success = "Servicio actualizado con éxito.";
                    // Eliminar imagen antigua si se subió una nueva
                    if (!empty($_POST['imagen_actual']) && $imagen != $_POST['imagen_actual']) {
                        unlink($target_dir . $_POST['imagen_actual']);
                    }
                    // Recargar la lista de servicios
                    $stmt_servicios = $conn->prepare("SELECT id, nombre_servicio, descripcion, precio, duracion, imagen FROM servicios");
                    $stmt_servicios->execute();
                    $result_servicios = $stmt_servicios->get_result();
                } else {
                    $error = "Error al actualizar el servicio: " . $conn->error;
                }
                $stmt_update->close();
            } elseif (isset($_POST['add_servicio'])) {
                // Agregar servicio
                $stmt_insert = $conn->prepare("INSERT INTO servicios (nombre_servicio, descripcion, precio, duracion, imagen) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("ssdss", $nombre_servicio, $descripcion, $precio, $duracion, $imagen);

                if ($stmt_insert->execute()) {
                    $success = "Servicio agregado con éxito.";
                    // Recargar la lista de servicios
                    $stmt_servicios = $conn->prepare("SELECT id, nombre_servicio, descripcion, precio, duracion, imagen FROM servicios");
                    $stmt_servicios->execute();
                    $result_servicios = $stmt_servicios->get_result();
                } else {
                    $error = "Error al agregar el servicio: " . $conn->error;
                }
                $stmt_insert->close();
            }
        }
    }
}

// Manejar eliminación
if (isset($_GET['delete']) && $_SERVER["REQUEST_METHOD"] == "GET") {
    $delete_id = intval($_GET['delete']);
    $stmt_select = $conn->prepare("SELECT imagen FROM servicios WHERE id = ?");
    $stmt_select->bind_param("i", $delete_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $imagen = $result->fetch_assoc()['imagen'];
    $stmt_select->close();

    // Verificar si el servicio tiene citas asociadas
    $stmt_check_citas = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE id_servicio = ?");
    $stmt_check_citas->bind_param("i", $delete_id);
    $stmt_check_citas->execute();
    $result_citas = $stmt_check_citas->get_result();
    $citas_count = $result_citas->fetch_assoc()['total'];
    $stmt_check_citas->close();

    if ($citas_count > 0) {
        $error = "No se puede eliminar el servicio porque está asociado a $citas_count cita(s).";
    } else {
        $stmt_delete = $conn->prepare("DELETE FROM servicios WHERE id = ?");
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            if ($imagen && file_exists("uploads/servicios/" . $imagen)) {
                unlink("uploads/servicios/" . $imagen);
            }
            $success = "Servicio eliminado con éxito.";
            // Recargar la lista de servicios
            $stmt_servicios = $conn->prepare("SELECT id, nombre_servicio, descripcion, precio, duracion, imagen FROM servicios");
            $stmt_servicios->execute();
            $result_servicios = $stmt_servicios->get_result();
        } else {
            $error = "Error al eliminar el servicio: " . $conn->error;
        }
        $stmt_delete->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Gestión de Servicios</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            height: 100vh;
            background-color: #f5f5f5;
        }

        .container {
            display: flex;
            width: 100%;
            height: 100%;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, #ff99cc, #ffffff);
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            transition: width 0.3s ease;
        }

        .sidebar .logo {
            font-size: 26px;
            font-weight: bold;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .sidebar .menu ul {
            list-style: none;
        }

        .sidebar .menu ul li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 16px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .menu ul li a .icon {
            margin-right: 15px;
            font-size: 18px;
        }

        .sidebar .menu ul li a:hover,
        .sidebar .menu ul li a.active {
            background-color: #ff66b3;
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .logout a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .sidebar .logout a:hover {
            background-color: #ff66b3;
            color: #fff;
            transform: translateX(5px);
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #fff;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
        }

        .user {
            display: flex;
            align-items: center;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-right: 10px;
        }

        .user-info .user-email {
            font-size: 14px;
            color: #333;
        }

        .user-info .user-role {
            font-size: 12px;
            color: #666;
        }

        .recent-appointments {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            max-width: 100%;
        }

        .recent-appointments h2 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
            text-align: center;
        }

        .table-container {
            margin: 20px 0;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #ff99cc;
            color: #fff;
        }

        td a {
            color: #4caf50;
            text-decoration: none;
            margin-right: 10px;
        }

        td a:hover {
            text-decoration: underline;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 10px;
        }

        .btn-add {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4caf50;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .btn-add:hover {
            background-color: #45a049;
        }

        .form-container {
            display: grid;
            gap: 15px;
            margin-top: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 2px solid #ff99cc;
            border-radius: 8px;
            font-size: 14px;
            background-color: #ffffff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
            max-width: 400px;
        }

        .form-group input[type="file"] {
            padding: 5px;
            border: none;
            background: none;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #ff66b3;
            box-shadow: 0 0 5px rgba(255, 102, 179, 0.3);
            outline: none;
        }

        .form-group button {
            padding: 12px 25px;
            background-color: #4caf50;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            width: auto;
            align-self: flex-start;
        }

        .form-group button:hover {
            background-color: #45a049;
        }

        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                padding: 15px;
            }

            .sidebar .logo {
                margin-bottom: 20px;
            }

            .sidebar .menu ul li a {
                padding: 10px 15px;
            }

            .main-content {
                padding: 15px;
            }

            .recent-appointments {
                padding: 15px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px;
            }

            .form-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Glamour y Arte</div>
            <div class="menu">
                <ul>
                    <li><a href="paneladmin.php"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="gestorestilistas.php"><span class="icon">👩‍💼</span> Estilistas</a></li>
                    <li><a href="gestoreservicios.php" class="active"><span class="icon">✂️</span> Servicios</a></li>
                    <li><a href="agendarcita_admin.php" class="active"><span class="icon">📅</span> Agendar</a></li>
                    <li><a href="gestionpagos.php"><span class="icon">💰</span> Pagos</a></li>   
                    <li><a href="clientesadmin.php"><span class="icon">👥</span> Clientes</a></li>
                    <li><a href="configadmin.php"><span class="icon">⚙️</span> Configuración</a></li>
                </ul>
            </div>
            <div class="logout">
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Gestión de Servicios</h1>
                <div class="user">
                    <div class="user-info">
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Administrador</span>
                    </div>
                </div>
            </header>
            <section class="dashboard">
                <div class="recent-appointments">
                    <h2>Lista de Servicios</h2>
                    <?php if ($success): ?>
                        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                    <?php elseif ($error): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($edit_id > 0 && $servicio_to_edit): ?>
                        <div class="form-container">
                            <form class="form-group" method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id); ?>">
                                <label for="nombre_servicio">Nombre del Servicio:</label>
                                <input type="text" id="nombre_servicio" name="nombre_servicio" value="<?php echo htmlspecialchars($servicio_to_edit['nombre_servicio']); ?>" required>
                                <label for="descripcion">Descripción:</label>
                                <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($servicio_to_edit['descripcion'] ?? ''); ?></textarea>
                                <label for="precio">Precio:</label>
                                <input type="number" id="precio" name="precio" step="0.01" value="<?php echo htmlspecialchars($servicio_to_edit['precio'] ?? ''); ?>" required>
                                <label for="duracion">Duración (minutos):</label>
                                <input type="number" id="duracion" name="duracion" value="<?php echo htmlspecialchars($servicio_to_edit['duracion'] ?? ''); ?>" required>
                                <label for="imagen">Foto del Servicio:</label>
                                <input type="file" id="imagen" name="imagen" accept="image/*">
                                <?php if ($servicio_to_edit['imagen']): ?>
                                    <p>Imagen actual: <a href="uploads/servicios/<?php echo htmlspecialchars($servicio_to_edit['imagen']); ?>" target="_blank"><?php echo htmlspecialchars($servicio_to_edit['imagen']); ?></a></p>
                                    <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($servicio_to_edit['imagen']); ?>">
                                <?php endif; ?>
                                <button type="submit">Guardar Cambios</button>
                            </form>
                        </div>
                    <?php elseif ($add_mode): ?>
                        <div class="form-container">
                            <form class="form-group" method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="add_servicio" value="1">
                                <label for="nombre_servicio">Nombre del Servicio:</label>
                                <input type="text" id="nombre_servicio" name="nombre_servicio" value="<?php echo htmlspecialchars($new_servicio['nombre_servicio']); ?>" required>
                                <label for="descripcion">Descripción:</label>
                                <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($new_servicio['descripcion'] ?? ''); ?></textarea>
                                <label for="precio">Precio:</label>
                                <input type="number" id="precio" name="precio" step="0.01" value="<?php echo htmlspecialchars($new_servicio['precio'] ?? ''); ?>" required>
                                <label for="duracion">Duración (minutos):</label>
                                <input type="number" id="duracion" name="duracion" value="<?php echo htmlspecialchars($new_servicio['duracion'] ?? ''); ?>" required>
                                <label for="imagen">Foto del Servicio:</label>
                                <input type="file" id="imagen" name="imagen" accept="image/*" required>
                                <button type="submit">Agregar Servicio</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <?php if ($result_servicios->num_rows > 0): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Precio</th>
                                            <th>Duración</th>
                                            <th>Imagen</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result_servicios->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nombre_servicio']); ?></td>
                                                <td><?php echo htmlspecialchars($row['descripcion'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['precio'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['duracion']) . ' minutos'; ?></td>
                                                <td><?php echo $row['imagen'] ? '<a href="uploads/servicios/' . htmlspecialchars($row['imagen']) . '" target="_blank">Ver</a>' : 'N/A'; ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $row['id']; ?>">Editar</a> |
                                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('¿Eliminar servicio?');">Eliminar</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No hay servicios registrados.</p>
                        <?php endif; ?>
                        <a href="?add=1" class="btn-add">Agregar Servicio</a>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>