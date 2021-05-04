<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["fechaInicioMensajesCliente"], $input["fechaFinMensajesCliente"], $input["cantidadMensajesCliente"], $input["cantidadMensajesClienteAnterior"])) {
  $fechaInicioMensajesCliente = $api->sanitize_string($input["fechaInicioMensajesCliente"]);
  $fechaFinMensajesCliente = $api->sanitize_string($input["fechaFinMensajesCliente"]);
  $cantidadMensajesCliente = (int) $api->sanitize_integer($input["cantidadMensajesCliente"]);
  $cantidadMensajesClienteAnterior = (int) $api->sanitize_integer($input["cantidadMensajesClienteAnterior"]);

  $json = $api->updateCuotaMensajesClienteDod($fechaInicioMensajesCliente, $fechaFinMensajesCliente, $cantidadMensajesCliente, $cantidadMensajesClienteAnterior);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
