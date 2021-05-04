import { Component, OnInit, Input, ViewChild } from "@angular/core";
import { FormBuilder, Validators, FormGroup } from "@angular/forms";
import { HttpClient } from "@angular/common/http";
import { ServiceProvider } from "../../../config/services";
import { MESSAGES } from "../../../config/messages";
import { WEBSERVICE } from "../../../config/webservices";
import { Router, ActivatedRoute } from "@angular/router";
import { AuthenticationService } from "../../../config/authentication.service";
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { ExcelUtil } from '../../../config/excelUtil';
import { animate, state, style, transition, trigger } from '@angular/animations';

@Component({
  selector: "detalle-envio-dod",
  templateUrl: "detalleEnvioDod.component.html",
  animations: [
    trigger('detailExpand', [
      state('collapsed', style({ height: '0px', minHeight: '0' })),
      state('expanded', style({ height: '*' })),
      transition('expanded <=> collapsed', animate('225ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
    ]),
  ],
})

export class DetalleEnvioDodComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  usuarioActual: any = {};
  displayedColumns: string[] = ["celular", "mensaje", /* "estado" */];
  dataSourceDetalleEnvio: MatTableDataSource<any>;
  @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
  @ViewChild(MatSort, { static: true }) sort: MatSort;
  idEnvio: string;
  detallesEnvioDod: any = {};

  constructor(
    private authenticationService: AuthenticationService,
    private fb: FormBuilder,
    public ServiceProvider: ServiceProvider,
    private http: HttpClient,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit() {
    this.route.params.subscribe(param => this.idEnvio = param["idEnvio"]);
    this.ServiceProvider.setTituloPestana("Detalle envío DOD");
    this.getDetalleEnvio();
    this.usuarioActual = this.authenticationService.usuarioActualValue;
  }

  exportListadoEnvio() {
    ExcelUtil.exportToExcel(this.detallesEnvioDod.mensajes, "Listado envío DOD");
  }

  async getDetalleEnvio() {
    this.ServiceProvider.preloaderOn();

    try {
      this.detallesEnvioDod = await this.ServiceProvider.post(WEBSERVICE.GET_DETALLE_ENVIO_DOD, { idDetalleEnvio: this.idEnvio });

      /* Se agregan las columnas "cuenta" y "nombre" si el envío se realizó desde BD */
      if (this.detallesEnvioDod.metodoEnvio === "Desde base de datos") {
        this.displayedColumns = ["cuenta", "nombre", ...this.displayedColumns];
        // this.displayedColumns.unshift("cuenta", "nombre");
      }
      this.dataSourceDetalleEnvio = new MatTableDataSource(this.detallesEnvioDod.mensajes);
      this.dataSourceDetalleEnvio.paginator = this.paginator;
      this.dataSourceDetalleEnvio.sort = this.sort;
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }
  }
}
