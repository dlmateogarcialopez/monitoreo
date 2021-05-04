import { Component, OnInit, Input, EventEmitter, Output, ViewChild } from '@angular/core';
import { MESSAGES } from '../../../config/messages';
import { FormGroup, AbstractControl } from '@angular/forms';
import { MatRadioChange } from '@angular/material/radio';
import { Mensaje, InfoMensajes } from '../../../config/interfaces';
import { ModalDirective } from 'ngx-bootstrap';
import { ExcelUtil } from '../../../config/excelUtil';

@Component({
  selector: 'alertas-mensajes',
  templateUrl: './alertasMensajes.component.html',
})
export class AlertasMensajesComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  isCollapsedMensajesSuperiores: boolean = true;
  @Input() isSuperiorMaxCaracteres: boolean;
  @Input() countMensajesSuperioresMaxCaracteres: number;
  @Input() totalDefinitivoMensajes: number;
  @Input() totalDefinitivoCelulares: number;
  @Input() arrayTodosMensajes: Mensaje[];
  @Input() valorMensajesAEnviar: number;
  @Input() valorMensajeIndividual: number;
  @Input() maxCaracteresMensaje: number;
  @Input() formConfirmacion: FormGroup;
  @Input() formConfirmacionFields: { [key: string]: AbstractControl; };
  @Input() arrayCelularesExcluidos: string[];
  @Output() updateTotalesAlertas = new EventEmitter<InfoMensajes>();
  @ViewChild("modalCelularesExcluidos") modalCelularesExcluidos: ModalDirective;

  constructor() { }

  ngOnInit() { }

  setMensajesAEnviar($event: MatRadioChange) {
    this.totalDefinitivoMensajes = 0;
    this.totalDefinitivoCelulares = 0;

    if ($event.value === "true") {
      this.arrayTodosMensajes.forEach((datosMensaje: Mensaje) => {
        this.totalDefinitivoMensajes += datosMensaje.cantidadMensajes;
        this.totalDefinitivoCelulares++;
      });
    } else {
      this.arrayTodosMensajes.forEach((datosMensaje: Mensaje) => {
        if (datosMensaje.cantidadCaracteres <= this.maxCaracteresMensaje) {
          this.totalDefinitivoMensajes += datosMensaje.cantidadMensajes;
          this.totalDefinitivoCelulares++;
        }
      });
    }
    this.valorMensajesAEnviar = this.totalDefinitivoMensajes * this.valorMensajeIndividual;

    const infoMensajes: InfoMensajes = {
      totalMensajes: this.totalDefinitivoMensajes,
      valorMensajes: this.valorMensajesAEnviar,
      totalCelulares: this.totalDefinitivoCelulares
    };

    this.updateTotalesAlertas.emit(infoMensajes);
  }

  descargarCelularesExcluidos() {
    const arrayToJsonArray: object[] = this.arrayCelularesExcluidos.map(celular => ({ celular }));
    ExcelUtil.exportToExcel(arrayToJsonArray, "Celulares excluidos envío");
  }

}
