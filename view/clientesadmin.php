<?php
// Incluir la conexión a la base de datos
include_once('../model/conexion.php');

// Iniciar sesión
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

// Obtener el correo del administrador
$correo = $_SESSION['correo'];

// Preparar y ejecutar la consulta para obtener la lista de clientes
try {
    $stmt_clientes = $conn->prepare("SELECT id, nombre, apellido, correo, telefono, fecha_registro FROM users WHERE id_rol = 3");
    if (!$stmt_clientes) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }
    $stmt_clientes->execute();
    $result_clientes = $stmt_clientes->get_result();
} catch (Exception $e) {
    // Log the error (e.g., to a file) instead of displaying it directly
    error_log("Error en gestionclientes.php: " . $e->getMessage());
    $result_clientes = null; // Set to null to trigger no-data message
}
$stmt_clientes->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Gestión de Clientes</title>
    <link rel="icon" href="../img/icono.png" type="image/png">

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
                    <li><a href="gestorservicios.php"><span class="icon">✂️</span> Servicios</a></li>
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
                <h1>Gestión de Clientes</h1>
                <div class="user">
                    <div class="user-info">
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Administrador</span>
                    </div>
                </div>
            </header>
            <section class="recent-appointments"> <!-- Cambié 'dashboard' por 'recent-appointments' para coincidir con el CSS -->
                <div class="card">
                    <h2>Lista de Clientes</h2>
                    <?php if ($result_clientes && $result_clientes->num_rows > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>Correo</th>
                                        <th>Teléfono</th>
                                        <th>Fecha de Registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result_clientes->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['apellido'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['correo']); ?></td>
                                            <td><?php echo htmlspecialchars($row['telefono'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['fecha_registro'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No hay clientes registrados o ocurrió un error al cargar los datos.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>