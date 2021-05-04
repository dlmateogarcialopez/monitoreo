<?php

class sendPhoneAPI
{
  public function __construct()
  {
  }

  /** Realiza envíos de los mensajes individuales o precargados desde DOD */
  public function sendMessageIndividual($datosApiPhoneIndividual)
  {
    $celular = $datosApiPhoneIndividual["celular"];
    $mensaje = $datosApiPhoneIndividual["mensaje"];
    $idConsolidado = $datosApiPhoneIndividual["idConsolidado"];
    $cantidadMensajes = $datosApiPhoneIndividual["cantidadMensajes"];
    $nombreBolsa = $datosApiPhoneIndividual["nombreBolsa"];
    $valorMensajeIndividual = $datosApiPhoneIndividual["valorMensajeIndividual"];
    $celularCompleto = "57" . $celular;

    if (strtoupper($nombreBolsa) === "UM") {
      // var_dump($idUsuario, $celular, $mensaje);
      $post['message'] = $mensaje;
      $post['to'] = array($celularCompleto);
      $post['from'] = 'FROM UM';
      $post['campaignName'] = 'Envío DOD Individual/Precargado';
      /** Quita tildes en los caracteres: á => a; ú => u  */
      $post['trans'] = 1;
      /** Divide el mensaje en dos partes, en caso de que este supere los 160 caracteres */
      $post['parts'] = 2;
      // $post['dlr-url'] = "http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php?idenvio=umanizales12345&tel=%p&idConsolidado=$idConsolidado&estado=%d&fecha=%t&cantidadMensajes=$cantidadMensajes";
      $post['notificationUrl'] = "http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php?idenvio=umanizales12345&tel=%p&idConsolidado=$idConsolidado&estado=%d&fecha=%t&cantidadMensajes=$cantidadMensajes&nombreBolsa=$nombreBolsa&valorMensajeIndividual=$valorMensajeIndividual";
      //http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php?idenvio=umanizales12345&idUsuario=tertw4e535&cuenta=16525424&tel=3122327042&nombre=edwin&mensaje=holaedwin&tipoMensaje=individual&idConsolidado=wehbryy4&cantidadCaracteres=134&cantidadMensajes=1&estado=%d&fecha=%t

      $user = 'umanizales360';
      $password = 'Umanizl8';
      try {
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, 'https://gateway.plusmms.net/rest/message');
        curl_setopt($ch, CURLOPT_URL, 'https://dashboard.360nrs.com/api/rest/sms');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Accept: application/json',
          'Authorization: Basic ' . base64_encode($user . ':' . $password),
        ));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
      } catch (\Throwable $th) {
        return 'Error catch';
        // $apiMail = new sendEmailAPI();
        // $responseEmail = $apiMail->MailSendErrorDifusion($th->getTraceAsString() + "Mensaje no: $i Cantidad a difundir: $cantidadMensajes APERTURA: $apertura", 'SISTEMA DIFUSIÓN ERROR');
      }
    } else if (strtoupper($nombreBolsa) === "CHEC") {
      $smsIntcobranzaIndividual = [
        ["correlative" => 1, "phone" => $celular, "message" => $mensaje]
      ];
      $this->sendSmsIntcobranza($smsIntcobranzaIndividual);
    }
  }

  /** Realiza envíos de los mensajes generales desde DOD */
  public function sendMessageGeneral($datosApiPhoneGeneral)
  {
    $celulares = $datosApiPhoneGeneral["celulares"];
    $mensaje = $datosApiPhoneGeneral["mensaje"];
    $idConsolidado = $datosApiPhoneGeneral["idConsolidado"];
    $cantidadMensajes = $datosApiPhoneGeneral["cantidadMensajes"];
    $nombreBolsa = $datosApiPhoneGeneral["nombreBolsa"];
    $valorMensajeIndividual = $datosApiPhoneGeneral["valorMensajeIndividual"];

    if (strtoupper($nombreBolsa) === "UM") {
      $post['message'] = $mensaje;
      $post['to'] = $celulares;
      $post['from'] = 'FROM UM';
      $post['campaignName'] = 'Envío DOD General';
      /** Quita tildes en los caracteres: á => a; ú => u */
      $post['trans'] = 1;
      /** Divide el mensaje en dos máximo partes, en caso de que este supere los 160 caracteres */
      $post['parts'] = 2;
      // $post['dlr-url'] = "http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php?idenvio=umanizales12345&tel=%p&idConsolidado=$idConsolidado&estado=%d&fecha=%t&cantidadMensajes=$cantidadMensajes";
      $post['notificationUrl'] = "http://52.147.221.17/reglasDifusion/acuses_recibo_dod.php?idenvio=umanizales12345&tel=%p&idConsolidado=$idConsolidado&estado=%d&fecha=%t&cantidadMensajes=$cantidadMensajes&nombreBolsa=$nombreBolsa&valorMensajeIndividual=$valorMensajeIndividual";

      $user = 'umanizales360';
      $password = 'Umanizl8';
      try {
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, 'https://gateway.plusmms.net/rest/message');
        curl_setopt($ch, CURLOPT_URL, 'https://dashboard.360nrs.com/api/rest/sms');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Accept: application/json',
          'Authorization: Basic ' . base64_encode($user . ':' . $password),
        ));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
      } catch (\Throwable $th) {
        return 'Error catch';
        // $apiMail = new sendEmailAPI();
        // $responseEmail = $apiMail->MailSendErrorDifusion($th->getTraceAsString() + "Mensaje no: $i Cantidad a difundir: $cantidadMensajes APERTURA: $apertura", 'SISTEMA DIFUSIÓN ERROR');
      }
    } else if (strtoupper($nombreBolsa) === "CHEC") {
      $smsIntcobranzaGeneral = [];

      foreach ($celulares as $celularIndex => $celularValue) {
        array_push($smsIntcobranzaGeneral, [
          "correlative" => $celularIndex + 1,
          "phone" => $celularValue,
          "message" => $mensaje
        ]);
      }
      $this->sendSmsIntcobranza($smsIntcobranzaGeneral);
    }
  }

  /** Define la estructura y realiza el envío de mensajes desde INTCOBRANZA
   * @param array $arrayMensajes Array que contiene un array asociativo que se construye para el parámetro `messages`. Su estructura es:
   *
   * [
   *
   *  `correlative` => Inicia en 1. Para envío masivo, implementar ciclo que inicia en 1, hasta la cantidad de mensajes a enviar,
   *
   *  `phone` => Número de celular. Debe iniciar en 3,
   *
   * `message` => Mensaje a enviar
   *
   * ]
   */
  private function sendSmsIntcobranza($arrayMensajes)
  {
    $datosJson = [
      "api_key" => "J274bMdKCjx/J3JkHdglXGOjs3NpnMz5k3EfjuQCyZw=",
      "data" => [
        "messages" => $arrayMensajes,
        "smslargo" => 1,
        "hour" => date("H:i:s"),
        "delivery_date" => date("d/m/Y"),
        "delivery_type" => 0,
        "user" => ["user_id" => 14817],
      ]
    ];
    // echo json_encode($datosJson);
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://sms.intico.com.co:3312/api/sms_bulk",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($datosJson),
      CURLOPT_HTTPHEADER => [
        "api_key: y5tFdsSDF3$78dftttpokk88374fWwerWr",
        "session_key: FGW3xrX0YYvnCOPZM1n2liC",
        "Content-Type: application/json",
        "Accept: application/json"
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    // echo $response;
    return json_decode($response, true)["FileRegister"];
  }
}
