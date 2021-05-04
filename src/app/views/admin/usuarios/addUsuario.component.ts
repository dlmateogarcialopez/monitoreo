import { Component, OnInit } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray } from "@angular/forms";
import { ServiceProvider } from "../../../config/services";
import { MESSAGES } from '../../../config/messages';
import { WEBSERVICE } from "../../../config/webservices";
import { MatCheckboxChange } from '@angular/material/checkbox';
import { ActivatedRoute, Router } from '@angular/router';
import * as moment from "moment";
import "moment/locale/es";
import "moment-timezone";
import { Users, CuotaMensajesUsuario, BolsaMensajes } from '../../../config/interfaces';

@Component({
  selector: "add-usuario",
  templateUrl: "addUsuario.component.html"
})

export class AddUsuarioComponent implements OnInit {
  isLinear: boolean = true;
  formDatosPersonales: FormGroup;
  formPermisosUsuario: FormGroup;
  MESSAGES: object = MESSAGES;
  hidePassword: boolean = true;
  hideConfirmPassword: boolean = true;
  formUsuarios: FormGroup;
  isAddUser: boolean = true; /* TRUE: Agregar usuario; FALSE: Editar usuario */
  isAddPermisos: boolean = true;
  permisosGuardados: object = null;
  periodos: object[] = [
    { periodoMostrar: "Día", periodoValue: "day" },
    { periodoMostrar: "Semana", periodoValue: "week" },
    { periodoMostrar: "Mes", periodoValue: "month" }
  ];
  /** Nombres de las bolsas de mensajes */
  bolsasMensaje: string[];
  idUsuario: string;
  totalCuotaMensajesUsuario: number = 0;
  cantidaMensajesUsuarioEnviados: number = 0;

  constructor(
    private fb: FormBuilder,
    private ServiceProvider: ServiceProvider,
    private route: ActivatedRoute,
    private router: Router,
  ) {
    moment.tz.setDefault("America/Bogota");
  }

  ngOnInit() {
    this.ServiceProvider.preloaderOn();
    this.route.params.subscribe(param => this.idUsuario = param["idUsuario"]);
    this.setDatosPersonalesControls();
    this.setPermisosControls();
    this.getDatosBolsasMensajes().then(() => {
      /* Si se va a editar un usuario, llamar el servicio `getUsuario()`*/
      if (this.setValidatorsEditarUsuario()) {
        this.getUsuario();
      }
    }).finally(() => this.ServiceProvider.preloaderOff());
  }

  setDatosPersonalesControls() {
    this.formDatosPersonales = this.fb.group({
      nombres: ["", Validators.required],
      apellidos: ["", Validators.required],
      cargo: [""],
      correo: ["", [Validators.required, Validators.email]],
      password: ["", [Validators.required, Validators.minLength(6)]],
      confirmarPassword: ["", Validators.required],
    }, { validator: this.ServiceProvider.comparePasswords });
  }

  setPermisosControls() {
    this.formPermisosUsuario = this.fb.group({
      checkboxPermisos: this.fb.group({
        administrador: [false],
        dodEnviarSms: [false],
        dodPrioridadEnvio: [false],
        dodVerReportes: [false],
        dodActivarDesactivar: [false],
        monitoreoVerReportes: [false],
        dinpVerReportes: [false],
        dinpAdminReglas: [false],
        dinpActivarDesactivar: [false],
        dipVerReportes: [false],
        dipAdminReglas: [false],
        dipActivarDesactivar: [false],
      }),
      selectBolsa: ["", Validators.required],
      selectPeriodoMensajesUsuario: ["", Validators.required],
      cantidadMensajesUsuario: ["", [Validators.required, Validators.min(1)]]
    });
  }

  get formDatosPersonalesFields() {
    return this.formDatosPersonales.controls;
  }

  get formPermisosUsuarioFields() {
    return this.formPermisosUsuario.controls;
  }

  get formPermisosCheckbox() {
    return this.formPermisosUsuario.get("checkboxPermisos") as FormArray;
  }

  setValidatorsEditarUsuario() {
    if (this.idUsuario) {
      this.isAddUser = false;
      this.formDatosPersonalesFields.password.clearValidators();
      this.formDatosPersonalesFields.confirmarPassword.clearValidators();
      this.formDatosPersonalesFields.password.updateValueAndValidity();
      this.formDatosPersonalesFields.confirmarPassword.updateValueAndValidity();
      return true;
    }
    return false;
  }

