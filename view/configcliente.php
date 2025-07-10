<?php
include_once('../model/conexion.php');

// Iniciar sesión
session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id']) || $_SESSION['id_rol'] != 3) {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['id'];
$email = $_SESSION['correo'];
$display_name = $_SESSION['nombre'] ?? $email; // Fallback si no hay nombre en la sesión

// Obtener datos del cliente (incluyendo apellido)
$stmt_user = $conn->prepare("SELECT nombre, correo, apellido FROM users WHERE id = ? AND id_rol = 3");
$stmt_user->bind_param("i", $id_cliente);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$stmt_user->close();

$nombre = $user_data['nombre'] ?? '';
$correo = $user_data['correo'] ?? $email;
$apellido = $user_data['apellido'] ?? ''; // Inicializar apellido

// Procesar actualización de datos
$success_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $new_nombre = $_POST['nombre'];
    $new_apellido = $_POST['apellido'] ?? ''; // Opcional, si no se envía, usa vacío
    $new_correo = $_POST['correo'];
    $new_password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    if ($new_password) {
        $stmt_update = $conn->prepare("UPDATE users SET nombre = ?, apellido = ?, correo = ?, password = ? WHERE id = ?");
        $stmt_update->bind_param("ssssi", $new_nombre, $new_apellido, $new_correo, $new_password, $id_cliente);
    } else {
        $stmt_update = $conn->prepare("UPDATE users SET nombre = ?, apellido = ?, correo = ? WHERE id = ?");
        $stmt_update->bind_param("sssi", $new_nombre, $new_apellido, $new_correo, $id_cliente);
    }

    if ($stmt_update->execute()) {
        $_SESSION['nombre'] = $new_nombre;
        $_SESSION['correo'] = $new_correo;
        $success_message = "<p class='success-message'>Configuración actualizada exitosamente!</p>";
    } else {
        $success_message = "<p class='error-message'>Error al actualizar: " . $conn->error . "</p>";
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            height: 100vh;
            background: linear-gradient(135deg, #f5f5f5, #fff0f6);
            overflow: hidden;
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
            margin-bottom: 20px;
            text-align: center;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .sidebar ul {
            list-style: none;
            flex-grow: 1;
        }

        .sidebar ul li a {
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

        .sidebar ul li a .icon {
            margin-right: 15px;
            font-size: 18px;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #ff66b3;
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .logout {
            margin-top: auto; /* Empuja Cerrar Sesión hacia abajo */
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
        }

        .sidebar .logout a:hover {
            background-color: #ff66b3;
            color: #fff;
            transform: translateX(5px);
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px 0 0 10px;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 2px solid #ff99cc;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .header h1 {
            font-size: 28px;
            color: #ff66b3;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-right: 15px;
        }

        .user-info .user-email {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .user-info .user-role {
            font-size: 14px;
            color: #666;
            font-style: italic;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 900px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .card h3 {
            margin-bottom: 15px;
            font-size: 24px;
            color: #ff66b3;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid #ff99cc;
            padding-bottom: 5px;
        }

        .card p {
            margin-bottom: 20px;
            color: #666;
            font-size: 15px;
            text-align: center;
        }

        .card .form-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .card .form-group {
            margin-bottom: 15px;
        }

        .card .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .card .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ff99cc;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .card .form-group input:focus {
            border-color: #ff66b3;
            box-shadow: 0 0 8px rgba(255, 102, 179, 0.4);
            outline: none;
        }

        .card .form-group button {
            padding: 12px 25px;
            background: #ff66b3;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .card .form-group button:hover {
            background: #e91e63;
            transform: translateY(-2px);
        }

        .success-message {
            padding: 15px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
            animation: bounceIn 0.5s ease-out;
        }

        .error-message {
            padding: 15px;
            background: #ffebee;
            color: #c62828;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
            animation: bounceIn 0.5s ease-out;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.9); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                padding: 15px;
            }

            .sidebar .logo {
                margin-bottom: 15px;
            }

            .sidebar ul li a,
            .sidebar .logout a {
                padding: 10px 15px;
            }

            .main-content {
                padding: 15px;
                width: 100%;
            }

            .header h1 {
                font-size: 22px;
            }

            .card {
                max-width: 100%;
                padding: 15px;
            }

            .card .form-group input {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Glamour y Arte</div>
            <ul>
                <li><a href="panelcliente.php"><span class="icon">📅</span>Mi Agenda</a></li>
                <li><a href="agendar_cita.php"><span class="icon">📅</span>Agendar Cita</a></li>
                <li><a href="configcliente.php" class="active"><span class="icon">⚙️</span>Configuración</a></li>
            </ul>
            <div class="logout">
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Configuración</h1>
                <div class="user-info">
                    <span>
                        <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                        <span class="user-role">Cliente</span>
                    </span>
                </div>
            </header>
            <section class="dashboard">
                <div class="card" id="configuracion">
                    <h3>Editar Perfil</h3>
                    <?php echo $success_message; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="form-container">
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido:</label>
                            <input type="text" name="apellido" id="apellido" value="<?php echo htmlspecialchars($apellido); ?>">
                        </div>
                        <div class="form-group">
                            <label for="correo">Correo:</label>
                            <input type="email" name="correo" id="correo" value="<?php echo htmlspecialchars($correo); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Nueva Contraseña (dejar en blanco si no cambia):</label>
                            <input type="password" name="password" id="password">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="update">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>