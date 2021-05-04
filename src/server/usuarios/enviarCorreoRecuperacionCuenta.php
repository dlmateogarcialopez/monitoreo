<?php

require "../vendor/autoload.php";
require "../lib.php";
require "../email.php";

$api = new sgcbApi();
$emailApi = new emailApi();
$input = $api->detectRequestBody();

if (isset($input["idUsuario"], $input["correo"])) {
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $correoDestinatario = $api->sanitize_string($input["correo"]);
  $hashId = $input["hashId"]; /* Password del usuario */

  $datos = (object) [
    "idUsuario" => $idUsuario,
    "correoDestinatario" => $correoDestinatario,
    "hashId" => $hashId
  ];

  /* El JWT se invalidará en tres horas */
  $jwtToken = Auth::encodeJwt($datos, "+3 hours");
  $asunto = "[SGCB] Cambio de contraseña";
  $cuerpo = "
    <p>
      Has solicitado el cambio de contraseña para tu cuenta. Por favor, sigue este enlace para ingresar una nueva contraseña:
    </p>
    <p>
      <a href='$emailApi->domain/#/nuevoPassword/$jwtToken' target='_blank' rel='noopener noreferrer'>$emailApi->domain/#/nuevoPassword/$jwtToken</a>
    </p>
    <p>Ten en cuenta que este enlace expirará en tres horas. Para obtener un nuevo enlace, ingresa a:
      <a href='$emailApi->domain/#/recuperarCuenta' target='_blank' rel='noopener noreferrer'>$emailApi->domain/#/recuperarCuenta</a>
    </p>";

  $json = $emailApi->enviarCorreo($correoDestinatario, $asunto, $cuerpo);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