  validateStep(formGroup: FormGroup) {
    return this.ServiceProvider.validateStep(formGroup);
  }

  /* Verifica que el checkbox de Administrador esté seleccionado. Si está seleccionado, chequea todos los demás checkboxes de Permisos */
  checkAdmin($event: MatCheckboxChange) {
    if ($event.checked) {
      for (const clavePermiso of Object.keys(this.formPermisosCheckbox.value)) {
        this.formPermisosCheckbox.controls[clavePermiso].setValue(true);
      }
    }
  }

  /** Quita el check de `Administrador` si alguno de los checkboxes de permisos no está chequeado */
  isCheckboxSelected($event: MatCheckboxChange) {
    if (!$event.checked) {
      this.formPermisosCheckbox.controls["administrador"].setValue(false);
    }
  }

  /** Quita la selección del checkbox de `dodPrioridadEnvio`, si el checkbox de `dodEnviarSms` no está seleccionado */
  isDodEnviarSmsChecked($event: MatCheckboxChange) {
    if (!$event.checked) {
      this.formPermisosCheckbox.controls["dodPrioridadEnvio"].setValue(false);
    }
  }

  /** Selecciona el checkbox de `dodEnviarSms`, si el checkbox de `dodPrioridadEnvio` es seleccionado */
  isDodPrioridadEnvioChecked($event: MatCheckboxChange) {
    if ($event.checked) {
      this.formPermisosCheckbox.controls["dodEnviarSms"].setValue(true);
    }
  }

  /** Actualiza la validación de los controles de cuota de mensajes de usuario cuando este selecciona el permiso `dodEnviarSms` */
  setCantidadMensajesValidators() {
    if (this.formPermisosUsuario.value.checkboxPermisos.dodEnviarSms) {
      this.formPermisosUsuarioFields.selectBolsa.setValidators(Validators.required);
      this.formPermisosUsuarioFields.selectPeriodoMensajesUsuario.setValidators(Validators.required);
      this.formPermisosUsuarioFields.cantidadMensajesUsuario.setValidators([Validators.required, Validators.min(1)]);
    } else {
      this.formPermisosUsuarioFields.selectBolsa.clearValidators();
      this.formPermisosUsuarioFields.selectPeriodoMensajesUsuario.clearValidators();
      this.formPermisosUsuarioFields.cantidadMensajesUsuario.clearValidators();
    }
    this.formPermisosUsuarioFields.selectBolsa.updateValueAndValidity();
    this.formPermisosUsuarioFields.selectPeriodoMensajesUsuario.updateValueAndValidity();
    this.formPermisosUsuarioFields.cantidadMensajesUsuario.updateValueAndValidity();
  }

  async getDatosBolsasMensajes() {
    try {
      const datosBolsas = <BolsaMensajes[]>await this.ServiceProvider.get(WEBSERVICE.GET_DATOS_BOLSAS_MENSAJES);

      this.bolsasMensaje = datosBolsas.map(bolsa => bolsa.nombre);
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    }
  }

  async getUsuario() {
    try {
      const usuario = <Users> await this.ServiceProvider.post(WEBSERVICE.GET_USUARIO_INDIVIDUAL, { idUsuario: this.idUsuario });
      const cuotaMensajesUsuario = <CuotaMensajesUsuario> await this.ServiceProvider.post(WEBSERVICE.GET_CUOTA_MENSAJES_USUARIO, { idUsuario: this.idUsuario });

      this.formDatosPersonalesFields.nombres.setValue(usuario.nombres);
      this.formDatosPersonalesFields.apellidos.setValue(usuario.apellidos);
      this.formDatosPersonalesFields.cargo.setValue(usuario.cargo);
      this.formDatosPersonalesFields.correo.setValue(usuario.correo);
      this.totalCuotaMensajesUsuario = usuario.permisos.cantidadMensajesUsuario;
      this.cantidaMensajesUsuarioEnviados = this.totalCuotaMensajesUsuario - cuotaMensajesUsuario.cantidadMensajesUsuario;

      for (const [clavePermisoBack, valorPermisoBack] of Object.entries(usuario.permisos)) {
        /* Si la `clavePermisoBack` existe en `formPermisosCheckbox` poner el `valorPermisoBack` en el checkbox correspondiente */
        if (this.formPermisosCheckbox.controls[clavePermisoBack]) {
          this.formPermisosCheckbox.controls[clavePermisoBack].setValue(valorPermisoBack);
        } else {
          /* Se agrega `valorPermisoBack` en los controles que no son checkbox (<input> de cantidad y <select> de periodo de envíos por usuario) */
          this.formPermisosUsuarioFields[clavePermisoBack].setValue(valorPermisoBack);
        }
      }
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    }
  }

