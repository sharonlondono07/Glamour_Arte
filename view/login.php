<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('../model/conexion.php');

// Check if database connection is successful
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, correo, id_rol, password FROM users WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Compare password directly (text plain)
        if ($password === $user['password']) {
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $user['id'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['id_rol'] = $user['id_rol'];

            error_log("Login successful - User: $correo, Role: " . $user['id_rol']);

            switch ($user['id_rol']) {
                case 1: // Administrador
                    header("Location: paneladmin.php");
                    break;
                case 2: // Estilista
                    header("Location: panelestilista.php");
                    break;
                case 3: // Cliente
                    header("Location: panelcliente.php");
                    break;
                default:
                    $error = "Rol no reconocido";
                    error_log("Unrecognized role: " . $user['id_rol']);
                    break;
            }
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos";
            error_log("Login failed - Incorrect password for: $correo");
        }
    } else {
        $error = "Usuario o contraseña incorrectos";
        error_log("Login failed - User not found: $correo");
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Iniciar Sesión</title>
    <link rel="icon" href="../img/icono.png" type="image/png">
    <link rel="stylesheet" href="../estilo/styleslogin.css">
</head>
<body>
    <main>
        <section class="login-section">
            <div class="login-card">
                <div class="login-logo">
                    <img src="../img/icono.png" alt="Logo Glamour y Arte" class="logo-login">
                </div>
                <div class="h2">
                    <h2>Iniciar Sesión</h2>
                </div>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="correo" id="correo" placeholder="correo" required>
                    </div>
                    <div class="form-group">
                        <input type="password" id="password" name="password" placeholder="password" required>
                    </div>
                    <button 
                        type="submit"
                        style="background-color: #c43d6c; color: #fff; border: none; padding: 0.75rem; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; margin-top: 1rem; width: 100%;"
                        onmouseover="this.style.backgroundColor='#a23056'; this.style.transform='scale(1.05)'"
                        onmouseout="this.style.backgroundColor='#c43d6c'; this.style.transform='scale(1)'"
                        onmousedown="this.style.transform='scale(0.95)'"
                        onmouseup="this.style.transform='scale(1.05)'"
                    >Iniciar Sesión</button>
                </form>
                <a href="recuperarcontra.php">¿Has olvidado tu contraseña?</a>
                <a href="registro.php" class="register-link">No tengo cuenta. Crear cuenta</a>
            </div>
        </section>
    </main>
</body>
</html>