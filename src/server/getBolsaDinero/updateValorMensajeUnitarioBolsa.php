<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["valorMensajeUnidireccional"], $input["valorMensajeBidireccional"], $input["idUsuario"], $input["nombreBolsa"])) {
  $valorMensajeUnidireccional = $api->sanitize_float($input["valorMensajeUnidireccional"]);
  $valorMensajeBidireccional = $api->sanitize_float($input["valorMensajeBidireccional"]);
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $nombreBolsa = $api->sanitize_string($input["nombreBolsa"]);

  $datosValorMensajeUnitario = array(
    "valorMensajeUnidireccional" => (float) $valorMensajeUnidireccional,
    "valorMensajeBidireccional" => (float) $valorMensajeBidireccional,
    "idUsuario" => $idUsuario,
    "nombreBolsa" => $nombreBolsa
  );

  $json = $api->updateValorMensajeUnitarioBolsa($datosValorMensajeUnitario);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
