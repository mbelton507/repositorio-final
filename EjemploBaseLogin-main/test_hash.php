<?php
// Generar un hash de prueba para la contraseña admin123
echo password_hash("admin123", PASSWORD_BCRYPT, ["cost" => 13]);
?>
