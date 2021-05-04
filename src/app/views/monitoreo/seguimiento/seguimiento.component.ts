import { Component, OnInit, ViewChild } from '@angular/core';
import * as moment from 'moment';
import { ServiceProvider } from '../../../config/services';
import { WEBSERVICE } from '../../../config/webservices';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { FormControl } from '@angular/forms';
import { MESSAGES } from '../../../config/messages';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { MatSort } from '@angular/material/sort';
import { MatPaginator } from '@angular/material/paginator';
import { MatTableDataSource } from '@angular/material/table';

declare const getPDF: any;
declare const SegimientoGrafica: any;

export interface DatosTablaClientes {
  NOMBRE: string;
  NIU: number;
  DIRECCION: string;
  CELULAR: number;
}

@Component({
  selector: 'app-seguimiento',
  templateUrl: './seguimiento.component.html'
})
export class SeguimientoComponent implements OnInit {
  //...


  public startDate;
  public endDate;
  public formCriterioSeleccionado: FormGroup;
  public dateForm = new FormGroup({
    date: new FormControl()
  });
  public date = '';
  public selected = {
    startDate: moment().subtract(29, 'days'),
    endDate: moment(),
  };
  public criterio = 'cuenta';
  public criterioValue = '';
  public municipios = [];
  public ubicacion = '';
  public flag = true;
  public datosClientes: DatosTablaClientes[] = [];
  public picker = '';
  public niuUsuarioSeleccionado = '';
  public usuarioSelecconado = '';
  public flagUsuarioSeleccionado = false;
  public datosTabla: any = [];
  public conversaciones: any = [];
  public tablaConversaciones = false;
  //public sendBy:any = '';
  public conversacionDetalle = [];
  public type = 'number';
  public habilitarCalendario = true;
  public messages: any = [];
  public today = new Date();
  public imagenDescarga: boolean = false;
  public cargaYdescarga = "./assets/img/web.png";
  public mostrarchat = false;
  public chat = false;
  public dataAtribute = { toggle: "", target: "" };
  //public type = '';
  public Seleccionado = 'cuenta';
  public active = '';
  public active1 = '';
  public color = '';
  public datosTablaLucy: any = [];
  public porcentajesConsultas: any = { "pchats": 0, "pdinp": 0, "pchapinvitacion_suspts": 0, "pllamadas": 0, "ppormocion_lucy": 0 };
  public datosTablaPromocionLucy: any = [];
  public datosTablaDinp: any = [];
  public datosTablaInvitacionSusp: any = [];
  public tarjetaLlamadas: boolean = false;
  public tarjetaLucy: boolean = false;
  public tarjetaDinp: boolean = false;
  public tarjetaInvitacionsusp: boolean = false;
  public tarjetaPromocion: boolean = false;
  /** Muestra en el label del input el nombre del criterio seleccionado por el usuario  */
  public objLabelInputCriterio: { [key: string]: string; } = {
    cuenta: "Cuenta",
    documento: "Número de documento",
    nombre: "Nombre",
    direccion: "Dirección",
    telefono: "Número telefónico"
  };

  constructor(public ServiceProvider: ServiceProvider, private fb: FormBuilder, private modal: NgbModal) { }

  ngOnInit(): void {
    this.ServiceProvider.setTituloPestana("Seguimiento");
    this.createForm();
    this.datosTabla = [
      { fuente: "Lucy", nro_consultas: "0", porce_consultas: "0", fecha: '' },
      { fuente: "DINP", nro_consultas: "0", porce_consultas: "0", fecha: '' },
      { fuente: "Promoción Lucy", nro_consultas: "0", porce_consultas: "0", fecha: '' },
      { fuente: "Aviso Sus", nro_consultas: "0", porce_consultas: "0", fecha: '' },
      { fuente: "Llamadas", nro_consultas: "0", porce_consultas: "0", fecha: '' }
    ];
  }

  activarNavConversacion() {
    this.active1 = '';
    this.active = 'active';
  }

  createForm() {
    this.formCriterioSeleccionado = this.fb.group({
      selectCriterio: ['cuenta', Validators.required],
      inputCriterio: ['', Validators.required]
    });
  }

