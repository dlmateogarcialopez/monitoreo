<?php
require "lib.php";
$api = new sgcbApi();

$regex = new MongoDB\BSON\Regex('^foo', 'i');
var_dump($regex);

// echo (json_encode($_SERVER));
// isHttps($api);
// function isHttps($api)
// {
//   echo  "url $api->nombreServidorChec";
//   return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === '443';
// }