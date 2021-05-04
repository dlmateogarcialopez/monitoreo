<?php
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;

class Auth
{
  private static $secret_key = 'TTF75vfo9O!$eNVsE8nh&UsFAtY5yxSdw1s!3e3_J1aNlvnK3P53CmmxCQz68A1586456308#ew5h29x8@';
  private static $encrypt = ['HS256'];
  private static $aud = null;

  /** Codifica el token JWT.
   * @var data Datos a codificar
   * @var expiry Fecha en la que expirará el token. Por defecto, si no se asigna una fecha, el token expirará en 10 minutos */
  public static function encodeJwt($data, $expiry = "+10 minutes")
  {
    $token = array(
      'exp' => strtotime($expiry),
      'aud' => self::Aud(),
      'data' => $data
    );

    return JWT::encode($token, self::$secret_key);
  }

  public static function Check($token)
  {
    if (empty($token)) {
      throw new Exception("Invalid token supplied.");
    }

    $decode = JWT::decode(
      $token,
      self::$secret_key,
      self::$encrypt
    );

    if ($decode->aud !== self::Aud()) {
      throw new Exception("Invalid user logged in.");
    }
    return $decode;
  }

  public static function decodeJwt($token)
  {
    return JWT::decode(
      $token,
      self::$secret_key,
      self::$encrypt
    )->data;
  }

  private static function Aud()
  {
    $aud = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $aud = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $aud = $_SERVER['REMOTE_ADDR'];
    }

    $aud .= @$_SERVER['HTTP_USER_AGENT'];
    $aud .= gethostname();

    return sha1($aud);
  }
}
