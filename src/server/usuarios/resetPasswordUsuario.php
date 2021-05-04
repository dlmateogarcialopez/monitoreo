<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["id"], $input["password"])) {
  $id = $api->sanitize_string($input["id"]);
  $password = $input["password"];
  $json = $api->resetPasswordUsuario($id, $password);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);