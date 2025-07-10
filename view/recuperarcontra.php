<xaiArtifact artifact_id="60ae2ea8-e4fc-4ef3-9f9d-9faac23b05be" artifact_version_id="e8eedaf9-25cb-4fd7-a2ef-1bbd5ec388d1" title="recuperar.php" contentType="text/php">

<?php
include_once('../model/conexion.php');

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];

    // Verificar si es un correo válido
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un correo electrónico válido.";
    } else {
        $stmt = $conn->prepare("SELECT id, correo FROM users WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Generar código de verificación (6 dígitos)
            $verification_code = sprintf("%06d", mt_rand(0, 999999));
            $code_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Código válido por 15 minutos

            // Actualizar código en la base de datos
            $stmt_update = $conn->prepare("UPDATE users SET verification_code = ?, code_expires_at = ? WHERE id = ?");
            $stmt_update->bind_param("ssi", $verification_code, $code_expires_at, $user['id']);
            $stmt_update->execute();
            $stmt_update->close();

            // Enviar correo con mail()
            $to = $correo;
            $subject = "Código de Verificación - Glamour y Arte";
            $message = "Hola,\n\nTu código de verificación para restablecer tu contraseña es: $verification_code\n\nEste código expirará en 15 minutos. Ingresa este código en el siguiente paso para continuar.\n\nSaludos,\nEquipo Glamour y Arte";
            $headers = "From: no-reply@glamouryarte.com\r\n";
            $headers .= "Reply-To: no-reply@glamouryarte.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($to, $subject, $message, $headers)) {
                // Guardar el correo en la sesión para el siguiente paso
                $_SESSION['reset_email'] = $correo;
                header("Location: verificar_codigo.php");
                exit();
            } else {
                $error = "Error al enviar el correo. Verifica la configuración del servidor.";
                error_log("Mail failed for: $correo");
            }
        } else {
            $error = "No se encontró un usuario con ese correo electrónico.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glamour y Arte - Recuperar Contraseña</title>
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
                    <h2>Recuperar Contraseña</h2>
                </div>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="email" name="correo" id="correo" placeholder="Correo Electrónico" required>
                    </div>
                    <button 
                        type="submit"
                        style="background-color: #c43d6c; color: #fff; border: none; padding: 0.75rem; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; margin-top: 1rem; width: 100%;"
                        onmouseover="this.style.backgroundColor='#a23056'; this.style.transform='scale(1.05)'"
                        onmouseout="this.style.backgroundColor='#c43d6c'; this.style.transform='scale(1)'"
                        onmousedown="this.style.transform='scale(0.95)'"
                        onmouseup="this.style.transform='scale(1.05)'"
                    >Enviar Código de Verificación</button>
                </form>
                <a href="login.php">Volver al Inicio de Sesión</a>
            </div>
        </section>
    </main>
</body>
</html>