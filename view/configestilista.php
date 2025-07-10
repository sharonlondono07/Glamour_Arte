<?php
include_once('../model/conexion.php');

// Iniciar sesión
session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id']) || $_SESSION['id_rol'] != 2) {
    header("Location: routing.php");
    exit();
}

$id_estilista = $_SESSION['id'];
$correo = $_SESSION['correo'];

// Obtener datos actuales del estilista
$stmt = $conn->prepare("SELECT nombre, apellido, correo, telefono FROM users WHERE id = ?");
$stmt->bind_param("i", $id_estilista);
$stmt->execute();
$result = $stmt->get_result();
$estilista = $result->fetch_assoc();
$stmt->close();

// Actualizar información
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_info') {
    $nuevo_nombre = $_POST['nombre'];
    $nuevo_apellido = $_POST['apellido'];
    $nuevo_correo = $_POST['correo'];
    $nuevo_telefono = $_POST['telefono'];

    $stmt = $conn->prepare("UPDATE users SET nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $nuevo_nombre, $nuevo_apellido, $nuevo_correo, $nuevo_telefono, $id_estilista);
    $stmt->execute();
    $stmt->close();
    header("Location: configestilista.php");
    exit();
}

// Actualizar contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_password') {
    $password_actual = $_POST['password_actual'];
    $nueva_password = $_POST['nueva_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_estilista);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if ($usuario['password'] == $password_actual) {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $nueva_password, $id_estilista);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: configestilista.php");
    exit();
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
            max-width: 500px;
        }

        .card h3 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ff99cc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .form-group button {
            background-color: #ff66b3;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .form-group button:hover {
            background-color: #e91e63;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Glamour y Arte</div>
            <div class="menu">
                <a href="panelestilista.php"><span class="icon">📊</span> Dashboard</a>
                <a href="miscitasesti.php"><span class="icon">📅</span> Mis Citas</a>                
                <a href="mis_trabajos.php"><span class="icon">🖼️</span> Mis Trabajos</a>
                <a href="configestilista.php" class="active"><span class="icon">⚙️</span> Configuración</a>
            </div>
            <div class="logout">
                <a href="logout.php"><span class="icon">🚪</span> Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Configuración</h1>
                <div class="user-info">
                    <span>
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Estilista</span>
                    </span>
                </div>
            </header>
            <section class="dashboard">
                <div class="card">
                    <h3>Actualizar Información Personal</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_info">
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($estilista['nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido:</label>
                            <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($estilista['apellido'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="correo">Correo:</label>
                            <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($estilista['correo']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono:</label>
                            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($estilista['telefono'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit">Guardar Cambios</button>
                        </div>
                    </form>

                    <h3>Cambiar Contraseña</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_password">
                        <div class="form-group">
                            <label for="password_actual">Contraseña Actual:</label>
                            <input type="password" id="password_actual" name="password_actual" required>
                        </div>
                        <div class="form-group">
                            <label for="nueva_password">Nueva Contraseña:</label>
                            <input type="password" id="nueva_password" name="nueva_password" required>
                        </div>
                        <div class="form-group">
                            <button type="submit">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>