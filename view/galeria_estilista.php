<?php
include_once('../model/conexion.php');

if (!$conn) {
    die("Error: La conexión no se estableció. Verifica model/conexion.php: " . mysqli_connect_error());
}

// Obtener el ID del estilista desde la URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID de estilista no válido.");
}

// Fetch stylist details
$sql_stylist = "SELECT u.nombre, e.foto 
                FROM users u 
                LEFT JOIN estilistas e ON u.id = e.id_usuario 
                WHERE u.id = ? AND u.id_rol = 2";
$stmt = $conn->prepare($sql_stylist);
$stmt->bind_param("i", $id);
$stmt->execute();
$result_stylist = $stmt->get_result();
$stylist = $result_stylist->fetch_assoc();
$stmt->close();

// Fetch stylist works
$sql_works = "SELECT foto_trabajo FROM trabajos_estilista WHERE id_estilista = ?";
$stmt = $conn->prepare($sql_works);
$stmt->bind_param("i", $id);
$stmt->execute();
$result_works = $stmt->get_result();
$works = $result_works->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$stylist) {
    die("Estilista no encontrado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Galería de <?php echo htmlspecialchars($stylist['nombre']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f5f5, #fff0f6);
            color: #333;
            overflow-x: hidden;
        }

        nav {
            background: linear-gradient(90deg, #ff99cc, #ffccd5);
            padding: 15px 30px;
            display: flex;
            justify-content: center;
            gap: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav a {
            color: #333;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            padding: 0.5em 1em;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        nav a:hover {
            color: #fff;
            background: rgba(255, 102, 179, 0.2);
        }

        .content {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .stylist-info {
            margin-bottom: 30px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .stylist-info img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #ff99cc;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .stylist-info img:hover {
            transform: scale(1.1);
        }

        .stylist-info h2 {
            font-size: 2.5em;
            color: #ff66b3;
            margin: 0.5em 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .work-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
            animation: fadeInGrid 1s ease-in;
        }

        @keyframes fadeInGrid {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .work-gallery .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .work-gallery .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .work-gallery .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .work-gallery .gallery-item:hover img {
            transform: scale(1.1);
        }

        .work-gallery .gallery-item .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 102, 179, 0.7);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .work-gallery .gallery-item:hover .overlay {
            opacity: 1;
        }

        .work-gallery .gallery-item .overlay p {
            font-size: 16px;
            text-align: center;
            padding: 10px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90vh;
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            animation: zoomIn 0.3s ease-out;
        }

        @keyframes zoomIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-content img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 10px;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 24px;
            color: #ff66b3;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #e91e63;
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
        }

        .footer div {
            text-align: center;
            flex: 1;
        }

        .footer h3 {
            font-size: 20px;
            color: #ff66b3;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .footer p {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .footer a {
            color: #ff66b3;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: #e91e63;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                padding: 10px;
                gap: 10px;
            }

            .content {
                padding: 20px 15px;
            }

            .stylist-info img {
                width: 150px;
                height: 150px;
            }

            .stylist-info h2 {
                font-size: 1.8em;
            }

            .work-gallery {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .work-gallery .gallery-item img {
                height: 200px;
            }

            .modal-content {
                max-width: 95%;
                padding: 15px;
            }

            .modal-content img {
                max-height: 70vh;
            }

            .footer {
                flex-direction: column;
                padding: 20px;
                gap: 20px;
            }
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
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-...your-integrity-hash..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const galleryItems = document.querySelectorAll('.gallery-item');
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const closeBtn = document.getElementsByClassName('close')[0];

            galleryItems.forEach(item => {
                item.addEventListener('click', function() {
                    modal.style.display = 'flex';
                    modalImg.src = this.querySelector('img').src;
                });
            });

            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</head>
<body>
    <nav>
        <a href="index.php">Inicio</a>
        <a href="visualizarservicios.php">Servicios</a>
        <a href="visualizarestilistas.php">Estilistas</a>
        
    </nav>
    <div class="content">
        <div class="stylist-info">            
            <h2><?php echo htmlspecialchars($stylist['nombre']); ?></h2>
        </div>
        <div class="work-gallery">
            <?php
            // Incluir la foto del estilista como primer elemento en la galería
            if ($stylist['foto']) {
                echo "<div class='gallery-item'><img src='" . htmlspecialchars($stylist['foto']) . "' alt='Foto de " . htmlspecialchars($stylist['nombre']) . "'><div class='overlay'><p>¡Foto del estilista!</p></div></div>";
            }
            // Añadir los trabajos
            if (empty($works)) {
                echo "<p>No hay trabajos registrados para este estilista.</p>";
            } else {
                foreach ($works as $work) {
                    echo "<div class='gallery-item'><img src='" . htmlspecialchars($work['foto_trabajo']) . "' alt='Trabajo de " . htmlspecialchars($stylist['nombre']) . "'><div class='overlay'><p>¡Haz clic para ver más !</p></div></div>";
                }
            }
            ?>
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

    <!-- Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img id="modalImage" src="" alt="Imagen Ampliada">
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>