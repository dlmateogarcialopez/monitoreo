<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require "vendor/autoload.php";

class emailApi
{
  public function __construct()
  {
    // $this->domain = "https://intrachecdes.chec.com.co/sgcb";
    // $this->domain = $this->setDomainUrl("intrachecdes.chec.com.co");
  }

  /** Define el dominio que se utilizará para los enlaces de la aplicación que se enviarán al correo electrónico */
  private function setDomainUrl($nombreServidorChec)
  {
    if ($_SERVER['SERVER_NAME'] === $nombreServidorChec) {
      return "https://$nombreServidorChec/sgcb";
    }
    return "http://localhost:4200";
  }

  public $domain = "https://intrachecdes.chec.com.co/sgcb";
  public $host = "mail.epm.com.co"; // Servidor SMTP por el cual enviar
  public $smtpAuth = true; // Habilita autenticación SMTP
  public $smtpSecure = PHPMailer::ENCRYPTION_STARTTLS; // Habilita encriptación TLS; `PHPMailer::ENCRYPTION_SMTPS` recomendada
  public $username = "CHEC\\infocomercial"; // Nombre de usuario SMTP
  public $password = '1yX8EwuICjVZ'; // Contraseña SMTP
  public $port = 25; // Puerto TCP al cual conectarse; usar 465 para `PHPMailer::ENCRYPTION_SMTPS`
  public $charSet = "UTF-8";
  public $address = "infocomercial@chec.com.co"; // Dirección del correo
  public $addressName = "Sistema de Gestión de la Comunicación Bidireccional";
  public $isHtml = true;

  public function enviarCorreo($correoDestinatario, $asunto, $cuerpo)
  {
    $mail = new PHPMailer(true);
    try {
      $mail->SMTPAuth = $this->smtpAuth;
      $mail->SMTPSecure = $this->smtpSecure;
      $mail->isSMTP();
      // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
      $mail->Host = $this->host;
      $mail->Username = $this->username;
      $mail->Password = $this->password;
      $mail->Port = $this->port;
      $mail->CharSet = $this->charSet;
      $mail->setFrom($this->address, $this->addressName);
      $mail->addAddress($correoDestinatario); /* Destinatario */
      $mail->isHTML($this->isHtml);

      /* Contenido */
      $mail->Subject = $asunto;
      $mail->Body = $cuerpo;

      $mail->send();
      return "ok";
    } catch (Exception $e) {
      // echo "Mailer Error: {$mail->ErrorInfo}";
      return "El mensaje no pudo ser enviado. Por favor, inténtalo de nuevo.";
    }
  }
}
