<xaiArtifact artifact_id="25acff7a-01ce-40c9-bf24-827f63f45d7f" artifact_version_id="9c6f12cf-bc6e-4602-8595-a32f4247472e" title="verificar_codigo.php" contentType="text/php">

<?php
include_once('../model/conexion.php');

session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: recuperarcontra.php");
    exit();
}

$correo = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $verification_code = $_POST['verification_code'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE correo = ? AND verification_code = ? AND code_expires_at > NOW()");
    $stmt->bind_param("ss", $correo, $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Código de verificación incorrecto o expirado.";
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
    <title>Glamour y Arte - Verificar Código</title>
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
                    <h2>Verificar Código</h2>
                </div>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
                <p>Te hemos enviado un código de verificación a <?php echo htmlspecialchars($correo); ?>. Ingresa el código a continuación.</p>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="verification_code" id="verification_code" placeholder="Código de Verificación" required>
                    </div>
                    <button 
                        type="submit"
                        style="background-color: #c43d6c; color: #fff; border: none; padding: 0.75rem; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; margin-top: 1rem; width: 100%;"
                        onmouseover="this.style.backgroundColor='#a23056'; this.style.transform='scale(1.05)'"
                        onmouseout="this.style.backgroundColor='#c43d6c'; this.style.transform='scale(1)'"
                        onmousedown="this.style.transform='scale(0.95)'"
                        onmouseup="this.style.transform='scale(1.05)'"
                    >Verificar Código</button>
                </form>
                <a href="recuperarcontra.php">Reenviar Código</a> | <a href="login.php">Volver al Inicio de Sesión</a>
            </div>
        </section>
    </main>
</body>
</html>