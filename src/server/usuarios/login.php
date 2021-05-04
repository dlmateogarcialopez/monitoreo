<?php
// setcookie("prueba", "probando", 15*60);
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["correo"], $input["password"])) {
  $correo = $api->sanitize_string($input["correo"]);
  $password = $input["password"];
  $json = $api->getLogin($correo, $password);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);