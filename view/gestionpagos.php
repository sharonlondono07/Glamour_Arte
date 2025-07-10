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

// Preparar y ejecutar la consulta para obtener la lista de pagos
try {
    $stmt_pagos = $conn->prepare("SELECT p.id, c.id_cita, u.correo AS cliente, s.nombre_servicio, p.monto, p.fecha_pago, p.estado 
                                 FROM pagos p 
                                 JOIN citas c ON p.cita_id = c.id_cita 
                                 JOIN users u ON c.id_cliente = u.id 
                                 JOIN servicios s ON c.id_servicio = s.id");
    if (!$stmt_pagos) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }
    $stmt_pagos->execute();
    $result_pagos = $stmt_pagos->get_result();
} catch (Exception $e) {
    // Log the error (e.g., to a file) instead of displaying it directly
    error_log("Error en gestionpagos.php: " . $e->getMessage());
    $result_pagos = null; // Set to null to trigger no-data message
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Gestión de Pagos</title>
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
                    <li><a href="agendarcita_admin.php"><span class="icon">📅</span> Agendar</a><li>
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
                <h1>Gestión de Pagos</h1>
                <div class="user">
                    <div class="user-info">
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Administrador</span>
                    </div>
                </div>
            </header>
            <section class="dashboard">
                <div class="card">
                    <h2>Lista de Pagos</h2>
                    <?php if ($result_pagos && $result_pagos->num_rows > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ID Cita</th>
                                        <th>Cliente</th>
                                        <th>Servicio</th>
                                        <th>Monto</th>
                                        <th>Fecha de Pago</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result_pagos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['id_cita']); ?></td>
                                            <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre_servicio']); ?></td>
                                            <td><?php echo isset($row['monto']) ? htmlspecialchars(number_format($row['monto'], 2)) . ' USD' : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($row['fecha_pago'] ?? 'N/A'); ?></td>
                                            <td class="status <?php echo strtolower(str_replace(' ', '_', htmlspecialchars($row['estado']))); ?>"><?php echo htmlspecialchars($row['estado']); ?></td>
                                            <td>
                                                <a href="detallepago.php?id=<?php echo $row['id']; ?>">Ver Detalle</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No hay pagos registrados o ocurrió un error al cargar los datos.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
