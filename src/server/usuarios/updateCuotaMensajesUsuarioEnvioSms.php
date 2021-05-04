<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["idCuota"], $input["cantidadMensajesEnviados"])) {
  $idCuota = $api->sanitize_string($input["idCuota"]);
  $cantidadMensajesEnviados = $api->sanitize_integer($input["cantidadMensajesEnviados"]);

  $json = $api->updateCuotaMensajesUsuarioEnvioSms($idCuota, $cantidadMensajesEnviados);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