  //se ejecuta al momento de seleccionar un usuario para ver su historial
  selectedUser(niu, nombre) {
    this.getDataMonitoreoLucyPorcentajesDeConsultas(niu, '1900-01-02', '2040-01-02', true);
    this.getDataMonitoreoLucyHistorialLlamadas(niu, '1900-01-02', '2040-01-02', true);
    this.getDataMonitoreoLucyHistorialPromocionLucy(niu, '1900-01-02', '2040-01-02', true);
    this.getDataMonitoreoLucyHistorialDinp(niu, '1900-01-02', '2040-01-02', true);
    this.getDataMonitoreoLucyHistorialInvitacionSus(niu, '1900-01-02', '2040-01-02', true);
    this.getDataMonitoreoLucyHistorialLucy(niu, '1900-01-02', '2040-01-02', true);
    this.niuUsuarioSeleccionado = niu;
    //this.flagUsuarioEncontrado = true;
    this.usuarioSelecconado = nombre;
    this.habilitarCalendario = false;
    this.tablaConversaciones = true;
    this.dataAtribute = { toggle: "modal", target: "#exampleModal" };
  }



  columnasTablaClientes: string[] = ["NOMBRE", "NIU", "DIRECCION", "CELULAR"];
  dataSource: MatTableDataSource<DatosTablaClientes>;
  @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
  @ViewChild(MatSort, { static: true }) sort: MatSort;



