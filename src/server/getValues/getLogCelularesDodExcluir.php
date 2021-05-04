<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["datosCelularesDod"])) {
  $datosCelularesDod = $input["datosCelularesDod"];
  $arrayCelulares = [];

  foreach ($datosCelularesDod["celulares"] as $key => $value) {
    array_push($arrayCelulares, $api->sanitize_string($value));
  }
  $datosCelularesDod["celulares"] = $arrayCelulares;
  $json = $api->getLogCelularesDodExcluir($datosCelularesDod);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
