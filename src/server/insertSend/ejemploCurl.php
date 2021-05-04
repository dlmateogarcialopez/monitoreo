<?php

//  require "../lib.php";

// Creamos un array con los valores que se pasaran por post
// require '../lib.php';

// $post['text'] = 'Mensaje de prueba';
$post['message'] = 'Mensaje de prueba';
$post['to'] = array('573122327042');
$post['from'] = 'FROM UM';
$post['campaignName'] = 'Envio DOD General';
// $post['dlr-url'] = 'http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php?idenvio=umanizales12345&tel=%p&idConsolidado=1234567&estado=%d&fecha=%t&cantidadMensajes=1';
$post['notificationUrl'] = 'http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php?idenvio=umanizales12345&tel=%p&idConsolidado=1234567&estado=%d&fecha=%t&cantidadMensajes=1';

$user = 'umanizales360';
$password = 'Umanizl8';
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://dashboard.360nrs.com/api/rest/sms');
    // curl_setopt($ch, CURLOPT_URL, 'https://gateway.plusmms.net/rest/message');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Basic '.base64_encode($user.':'.$password),
));
    $result = curl_exec($ch);
    curl_close($ch);

    var_dump($result);
} catch (\Throwable $th) {
    var_dump($th);

    // $apiMail = new sendEmailAPI();
// $responseEmail = $apiMail->MailSendErrorDifusion($th->getTraceAsString() + "Mensaje no: $i Cantidad a difundir: $cantidadMensajes APERTURA: $apertura", 'SISTEMA DIFUSIÓN ERROR');
}