  async insertUsuario() {
    this.setCantidadMensajesValidators();

    if (this.formPermisosUsuario.valid) {
      this.ServiceProvider.preloaderOn();

      try {
        const datosCorreo = {
          /** Si se va a agregar un usuario, enviar `""`; sino, enviar `idUsuario` */
          idUsuario: this.isAddUser ? "" : this.idUsuario,
          correo: this.formDatosPersonales.value.correo,
        };
        const buscarCorreoExistente: any = await this.ServiceProvider.post(WEBSERVICE.BUSCAR_CORREO_EXISTENTE_USUARIO, datosCorreo);

        /* Verifica que el correo no se encuentre registrado más de una vez en la BD */
        if (!buscarCorreoExistente.length) {
          const datosUsuario: Users = {
            nombres: this.formDatosPersonales.value.nombres,
            apellidos: this.formDatosPersonales.value.apellidos,
            cargo: this.formDatosPersonales.value.cargo,
            correo: this.formDatosPersonales.value.correo,
            permisos: this.formPermisosUsuario.value,
            isAddUser: this.isAddUser /* Verifica si en el backend se hará INSERT (`true`) o UPDATE (`false`) */
          };

          /* Si se agrega un nuevo usuario, agregar la clave `password` a `datosUsuario`. Si se edita tomar, el `id` del mismo. */
          if (this.isAddUser) {
            datosUsuario.password = this.formDatosPersonales.value.password;
          } else {
            datosUsuario.idUsuario = this.idUsuario;
          }

          let cuotaMensajes: CuotaMensajesUsuario = {
            selectBolsa: "",
            selectPeriodoMensajesUsuario: "",
            cantidadMensajesUsuario: 0,
            fechaInicioMensajesUsuario: "",
            fechaFinMensajesUsuario: ""
          };

          if (this.formPermisosUsuario.value.checkboxPermisos.dodEnviarSms) {
            const selectPeriodoMensajesUsuario = this.formPermisosUsuario.value.selectPeriodoMensajesUsuario;
            const fechaHoy = moment().format(this.ServiceProvider.formatoFecha);

            cuotaMensajes.selectBolsa = this.formPermisosUsuario.value.selectBolsa;
            cuotaMensajes.selectPeriodoMensajesUsuario = selectPeriodoMensajesUsuario;
            cuotaMensajes.cantidadMensajesUsuario = this.formPermisosUsuario.value.cantidadMensajesUsuario;
            cuotaMensajes.fechaInicioMensajesUsuario = fechaHoy;
            cuotaMensajes.fechaFinMensajesUsuario = moment().endOf(selectPeriodoMensajesUsuario).format(this.ServiceProvider.formatoFecha);
          } else {
            datosUsuario.permisos.selectBolsa = "";
            datosUsuario.permisos.selectPeriodoMensajesUsuario = "";
            datosUsuario.permisos.cantidadMensajesUsuario = 0;
          }
          datosUsuario.cuotaMensajes = cuotaMensajes;

          await this.ServiceProvider.post(WEBSERVICE.INSERT_USUARIO, datosUsuario);
          this.router.navigate(["/admin"]);

          if (this.isAddUser) {
            this.ServiceProvider.openToast("success", `Usuario ${MESSAGES.insertadoExito.toLowerCase()}`);
          } else {
            this.ServiceProvider.openToast("success", `Usuario ${MESSAGES.actualizadoExito.toLowerCase()}`);
          }
        } else {
          this.ServiceProvider.openToast("error", MESSAGES.errorCorreoExiste);
        }
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }
    } else {
      this.ServiceProvider.validateAllFormFields(this.formPermisosUsuario);
    }
  }
}
