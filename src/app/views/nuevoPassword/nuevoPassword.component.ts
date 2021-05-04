import { Component, OnInit, Input, ViewChild } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray } from "@angular/forms";
import { HttpClient, HttpHeaders } from "@angular/common/http";
import { ServiceProvider } from "../../config/services";
import { MESSAGES } from '../../config/messages';
import { WEBSERVICE } from "../../config/webservices";
import { Router, NavigationExtras, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'nuevo_password',
  templateUrl: 'nuevoPassword.component.html'
})

export class NuevoPasswordComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  formCambiarPassword: FormGroup;
  hidePassword: boolean = true;
  hideConfirmPassword: boolean = true;
  jwtToken: string = "";
  idUsuario: string = "";
  correoUsuario: string = "";
  navigationExtras: NavigationExtras = {};

  constructor(
    private fb: FormBuilder,
    private ServiceProvider: ServiceProvider,
    private http: HttpClient,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit() {
    this.ServiceProvider.preloaderOn();
    this.ServiceProvider.setTituloPestana("Cambiar contraseña");
    this.setNuevoPasswordControls();
    this.verifyJwtToken();
  }

  async verifyJwtToken() {
    this.route.params.subscribe(params => {
      this.jwtToken = params["tokenUsuario"];
    });

    try {
      const tokenData = await this.ServiceProvider.post(WEBSERVICE.VERIFY_JWT_TOKEN, { jwtToken: this.jwtToken });

      if (tokenData) {
        this.idUsuario = tokenData[0]._id.$oid;
        this.correoUsuario = tokenData[0].correo;
      } else {
        this.navigationExtras.state = {
          isLinkInvalidFromNuevoPassword: true
        };

        this.router.navigate(["/recuperarCuenta"], this.navigationExtras);
      }
    } catch (error) {
      this.ServiceProvider.openPopup("error", error);
    } finally {
      this.ServiceProvider.preloaderOff();
    }

  }

  setNuevoPasswordControls() {
    this.formCambiarPassword = this.fb.group({
      password: this.fb.control("", [Validators.required, Validators.minLength(6)]),
      confirmarPassword: this.fb.control("", [Validators.required]),
    }, { validator: this.ServiceProvider.comparePasswords });
  }

  get password() {
    return this.formCambiarPassword.get("password");
  }
  get confirmarPassword() {
    return this.formCambiarPassword.get("confirmarPassword");
  }

  async changePassword() {
    if (this.formCambiarPassword.valid) {
      this.ServiceProvider.preloaderOn();
      const datosUsuario = {
        id: this.idUsuario,
        password: this.password.value
      };

      try {
        const resetPassword = await this.ServiceProvider.post(WEBSERVICE.RESET_PASSWORD_USUARIO, datosUsuario);

        this.navigationExtras.state = {
          isFromNuevoPassword: true
        };
        this.router.navigate(["/login"], this.navigationExtras);
        this.sendCorreoPasswordRestaurado();
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }
    } else {
      this.ServiceProvider.validateAllFormFields(this.formCambiarPassword);
    }
  }

  async sendCorreoPasswordRestaurado() {
    try {
      const correoPasswordRestaurado = await this.ServiceProvider.post(WEBSERVICE.ENVIAR_CORREO_PASSWORD_RESTAURADO, { correo: this.correoUsuario });

      if (correoPasswordRestaurado !== "ok") {
        console.error(correoPasswordRestaurado);
      }
    } catch (error) {
      console.error(error);
      // this.ServiceProvider.openPopup("error", error);
    }
  }
}
