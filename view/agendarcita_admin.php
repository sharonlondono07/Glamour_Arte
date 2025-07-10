<?php
include_once('../model/conexion.php');

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['id_rol'] != 1) {
    header("Location: view/login.php");
    exit();
}

$correo = $_SESSION['correo'];

// Obtener servicios
$servicios = [];
$stmt_servicios = $conn->prepare("SELECT id, nombre_servicio FROM servicios");
$stmt_servicios->execute();
$result_servicios = $stmt_servicios->get_result();
while ($row = $result_servicios->fetch_assoc()) {
    $servicios[$row['id']] = $row['nombre_servicio'];
}
$stmt_servicios->close();

// Obtener estilistas (asumiendo id_rol = 2 para estilistas)
$estilistas = [];
$stmt_estilistas = $conn->prepare("SELECT id, nombre, apellido, correo FROM users WHERE id_rol = 2");
$stmt_estilistas->execute();
$result_estilistas = $stmt_estilistas->get_result();
while ($row = $result_estilistas->fetch_assoc()) {
    $estilistas[$row['id']] = $row['nombre'] . ' ' . $row['apellido'];
}
$stmt_estilistas->close();

// Procesar el formulario
$success = '';
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agendar_cita'])) {
    $id_servicio = $_POST['servicio'];
    $id_estilista = $_POST['estilista'];
    $metodo_pago = $_POST['metodo_pago'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    if (empty($id_servicio) || empty($id_estilista) || empty($metodo_pago) || empty($fecha) || empty($hora)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            // Obtener el id del administrador que crea la cita
            $stmt_admin = $conn->prepare("SELECT id FROM users WHERE correo = ? LIMIT 1");
            $stmt_admin->bind_param("s", $correo);
            $stmt_admin->execute();
            $result_admin = $stmt_admin->get_result();
            if ($result_admin->num_rows === 0) {
                $error = "No se encontró el usuario administrador.";
            } else {
                $admin = $result_admin->fetch_assoc();
                $creado_por = $admin['id'];

                // Insertar en pagos y obtener id_pago
                $stmt_pago = $conn->prepare("INSERT INTO pagos (monto, metodo_pago, estado) VALUES (?, ?, 'pendiente')");
                $monto = 0.00; // Debes definir un monto o obtenerlo del servicio
                $stmt_pago->bind_param("ds", $monto, $metodo_pago);
                $stmt_pago->execute();
                $id_pago = $conn->insert_id; // Obtener el último ID insertado
                $stmt_pago->close();

                // Insertar en citas
                $fecha_hora = "$fecha $hora:00";
                $stmt_cita = $conn->prepare("INSERT INTO citas (id_servicio, id_estilista, id_cliente, id_pago, fecha_hora, estado, creado_por) VALUES (?, ?, ?, ?, ?, 'pendiente', ?)");
                $stmt_cita->bind_param("iiiisi", $id_servicio, $id_estilista, $creado_por, $id_pago, $fecha_hora, $creado_por);
                $stmt_cita->execute();
                $stmt_cita->close();
                $success = "Cita agendada con éxito.";
            }
            $stmt_admin->close();
        } catch (Exception $e) {
            error_log("Error en agendarcita.php: " . $e->getMessage());
            $error = "Error al agendar la cita. Verifica los datos o contacta al soporte: " . $e->getMessage();
        }
    }
}

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
            padding: 0;
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

        .card h2 {
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

        .form-group select,
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

        .form-group select:focus,
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

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                padding: 15px;
            }

            .sidebar .logo {
                margin-bottom: 20px;
            }

            .sidebar .menu ul li a,
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

            .form-group select,
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
                <ul>
                    <li><a href="paneladmin.php"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="gestorestilistas.php"><span class="icon">👩‍💼</span> Estilistas</a></li>
                    <li><a href="gestoreservicios.php"><span class="icon">✂️</span> Servicios</a></li>
                    <li><a href="agendarcita_admin.php" class="active"><span class="icon">📅</span> Agendar </a></li> 
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
                <h1>Agendar Cita</h1>
                <div class="user">
                    <div class="user-info">
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Administrador</span>
                    </div>
                </div>
            </header>
            <section class="dashboard">
                <div class="card">
                    <h2>Agendar Nueva Cita</h2>
                    <?php if ($success): ?>
                        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                    <?php elseif ($error): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="" class="form-container">
                        <div class="form-group">
                            <label for="servicio">Servicio:</label>
                            <select id="servicio" name="servicio" required>
                                <option value="">Seleccione un servicio</option>
                                <?php foreach ($servicios as $id => $nombre): ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($nombre); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="estilista">Estilista:</label>
                            <select id="estilista" name="estilista" required>
                                <option value="">Seleccione un estilista</option>
                                <?php foreach ($estilistas as $id => $nombre_completo): ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($nombre_completo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="metodo_pago">Método de Pago:</label>
                            <select id="metodo_pago" name="metodo_pago" required>
                                <option value="">Seleccione un método</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fecha">Fecha:</label>
                            <input type="date" id="fecha" name="fecha" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora">Hora:</label>
                            <input type="time" id="hora" name="hora" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="agendar_cita">Agendar Cita</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>