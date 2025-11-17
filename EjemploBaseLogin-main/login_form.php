<!DOCTYPE html>
<html lang="es">
<head>

<meta name="Description" content="Ejemplo de Login" />
<meta name="Keywords" content="your, keywords" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="Distribution" content="Global" />
<meta name="Author" content="Irina Fong - dreamsweb7@gmail.com" />
<meta name="Robots" content="index,follow" />

<script src="jquery/jquery-latest.js" type="text/javascript"></script> 
<script src="jquery/jquery.validate.js"  type="text/javascript"></script>
<link rel="shortcut icon"  href="patria/5564844.png">

<link rel="stylesheet" href="css/cmxform.css" type="text/css" />
<link rel="stylesheet" href="Estilos/Techmania.css" type="text/css" />
<link rel="stylesheet" href="Estilos/general.css"   type="text/css">
<title>Ejemplo de Prueba del Login</title>

<script type="text/javascript">
  $(document).ready(function(){
    $("#deteccionUser").validate({
      rules: {
        usuario: "required",
        contrasena: "required",
      }
    });
  });
</script>

<style>
.alerta-error{
    background-color: #fff9f9;
    color: #6a0e0e;
    border: 1px solid #eee;
    border-left: 5px solid #d32f2f;
    border-radius: 4px; 
    padding: 12px 18px; 
    margin: 10px auto; 
    display: flex;
    align-items: center;
    max-width: 450px;
    font-size: 15px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
a.volver {
    color: #007bff;
    text-decoration: none;
}
a.volver:hover {
    text-decoration: underline;
}
</style>

</head>

<body>
<!-- wrap starts here -->	
<div id="wrap">
  <div id="headerlogin"></div>
  <p>
    <a href=""><img src="img/regresar.gif" alt="Atr&aacute;s" width="90" height="30" longdesc="login.php" /></a>
  </p>

  <?php
    session_start();
    // Generar y almacenar token CSRF
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
  ?>

  <div align="center">
    <form  class="cmxform" id="deteccionUser"  name="deteccionUser" method="post" action="index.php">
      <br />
      <input type="hidden" name="tolog"  id="tolog"  value="<?php echo $csrf_token; ?>">

      <table width="89%" border="0" align="center">
        <tr>
          <td height="19" colspan="2"  align="center">Ing. Web | UTP</td>
        </tr>
        <tr>
          <td width="25%">Usuario:</td>
          <td width="42%">
            <input id="usuario" name="usuario" type="text" minlength="4" />
          </td>
        </tr>
        <tr>
          <td>Contrase&ntilde;a:</td>
          <td>
            <input id="contrasena" name="contrasena" type="password" />
            <span id="toggleContrasena" 
              style="position:absolute; right:8px; top:5px; cursor:pointer; user-select:none;">👁️</span>
          </td>
        </tr>
        <tr>
          <td colspan="2" align="center">                     
            <div align="center">
              <input name="Submit" type="submit" class="clear" value="Iniciar sesión" />
              <br><small>(*Presiona Enter o haz clic para entrar)</small>
            </div>
          </td>
        </tr>

        <tr>
          <td colspan="2" align="center">
            <a href="registro_usuario.php" class="volver">¿No tienes una cuenta? Regístrate aquí</a>
          </td>
        </tr>
      </table>

      <div id="error">
        <font color="#FF0000">
        <?php
          if (!empty($_SESSION["emsg"]) && $_SESSION["emsg"] == 1) {
            echo '<div class="alerta-error">';
            echo '<strong>¡Error de Autenticación!</strong> Usuario o contraseña incorrectos. Por favor, vuelva a intentarlo.';
            echo '</div>';
            unset($_SESSION["emsg"]);
          }
        ?>
        </font>
      </div>
    </form>
  </div>

  <br />
  <?php include("comunes/footer.php");?>
  <!-- wrap ends here -->		
</div>

<script>
const toggle = document.getElementById('toggleContrasena');
const input = document.getElementById('contrasena');

toggle.addEventListener('click', () => {
  const isPassword = input.type === 'password';
  input.type = isPassword ? 'text' : 'password';
  toggle.textContent = isPassword ? '🙈' : '👁️';
});
</script>

</body>
</html>
