<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["id"], $input["passwordActual"], $input["passwordNuevo"])) {
  $id = $api->sanitize_string($input["id"]);
  $json = $api->getPasswordActual($id, $input["passwordActual"], $input["passwordNuevo"]);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);