import { environment } from '../../environments/environment';

export const URL: string = environment.serverUrl;
export const WEBSERVICE = {
  /* USUARIOS */
  LOGIN: "usuarios/login.php",
  LOGOUT: "usuarios/logout.php",
  GET_USUARIOS: "usuarios/getUsuarios.php",
  GET_USUARIO_INDIVIDUAL: "usuarios/getUsuarioIndividual.php",
  GET_DATOS_USUARIO: "usuarios/getDatosUsuario.php",
  GET_CORREO_USUARIO: "usuarios/getCorreoUsuario.php",
  BUSCAR_CORREO_EXISTENTE_USUARIO: "usuarios/buscarCorreoExistenteUsuario.php",
  RESET_PASSWORD_USUARIO: "usuarios/resetPasswordUsuario.php",
  INSERT_USUARIO: "usuarios/insertUsuario.php",
  UPDATE_PERFIL_USUARIO: "usuarios/updatePerfilUsuario.php",
  GET_CUOTA_MENSAJES_USUARIO: "usuarios/getCuotaMensajesUsuario.php",
  INSERT_CUOTA_MENSAJES_USUARIO: "usuarios/insertCuotaMensajesUsuario.php",
  UPDATE_CUOTA_MENSAJES_USUARIO_ENVIO_SMS: "usuarios/updateCuotaMensajesUsuarioEnvioSms.php",
  GET_CUOTA_MENSAJES_CLIENTE: "usuarios/getCuotaMensajesCliente.php",
  INSERT_CUOTA_MENSAJES_CLIENTE: "usuarios/insertCuotaMensajesCliente.php",
  UPDATE_CUOTA_MENSAJES_CLIENTE_DOD: "usuarios/updateCuotaMensajesClienteDod.php",
  INACTIVAR_USUARIO: "usuarios/inactivarUsuario.php",
  GET_PASSWORD_ACTUAL: "usuarios/getPasswordActual.php",
  ENVIAR_CORREO_RECUPERACION_CUENTA: "usuarios/enviarCorreoRecuperacionCuenta.php",
  ENVIAR_CORREO_PASSWORD_RESTAURADO: "usuarios/enviarCorreoPasswordRestaurado.php",

  /* TOKEN */
  SET_JWT_TOKEN: "tokens/setJwtToken.php",
  VERIFY_JWT_TOKEN: "tokens/verifyJwtToken.php",

  /* DATOS BD heroku_qqkvqh3x */
  GET_DATOS_BD_CHEC: "getValues/getValuesInividuales.php",
  GET_TOTAL_USUARIOS_FILTRO_BD_CHEC: "getValues/getTotalValuesInividuales.php",
  GET_DATOS_USUARIOS_POR_FILTRO: "getUsersSend/getUsersSendSMS.php",
  GET_DATOS_USUARIOS_POR_CUENTA: "getValues/getUsersNIU.php",

  /* Consultar, enviar y guardar mensajes */
  SEND_MENSAJES_DOD: "insertSend/insertSendMessages.php",
  GET_LISTADO_ENVIOS_DOD: "insertSend/getListSends.php",
  GET_DETALLE_ENVIO_DOD: "insertSend/getDetalleEnvio.php",
  GET_LOG_CELULARES_DOD_EXCLUIR: "getValues/getLogCelularesDodExcluir.php",

  /* Bolsas de dinero para mensajes */
  GET_DATOS_BOLSAS_MENSAJES: "getBolsaDinero/getDatosBolsas.php",
  GET_BOLSA_DINERO_MENSAJES: "getBolsaDinero/getBolsaDineroActual.php",
  GET_BOLSA_MENSAJES_USUARIO: "getBolsaDinero/getBolsaMensajesUsuario.php",
  UPDATE_VALOR_MENSAJE_UNITARIO_BOLSA: "getBolsaDinero/updateValorMensajeUnitarioBolsa.php",
  ADICIONAR_SALDO_BOLSA: "getBolsaDinero/adicionarSaldoBolsa.php",

  /* Obtener fecha actual */
  GET_FECHA_ACTUAL: "getValues/getFechaActual.php",
  /* Log de errores */
  INSERT_ERROR_LOG: "logs/insertLogError.php",

  /*Monitoreo*/
  GET_DATA_MONITOREO_LUCY: "monitoreo/getDataMonitoreoLucy.php",
  GET_DATA_MONITOREO_LUCY_FALTA_ENERGIA: "monitoreo/getDataMonitoreoFaltaEnergia.php",
  GET_DATA_MONITOREO_LUCY_COPIA_FACTURA: "monitoreo/getDataMonitoreoCopiaFactura.php",
  GET_DATA_MONITOREO_DINP: "monitoreo/getDataMonitoreoDINP.php",
  GET_DATA_MONITOREO_HISTORIAL: "monitoreo/getDataMonitoreoHistorial.php",
  GET_DATA_MONITOREO_GENERAL: "monitoreo/getDataMonitoreoGeneral.php",
  GET_DATA_MONITOREO_REPORTES: "monitoreo/getDataMonitoreoReportes.php",
  GET_DATA_MONITOREO_CONTACT: "monitoreo/getDataMonitoreoContact.php"
  
};
