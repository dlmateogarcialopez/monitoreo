<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["tipo"], $input["mensajeError"])) {
  $tipo = $input["tipo"];
  $mensajeError = $input["mensajeError"];
  $json = file_put_contents("frontBackErrors.log", "[" . date("l Y-m-d H:i:s") . "] [$tipo $mensajeError]\n", FILE_APPEND);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);