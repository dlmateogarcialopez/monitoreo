import { MatTableDataSource } from '@angular/material/table';

/** @InterfazAgregarCampos Se utiliza en los modales para agregar campos en el envío de mensajes a través del uso de checkboxes
 *
 * @posicion Define un índice con el cual se puede hacer click en toda la fila y seleccionar el checkbox correspondiente a esa fila
 *
 * @nombreCampo Define el nombre del campo que se va a agregar
  */
export interface InterfazAgregarCampos {
  posicion: number;
  nombreCampo: string;
}

/** Campos de los mensajes a enviar */
export interface Mensaje {
  cuenta: string;
  nombre: string;
  celular: string;
  mensaje: string;
  cantidadCaracteres: number;
  cantidadMensajes: number;
  accion?: string;
  maxCaracteresMensaje?: number;
}

export interface CuotaMensajesUsuario {
  /** Tratamiento del identificador de cuota de usuario proveniente de la BD */
  _id?: {
    $oid: string;
  };
  idUsuario?: string;
  nombreUsuario?: string;
  selectBolsa?: string;
  selectPeriodoMensajesUsuario?: string;
  cantidadMensajesUsuario?: number;
  fechaInicioMensajesUsuario?: string;
  fechaFinMensajesUsuario?: string;
  totalInicialMensajes?: string;
}

export interface Users {
  idUsuario?: string;
  /** Tratamiento del identificador de usuario proveniente de la BD */
  _id?: {
    $oid: string;
  };
  nombres?: string;
  apellidos?: string;
  cargo?: string;
  correo?: string;
  password?: string;
  permisos?: PermisosUsuario;
  isAddUser?: boolean;
  cuotaMensajes?: object;
}

export interface PermisosUsuario {
  administrador: boolean;
  dodEnviarSms: boolean;
  dodPrioridadEnvio: boolean;
  dodVerReportes: boolean;
  dodActivarDesactivar: boolean;
  monitoreoVerReportes: boolean;
  dinpVerReportes: boolean;
  dinpAdminReglas: boolean;
  dinpActivarDesactivar: boolean;
  dipVerReportes: boolean;
  dipAdminReglas: boolean;
  dipActivarDesactivar: boolean;
  selectBolsa: string;
  selectPeriodoMensajesUsuario: string;
  cantidadMensajesUsuario: number;
}

/** Datos del objeto que se construye para enviar los mensajes del módulo DOD */
export interface DatosMensajeDod {
  idUsuario: string;
  nombreUsuario: string;
  motivoEnvio: string;
  nombreBolsa: string;
  mensajes: {
    metodoEnvio: string,
    tipoMensaje: string,
    rawMensaje: string;
    mensajes: {},
    valorMensajeIndividual: number;
  };
}

export interface BolsaMensajes {
  _id?: string;
  nombre?: string;
  valor_actual?: number;
  valor_anterior?: number;
  valor_mensaje_unidireccional?: number;
  valor_mensaje_bidireccional?: number;
  usuario_modifica?: string;
  fecha_modificacion?: string;
}

/** Comparte información de los mensajes a enviar entre los componentes implementados en el paso de confirmación para el envío de SMS en el módulo DOD */
export interface InfoMensajes {
  totalMensajes: number;
  valorMensajes: number;
  totalCelulares: number;
  isSuperiorMaxCaracteres?: boolean;
  countMensajesSuperioresMaxCaracteres?: number;
  dataSourceStepConfirmacion?: MatTableDataSource<any>;
}