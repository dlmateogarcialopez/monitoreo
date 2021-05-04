import { Component, OnInit, Input, ViewChild } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray } from "@angular/forms";
import { HttpClient } from "@angular/common/http";
import { ServiceProvider } from "../../config/services";
import { MESSAGES } from "../../config/messages";
import { WEBSERVICE } from "../../config/webservices";
import { Router } from "@angular/router";
import { AuthenticationService } from '../../config/authentication.service';
import { JwtHelperService } from '@auth0/angular-jwt';
import { Users } from '../../config/interfaces';

@Component({
  selector: "cuenta",
  templateUrl: "cuenta.component.html"
})

export class CuentaComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  formPerfil: FormGroup;
  formPassword: FormGroup;
  hideCurrentPassword: boolean = true;
  hidePassword: boolean = true;
  hideConfirmPassword: boolean = true;
  usuarioActual: any = {};
  datosUsuarioJWT: Users = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken())?.data;
  idUsuario: string = this.datosUsuarioJWT._id.$oid;

  constructor(
    private authenticationService: AuthenticationService,
    private fb: FormBuilder,
    private ServiceProvider: ServiceProvider,
    public jwtHelper: JwtHelperService
  ) { }

  ngOnInit() {
    this.usuarioActual = this.authenticationService.usuarioActualValue;
    this.ServiceProvider.setTituloPestana("Cuenta");
    this.setPerfilControls();
    this.setPasswordControls();
  }

  setPerfilControls() {
    this.formPerfil = this.fb.group({
      nombres: [this.datosUsuarioJWT.nombres, [Validators.required]],
      apellidos: [this.datosUsuarioJWT.apellidos, [Validators.required]],
      cargo: [this.datosUsuarioJWT.cargo, []],
      correo: [this.datosUsuarioJWT.correo, [Validators.required, Validators.email]],
    });
  }

  setPasswordControls() {
    this.formPassword = this.fb.group({
      passwordActual: ["", [Validators.required]],
      password: ["", [Validators.required, Validators.minLength(6)]],
      confirmarPassword: ["", [Validators.required]],
    }, { validator: this.ServiceProvider.comparePasswords });
  }

  get formPerfilControls() {
    return this.formPerfil.controls;
  }

  get formPasswordControls() {
    return this.formPassword.controls;
  }

  async actualizarPerfil() {
    if (this.formPerfil.valid) {
      this.ServiceProvider.preloaderOn();
      try {
        const datosCorreo = {
          idUsuario: this.idUsuario,
          correo: this.formPerfilControls.correo.value,
        };

        const buscarCorreoExistente = <any[]> await this.ServiceProvider.post(WEBSERVICE.BUSCAR_CORREO_EXISTENTE_USUARIO, datosCorreo);

        /* Verifica que el correo no se encuentre registrado más de una vez en la BD */
        if (!buscarCorreoExistente.length) {
          const datosUsuario = {
            idUsuario: this.idUsuario,
            nombres: this.formPerfilControls.nombres.value,
            apellidos: this.formPerfilControls.apellidos.value,
            cargo: this.formPerfilControls.cargo.value,
            correo: this.formPerfilControls.correo.value,
          };

          await this.ServiceProvider.post(WEBSERVICE.UPDATE_PERFIL_USUARIO, datosUsuario);

          this.ServiceProvider.openToast("success", `Usuario ${MESSAGES.actualizadoExito.toLowerCase()}`);
        } else {
          this.ServiceProvider.openToast("error", MESSAGES.errorCorreoExiste);
        }
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }
    } else {
      this.ServiceProvider.validateAllFormFields(this.formPerfil);
    }
  }

  async actualizarPassword() {
    if (this.formPassword.valid) {
      this.ServiceProvider.preloaderOn();
      const datosPassword = {
        id: this.idUsuario,
        passwordActual: this.formPasswordControls.passwordActual.value,
        passwordNuevo: this.formPasswordControls.password.value
      };

      try {
        const getUpdatePasswordActual = await this.ServiceProvider.post(WEBSERVICE.GET_PASSWORD_ACTUAL, datosPassword);

        if (getUpdatePasswordActual instanceof Object) {
          this.ServiceProvider.openToast("success", MESSAGES.passwordActualizado);
        } else {
          this.ServiceProvider.openToast("error", getUpdatePasswordActual);
        }

      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }
    } else {
      this.ServiceProvider.validateAllFormFields(this.formPassword);
    }
  }
}
