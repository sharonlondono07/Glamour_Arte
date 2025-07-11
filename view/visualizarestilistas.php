<?php
include_once('../model/conexion.php');

if (!$conn) {
    die("Error: La conexión no se estableció. Verifica model/conexion.php: " . mysqli_connect_error());
}

// Fetch stylists with description and photo
$sql_stylists = "SELECT u.id, u.correo, u.nombre, e.descripcion, e.foto 
                 FROM users u 
                 LEFT JOIN estilistas e ON u.id = e.id_usuario 
                 WHERE u.id_rol = 2";
$result_stylists = $conn->query($sql_stylists);

if (!$result_stylists) {
    $error = "Error al ejecutar la consulta: " . mysqli_error($conn);
    error_log($error); // Registra el error para depuración
    $stylists = [];
} else {
    $stylists = $result_stylists->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Estilistas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f5f5, #fff0f6);
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .header {
            background: linear-gradient(90deg, #ff99cc, #ff66b3);
            padding: 1.5em 2em;
            color: #fff;
            position: relative;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header .logo {
            font-size: 2em;
            font-weight: 700;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .login-buttons a {
            color: #fff;
            text-decoration: none;
            margin-left: 1em;
            padding: 0.7em 1.5em;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .login-buttons a:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }

        nav {
            background: #ffccd5;
            padding: 1em;
            display: flex;
            justify-content: center;
            gap: 2em;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        nav a {
            color: #333;
            text-decoration: none;
            font-size: 1.1em;
            font-weight: 500;
            padding: 0.5em 1em;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        nav a:hover {
            color: #ff66b3;
            background: rgba(255, 102, 179, 0.1);
        }

        .content {
            padding: 2.5em 2em;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stylists h2 {
            font-size: 2.5em;
            color: #ff66b3;
            margin-bottom: 0.5em;
            text-align: center;
            animation: fadeIn 1s ease-in;
        }

        .stylists p {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 2em;
            text-align: center;
            animation: fadeIn 1.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .stylist-cards {
            display: flex;
            justify-content: center;
            gap: 2em;
            flex-wrap: wrap;
            padding: 2em 0;
        }

        .stylist-card {
            background: linear-gradient(135deg, #fff, #fff0f6);
            border: 1px solid #ddd;
            border-radius: 15px;
            padding: 1.5em;
            width: 250px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .stylist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .stylist-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ff99cc;
            margin-bottom: 1em;
            transition: transform 0.3s ease;
        }

        .stylist-card:hover img {
            transform: scale(1.1);
        }

        .stylist-card h3 {
            margin: 0.5em 0;
            font-size: 1.3em;
            color: #ff66b3;
        }

        .stylist-card p {
            margin: 0.5em 0;
            color: #444;
            font-size: 0.95em;
            min-height: 3em;
        }

        .stylist-card button {
            background: #ff66b3;
            color: #fff;
            border: none;
            padding: 0.7em 1.5em;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            width: 100%;
        }

        .stylist-card button:hover {
            background: #e91e63;
            transform: translateY(-2px);
        }

        .footer {
            display: flex;
            justify-content: space-around;
            padding: 40px 20px;
            background: linear-gradient(90deg, #ffccd5, #fff0f6);
            max-width: 1200px;
            margin: 40px auto 0;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
            border-radius: 10px 10px 0 0;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-section {
            text-align: center;
            flex: 1;
            min-width: 200px;
        }

        .footer-section h3 {
            font-size: 20px;
            color: #ff66b3;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .footer-section p {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .social-media {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 10px;
        }

        .social-media a {
            text-decoration: none;
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .social-media a:hover {
            transform: scale(1.2);
        }

        .social-media .text-light {
            color: #25d366; /* Color verde de WhatsApp */
        }

        .social-media .text-success {
            color: #25d366; /* Color verde de WhatsApp */
        }

        .social-media i {
            font-size: 1.5em;
        }

        .footer-section a {
            color: #ff66b3;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: #e91e63;
        }

        .copyright {
            width: 100%;
            text-align: center;
            padding: 15px;
            background: #fde0f8;
            color: #666;
            font-size: 12px;
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            border-radius: 0 0 10px 10px;
        }

        .copyright a {
            color: #ff66b3;
            text-decoration: none;
            margin: 0 5px;
            transition: color 0.3s ease;
        }

        .copyright a:hover {
            color: #e91e63;
        }

        @media (max-width: 768px) {
            .header {
                padding: 1em;
                flex-direction: column;
                text-align: center;
            }

            .login-buttons {
                margin-top: 1em;
            }

            .login-buttons a {
                margin: 0 0.5em;
            }

            nav {
                flex-direction: column;
                text-align: center;
                gap: 1em;
            }

            .content {
                padding: 1.5em;
            }

            .stylist-card {
                width: 100%;
                max-width: 250px;
            }

            .footer {
                flex-direction: column;
                padding: 20px;
                gap: 20px;
            }

            .social-media {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }

            .social-media a {
                margin: 0 5px;
            }
        }
    </style>
    <!-- Añadir Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-...your-integrity-hash..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>    
    <nav>
        <a href="index.php">Inicio</a>
        <a href="visualizarservicios.php">Servicios</a>
        <a href="visualizarestilistas.php" class="active">Estilistas</a>
        
    </nav>
    <div class="content">
        <div class="stylists">
            <h2>Nuestros Estilistas</h2>
            <p>Conoce a nuestro talentoso equipo de profesionales</p>
            <div class="stylist-cards">
                <?php
                if (!empty($stylists)) {
                    foreach ($stylists as $row) {
                        echo "<div class='stylist-card'>";
                        $image_src = $row['foto'] ? "../uploads/" . htmlspecialchars($row['foto']) : "images/default.png";
                        echo "<img src='" . $image_src . "' alt='" . htmlspecialchars($row['nombre'] ?? $row['correo']) . "'>";
                        echo "<h3>" . htmlspecialchars($row['nombre'] ?? $row['correo']) . "</h3>";
                        echo "<p>" . htmlspecialchars($row['descripcion'] ?? 'Sin descripción disponible') . "</p>";
                        echo "<form action='galeria_estilista.php' method='get' style='display:inline;'>";
                        echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                        echo "<button type='submit'>Ver Detalles</button>";
                        echo "</form>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No hay estilistas registrados. Agrega algunos estilistas con rol 'estilista' (id_rol = 2).</p>";
                }
                ?>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="footer-section">
            <h3>Contacto</h3>
            <p>📍 Calle 24B #45-114 Barrio Santander - Neiva</p>
            <p>📞 +57 3123979732</p>
            <p>✉️ info@glamouryarte.com</p>
        </div>
        <div class="footer-section">
            <h3>Síguenos</h3>
            <div class="social-media">
                <a href="https://www.instagram.com/glamour.y.arte?igsh=MTUyNHYxeXVqYmI0Zw==" target="_blank" class="text-light">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://wa.me/573123979732" target="_blank" class="text-success">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
            <p>Horario: Lunes a Sábado 8:00 AM - 9:00 PM</p>
        </div>
    </footer>
    <div class="copyright">
        <p>© 2025 Glamour y Arte. Todos los derechos reservados.</p>
        <p><a href="#">Términos y Condiciones</a> | <a href="#">Política de Privacidad</a></p>
    </div>
    <?php $conn->close(); ?>
</body>
</html>