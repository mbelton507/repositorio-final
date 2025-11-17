<?php
require 'vendor/autoload.php';
require_once("clases/mysql.inc.php");

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

// =====================================================
// 🧠 INICIO SEGURO DE SESIÓN
// =====================================================
session_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verificar que el usuario haya iniciado sesión correctamente
if (
    !isset($_SESSION["Usuario"]) ||
    empty($_SESSION["Usuario"]) ||
    !isset($_SESSION["autenticado"]) ||
    $_SESSION["autenticado"] !== "SI"
) {
    die("❌ Acceso denegado. Inicia sesión primero desde <a href='login.php'>login.php</a>");
}

// =====================================================
// 🔗 CONEXIÓN A BASE DE DATOS
// =====================================================
$db = new mod_db();
$conn = $db->getConexion();

// =====================================================
// 🔎 OBTENER DATOS DEL USUARIO
// =====================================================
$usuario_nombre = $_SESSION["Usuario"];
$stmt = $conn->prepare("SELECT id, secret_2fa, Correo FROM usuarios WHERE Usuario = :usuario OR Correo = :usuario");
$stmt->bindParam(":usuario", $usuario_nombre);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ No se encontró el usuario en la base de datos.");
}

// =====================================================
// 🔐 GENERAR O USAR SECRET EXISTENTE
// =====================================================
if (!empty($user["secret_2fa"])) {
    $secret = $user["secret_2fa"];
} else {
    // Generar nuevo secreto y guardarlo
    $g = new GoogleAuthenticator();
    $secret = $g->generateSecret();

    $update = $conn->prepare("UPDATE usuarios SET secret_2fa = :secret WHERE id = :id");
    $update->bindParam(":secret", $secret);
    $update->bindParam(":id", $user["id"]);
    $update->execute();
}

// =====================================================
// 📱 GENERAR CÓDIGO QR
// =====================================================
$nombre_app = "UTP_Login_2FA";
$correo = $user["Correo"] ?? $usuario_nombre;
$qrCodeUrl = GoogleQrUrl::generate($correo, $secret, $nombre_app);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Activar 2FA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            text-align: center;
            padding-top: 50px;
        }
        .container {
            background: #fff;
            display: inline-block;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        img {
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Activación del Segundo Factor (2FA)</h2>
    <p>Escanea este código QR con <b>Google Authenticator</b> en tu celular.</p>
    <img src="<?= $qrCodeUrl ?>" alt="QR Code 2FA">
    <p>O introduce este código manualmente en la app:</p>
    <strong><?= $secret ?></strong>
    <br><br>
    <form action="verificar_2fa.php" method="POST">
        <input type="hidden" name="secret" value="<?= $secret ?>">
        <input type="text" name="codigo" placeholder="Código de 6 dígitos" required>
        <br><br>
        <button type="submit">Verificar Código</button>
    </form>

    <!-- Enlace adicional -->
    <a href="registro_usuario.php">← Volver al registro</a>
</div>

</body>
</html>
