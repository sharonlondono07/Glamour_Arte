<?php
include_once('../model/conexion.php');

// Iniciar sesión
session_start();
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id']) || $_SESSION['id_rol'] != 2) {
    header("Location: mis_trabajos.php");
    exit();
}

$id_estilista = isset($_GET['id_estilista']) ? intval($_GET['id_estilista']) : $_SESSION['id']; // Usar ID de la URL o sesión
$correo = $_SESSION['correo'];

// Obtener información del estilista
$stmt_estilista = $conn->prepare("SELECT u.nombre, e.foto 
                                 FROM users u 
                                 LEFT JOIN estilistas e ON u.id = e.id_usuario 
                                 WHERE u.id = ? AND u.id_rol = 2");
$stmt_estilista->bind_param("i", $id_estilista);
$stmt_estilista->execute();
$result_estilista = $stmt_estilista->get_result();
$estilista = $result_estilista->fetch_assoc();      
$stmt_estilista->close();

if (!$estilista) {
    die("Estilista no encontrado.");
}

// Subir imagen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_trabajo'])) {
    $foto = $_FILES['foto_trabajo'];
    $nombre_archivo = uniqid() . '_' . $foto['name'];
    $ruta = 'uploads/' . $nombre_archivo;

    if (move_uploaded_file($foto['tmp_name'], $ruta)) {
        $stmt = $conn->prepare("INSERT INTO trabajos_estilista (id_estilista, foto_trabajo) VALUES (?, ?)");
        $stmt->bind_param("is", $id_estilista, $ruta);
        $stmt->execute();
        $stmt->close();
    }
}

// Obtener trabajos del estilista
$stmt_trabajos = $conn->prepare("SELECT foto_trabajo FROM trabajos_estilista WHERE id_estilista = ?");
$stmt_trabajos->bind_param("i", $id_estilista);
$stmt_trabajos->execute();
$result_trabajos = $stmt_trabajos->get_result();
$trabajos = $result_trabajos->fetch_all(MYSQLI_ASSOC);
$stmt_trabajos->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Mis Trabajos</title>
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

        .estilista-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .estilista-info img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 15px;
        }

        .estilista-info h3 {
            font-size: 20px;
            color: #333;
        }

        .upload-form {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9e6ec;
            border-radius: 10px;
            border: 2px solid #ff99cc;
        }

        .upload-form input[type="file"] {
            margin: 10px 0;
        }

        .upload-form button {
            background-color: #ff66b3;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .upload-form button:hover {
            background-color: #e91e63;
        }

        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .gallery img {
            width: 200px;
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

            .gallery img {
                width: 150px;
                height: 150px;
            }

            .estilista-info {
                flex-direction: column;
                text-align: center;
            }

            .estilista-info img {
                margin-right: 0;
                margin-bottom: 10px;
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
                <a href="mis_trabajos.php" class="active"><span class="icon">🖼️</span> Mis Trabajos</a>
                <a href="configestilista.php"><span class="icon">⚙️</span> Configuración</a>
            </div>
            <div class="logout">
                <a href="logout.php"><span class="icon">🚪</span> Cerrar Sesión</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <h1>Mis Trabajos</h1>
                <div class="user-info">
                    <span>
                        <span class="user-email"><?php echo htmlspecialchars($correo); ?></span>
                        <span class="user-role">Estilista</span>
                    </span>
                </div>
            </header>
            <section class="dashboard">
                <div class="card">
                    <div class="estilista-info">
                        <?php if ($estilista['foto']): ?>
                            <img src="<?php echo htmlspecialchars($estilista['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($estilista['nombre']); ?>">
                        <?php else: ?>
                            <img src="uploads/default.jpg" alt="Foto por defecto" style="max-width: 100px; max-height: 100px;">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($estilista['nombre']); ?></h3>
                    </div>
                    <h3>Subir Nuevos Trabajos</h3>
                    <form class="upload-form" method="POST" enctype="multipart/form-data">
                        <input type="file" name="foto_trabajo" accept="image/*" required>
                        <button type="submit">Subir Imagen</button>
                    </form>
                    <h3>Galería de Trabajos</h3>
                    <div class="gallery">
                        <?php foreach ($trabajos as $trabajo): ?>
                            <img src="<?php echo htmlspecialchars($trabajo['foto_trabajo']); ?>" alt="Trabajo de Estilista">
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>