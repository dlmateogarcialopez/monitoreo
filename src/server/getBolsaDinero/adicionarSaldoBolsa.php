<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["valorSaldoAdicionar"], $input["idUsuario"], $input["nombreBolsa"])) {
  $valorSaldoAdicionar = $api->sanitize_float($input["valorSaldoAdicionar"]);
  $idUsuario = $api->sanitize_string($input["idUsuario"]);
  $nombreBolsa = $api->sanitize_string($input["nombreBolsa"]);

  $datosValorSaldoAdicionar = array(
    "valorSaldoAdicionar" => (float) $valorSaldoAdicionar,
    "idUsuario" => $idUsuario,
    "nombreBolsa" => $nombreBolsa,
  );

  $json = $api->adicionarSaldoBolsa($datosValorSaldoAdicionar);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
