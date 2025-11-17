<?php
// ==============================================
// ✔ Verificación del código 2FA en el login
// Compatible con mod_db y tabla usuarios
// ==============================================

require 'vendor/autoload.php';
require_once("clases/mysql.inc.php");

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

session_start();

// ----------------------------------------------
// 🔒 Validar sesión previa de login
// ----------------------------------------------
if (!isset($_SESSION["usuario_id"])) {
    // Sesión inválida → forzar logout
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["Usuario"])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Ya está autenticado con usuario/contraseña
// pero aún falta validar 2FA
// Si ya verificó el 2FA → enviarlo al panel directamente
if (isset($_SESSION["verificado_2fa"]) && $_SESSION["verificado_2fa"] === true) {
    header("Location: formularios/PanelControl.php");
    exit;
}


// ----------------------------------------------
// 🔐 Conexión a BD
// ----------------------------------------------
$db = new mod_db();
$conn = $db->getConexion();

// ----------------------------------------------
// Obtener secreto 2FA del usuario
// ----------------------------------------------
$id_usuario = $_SESSION["usuario_id"];

$stmt = $conn->prepare("SELECT secret_2fa FROM usuarios WHERE id = :id");
$stmt->bindParam(":id", $id_usuario);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    // Usuario no encontrado → error grave
    session_destroy();
    header("Location: login.php");
    exit;
}

if (empty($usuario["secret_2fa"])) {
    // Usuario NO usa 2FA → permitir acceso normal
    $_SESSION["verificado_2fa"] = true;
    header("Location: formularios/PanelControl.php");
    exit;
}

$secret = $usuario["secret_2fa"];
$mensaje = "";


// ----------------------------------------------
// 📝 Procesar envío del código 2FA
// ----------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $codigo = trim($_POST["codigo"] ?? '');
    $g = new GoogleAuthenticator();

    // Validación del código
    if ($g->checkCode($secret, $codigo)) {

        // =============================
        // 🎉 Código correcto
        // =============================
        $_SESSION["verificado_2fa"] = true;

        header("Location: formularios/PanelControl.php");
        exit;

    } else {
        $mensaje = "❌ Código incorrecto. Inténtalo nuevamente.";
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación 2FA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background: #fff;
            padding: 25px;
            width: 320px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            text-align: center;
        }
        input {
            padding: 10px;
            width: 90%;
            margin-bottom: 10px;
            font-size: 16px;
        }
        button {
            padding: 12px;
            width: 100%;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 6px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .mensaje {
            color: red;
            margin-bottom: 10px;
            font-size: 15px;
        }
    </style>
</head>
<body>

<form method="POST">
    <h2>Verificación de Segundo Factor</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje"><?= $mensaje ?></div>
    <?php endif; ?>

    <input 
        type="text" 
        name="codigo" 
        placeholder="Código de 6 dígitos" 
        maxlength="6"
        required
    ><br>

    <button type="submit">Verificar</button>
</form>

</body>
</html>
