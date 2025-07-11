<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte</title>
    <link rel="stylesheet" href="../estilo/stylesinicio.css">
    <!-- Añadir Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-...your-integrity-hash..." crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <nav>
        <div class="logo-nombre">
            <img src="../img/icono.png" alt="Logo" class="logo-header">
            <span>Glamour y Arte</span>
        </div>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="visualizarestilistas.php">Estilistas</a></li>
            
        </ul>
        <div class="auth">
            <a href="login.php">Iniciar Sesión</a>
            <a href="registro.php" class="register">Registrarse</a>
        </div>
    </nav>

    <header class="content">
        <div class="text-content">
            <h1>Glamour y Arte</h1><br>
            <p>Tu salón de belleza de confianza. Reserva tu cita online y disfruta de nuestros servicios profesionales.</p><br>
            <div class="buttons">
                <a href="login.php" class="btn">Reservar Cita</a>
                <a href="visualizarservicios.php" class="link">Ver Servicios</a>
            </div>
        </div>
        <div class="header-image">
            <img src="../img/inicio.png" alt="Header Image">
        </div>
    </header>

    <footer class="footer">
        <div class="footer-section">
            <h3>Contacto</h3>
            <p>📍 Calle 24B #45-114 Barrio Santander - Neiva</p>
            <p>📞 +57 3123979732</p>
            <p>✉️ info@glamouryarte.com</p>
        </div>
        <div class="footer-section">
            <h3>Síguenos:</h3>       
        
            <!-- Integración de redes sociales -->
            <div class="social-media d-flex gap-3" style="margin-top: 10px;">
                <a href="https://www.instagram.com/glamour.y.arte?igsh=MTUyNHYxeXVqYmI0Zw=="<?php echo isset($INSTAGRAM_URL) ? $INSTAGRAM_URL : 'https://www.instagram.com/glamour.y.arte?igsh=MTUyNHYxeXVqYmI0Zw=='; ?>" class="text-light" target="_blank">
                    <i class="fab fa-instagram fa-2x"></i>
                </a>
                <a href="https://wa.me/573123979732" class="text-success" target="_blank">
                    <i class="fab fa-whatsapp fa-2x"></i>
                </a>
            </div>
             <p>Horario: Lunes a Sábado 8:00 AM - 9:00 PM</p>
        </div>
    </footer>
    <div class="copyright">
        <p>© 2025 Glamour y Arte. Todos los derechos reservados.</p>
        <p><a href="#">Términos y Condiciones</a> | <a href="#">Política de Privacidad</a></p>
    </div>
</body>
</html>