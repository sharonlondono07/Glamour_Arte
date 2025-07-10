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

// Obtener estadísticas de citas
$stmt_citas_hoy = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE id_estilista = ? AND DATE(fecha_hora) = CURDATE()");
$stmt_citas_hoy->bind_param("i", $id_estilista);
$stmt_citas_hoy->execute();
$citas_hoy = $stmt_citas_hoy->get_result()->fetch_assoc()['total'];
$stmt_citas_hoy->close();

$stmt_citas_pendientes = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE id_estilista = ? AND estado = 'pendiente'");
$stmt_citas_pendientes->bind_param("i", $id_estilista);
$stmt_citas_pendientes->execute();
$citas_pendientes = $stmt_citas_pendientes->get_result()->fetch_assoc()['total'];
$stmt_citas_pendientes->close();

$stmt_citas_completadas = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE id_estilista = ? AND estado = 'completada'");
$stmt_citas_completadas->bind_param("i", $id_estilista);
$stmt_citas_completadas->execute();
$citas_completadas = $stmt_citas_completadas->get_result()->fetch_assoc()['total'];
$stmt_citas_completadas->close();

$stmt_citas_canceladas = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE id_estilista = ? AND estado = 'cancelada'");
$stmt_citas_canceladas->bind_param("i", $id_estilista);
$stmt_citas_canceladas->execute();
$citas_canceladas = $stmt_citas_canceladas->get_result()->fetch_assoc()['total'];
$stmt_citas_canceladas->close();

// Obtener citas programadas
if ($conn) {
    $stmt_citas = $conn->prepare("SELECT c.id_cita AS cita_id, u.correo AS cliente, s.nombre_servicio, c.fecha_hora, c.estado
                                 FROM citas c
                                 JOIN users u ON c.id_cliente = u.id
                                 JOIN servicios s ON c.id_servicio = s.id
                                 WHERE c.id_estilista = ?");
    if ($stmt_citas) {
        $stmt_citas->bind_param("i", $id_estilista);
        if ($stmt_citas->execute()) {
            $result_citas = $stmt_citas->get_result();
            echo "<!-- Número de filas: " . $result_citas->num_rows . " -->"; // Depuración
        } else {
            echo "Error al ejecutar la consulta: " . $conn->error;
        }
        $stmt_citas->close();
    } else {
        echo "Error en la preparación: " . $conn->error;
    }
} else {
    echo "No hay conexión: Revisa model/conexion.php";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Panel de Estilista</title>
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
            max-width: 100%;
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

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
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
            margin-top: 20px;
        }

        .add-link:hover {
            background-color: #45a049;
        }

        /* Estilos para el dashboard */
        .welcome-text {
            font-size: 16px;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .stat-number {
            font-size: 32px;
            color: #ff66b3;
            margin: 10px 0;
        }

        .stat-trend {
            font-size: 14px;
            color: #666;
        }

        /* Estilos para los estados de citas */
        .status.pendiente {
            color: #f39c12;
            font-weight: bold;
        }

        .status.completada {
            color: #4caf50;
            font-weight: bold;
        }

        .status.cancelada {
            color: #c62828;
            font-weight: bold;
        }

        .status.en_proceso {
            color: #0288d1;
            font-weight: bold;
        }

        .no-data {
            color: #666;
            text-align: center;
            padding: 20px;
            font-size: 16px;
        }

        /* Estilos para la página de Mis Trabajos */
        .upload-form-container {
            margin-bottom: 30px;
        }

        .upload-form-container input[type="file"] {
            padding: 8px;
            border: 2px solid #ff99cc;
            border-radius: 6px;
            font-size: 14px;
            background-color: #ffffff;
            max-width: 300px;
            width: 100%;
        }

        .upload-form-container button {
            padding: 10px 20px;
            background-color: #ff66b3;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .upload-form-container button:hover {
            background-color: #e91e63;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .gallery img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .gallery img:hover {
            transform: scale(1.05);
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

            .form-group input,
            .upload-form-container input[type="file"] {
                max-width: 100%;
            }

            table {
                font-size: 14px;
                max-width: 100%;
            }

            th, td {
                padding: 8px;
            }

            .stats-container {
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
                <a href="panelestilista.php" class="active"><span class="icon">📊</span> Dashboard</a>
                <a href="miscitasesti.php"><span class="icon">📅</span> Mis Citas</a>                
                <a href="mis_trabajos.php"><span class="icon">🖼️</span> Mis Trabajos</a>
                <a href="configestilista.php"><span class="icon">⚙️</span> Configuración</a>
            </div>
            <div class="logout">
                <a href="logout.php"><span class="icon">🚪</span> Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Estilista</span>
                    </span>
                </div>
            </header>
            <section class="dashboard">
                <p class="welcome-text">Bienvenido al panel de estilista de Glamour y Arte.</p>
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Citas Hoy</h3>
                        <p class="stat-number"><?php echo $citas_hoy; ?></p>                        
                    </div>
                    <div class="stat-card">
                        <h3>Citas Pendientes</h3>
                        <p class="stat-number"><?php echo $citas_pendientes; ?></p>                    
                    </div>
                    <div class="stat-card">
                        <h3>Citas Completadas</h3>
                        <p class="stat-number"><?php echo $citas_completadas; ?></p>                        
                    </div>
                    <div class="stat-card">
                        <h3>Citas Canceladas</h3>
                        <p class="stat-number"><?php echo $citas_canceladas; ?></p>                       
                    </div>
                </div>
                <div class="card">
                    <h3>Citas Programadas</h3>
                    <p>Lista de las últimas citas programadas.</p>
                    <?php if (isset($result_citas) && $result_citas->num_rows > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Servicio</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result_citas->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['cita_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre_servicio']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['fecha_hora'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['fecha_hora'])); ?></td>
                                            <td class="status <?php echo htmlspecialchars(str_replace('_', '', $row['estado'])); ?>">
                                                <p><?php echo htmlspecialchars($row['estado']); ?></p>
                                            </td>
                                            <td>
                                                <?php if ($row['estado'] === 'pendiente'): ?>
                                                    <a href="actualizar_cita.php?action=completada&id=<?php echo htmlspecialchars($row['cita_id']); ?>" onclick="return confirm('¿Marcar como completada?');">Completar</a> |
                                                    <a href="actualizar_cita.php?action=cancelada&id=<?php echo htmlspecialchars($row['cita_id']); ?>" onclick="return confirm('¿Cancelar la cita?');">Cancelar</a> |
                                                    
                                                <?php elseif ($row['estado'] === 'completada'): ?>
                                                    Completada
                                                <?php elseif ($row['estado'] === 'cancelada'): ?>
                                                    Cancelada                                      
                                                    
                                                <?php else: ?>
                                                    <a href="actualizar_cita.php?action=pendiente&id=<?php echo htmlspecialchars($row['cita_id']); ?>" onclick="return confirm('¿Reabrir la cita?');">Reabrir</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No hay citas programadas por el momento.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>