<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

$correo = $_SESSION['correo'];
$id_admin = $_SESSION['id'];
$error = null;

$stmt_select = $conn->prepare("SELECT correo, telefono FROM users WHERE id = ? AND id_rol = 1");
$stmt_select->bind_param("i", $id_admin);
$stmt_select->execute();
$result = $stmt_select->get_result();
$admin = $result->fetch_assoc();
$stmt_select->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_correo = $_POST['correo'];
    $nuevo_telefono = $_POST['telefono'] ?? null;

    $stmt_update = $conn->prepare("UPDATE users SET correo = ?, telefono = ? WHERE id = ? AND id_rol = 1");
    $stmt_update->bind_param("ssi", $nuevo_correo, $nuevo_telefono, $id_admin);
    if ($stmt_update->execute()) {
        $_SESSION['correo'] = $nuevo_correo; // Actualizar sesión
        header("Location: configadmin.php");
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
    $stmt_update->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Configuración</title>
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

        .sidebar .menu a {
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

        .sidebar .menu a .icon {
            margin-right: 15px;
            font-size: 18px;
        }

        .sidebar .menu a:hover,
        .sidebar .menu a.active {
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

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
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

        .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            max-width: 600px;
            width: 100%;
        }

        .card h2, .card h3 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
            text-align: center;
        }

        .form-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
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

        .form-group input {
            padding: 8px;
            border: 2px solid #ff99cc;
            border-radius: 6px;
            font-size: 14px;
            background-color: #ffffff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            max-width: 300px;
            width: 100%;
        }

        .form-group input:focus {
            border-color: #ff66b3;
            box-shadow: 0 0 5px rgba(255, 102, 179, 0.3);
            outline: none;
        }

        .form-group button {
            padding: 10px 20px;
            background-color: #4caf50;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            width: auto;
            align-self: center;
            margin-top: 15px;
        }

        .form-group button:hover {
            background-color: #45a049;
        }

        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
        }

        .add-link {
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
            margin-top: 10px;
        }

        .add-link:hover {
            background-color: #45a049;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                padding: 15px;
            }

            .sidebar .logo {
                margin-bottom: 20px;
            }

            .sidebar .menu a,
            .sidebar .logout a {
                padding: 10px 15px;
            }

            .main-content {
                padding: 15px;
            }

            .card {
                max-width: 100%;
                padding: 15px;
            }

            .form-group input {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Glamour y Arte</div>
            <div class="menu">
                <a href="paneladmin.php"><span class="icon">📊</span> Dashboard</a>
                <a href="gestorestilistas.php"><span class="icon">💇‍♀️</span> Estilistas</a>
                <a href="gestoreservicios.php"><span class="icon">🛠️</span> Servicios</a>
                <a href="agendarcita_admin.php"><span class="icon">📅</span> Agendar</a>
                <a href="gestionpagos.php"><span class="icon">💸</span> Pagos</a>
                <a href="clientesadmin.php"><span class="icon">👥</span> Clientes</a>
                <a href="configadmin.php"><span class="icon">⚙️</span> Configuración</a>
            </div>
            <div class="logout">
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Configuración</h1>
                <div class="user-info">
                    <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                    <span class="user-role">Administrador</span>
                </div>
            </header>
            <section class="dashboard">
                <div class="card">
                    <h3>Configuración de Cuenta</h3>
                    <?php if (isset($error)): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <div class="form-container">
                        <form class="form-group" method="POST">
                            <label for="correo">Correo:</label>
                            <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($admin['correo']); ?>" required>
                            <label for="telefono">Teléfono (opcional):</label>
                            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($admin['telefono'] ?? ''); ?>">
                            <button type="submit">Guardar Cambios</button>
                        </form>
                    </div>
                    <a href="paneladmin.php" class="add-link">Volver</a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
