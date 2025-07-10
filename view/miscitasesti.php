<?php
include_once('../model/conexion.php');

// Iniciar sesión
session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id']) || $_SESSION['id_rol'] != 2) {
    header("Location: panelestilista.php");
    exit();
}

$id_estilista = $_SESSION['id'];
$correo = $_SESSION['correo'];

// Obtener citas con detalles para el calendario
$stmt_citas = $conn->prepare("SELECT c.id_cita AS id, c.fecha_hora, c.estado, s.nombre_servicio, CONCAT(u.nombre, ' ', u.apellido) AS cliente 
                             FROM citas c 
                             LEFT JOIN servicios s ON c.id_servicio = s.id 
                             LEFT JOIN users u ON c.id_cliente = u.id 
                             WHERE c.id_estilista = ?");
$stmt_citas->bind_param("i", $id_estilista);
$stmt_citas->execute();
$result_citas = $stmt_citas->get_result();
$citas = [];
while ($row = $result_citas->fetch_assoc()) {
    $citas[] = [
        'id' => $row['id'],
        'start' => $row['fecha_hora'],
        'allDay' => true,
        'title' => $row['nombre_servicio'], // Título opcional para referencia
        'estado' => $row['estado'],
        'cliente' => $row['cliente'],
        'nombre_servicio' => $row['nombre_servicio']
    ];
}
$stmt_citas->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Mis Citas</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
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

        .calendar-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        #calendar {
            max-width: 800px;
            width: 100%;
            background: linear-gradient(135deg, #fff5f7, #ffe6f0);
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border: 2px solid #ff99cc;
            transition: transform 0.3s ease;
        }

        #calendar:hover {
            transform: scale(1.02);
        }

        .fc-daygrid-day-events {
            display: none; /* Oculta los eventos dentro del calendario */
        }

        .fc-daygrid-day.fc-day-today {
            background-color: #ffe6f0; /* Resalta el día actual */
        }

        .fc-daygrid-day.fc-day {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .fc-daygrid-day.fc-day:hover {
            background-color: #fff0f5;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border: 2px solid #ff99cc;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            color: #ff66b3;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #e91e63;
        }

        .event-item {
            margin-bottom: 15px;
            padding: 12px;
            background-color: #f9e6ec;
            border-radius: 6px;
            border: 2px solid #ff99cc;
            transition: all 0.3s ease;
        }

        .event-item:hover {
            border-color: #ff66b3;
            background-color: #ffe6f0;
            transform: translateY(-2px);
        }

        .event-item strong {
            color: #ff66b3;
        }

        .event-item i {
            margin-right: 5px;
            color: #ff66b3;
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

            #calendar {
                max-width: 100%;
            }

            .modal-content {
                width: 95%;
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
                <a href="miscitasesti.php" class="active"><span class="icon">📅</span> Mis Citas</a>                
                <a href="mis_trabajos.php"><span class="icon">🖼️</span> Mis Trabajos</a>
                <a href="configestilista.php"><span class="icon">⚙️</span> Configuración</a>
            </div>
            <div class="logout">
                <a href="logout.php"><span class="icon">🚪</span> Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Mis Citas</h1>
                <div class="user-info">
                    <span>
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Estilista</span>
                    </span>
                </div>
            </header>
            <section class="dashboard">
                <div class="card">
                    <h3>Calendario de Citas</h3>
                    <div class="calendar-container">
                        <div id="calendar"></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="modal" id="modal">
        <div class="modal-content">
            <span class="close-modal" id="close-modal">×</span>
            <h4>Citas Agendadas</h4>
            <div id="events-list"></div>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                themeSystem: 'bootstrap',
                events: <?php echo json_encode($citas); ?>,
                eventDisplay: 'background', // Muestra solo fondo, sin texto
                eventBackgroundColor: '#ff99cc',
                eventBorderColor: '#ff66b3',
                height: 'auto',
                dateClick: function(info) {
                    var modal = document.getElementById('modal');
                    var eventsList = document.getElementById('events-list');
                    modal.style.display = 'flex';
                    eventsList.innerHTML = '';

                    var selectedDate = info.dateStr;
                    var citasDia = <?php echo json_encode($citas); ?>.filter(function(cita) {
                        return new Date(cita.start).toISOString().split('T')[0] === selectedDate;
                    });

                    if (citasDia.length > 0) {
                        citasDia.forEach(function(cita) {
                            var eventItem = document.createElement('div');
                            eventItem.className = 'event-item';
                            eventItem.innerHTML = `
                                <i class="fas fa-scissors"></i><strong>Servicio:</strong> ${cita.nombre_servicio}
                                <br><i class="fas fa-user"></i><strong>Cliente:</strong> ${cita.cliente}
                                <br><i class="fas fa-info-circle"></i><strong>Estado:</strong> ${cita.estado}
                                <br><i class="fas fa-clock"></i><strong>Hora:</strong> ${new Date(cita.start).toLocaleTimeString()}
                            `;
                            eventsList.appendChild(eventItem);
                        });
                    } else {
                        var noEventItem = document.createElement('div');
                        noEventItem.className = 'event-item';
                        noEventItem.textContent = 'No hay citas agendadas para este día.';
                        eventsList.appendChild(noEventItem);
                    }
                }
            });
            calendar.render();

            // Cerrar modal
            document.getElementById('close-modal').addEventListener('click', function() {
                document.getElementById('modal').style.display = 'none';
            });

            // Cerrar modal al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.style.display = 'none';
                }
            });
        });
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>