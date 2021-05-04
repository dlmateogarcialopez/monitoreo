<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["idUsuario"], $input["nombreUsuario"], $input["cuotaMensajes"])) {
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $nombreUsuario = $api->sanitize_string($input["nombreUsuario"]);
  $cuotaMensajes = $input["cuotaMensajes"];

  $cuotaMensajes["selectBolsa"] = $api->sanitize_string($input["cuotaMensajes"]["selectBolsa"]);
  $cuotaMensajes["selectPeriodoMensajesUsuario"] = $api->sanitize_string($input["cuotaMensajes"]["selectPeriodoMensajesUsuario"]);
  $cuotaMensajes["cantidadMensajesUsuario"] = (int) $api->sanitize_integer($input["cuotaMensajes"]["cantidadMensajesUsuario"]);

  $json = $api->insertCuotaMensajesUsuario($idUsuario, $nombreUsuario, $cuotaMensajes);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
