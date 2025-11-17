<?php
session_start();
require_once("clases/mysql.inc.php");

$db = new mod_db();
$conn = $db->getConexion();

$mensaje = "";
$tipoMensaje = ""; // info | error | success

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $correo = trim($_POST["correo"]);
    $sexo = $_POST["sexo"];
    $password = $_POST["contrasena"];

    // Encriptar la contraseña
    $hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 13]);
    $usuario = $correo; // Usamos el correo también como nombre de usuario

    try {
        // ✅ Verificar si ya existe el correo o usuario
        $check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE Correo = :correo OR Usuario = :usuario");
        $check->bindParam(":correo", $correo);
        $check->bindParam(":usuario", $usuario);
        $check->execute();
        $existe = $check->fetchColumn();

        if ($existe > 0) {
            $mensaje = "⚠️ El correo o usuario ingresado ya está registrado. Por favor, inicie sesión o use otro.";
            $tipoMensaje = "error";
        } else {
            // Insertar nuevo usuario
            $sql = "INSERT INTO usuarios (Usuario, Nombre, Apellido, Correo, HashMagic, Sexo) 
                    VALUES (:usuario, :nombre, :apellido, :correo, :hash, :sexo)";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(":usuario", $usuario);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":apellido", $apellido);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":hash", $hash);
            $stmt->bindParam(":sexo", $sexo);
            $stmt->execute();

            // ✅ Guardar sesión automáticamente
            $_SESSION["Usuario"] = $usuario;
            $_SESSION["autenticado"] = "SI";

            // Redirigir a la activación de 2FA
            header("Location: activar_2fa.php");
            exit;
        }
    } catch (PDOException $e) {
        $mensaje = "❌ Error al registrar usuario: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #98f5f5ff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            width: 320px;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
        }
        button:hover {
            background-color: #0056b3;
        }
        .msg {
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .error {
            background-color: #fff0f0;
            color: #b20000;
            border: 1px solid #f5c2c2;
        }
        .success {
            background-color: #e6ffe6;
            color: #0a7a0a;
            border: 1px solid #b5e0b5;
        }
        a {
            display: inline-block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<form method="POST" action="">
    <h2>Registro de Usuario</h2>

    <?php if ($mensaje): ?>
        <div class="msg <?= $tipoMensaje ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="text" name="apellido" placeholder="Apellido" required>
    <input type="email" name="correo" placeholder="Correo electrónico" required>
    <input type="password" name="contrasena" placeholder="Contraseña" required>
    <select name="sexo" required>
        <option value="">Seleccione sexo</option>
        <option value="M">Masculino</option>
        <option value="F">Femenino</option>
    </select>

    <button type="submit">Registrar y activar 2FA</button>
    <a href="login.php">Ya tengo una cuenta / Iniciar sesión</a>
</form>

</body>
</html>
