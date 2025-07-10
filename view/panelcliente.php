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

// Obtener el nombre del cliente logueado
$stmt_user = $conn->prepare("SELECT COALESCE(nombre, correo) as display_name FROM users WHERE id = ?");
$stmt_user->bind_param("i", $id_cliente);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$display_name = $user['display_name'];
$stmt_user->close();

// Obtener citas del cliente
$stmt_agenda = $conn->prepare("SELECT s.nombre_servicio, COALESCE(u.nombre, u.correo) as estilista, c.fecha_hora, p.metodo_pago, c.estado FROM citas c JOIN servicios s ON c.id_servicio = s.id JOIN users u ON c.id_estilista = u.id JOIN pagos p ON c.id_pago = p.id WHERE c.id_cliente = ?");
$stmt_agenda->bind_param("i", $id_cliente);
$stmt_agenda->execute();
$result_agenda = $stmt_agenda->get_result();
$stmt_agenda->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Cliente</title>
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
            color:rgb(8, 7, 8);
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
            color:rgb(15, 15, 15);
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

        .card .agenda-section {
            background: linear-gradient(135deg, #fff0f6, #ffebff);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #ff99cc;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 102, 179, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 102, 179, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 102, 179, 0); }
        }

        .card .appointment {
            margin-bottom: 15px;
            padding: 15px;
            background: #fff0f6;
            border-radius: 8px;
            border-left: 4px solid #ff66b3;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .card .appointment:hover {
            border-left-color: #e91e63;
            background: #ffe6f0;
            transform: translateX(5px);
        }

        .card .appointment strong {
            color: #ff66b3;
            font-weight: 600;
        }

        .card .appointment span {
            color: #333;
            font-size: 14px;
        }

        .card .no-appointments {
            text-align: center;
            padding: 20px;
            background: #fff0f6;
            border-radius: 8px;
            color: #666;
            font-size: 16px;
            animation: fadeIn 1s ease-in;
        }

        .card .no-appointments a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: #ff66b3;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .card .no-appointments a:hover {
            background: #e91e63;
            transform: scale(1.05);
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

            .card .appointment {
                padding: 10px;
            }

            .card .agenda-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">Glamour y Arte</div>
            <ul>
                <li><a href="panelcliente.php" class="active"><span class="icon">📅</span> Mi Agenda</a></li>
                <li><a href="agendar_cita.php"><span class="icon">📅</span> Agendar Cita</a></li>
                <li><a href="configcliente.php"><span class="icon">⚙️</span> Configuración</a></li>
            </ul>
            <div class="logout">
                <a href="logout.php"><span class="icon">🚪</span> Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Bienvenid@ <?php echo htmlspecialchars($display_name); ?></h1>
                <div class="user-info">
                    <span>
                        <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                        <span class="user-role">Cliente</span>
                    </span>
                </div>
            </header>
            <section class="dashboard">
                <div class="card" id="agenda">
                    <h3>Mi Agenda</h3>
                    <p>Aquí puedes ver tus citas programadas y gestionar tu experiencia.</p>
                    <div class="agenda-section">
                        <?php
                        if ($result_agenda->num_rows > 0) {
                            while ($row = $result_agenda->fetch_assoc()) {
                                echo "<div class='appointment'><strong>Cita:</strong> <span>" . htmlspecialchars($row['nombre_servicio']) . "</span><br><strong>Estilista:</strong> <span>" . htmlspecialchars($row['estilista']) . "</span><br><strong>Fecha:</strong> <span>" . htmlspecialchars($row['fecha_hora']) . "</span><br><strong>Pago:</strong> <span>" . htmlspecialchars($row['metodo_pago']) . "</span><br><strong>Estado:</strong> <span>" . htmlspecialchars($row['estado']) . "</span></div>";
                            }
                        } else {
                            echo "<div class='no-appointments'>¡Aún no tienes citas programadas! <br> Descubre nuestros servicios y agenda tu próxima transformación. <a href='agendar_cita.php'>Agendar Ahora</a></div>";
                        }
                        ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>