<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["idUsuario"], $input["nombreUsuario"], $input["fechaRegistro"], $input["cuotaMensajes"])) {
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $nombreUsuario = $api->sanitize_string($input["nombreUsuario"]);
  $fechaRegistro = $api->sanitize_string($input["fechaRegistro"]);
  $cuotaMensajes = $input["cuotaMensajes"];

  $cuotaMensajes["periodoMensajesCliente"] = $api->sanitize_string($cuotaMensajes["periodoMensajesCliente"]);
  $cuotaMensajes["cantidadMensajesCliente"] = (int) $api->sanitize_integer($cuotaMensajes["cantidadMensajesCliente"]);

  $json = $api->insertCuotaMensajesCliente($idUsuario, $nombreUsuario, $cuotaMensajes, $fechaRegistro);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
