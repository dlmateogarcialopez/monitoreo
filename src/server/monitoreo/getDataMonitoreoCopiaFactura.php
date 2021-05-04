<?php
require('../lib.php');
$api =  new sgcbApi();
$input = $api->detectRequestBody();
$json = array();

if (isset($input['municipios'])) {  
  
  //municipios
  $json['falta_energia']['municipios'] = $api->getMunicipios();

} else if (isset($input['startDate']) && isset($input['endDate']) && isset($input['municipio']) && isset($input['ubicacion'])) {

  $fechai = explode("T", $input['startDate']);
  $fechainicio = $fechai[0];

  $fechaf =  explode("T", $input['endDate']);
  $fechafin = $fechaf[0];

  $municipio = strtolower($input['municipio']);
  $ubicacion =  strtolower($input['ubicacion']);

  //consultas copia factura chatWeb y Telegram por fecha-municipio-ubicacion
  $json['copia_factura']['consultasCopiaFacturaSources'] = $api->getConsultasCopiaFacturaWebTelegram($fechainicio, $fechafin, $municipio, $ubicacion);

  //consultas por municipio, filtrado por fecha y ubicacion - mapa (ubicacion, segmentos, municipio)
  $json['copia_factura']['segmentos'] =  $api->consultasMunicipioCopiaFactura($fechainicio, $fechafin, $municipio, $ubicacion, $json['copia_factura']['consultasCopiaFacturaSources']['consulta']);
  
}  else {
  $json['error'] = false;
  $json['message'] = 'Parametros incorrectos';
}

echo json_encode($json);
