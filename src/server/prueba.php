<?php

require "auth.php";

echo json_encode(Auth::Check("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE1ODQ2NjQ1NDgsImF1ZCI6IjMxY2NlZmUyODRlY2M5Mjk5NzNjODg5YjQ3MzA4M2YzN2E2NWNjN2QiLCJkYXRhIjpbeyJfaWQiOnsiJG9pZCI6IjVlNzI2YjllM2I5ZmY0MDY4MDFiMzRiYyJ9LCJub21icmVzIjoiTm9ybWFzICIsImFwZWxsaWRvcyI6IkVIIiwiY2FyZ28iOiJBc2lzdGVudGUiLCJjb3JyZW8iOiJhZG1pbkBkYXRhbGFiLmNvbSIsInBhc3N3b3JkIjoiMTIzNDU2IiwicGVybWlzb3MiOnsiZGlwQWRtaW5SZWdsYXMiOnRydWUsImRpcEFjdGl2YXJEZXNhY3RpdmFyIjp0cnVlfX1dfQ.Trmbe5shvttppS7lkEDrSC4RNrL2izVIEt7HmWVTx6g"));
/*
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;

$time = time();
$key = 'my_secret_key';

$token = array(
    'iat' => $time, // Tiempo que inició el token
    'exp' => $time + (60*60), // Tiempo que expirará el token (+1 hora)
    'data' => [ // información del usuario
        'id' => 1,
        'name' => 'Eduardo'
    ]
);

$jwt = JWT::encode($token, $key);
// $data = JWT::decode($jwt, 'SOY OTRO KEY', array('HS256'));
$data = JWT::decode($jwt, $key, array('HS256'));


echo($jwt);
// var_dump($data); */