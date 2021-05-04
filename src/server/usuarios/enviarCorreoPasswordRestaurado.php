<?php
require "../lib.php";
require "../vendor/autoload.php";
require "../email.php";

$api = new sgcbApi();
$emailApi = new emailApi();
$input = $api->detectRequestBody();

if (isset($input["correo"])) {
  $correoDestinatario = $api->sanitize_string($input["correo"]);
  $asunto = "[SGCB] Contraseña restaurada";
  $cuerpo = "
    <p>
      Te informamos que tu contraseña ha sido restaurada con éxito.
    </p>
    <p>
      Si no realizaste esta acción, puedes recuperar el acceso a tu cuenta ingresando tu dirección de correo electrónico en el siguiente enlace:
      <a href='$emailApi->domain/#/recuperarCuenta' target='_blank' rel='noopener noreferrer'>$emailApi->domain/#/recuperarCuenta</a>
    </p>
    <p>
      Si no puedes recuperar el acceso a tu cuenta, contacta al administrador de la aplicación.
    </p>";

  $json = $emailApi->enviarCorreo($correoDestinatario, $asunto, $cuerpo);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
