<?php
session_start();
include("clases/mysql.inc.php");
$db = new mod_db();

include("clases/SanitizarEntrada.php");
include("comunes/loginfunciones.php");
include("clases/objLoginAdmin.php");

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

$tokenizado = false;

// =====================================================
// ✅ Validación CSRF
// =====================================================
$token_enviado = $_POST['tolog'] ?? '';
$token_almacenado = $_SESSION['csrf_token'] ?? '';

if ($token_enviado !== '' && $token_almacenado !== '' && hash_equals($token_almacenado, $token_enviado)) {
    $tokenizado = true;
} else {
    error_log("[CSRF] Token inválido en login");
    $_SESSION["emsg"] = 1;
    header("Location: login.php");
    exit;
}


// =====================================================
// 🔑 VALIDAR LOGIN
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenizado) {

    $Usuario = trim($_POST['usuario']);
    $ClaveKey = trim($_POST['contrasena']);
    $ipRemoto = $_SERVER['REMOTE_ADDR'];

    $Logearme = new ValidacionLogin($Usuario, $ClaveKey, $ipRemoto, $db);

    // -------------------------
    // ✔ Usuario EXISTE
    // -------------------------
    if ($Logearme->logger()) {

        $Logearme->autenticar();

        // -------------------------
        // ✔ Contraseña correcta
        // -------------------------
        if ($Logearme->getIntentoLogin()) {

            $_SESSION['autenticado'] = "SI";
            $_SESSION['Usuario'] = $Logearme->getUsuario();

            // Convertir a correo si el usuario inicia con su username
            if (strpos($_SESSION['Usuario'], '@') === false) {

                $buscarCorreo = $db->getConexion()->prepare("
                    SELECT Correo FROM usuarios WHERE Usuario = :user
                ");
                $buscarCorreo->bindParam(":user", $_SESSION['Usuario']);
                $buscarCorreo->execute();
                $dato = $buscarCorreo->fetch(PDO::FETCH_ASSOC);

                if (!empty($dato['Correo'])) {
                    $_SESSION['Usuario'] = $dato['Correo'];
                }
            }

            // Registrar intento
            $Logearme->registrarIntentos();


            // ====================================================
            // 🚀 INTEGRACIÓN 2FA — FLUJO CORREGIDO
            // ====================================================
            try {
                $conn = $db->getConexion();
                $stmt = $conn->prepare("
                    SELECT id, secret_2fa 
                    FROM usuarios 
                    WHERE Usuario = :user OR Correo = :user
                ");
                $stmt->bindParam(":user", $_SESSION['Usuario']);
                $stmt->execute();
                $usuarioData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usuarioData) {

                    $_SESSION['usuario_id'] = $usuarioData['id'];
                    $_SESSION['secret_2fa'] = $usuarioData['secret_2fa'] ?? null;

                    // Estado inicial
                    $_SESSION['verificado_2fa'] = false;

                    // ➤ Si YA tiene 2FA → VALIDAR CÓDIGO
                    if (!empty($usuarioData['secret_2fa'])) {
                        header("Location: verificar_login_2fa.php");
                        exit;
                    }

                    // ➤ Si NO tiene 2FA → ACTIVAR
                    header("Location: activar_2fa.php");
                    exit;
                }

                // --------------------------------------------------
                // ✔ Usuario sin 2FA, entra NORMAL al panel
                // --------------------------------------------------
                $_SESSION['verificado_2fa'] = true;
                header("Location: formularios/PanelControl.php");
                exit;

            } catch (Exception $e) {
                error_log("Error 2FA: " . $e->getMessage());

                // Permitir entrar al panel aunque falle la consulta
                $_SESSION['verificado_2fa'] = true;
                header("Location: formularios/PanelControl.php");
                exit;
            }


        } else {
            // ❌ Contraseña incorrecta
            $Logearme->registrarIntentos();
            $_SESSION["emsg"] = 1;
            header("Location: login.php");
            exit;
        }

    } else {
        // ❌ Usuario no existe
        $Logearme->registrarIntentos();
        $_SESSION["emsg"] = 1;
        header("Location: login.php");
        exit;
    }
}


// ❌ CSRF o POST inválido
$_SESSION["emsg"] = 1;
header("Location: login.php");
exit;
?>
