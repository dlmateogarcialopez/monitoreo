<?php
require "../lib.php";

$api = new sgcbApi();
$input = $api->detectRequestBody();

if (isset($input["jwtToken"])) {
  $jwtToken = $api->sanitize_string($input["jwtToken"]);
  $json = $api->isJwtTokenValid($jwtToken);
} else {
  $json = "Error, entrada de datos incorrecta";
}

echo json_encode($json);
