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

// Procesar agendamiento de cita
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['servicio'])) {
    $servicio = $_POST['servicio'];
    $estilista_id = $_POST['estilista']; // Ahora usamos el id del estilista
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $pago = $_POST['pago'];

    // Obtener IDs desde las tablas
    $stmt_servicio = $conn->prepare("SELECT id, precio FROM servicios WHERE nombre_servicio = ?");
    $stmt_servicio->bind_param("s", $servicio);
    $stmt_servicio->execute();
    $result_servicio = $stmt_servicio->get_result();
    if ($result_servicio->num_rows === 0) {
        die("Servicio no encontrado.");
    }
    $servicio_data = $result_servicio->fetch_assoc();
    $id_servicio = $servicio_data['id'];
    $precio = $servicio_data['precio'];
    $stmt_servicio->close();

    // Obtener id_estilista directamente desde el id recibido
    $stmt_estilista = $conn->prepare("SELECT id FROM users WHERE id = ? AND id_rol = 2");
    $stmt_estilista->bind_param("i", $estilista_id);
    $stmt_estilista->execute();
    $result_estilista = $stmt_estilista->get_result();
    if ($result_estilista->num_rows === 0) {
        die("Estilista no encontrado.");
    }
    $estilista_data = $result_estilista->fetch_assoc();
    $id_estilista = $estilista_data['id'];
    $stmt_estilista->close();

    // Insertar pago
    $stmt_pago = $conn->prepare("INSERT INTO pagos (monto, metodo_pago, estado) VALUES (?, ?, 'pendiente')");
    if ($stmt_pago === false) {
        die("Error preparing payment insertion: " . $conn->error);
    }
    $stmt_pago->bind_param("ds", $precio, $pago);
    if (!$stmt_pago->execute()) {
        die("Error inserting pago: " . $conn->error);
    }
    $id_pago = $conn->insert_id;
    $stmt_pago->close();

    // Insertar cita con estado 'pendiente'
    $fecha_hora = "$fecha $hora:00";
    $stmt_cita = $conn->prepare("INSERT INTO citas (id_cliente, id_estilista, id_servicio, id_pago, fecha_hora, estado, creado_por) VALUES (?, ?, ?, ?, ?, 'pendiente', ?)");
    $stmt_cita->bind_param("iiiisi", $id_cliente, $id_estilista, $id_servicio, $id_pago, $fecha_hora, $id_cliente);
    if (!$stmt_cita->execute()) {
        die("Error inserting cita: " . $conn->error);
    }
    $stmt_cita->close();
}

// Obtener servicios y estilistas
$stmt_servicios = $conn->prepare("SELECT nombre_servicio, precio FROM servicios");
$stmt_servicios->execute();
$result_servicios = $stmt_servicios->get_result();

$stmt_estilistas = $conn->prepare("SELECT id, nombre, apellido FROM users WHERE id_rol = 2");
$stmt_estilistas->execute();
$result_estilistas = $stmt_estilistas->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Agendar Cita</title>
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
            color: rgb(10, 9, 10);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-transform: capitalize;
            font-style: italic;
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
            color: rgb(15, 15, 15);
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid #ff99cc;
            padding-bottom: 5px;
            font-style: italic;
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

        .card .form-group select,
        .card .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ff99cc;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .card .form-group select:focus,
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

            .card .form-group select,
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
                <li><a href="agendar_cita.php" class="active"><span class="icon">📅</span>Agendar Cita</a></li>
                <li><a href="configcliente.php"><span class="icon">⚙️</span>Configuración</a></li>
            </ul>
            <div class="logout">
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Agendar Cita</h1>
                <div class="user-info">
                    <span>
                        <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                        <span class="user-role">Cliente</span>
                    </span>
                </div>
            </header>
            <section class="dashboard">
                <div class="card" id="agendar-cita">
                    <h3>Solicitar Nueva Cita</h3>
                    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['servicio'])): ?>
                        <div class="success-message"><?php echo "<p>Cita agendada exitosamente! <a href='panelcliente.php'>Volver a Mi Agenda</a></p>"; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="form-container">
                        <div class="form-group">
                            <label for="servicio">Servicio:</label>
                            <select name="servicio" id="servicio" required>
                                <option value="">Seleccione un servicio</option>
                                <?php
                                $result_servicios->data_seek(0); // Reiniciar puntero
                                while ($row = $result_servicios->fetch_assoc()) {
                                    echo "<option value=\"" . htmlspecialchars($row['nombre_servicio']) . "\">" . htmlspecialchars($row['nombre_servicio']) . " ($" . htmlspecialchars($row['precio']) . ")</option>";
                                }
                                $stmt_servicios->close();
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="estilista">Estilista:</label>
                            <select name="estilista" id="estilista" required>
                                <option value="">Seleccione un estilista</option>
                                <?php
                                $result_estilistas->data_seek(0); // Reiniciar puntero
                                while ($row = $result_estilistas->fetch_assoc()) {
                                    echo "<option value=\"" . htmlspecialchars($row['id']) . "\">" . htmlspecialchars($row['nombre']) . " " . htmlspecialchars($row['apellido']) . "</option>";
                                }
                                $stmt_estilistas->close();
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fecha">Fecha:</label>
                            <input type="date" name="fecha" id="fecha" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora">Hora:</label>
                            <input type="time" name="hora" id="hora" required>
                        </div>
                        <div class="form-group">
                            <label for="pago">Método de Pago:</label>
                            <select name="pago" id="pago" required>
                                <option value="">Seleccione un método</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit">Solicitar Cita</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>