<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('../model/conexion.php');

// Verificar la conexión
if (!$conn) {
    die("Error: La conexión no se estableció. Verifica model/conexion.php: " . mysqli_connect_error());
}

session_start();

// Check if user is logged in and has admin role (id_rol = 1)
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

// Fetch recent appointments (citas)
$citas_query = "
    SELECT 
        c.id_cita, 
        c.fecha_hora as fecha, 
        c.estado, 
        s.nombre_servicio as servicio, 
        CONCAT(COALESCE(u1.nombre, ''), ' ', COALESCE(u1.apellido, '')) as cliente, 
        CONCAT(COALESCE(u2.nombre, ''), ' ', COALESCE(u2.apellido, '')) as estilista
    FROM citas c
    LEFT JOIN servicios s ON c.id_servicio = s.id
    LEFT JOIN users u1 ON c.id_cliente = u1.id AND u1.id_rol = 3  -- Cliente
    LEFT JOIN users u2 ON c.id_estilista = u2.id AND u2.id_rol = 2  -- Estilista
    ORDER BY c.fecha_hora DESC LIMIT 5";
$citas_result = mysqli_query($conn, $citas_query);

if (!$citas_result) {
    $error = "Citas query failed: " . mysqli_error($conn);
    error_log($error);
    echo "<p style='color:red;'>$error</p>"; // Para depuración
    $citas_result = false;
}

// Fetch summary metrics (example data - adjust queries as needed)
$citas_hoy = mysqli_num_rows(mysqli_query($conn, "SELECT id_cita FROM citas WHERE DATE(fecha_hora) = CURDATE()")); // Citas de hoy
$clientes_registrados = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE id_rol = 3")); // Clientes registrados
$ingresos_mes = 0; // Requiere tabla pagos: SUM(monto) WHERE MONTH(fecha_pago) = MONTH(CURDATE())
$servicios_activos = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM servicios")); // Servicios activos

// Procesar agendamiento de cita
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agendar_cita'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_estilista = $_POST['id_estilista'];
    $id_servicio = $_POST['id_servicio'];
    $fecha_hora = $_POST['fecha_hora'];
    $metodo_pago = $_POST['metodo_pago'];

    // Insert payment into pagos table
    $stmt_pago = $conn->prepare("INSERT INTO pagos (monto, metodo_pago, estado) VALUES (?, ?, 'pendiente')");
    $monto = 0; // Fetch this from servicios table based on id_servicio if needed
    $servicio = mysqli_fetch_assoc(mysqli_query($conn, "SELECT precio FROM servicios WHERE id = $id_servicio"));
    $monto = $servicio['precio'];
    $stmt_pago->bind_param("ds", $monto, $metodo_pago);
    $stmt_pago->execute();
    $id_pago = $conn->insert_id;
    $stmt_pago->close();

    // Insert cita into citas table
    $stmt_cita = $conn->prepare("INSERT INTO citas (id_cliente, id_estilista, id_servicio, id_pago, fecha_hora, estado, creado_por) VALUES (?, ?, ?, ?, ?, 'pendiente', ?)");
    $creado_por = $_SESSION['id']; // ID del administrador que crea la cita
    $stmt_cita->bind_param("iiiisi", $id_cliente, $id_estilista, $id_servicio, $id_pago, $fecha_hora, $creado_por);
    if ($stmt_cita->execute()) {
        header("Location: paneladmin.php?success=1");
    } else {
        error_log("Error al agendar cita: " . $conn->error);
    }
    $stmt_cita->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Admin</title>
    <link rel="stylesheet" href="../estilo/paneladmin.css">
<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
}

body {
    display: flex;
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f5f5 0%, #ffe6f0 100%);
    color: #333;
}

