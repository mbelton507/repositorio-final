<?php
require 'vendor/autoload.php';
require_once("clases/mysql.inc.php");

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

$db = new mod_db();
$conn = $db->getConexion();

$codigo = $_POST["codigo"] ?? '';
$secret = $_POST["secret"] ?? '';

$g = new GoogleAuthenticator();

if ($g->checkCode($secret, $codigo)) {
    echo "<h2 style='color:green; text-align:center;'>✅ Código correcto. 2FA verificado exitosamente.</h2>";
} else {
    echo "<h2 style='color:red; text-align:center;'>❌ Código incorrecto. Inténtalo nuevamente.</h2>";
}
?>
