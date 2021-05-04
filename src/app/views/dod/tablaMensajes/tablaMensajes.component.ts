import { Component, OnInit, Input, ViewChild, SimpleChanges, Output, EventEmitter, OnChanges, ElementRef } from '@angular/core';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { trigger, state, style, transition, animate } from '@angular/animations';
import { MESSAGES } from '../../../config/messages';
import { MatDialog } from '@angular/material/dialog';
import { Mensaje, InfoMensajes } from '../../../config/interfaces';
import { DialogBoxComponent } from '../dialogBox/dialogBox.component';
import { MatTableDataSource } from '@angular/material/table';
import { AbstractControl } from '@angular/forms';
import { ExcelUtil } from '../../../config/excelUtil';
import { ServiceProvider } from '../../../config/services';



@Component({
  selector: 'tabla-mensajes',
  templateUrl: './tablaMensajes.component.html',
  animations: [
    trigger('detailExpand', [
      state('collapsed', style({ height: '0px', minHeight: '0' })),
      state('expanded', style({ height: '*' })),
      transition('expanded <=> collapsed', animate('225ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
    ]),
  ],
})

export class TablaMensajesComponent implements OnInit, OnChanges {
  MESSAGES: object = MESSAGES;
  @Input() dataSourceStepConfirmacion: MatTableDataSource<Mensaje>;
  @Input() maxCaracteresMensaje: number;
  @Input() isSuperiorMaxCaracteres: boolean;
  @Input() countMensajesSuperioresMaxCaracteres: number;
  @Input() totalDefinitivoMensajes: number;
  @Input() totalDefinitivoCelulares: number;
  @Input() arrayTodosMensajes: Mensaje[];
  @Input() valorMensajesAEnviar: number;
  @Input() valorMensajeIndividual: number;
  @Input() formConfirmacionFields: { [key: string]: AbstractControl; };
  @Output() updateDatosMensajes = new EventEmitter<InfoMensajes>();
  @ViewChild("inputFiltro") inputFiltro: ElementRef;
  columnsStepConfirmacion: string[] = ["celular", "mensaje", "cantidadCaracteres", "cantidadMensajes", "accion"];
  @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
  @ViewChild(MatSort, { static: true }) sort: MatSort;

  constructor(
    public dialog: MatDialog,
    public ServiceProvider: ServiceProvider,
  ) { }

  ngOnChanges(changes: SimpleChanges) {
    //Called before any other lifecycle hook. Use it to inject dependencies, but avoid any serious work here.
    //Add '${implements OnChanges}' to the class.
    if (this.dataSourceStepConfirmacion) {
      this.dataSourceStepConfirmacion.paginator = this.paginator;
      this.dataSourceStepConfirmacion.sort = this.sort;

      /* Cuando el usuario realiza una búsqueda en la tabla, hacer el filtro solo teniendo en cuenta los campos `celular`, `mensaje`, `cantidadCaracteres` y `cantidadMensajes` */
      this.dataSourceStepConfirmacion.filterPredicate = (datosMensaje, filtro: string) => {
        return datosMensaje.celular.includes(filtro)
          || datosMensaje.mensaje.toLowerCase().includes(filtro)
          || String(datosMensaje.cantidadCaracteres).includes(filtro)
          || String(datosMensaje.cantidadMensajes).includes(filtro);
      };
    }
  }

  ngOnInit() { }

  /** Descarga los mensajes del paso de confirmación
   * @param tipoMensaje Define si se van a descargar todos los mensajes o solo los superiores a 160 caracteres
   */
  descargarMensajes(tipoMensaje: string) {
    let mensajes: Mensaje[];
    let nombreArchivo: string = "Listado mensajes";

    if (tipoMensaje === "todos") {
      mensajes = [...this.arrayTodosMensajes];
    } else {
      mensajes = this.arrayTodosMensajes.filter(datos => datos.cantidadCaracteres > this.maxCaracteresMensaje);
      nombreArchivo = "Mensajes superiores a 160 caracteres";
    }
    const llavesFiltro: string[] = ["celular", "mensaje", "cantidadCaracteres"];
    const mensajesFiltrados = this.ServiceProvider.filtrarArray(mensajes, llavesFiltro);
    ExcelUtil.exportToExcel(mensajesFiltrados, nombreArchivo);
  }

  openDialog(accion: string, datosMensaje: Mensaje) {
    datosMensaje.accion = accion;
    datosMensaje.maxCaracteresMensaje = this.maxCaracteresMensaje;
    const dialogRef = this.dialog.open(DialogBoxComponent, {
      width: "500px",
      data: datosMensaje,
      backdropClass: "backdrop_material_dialog",
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result && result.event === "Editar") {
        /* Se limipia `inputFiltro` cuando se edita un mensaje */
        this.inputFiltro.nativeElement.value = "";
        this.updateDatosFila(result.data);
      }
    });
  }

  updateDatosFila(datosFila: Mensaje) {
    this.isSuperiorMaxCaracteres = false;
    this.countMensajesSuperioresMaxCaracteres = 0;
    this.totalDefinitivoMensajes = 0;
    this.totalDefinitivoCelulares = 0;

    this.arrayTodosMensajes.forEach(datosMensaje => {
      let cantidadMensajes = 1;

      if (datosMensaje.celular === datosFila.celular) {
        datosMensaje.mensaje = datosFila.mensaje.trim();
        datosMensaje.cantidadCaracteres = datosMensaje.mensaje.length;
        let cantidadCaracteres = datosMensaje.cantidadCaracteres;

        while (cantidadCaracteres > this.maxCaracteresMensaje) {
          cantidadCaracteres -= this.maxCaracteresMensaje;
          cantidadMensajes++;
        }
        datosMensaje.cantidadMensajes = cantidadMensajes;
      }

      if (datosMensaje.cantidadCaracteres > this.maxCaracteresMensaje) {
        this.isSuperiorMaxCaracteres = true;
        this.countMensajesSuperioresMaxCaracteres++;
        this.formConfirmacionFields.radioMensajesSuperiores.setValue("");
      }

      this.totalDefinitivoMensajes += datosMensaje.cantidadMensajes;
      this.totalDefinitivoCelulares++;
    }); /* Fin forEach */
    this.valorMensajesAEnviar = this.totalDefinitivoMensajes * this.valorMensajeIndividual;
    this.dataSourceStepConfirmacion = new MatTableDataSource(this.arrayTodosMensajes);
    this.dataSourceStepConfirmacion.paginator = this.paginator;
    this.dataSourceStepConfirmacion.sort = this.sort;

    const infoMensajes: InfoMensajes = {
      totalMensajes: this.totalDefinitivoMensajes,
      valorMensajes: this.valorMensajesAEnviar,
      totalCelulares: this.totalDefinitivoCelulares,
      isSuperiorMaxCaracteres: this.isSuperiorMaxCaracteres,
      countMensajesSuperioresMaxCaracteres: this.countMensajesSuperioresMaxCaracteres,
      dataSourceStepConfirmacion: this.dataSourceStepConfirmacion
    };

    this.updateDatosMensajes.emit(infoMensajes);
  }

}