.sidebar {
    width: 250px;
    background: linear-gradient(180deg, #ff99cc 0%, #ffffff 100%);
    padding: 25px 15px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease, width 0.3s ease;
}

.sidebar .logo {
    font-size: 28px;
    font-weight: 700;
    color: #ff4d94;
    margin-bottom: 30px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 2px;
    animation: fadeIn 1s ease-in-out;
}

.sidebar .menu a {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: #444;
    text-decoration: none;
    font-size: 16px;
    margin-bottom: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.sidebar .menu a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 0;
    background: #ff4d94;
    transition: height 0.3s ease;
}

.sidebar .menu a:hover::before,
.sidebar .menu a.active::before {
    height: 100%;
}

.sidebar .menu a:hover,
.sidebar .menu a.active {
    background: linear-gradient(90deg, #ff99cc, #ff4d94);
    color: #fff;
    transform: translateX(10px);
}

.sidebar .menu a .icon {
    margin-right: 12px;
    font-size: 20px;
}

.sidebar .logout {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.sidebar .logout a {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: #ff4d94;
    text-decoration: none;
    font-size: 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: #fff;
    border: 1px solid #ff99cc;
}

.sidebar .logout a:hover {
    background: #ff4d94;
    color: #fff;
    border-color: #ff4d94;
    transform: translateX(5px);
}

.main-content {
    flex-grow: 1;
    padding: 30px;
    background: #fff;
    overflow-y: auto;
    border-radius: 10px 0 0 10px;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
}

.main-content .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #ff99cc;
}

.main-content .header h1 {
    font-size: 28px;
    color: #ff4d94;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.main-content .header .user {
    display: flex;
    align-items: center;
    background: #fff;
    padding: 10px;
    border-radius: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.main-content .header .user .user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-right: 15px;
}

.main-content .header .user .user-email {
    font-size: 15px;
    color: #333;
    font-weight: 500;
}

.main-content .header .user .user-role {
    font-size: 13px;
    color: #666;
    font-style: italic;
}

.main-content .header .user img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    border: 2px solid #ff99cc;
    transition: transform 0.3s ease;
}

.main-content .header .user img:hover {
    transform: scale(1.1);
}

.main-content .dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.main-content .dashboard .card {
    background: linear-gradient(135deg, #fff 0%, #ffe6f0 100%);
    padding: 20px;
    text-align: center;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.main-content .dashboard .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}

.main-content .dashboard .card h3 {
    font-size: 18px;
    color: #ff4d94;
    margin-bottom: 10px;
}

.main-content .dashboard .card p {
    font-size: 16px;
    color: #666;
}

.recent-appointments {
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    margin-top: 30px;
    animation: fadeInUp 1s ease-out;
}

.recent-appointments h2 {
    margin-bottom: 20px;
    font-size: 24px;
    color: #ff4d94;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.table-container {
    overflow-x: auto;
    margin-top: 20px;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    background: #fff;
}

th, td {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

th {
    background: linear-gradient(90deg, #ff99cc, #ff4d94);
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 1;
}

td {
    color: #444;
}

td .status {
    padding: 6px 12px;
    border-radius: 15px;
    font-weight: 500;
    display: inline-block;
    text-transform: capitalize;
}

.status.confirmado {
    background: #c8e6c9;
    color: #2e7d32;
}

.status.pendiente {
    background: #fff9c4;
    color: #f57f17;
}

.status.cancelado {
    background: #ffcdd2;
    color: #c62828;
}

td a {
    color: #ff4d94;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

td a:hover {
    color: #ff1a75;
    text-decoration: underline;
}

.no-data {
    text-align: center;
    color: #999;
    font-style: italic;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 10px;
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Media Queries */
@media (max-width: 1024px) {
    .main-content .dashboard {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }
    .main-content {
        padding: 20px;
    }
    .main-content .header {
        flex-direction: column;
        gap: 15px;
    }
    .main-content .dashboard {
        grid-template-columns: 1fr;
    }
    th, td {
        padding: 10px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
        padding: 15px;
    }
    .sidebar .logo {
        font-size: 22px;
    }
    .main-content .header h1 {
        font-size: 20px;
    }
    .recent-appointments h2 {
        font-size: 20px;
    }
}
</style>
</head>
<body>
    <div class="sidebar">
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
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user">
                <div class="user-info">
                    <span class="user-email"><?php echo htmlspecialchars($_SESSION['correo']); ?></span>
                    <span class="user-role">Administrador</span>
                </div>
                <img src="img/user.jpg" alt="Usuario" class="user-img">
            </div>
        </div>
        <p class="welcome-text">Bienvenido al panel de administración de Glamour y Arte.</p><br><br>

        <div class="dashboard">
            <div class="card">
                <h3>Citas Hoy</h3>
                <p><?php echo $citas_hoy; ?></p>
            </div>
            <div class="card">
                <h3>Clientes Registrados</h3>
                <p><?php echo $clientes_registrados; ?></p>
            </div>
            <div class="card">
                <h3>Ingresos del Mes</h3>
                <p>$<?php echo number_format($ingresos_mes, 0); ?></p>
            </div>
            <div class="card">
                <h3>Servicios Activos</h3>
                <p><?php echo $servicios_activos; ?></p>
            </div>
        </div>

        <div class="recent-appointments"><br>
            <h2>Citas Recientes</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Estilista</th>
                            <th>Servicio</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($citas_result && mysqli_num_rows($citas_result) > 0) { ?>
                            <?php while ($cita = mysqli_fetch_assoc($citas_result)) {
                                $fecha = new DateTime($cita['fecha']);
                                $hora = $fecha->format('h:i A');
                                $fecha_formateada = $fecha->format('Y-m-d');
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cita['cliente'] ?? 'No asignado'); ?></td>
                                    <td><?php echo htmlspecialchars($cita['estilista'] ?? 'No asignado'); ?></td>
                                    <td><?php echo htmlspecialchars($cita['servicio']); ?></td>
                                    <td><?php echo htmlspecialchars($fecha_formateada); ?></td>
                                    <td><?php echo htmlspecialchars($hora); ?></td>
                                    <td><span class="status <?php echo strtolower($cita['estado']); ?>"><?php echo htmlspecialchars($cita['estado']); ?></span></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="6" class="no-data">No se encontraron citas.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>