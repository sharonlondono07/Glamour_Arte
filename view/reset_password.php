<?php
include_once('../model/conexion.php');

session_start();

// Inicializar variables para evitar advertencias
$success = null;
$error = null;

if (!isset($_SESSION['verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: recuperarcontra.php");
    exit();
}

$correo = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $stmt_update = $conn->prepare("UPDATE users SET password = ?, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
        $stmt_update->bind_param("si", $new_password, $user['id']);
        if ($stmt_update->execute()) {
            unset($_SESSION['verified']);
            unset($_SESSION['reset_email']);
            $success = "Contraseña restablecida con éxito. <a href='login.php'>Inicia sesión</a>.";
        } else {
            $error = "Error al restablecer la contraseña.";
        }
        $stmt_update->close();
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
    <title>Glamour y Arte - Restablecer Contraseña</title>
    <link rel="icon" href="img/icono.png" type="image/png">
    <link rel="stylesheet" href="estilo/styleslogin.css">
</head>
<body>
    <main>
        <section class="login-section">
            <div class="login-card">
                <div class="login-logo">
                    <img src="img/icono.png" alt="Logo Glamour y Arte" class="logo-login">
                </div>
                <div class="h2">
                    <h2>Restablecer Contraseña</h2>
                </div>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
                <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
                <?php if (!$success): ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <input type="password" name="new_password" id="new_password" placeholder="Nueva Contraseña" required>
                        </div>
                        <button 
                            type="submit"
                            style="background-color: #c43d6c; color: #fff; border: none; padding: 0.75rem; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; margin-top: 1rem; width: 100%;"
                            onmouseover="this.style.backgroundColor='#a23056'; this.style.transform='scale(1.05)'"
                            onmouseout="this.style.backgroundColor='#c43d6c'; this.style.transform='scale(1)'"
                            onmousedown="this.style.transform='scale(0.95)'"
                            onmouseup="this.style.transform='scale(1.05)'"
                        >Restablecer Contraseña</button>
                    </form>
                <?php endif; ?>
                <a href="login.php">Volver al Inicio de Sesión</a>
            </div>
        </section>
    </main>
</body>
</html>