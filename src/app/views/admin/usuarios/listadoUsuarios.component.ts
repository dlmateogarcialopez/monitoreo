import { Component, OnInit, Input, ViewChild } from "@angular/core";
import { FormBuilder, Validators, FormGroup } from "@angular/forms";
import { ServiceProvider } from "../../../config/services";
import { MESSAGES } from '../../../config/messages';
import { WEBSERVICE } from "../../../config/webservices";
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { JwtHelperService } from '@auth0/angular-jwt';
import * as moment from "moment";
import "moment/locale/es";
import "moment-timezone";
import { AuthenticationService } from '../../../config/authentication.service';


@Component({
  selector: "listado-usuarios",
  templateUrl: "listadoUsuarios.component.html"
})

export class ListadoUsuariosComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  formCuotaClientes: FormGroup;
  displayedColumns: string[] = ["nombres", "cargo", "correo", "accion"];
  dataSourceUsuarios: any;
  @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
  @ViewChild(MatSort, { static: true }) sort: MatSort;
  periodos: object[] = [
    { periodoMostrar: "Día", periodoValue: "day" },
    { periodoMostrar: "Semana", periodoValue: "week" },
    { periodoMostrar: "Mes", periodoValue: "month" }
  ];
  datosUsuarioJWT = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken())?.data;

  constructor(
    private authenticationService: AuthenticationService,
    public ServiceProvider: ServiceProvider,
    private fb: FormBuilder,
    public jwtHelper: JwtHelperService,
  ) {
    moment.tz.setDefault("America/Bogota");
  }

  ngOnInit() {
    this.ServiceProvider.preloaderOn();
    this.ServiceProvider.setTituloPestana("Administración - Usuarios");
    this.setCuotaClientesControls();

    Promise
      .all([this.getUsuarios(), this.getCuotaClientes()])
      .then(() => this.ServiceProvider.preloaderOff());
  }

  setCuotaClientesControls() {
    this.formCuotaClientes = this.fb.group({
      selectPeriodoMensajesCliente: ["day", Validators.required],
      cantidadMensajesCliente: ["", [Validators.required, Validators.min(1)]],
    });
  }

  get formCuotaClientesFields() {
    return this.formCuotaClientes.controls;
  }

  async getUsuarios() {
    try {
      const usuarios: any = await this.ServiceProvider.get(WEBSERVICE.GET_USUARIOS);
      this.dataSourceUsuarios = new MatTableDataSource(usuarios);
      this.dataSourceUsuarios.paginator = this.paginator;
      this.dataSourceUsuarios.sort = this.sort;
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    }
  }

  showAdvertenciaEliminarUsuario(idUsuario: string) {
    this.ServiceProvider.openPopup("Advertencia", MESSAGES.advertenciaEliminar, "deleteUsuario", this, idUsuario);
  }

  async deleteUsuario(idUsuario: string) {
    this.ServiceProvider.preloaderOn();
    try {
      /* No se elimina el usuario de la BD. Se actualiza el estado a false */
      await this.ServiceProvider.post(WEBSERVICE.INACTIVAR_USUARIO, { idUsuario });
      this.ServiceProvider.openToast("success", `Usuario ${MESSAGES.eliminadoExito.toLowerCase()}`);
      this.getUsuarios();
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }

  async getCuotaClientes() {
    try {
      const cuotaClientes: any = await this.ServiceProvider.get(WEBSERVICE.GET_CUOTA_MENSAJES_CLIENTE);

      // this.formCuotaClientesFields.selectPeriodoMensajesCliente.setValue(cuotaClientes.periodoMensajesCliente);
      this.formCuotaClientesFields.cantidadMensajesCliente.setValue(cuotaClientes.cantidadMensajesCliente);

    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    }
  }

  async insertCuotaClientes() {
    if (this.formCuotaClientes.valid) {
      this.ServiceProvider.preloaderOn();
      try {
        const periodoMensajesCliente = this.formCuotaClientes.value.selectPeriodoMensajesCliente;
        const fechaInicioMensajesCliente = moment().startOf(periodoMensajesCliente).format(this.ServiceProvider.formatoFecha);
        const fechaFinMensajesCliente = moment().endOf(periodoMensajesCliente).format(this.ServiceProvider.formatoFecha);
        const fechaHoy = moment().format(this.ServiceProvider.formatoFecha);
        const cantidadMensajesCliente = this.formCuotaClientes.value.cantidadMensajesCliente;

        const datosCuotaCliente = {
          idUsuario: this.datosUsuarioJWT._id.$oid,
          nombreUsuario: `${this.datosUsuarioJWT.nombres} ${this.datosUsuarioJWT.apellidos}`,
          fechaRegistro: fechaHoy,
          cuotaMensajes: {
            periodoMensajesCliente,
            cantidadMensajesCliente,
            fechaInicioMensajesCliente,
            fechaFinMensajesCliente
          }
        };

        const cuotaClientes: any = await this.ServiceProvider.get(WEBSERVICE.GET_CUOTA_MENSAJES_CLIENTE);
        const cantidadMensajesClienteAnterior = cuotaClientes.cantidadMensajesCliente;

        const insertCuotaCliente = await this.ServiceProvider.post(WEBSERVICE.INSERT_CUOTA_MENSAJES_CLIENTE, datosCuotaCliente);

        const datosCuotaClienteDod = {
          fechaInicioMensajesCliente,
          fechaFinMensajesCliente,
          cantidadMensajesCliente,
          cantidadMensajesClienteAnterior
        };

        const updateCuotaClienteDod = await this.ServiceProvider.post(WEBSERVICE.UPDATE_CUOTA_MENSAJES_CLIENTE_DOD, datosCuotaClienteDod);
        this.ServiceProvider.openToast("success", MESSAGES.insertadoExito);
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }
    } else {
      this.ServiceProvider.validateAllFormFields(this.formCuotaClientes);
    }
  }
}
