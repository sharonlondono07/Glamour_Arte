<?php
include_once('../model/conexion.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $apellido = $_POST['apellido'] ?? ''; // Nuevo campo para apellido
    $nombre = $_POST['nombre'] ?? '';

    // Validaciones básicas
    if (empty($correo) || empty($password) || empty($telefono) || empty($apellido) || empty($nombre)) {
        echo "<script>alert('Todos los campos son obligatorios.'); window.location='registro.php';</script>";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Por favor, ingresa un correo electrónico válido.'); window.location='registro.php';</script>";
    } else {
        try {
            // Verificar si el correo ya está registrado
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE correo = ?");
            $stmt_check->bind_param("s", $correo);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                echo "<script>alert('Este correo ya está registrado. Por favor, use otro.'); window.location='registro.php';</script>";
            } else {
                // Insertar el nuevo usuario con id_rol fijo y contraseña en texto plano
                $stmt_insert = $conn->prepare("INSERT INTO users (correo, password, id_rol, telefono, apellido, nombre) VALUES (?, ?, 3, ?, ?, ?)");
                $stmt_insert->bind_param("sssss", $correo, $password, $telefono, $apellido, $nombre);

                if ($stmt_insert->execute()) {
                    // Mostrar modal en lugar de alerta
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var modal = document.getElementById('successModal');
                            modal.style.display = 'flex';
                        });
                    </script>";
                } else {
                    echo "<script>alert('Error al crear la cuenta: " . mysqli_error($conn) . "'); window.location='registro.php';</script>";
                    error_log("Insert error: " . mysqli_error($conn));
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        } catch (Exception $e) {
            echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='registro.php';</script>";
            error_log("Exception in registro.php: " . $e->getMessage());
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
    <title>Crear cuenta</title>
    <link rel="stylesheet" href="../estilo/stylregistro.css" />
    <style>
        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            padding: 2em;
            border-radius: 15px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.5s ease-out;
            position: relative;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-content h3 {
            color: #ff66b3;
            font-size: 1.8em;
            margin-bottom: 1em;
        }

        .modal-content p {
            color: #444;
            font-size: 1.1em;
            margin-bottom: 1.5em;
        }

        .modal-button {
            background: #ff66b3;
            color: #fff;
            border: none;
            padding: 0.8em 1.5em;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .modal-button:hover {
            background: #e91e63;
            transform: translateY(-2px);
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 1.5em;
            color: #ff66b3;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #e91e63;
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                padding: 1.5em;
            }
        }
    </style>
</head>

<body>
    <section class="login-container">
        <form class="login-form" method="POST" action="registro.php">
            <div class="register-link">
                <h2>Crear Cuenta</h2>
            </div>
            <input type="text" name="correo" placeholder="Correo" required>
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellido" placeholder="Apellido" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="number" name="telefono" placeholder="Teléfono" required>
            <button type="submit">Guardar</button>
            <div class="register-link">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            </div>
            <footer class="pie">
                <p>© 2025 Salón de Belleza. Todos los derechos reservados.</p>
            </footer>
        </form>
    </section>

    <!-- Modal de éxito -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('successModal').style.display='none'; window.location='login.php';">&times;</span>
            <h3>¡Éxito!</h3>
            <p>Cuenta creada exitosamente. Serás redirigido al inicio de sesión.</p>
            <button class="modal-button" onclick="document.getElementById('successModal').style.display='none'; window.location='login.php';">Cerrar</button>
        </div>
    </div>

    <script>
        // Asegúrate de que el modal se cierre al hacer clic fuera
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('successModal');
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                    window.location = 'login.php';
                }
            });
        });
    </script>
</body>
</html>