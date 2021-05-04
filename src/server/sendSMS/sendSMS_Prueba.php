<?php

date_default_timezone_set('America/Bogota');

// responseIntcobranza();

$fileRegister = envioDesdeIntcobranza();
echo "<br><br><br>";


responseIntcobranza($fileRegister);


function envioDesdeIntcobranza()
{
  $fecha = date("Y-m-d H:i:s");
  $apiKey = "J274bMdKCjx/J3JkHdglXGOjs3NpnMz5k3EfjuQCyZw=";
  $datosJson = [
    "api_key" => $apiKey,
    "data" => [
      "messages" => [
        // ["correlative" => 1, "phone" => "3148684045", "message" => "hola @nombre hola @nombre tu número"],
        // ["correlative" => 1, "phone" => "3122327042", "message" => "Mensaje largo INTCOBRANZA en la fecha: $fecha. hola @nombre tu número de cuenta @cuenta se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica provisto por CHEC Grupo EPM "],
        // ["correlative" => 2, "phone" => "3128028870", "message" => "Mensaje largo INTCOBRANZA en la fecha: $fecha. hola @nombre tu número de cuenta @cuenta se encuentra en proceso de suspensión debido a que no ha realizado el pago oportuno del servicio de energía eléctrica provisto por CHEC Grupo EPM "],
        ["correlative" => 3, "phone" => "3148684045", "message" => "Mensaje INTCOBRANZA en la fecha: $fecha."],
        // ["correlative" => 1, "phone" => "3148684045", "message" => "Hola mensaje 1"],
      ],
      "smslargo" => 1,
      "hour" => date("H:i:s"),
      "delivery_date" => date("d/m/Y"),
      "delivery_type" => 0,
      "user" => [
        "user_id" => 14817 // 14775
      ],
    ]
  ];

  // echo json_encode($datosJson);

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://sms.intico.com.co:3312/api/sms_bulk",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($datosJson),
    CURLOPT_HTTPHEADER => array(
      "api_key: y5tFdsSDF3$78dftttpokk88374fWwerWr",
      "session_key: FGW3xrX0YYvnCOPZM1n2liC",
      "Content-Type: application/json",
      "Accept: application/json"
    ),
  ));

  $response = curl_exec($curl);
  curl_close($curl);
  echo $response;

  return json_decode($response, true)["FileRegister"];
}

function responseIntcobranza($fileRegister)
{
  sleep(5);
  // echo ($fileRegister);
  $datosResponseJson = [
    "api_key" => "J274bMdKCjx/J3JkHdglXGOjs3NpnMz5k3EfjuQCyZw=",
    "data" => [
      "file_register" => "$fileRegister",
      "PageNumber" => 1,
      "PageSize" => 1
    ]
  ];

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://sms.intico.com.co:3312/api/sent_sms_detail",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($datosResponseJson),
    CURLOPT_HTTPHEADER => array(
      "api_key: y5tFdsSDF3$78dftttpokk88374fWwerWr",
      "session_key: FGW3xrX0YYvnCOPZM1n2liC",
      "Content-Type: application/json",
      "Accept: application/json"
    ),
  ));

  $response = curl_exec($curl);
  curl_close($curl);
  echo $response;
}


function envioDesdeInfobip()
{
  $arr = array(
    "messages" => array(
      array(
        "from" => "InfoSMS",
        "destinations" => array(
          array(
            "to" => "573148684045",
          ),
          // array(
          // "to" => "573122327042",
          // ),
        ),
        "text" => "Otra Prueba Infobip",
        "notify" => true,
        "notifyUrl" => "https://datalabum.000webhostapp.com/pru.php",
        // "notifyUrl" => "http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php",
        "notifyContentType" => "application/json",
      )
    )
  );

  $user = "chec-u";
  $password = "chec2020";
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://4yrkn.api.infobip.com/sms/2/text/advanced",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($arr),
    CURLOPT_HTTPHEADER => array(
      "Authorization: Basic " . base64_encode($user . ":" . $password),
      "Content-Type: application/json",
      "Accept: application/json"
    ),
  ));

  $response = curl_exec($curl);
  curl_close($curl);
  echo $response;
}


/** Muestra todas las opciones de configuración para el envío de SMS con Infobip */
$arrayInfobip = array(
  "messages" => array(
    array(
      "from" => "InfoSMS",
      "destinations" => array(
        array(
          "to" => "41793026727",
          "messageId" => "MESSAGE-ID-123-xyz"
        ),
        array(
          "to" => "41793026834"
        )
      ),
      "text" => "Artık Ulusal Dil Tanımlayıcısı ile Türkçe karakterli smslerinizi rahatlıkla iletebilirsiniz.",
      "flash" => false,
      "language" => array(
        "languageCode" => "TR"
      ),
      "transliteration" => "TURKISH",
      "intermediateReport" => true,
      "notifyUrl" => "https://www.example.com/sms/advanced",
      "notifyContentType" => "application/json",
      "callbackData" => "DLR callback data",
      "validityPeriod" => 720
    ),
    array(
      "from" => "41793026700",
      "destinations" => array(
        array(
          "to" => "41793026700"
        )
      ),
      "text" => "A long time ago, in a galaxy far, far away... It is a period of civil war. Rebel spaceships, striking from a hidden base, have won their first victory against the evil Galactic Empire.",
      "sendAt" => "2021-08-25T16:00:00.000+0000",
      "deliveryTimeWindow" => array(
        "from" => array(
          "hour" => 6,
          "minute" => 0
        ),
        "to" => array(
          "hour" => 15,
          "minute" => 30
        ),
        "days" => array(
          "MONDAY",
          "TUESDAY",
          "WEDNESDAY",
          "THURSDAY",
          "FRIDAY",
          "SATURDAY",
          "SUNDAY"
        )
      )
    )
  ),
  "bulkId" => "BULK-ID-123-xyz",
  "tracking" => array(
    "track" => "SMS",
    "type" => "MY_CAMPAIGN"
  )
);