  //traer datos de las consultas de monitoreo lucy para consultas individuales
  async getDataMonitoreoLucyHistorialIndividual(criterio, valor) {
    this.ServiceProvider.preloaderOn();
    try {
      var parameters = {
        criterio: criterio,
        valor: valor,
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_HISTORIAL, parameters);
      if (invocar.segumiento_individual.usuarios.length) {
        this.datosClientes = [...invocar.segumiento_individual.usuarios];
      } else {
        this.datosClientes = [];
        this.ServiceProvider.openToast('error', 'Cliente no encontrado');
      }

      this.dataSource = new MatTableDataSource(this.datosClientes);
      this.dataSource.sort = this.sort;
      this.dataSource.paginator = this.paginator;
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  //cambiar el tipo de dato que va a recibir el campo de valor de criterio, segun el riterio seleccionado
  criterioSelected() {
    if (["cuenta", "telefono", "documento"].includes(this.formCriterioSeleccionado.value.selectCriterio)) {
      this.type = 'number';
    } else {
      this.type = 'text';
    }
    this.formCriterioSeleccionado.controls.inputCriterio.setValue("");
  }

  //metodo que se ejecuta cuando seleccionan criterio y valor
  changeIndividual() {
    this.flagUsuarioSeleccionado = false;
    let criterio = this.formCriterioSeleccionado.value.selectCriterio;
    let criterioValue = this.formCriterioSeleccionado.value.inputCriterio;
    this.getDataMonitoreoLucyHistorialIndividual(criterio, criterioValue);
    // this.formCriterioSeleccionado = this.fb.group({
    //   selectCriterio: ['cuenta', Validators.required],
    //   inputCriterio: ['', Validators.required]
    // });

    /*this.formCriterioSeleccionado = this.fb.group({
      selectCriterio: ['cuenta', Validators.required],
      inputCriterio: ['', Validators.required]
    });
    this.datosTala = [{ fuente: "Lucy", nro_consultas: "0", porce_consultas: "0", fecha: '' }, { fuente: "DINP", nro_consultas: "0", porce_consultas: "0", fecha: '' }, { fuente: "Promoción Lucy", nro_consultas: "0", porce_consultas: "0", fecha: '' }, { fuente: "Aviso Sus", nro_consultas: "0", porce_consultas: "0", fecha: '' }, { fuente: "Llamadas", nro_consultas: "0", porce_consultas: "0", fecha: '' }];
    this.flagUsuarioEncontrado = false;*/
  }

  async getDataMonitoreoLucyPorcentajesDeConsultas(niu, startDate, endDate, isUserSelected?: boolean) {
    //this.ServiceProvider.preloaderOn();
    if (isUserSelected) {
      this.flagUsuarioSeleccionado = false;
    }
    try {
      var parameters = {
        niu: niu,
        startDate6: startDate,
        endDate6: endDate,
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_HISTORIAL, parameters);
      this.porcentajesConsultas = invocar.segumiento_individual.info_preliminar;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }

  //traer el historial del usuario seleccionado
  async getDataMonitoreoLucyHistorialLlamadas(niu, startDate, endDate, isUserSelected?: boolean) {
    //this.ServiceProvider.preloaderOn();
    if (isUserSelected) {
      this.flagUsuarioSeleccionado = false;
    }
    try {
      var parameters = {
        niu: niu,
        startDate: startDate,
        endDate: endDate,
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_HISTORIAL, parameters);
      this.datosTabla = this.getObjects(invocar.segumiento_individual.info_preliminar);
      if (isUserSelected) {
        this.flagUsuarioSeleccionado = true;
      }
      //console.log(this.datosTabla[0].nro_consultas);
      //this.datosTala = this.getObjects(invocar.segumiento_individual.info_preliminar);
      /*this.datosTala = invocar.segumiento_individual.info_preliminar;
      this.flagUsuarioEncontrado = true;*/

      /*if (this.tablaConversaciones) {
        this.conversaciones = this.getDates(invocar.segumiento_individual.info_preliminar.conversaciones);
      }*/
      this.tarjetaLlamadas = true;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }

  async getDataMonitoreoLucyHistorialLucy(niu, startDate, endDate, isUserSelected?: boolean) {
    this.ServiceProvider.preloaderOn();
    if (isUserSelected) {
      this.flagUsuarioSeleccionado = false;
    }
    try {
      var parameters = {
        niu: niu,
        startDate5: startDate,
        endDate5: endDate,
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_HISTORIAL, parameters);
      this.datosTablaLucy = this.getObjects(invocar.segumiento_individual.info_preliminar);
      //console.log(this.datosTablaLucy);

      // if (!Object.keys(this.datosTablaLucy[0]).length) {
      //   this.datosTablaLucy[0].fuente.idFuente = "idLucy"
      //   this.datosTablaLucy[0].fuente.fuente = "Lucy"
      //   this.datosTablaLucy[0].nro_consultas = 0
      // }

      if (isUserSelected) {
        this.flagUsuarioSeleccionado = true;
      }
      if (this.tablaConversaciones) {
        this.conversaciones = this.getDates(invocar.segumiento_individual.info_preliminar.conversaciones);
      }
      this.tarjetaLucy = true;
    } catch (error) {
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  async getDataMonitoreoLucyHistorialPromocionLucy(niu, startDate, endDate, isUserSelected?: boolean) {
    //this.ServiceProvider.preloaderOn();
    if (isUserSelected) {
      this.flagUsuarioSeleccionado = false;
    }
    try {
      var parameters = {
        niu: niu,
        startDate2: startDate,
        endDate2: endDate,
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_HISTORIAL, parameters);
      this.datosTablaPromocionLucy = this.getObjects(invocar.segumiento_individual.info_preliminar);
      if (isUserSelected) {
        this.flagUsuarioSeleccionado = true;
      }
      this.tarjetaPromocion = true;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }

  async getDataMonitoreoLucyHistorialDinp(niu, startDate, endDate, isUserSelected?: boolean) {
    //this.ServiceProvider.preloaderOn();
    if (isUserSelected) {
      this.flagUsuarioSeleccionado = false;
    }
    try {
      var parameters = {
        niu: niu,
        startDate3: startDate,
        endDate3: endDate,
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_HISTORIAL, parameters);
      this.datosTablaDinp = this.getObjects(invocar.segumiento_individual.info_preliminar);
      if (isUserSelected) {
        this.flagUsuarioSeleccionado = true;
      }
      this.tarjetaDinp = true;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }

  async getDataMonitoreoLucyHistorialInvitacionSus(niu, startDate, endDate, isUserSelected?: boolean) {
    //this.ServiceProvider.preloaderOn();
    if (isUserSelected) {
      this.flagUsuarioSeleccionado = false;
    }
    try {
      var parameters = {
        niu: niu,
        startDate4: startDate,
        endDate4: endDate,
      };
      const invocar: any = await this.ServiceProvider.post(WEBSERVICE.GET_DATA_MONITOREO_HISTORIAL, parameters);
      this.datosTablaInvitacionSusp = this.getObjects(invocar.segumiento_individual.info_preliminar);
      if (isUserSelected) {
        this.flagUsuarioSeleccionado = true;
      }
      this.tarjetaInvitacionsusp = true;
    } catch (error) {
      console.error(error);
    } finally {
      //this.ServiceProvider.preloaderOff();
    }
  }

  //extraer objetos de un arreglo de datos
  getObjects(arr) {
    let cont = 0;
    let objects = [];
    for (let valor in arr) {
      if (valor != 'conversaciones') {
        objects.push(arr[valor]);
      }
    }
    return objects;
  }

  //extraer la fecha del objeto entrante
  getDates(arr) {
    let mensajes = [];
    let cont = 0;
    for (let conversaciones in arr) {
      arr[conversaciones]['fecha'] = arr[conversaciones][0].mensaje[0].fecha;
      mensajes[conversaciones] = arr[conversaciones];
    }
    return mensajes;
  }


  //hacer la busqueda por una fecha especifica
  changeFecha() {
    if (this.niuUsuarioSeleccionado != '') {
      this.tablaConversaciones = true;
      this.getDataMonitoreoLucyPorcentajesDeConsultas(this.niuUsuarioSeleccionado, this.dateForm.get('date').value.begin, this.dateForm.get('date').value.end);
      this.getDataMonitoreoLucyHistorialLlamadas(this.niuUsuarioSeleccionado, this.dateForm.get('date').value.begin, this.dateForm.get('date').value.end);
      this.getDataMonitoreoLucyHistorialPromocionLucy(this.niuUsuarioSeleccionado, this.dateForm.get('date').value.begin, this.dateForm.get('date').value.end);
      this.getDataMonitoreoLucyHistorialDinp(this.niuUsuarioSeleccionado, this.dateForm.get('date').value.begin, this.dateForm.get('date').value.end);
      this.getDataMonitoreoLucyHistorialInvitacionSus(this.niuUsuarioSeleccionado, this.dateForm.get('date').value.begin, this.dateForm.get('date').value.end);
      this.getDataMonitoreoLucyHistorialLucy(this.niuUsuarioSeleccionado, this.dateForm.get('date').value.begin, this.dateForm.get('date').value.end);
      //this.criterioValue = '';
    } else {
      console.log('no se ha cargado el usuario');
    }
  }

  //conversaciones
  selectConversation(conversacion) {
    this.chat = true;
    this.messages = this.getDetailChat(conversacion);
    this.conversacionDetalle = this.getDetailChat(conversacion);
    this.mostrarchat = true;
    this.color = '#3c35355c';
    // console.log('aqui cambia ')
    //this.sendBy = this.conversacionDetalle.sendBy;
  }

  getDetailChat(arr) {
    let mensajes = [];
    let send = [];
    for (let conversaciones in arr) {
      //arr[conversaciones] = arr[conversaciones][0].mensaje[0].sendBy;
      mensajes[conversaciones] = arr[conversaciones].mensaje;

    }
    for (let sendBy in mensajes) {
      if (mensajes[sendBy] != undefined) {
        if (mensajes[sendBy][0].sentBy == 'Lucy') {
          mensajes[sendBy][0]['bot'] = true;
        } else if (mensajes[sendBy][0].sentBy == 'human') {
          mensajes[sendBy][0]['bot'] = false;
        }
        send[sendBy] = mensajes[sendBy][0];
      }
    }
    return send;
  }

  //chat
  chipOperation(c) {
    //console.log(c)
    if (c.hintMsg) {
      /*this._dfs.sentToBot({
        text: c.hintMsg,
        sentBy: 'human'
      })*/
    } else {
      window.open(c[c.type], '__blank');
    }

  }

  //metodo para sacar pantallazo a toda la pantalla
  getPDF() {
    getPDF();
  }

  SegimientoGrafica(id) {
    SegimientoGrafica(id);
  }

  changeColor() {
    this.color = '';
  }

  /*
  [class.fa-bolt]="datos.fuente === 'Invitacion suspension SMS'"
  [class.fa-robot]="datos.fuente === 'DINP'"
  */


}
