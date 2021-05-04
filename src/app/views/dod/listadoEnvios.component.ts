import { Component, OnInit, Input, ViewChild, OnDestroy, ViewEncapsulation } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray } from "@angular/forms";
import { HttpClient } from "@angular/common/http";
import { ServiceProvider } from "../../config/services";
import { MESSAGES } from "../../config/messages";
import { WEBSERVICE } from "../../config/webservices";
import { Router } from "@angular/router";
import { AuthenticationService } from '../../config/authentication.service';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { interval, Subscription } from 'rxjs';
import { takeWhile } from 'rxjs/operators';
import { JwtHelperService } from '@auth0/angular-jwt';
import * as moment from "moment";
import "moment/locale/es";


@Component({
  selector: "listado-envios",
  templateUrl: "listadoEnvios.component.html",
  /* Se define encapsulation None para centrar elementos `<th>` en la tabla */
  encapsulation: ViewEncapsulation.None
})

export class ListadoEnviosComponent implements OnInit, OnDestroy {
  usuarioActual: any = {};
  permisosUsuario: any = {};
  MESSAGES: object = MESSAGES;
  formPerfil: FormGroup;
  formPassword: FormGroup;
  hideCurrentPassword: boolean = true;
  hidePassword: boolean = true;
  hideConfirmPassword: boolean = true;
  displayedColumns: string[] = ["fecha", "nombreUsuario", "motivoEnvio", "cantidadEnviados", "estado", "accion"];
  dataSource: MatTableDataSource<any>; //new MatTableDataSource<Users>(ELEMENT_DATA);
  @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
  @ViewChild(MatSort, { static: true }) sort: MatSort;
  isEnvioEnProceso: boolean = false;
  intervalSubscription: Subscription;


  constructor(
    private authenticationService: AuthenticationService,
    private fb: FormBuilder,
    public ServiceProvider: ServiceProvider,
    private http: HttpClient,
    private router: Router,
    public jwtHelper: JwtHelperService
  ) { }

  ngOnInit() {
    this.ServiceProvider.preloaderOn();
    this.ServiceProvider.setTituloPestana("Listado de envíos");

    /* Ejecuta la función `this.getListadoEnvios()` cada 10 segundos, mientras (`takeWhile`) haya por lo menos un envío en proceso */
    this.intervalSubscription = interval(10000)
      .pipe(takeWhile(() => this.isEnvioEnProceso))
      .subscribe(() => this.getListadoEnvios());

    this.usuarioActual = this.authenticationService.usuarioActual;
    this.permisosUsuario = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken()).data.permisos;

    /* Se quita la columna "accion" si el usuario no tiene permiso `dodVerReportes` */
    if (!this.permisosUsuario.dodVerReportes) {
      this.displayedColumns.splice(this.displayedColumns.indexOf("accion"), 1);
      // this.displayedColumns.pop(); // Otro método para eliminar última columna
    }
    this.getListadoEnvios();
  }


  ngOnDestroy() {
    /* Se elimina la subscripción al intervalo cuando la instancia de la clase sea destruida, es decir, cuando se navega a otro componente. Esto con el fin de que la función del intervalo no se siga ejecutando en otros componentes */
    if (this.intervalSubscription) {
      this.intervalSubscription.unsubscribe();
    }
  }

  async getListadoEnvios() {
    try {
      const listado: any = await this.ServiceProvider.get(WEBSERVICE.GET_LISTADO_ENVIOS_DOD);
      listado.forEach((mensaje: object) => {
        /* Se convierten las fechas retornadas en formato legible (Julio 20, 2019).
        Se agrega la clave `fechaMostrar` para que el usuario pueda realizar la búsqueda de un envío por el nombre del mes */
        mensaje["fechaMostrar"] = moment(mensaje["fecha"]).format("MMMM DD, YYYY");
      });

      this.dataSource = new MatTableDataSource(listado);
      this.dataSource.paginator = this.paginator;
      /** Se define `sortingDataAccessor` para que la columna `fecha` se ordene correctamente
       * @param datosListado - Los datos a ordenar
       * @param encabezado - Encabezados de la tabla que vienen del array `displayedColumns`
       */
      /* this.dataSource.sortingDataAccessor = (datosListado, encabezado) => {
        switch (encabezado) {
          case "fecha":
            return new Date(datosListado.fecha);
          default:
            return datosListado[encabezado];
        }
      }; */
      this.dataSource.sort = this.sort;

      /* Si es `true`, quiere decir que por lo menos un envío de mensajes está en proceso */
      this.isEnvioEnProceso = listado.some((mensaje: object) => mensaje["estado"] === "En proceso");
    } catch (error) {
      // this.ServiceProvider.openPopup("error", error);
      console.error(error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }
}
