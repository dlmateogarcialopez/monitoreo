import { Component, Inject, Optional, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { Mensaje } from '../../../config/interfaces';
import { MESSAGES } from "../../../config/messages";
import { ServiceProvider } from "../../../config/services";
import { FormGroup, FormBuilder, Validators } from '@angular/forms';

@Component({
  selector: 'dialog-box',
  templateUrl: './dialogBox.component.html',
})
export class DialogBoxComponent implements OnInit {
  accion: string;
  datosMensaje: Mensaje;
  maxCaracteresMensaje: number;
  MESSAGES: object = MESSAGES;
  formMensaje: FormGroup;

  constructor(
    public dialogRef: MatDialogRef<DialogBoxComponent>,
    private ServiceProvider: ServiceProvider,
    private fb: FormBuilder,
    //Se usa @Optional() para evitar error si no se pasan datos
    @Optional() @Inject(MAT_DIALOG_DATA) public data: Mensaje) {
    this.datosMensaje = { ...data };
    this.accion = this.datosMensaje.accion;
    this.maxCaracteresMensaje = this.datosMensaje.maxCaracteresMensaje;
  }

  ngOnInit() {
    this.formMensaje = this.fb.group({
      mensaje: [this.datosMensaje.mensaje, [Validators.required, this.ServiceProvider.removeEspaciosInicioCadena]],
    });

  }

  get mensajeField() {
    return this.formMensaje.controls;
  }

  guardarMensaje() {
    if (this.formMensaje.valid) {
      this.datosMensaje.mensaje = this.formMensaje.value.mensaje;
      this.dialogRef.close({
        event: this.accion,
        data: this.datosMensaje
      });
    } else {
      this.ServiceProvider.validateAllFormFields(this.formMensaje);
    }
  }

  cerrarDialogo() {
    this.dialogRef.close({
      event: "Cerrar"
    });
  }

}