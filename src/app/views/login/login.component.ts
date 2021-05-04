import { Component, OnInit, Input, ViewChild } from "@angular/core";
import { FormBuilder, Validators, FormGroup, FormArray } from "@angular/forms";
import { HttpClient } from "@angular/common/http";
import { ServiceProvider } from "../../config/services";
import { MESSAGES } from '../../config/messages';
import { WEBSERVICE } from "../../config/webservices";
import { Router, ActivatedRoute, NavigationEnd } from '@angular/router';
import { AuthenticationService } from '../../config/authentication.service';

@Component({
  selector: 'login',
  templateUrl: 'login.component.html'
})

export class LoginComponent implements OnInit {
  MESSAGES: object = MESSAGES;
  formLogin: FormGroup;
  isEmailRegistered: boolean = true;
  msjCorreoPasswordIncorrectos: string = "";
  isFromNuevoPassword: boolean = false;
  /** Obtiene la URL de retorno definida en el parametro `returnUrl` o muestra la raíz `/`. Útil para redirigir al usuario a la última URL que visitó. */
  returnUrl: string;

  constructor(
    private fb: FormBuilder,
    private ServiceProvider: ServiceProvider,
    private http: HttpClient,
    private router: Router,
    private route: ActivatedRoute,
    private authenticationService: AuthenticationService
  ) {
    const navegacion = this.router.getCurrentNavigation();
    const estadoNavegacion = navegacion.extras.state;
    /* estadoNavegacion.isFromNuevoPassword proviene de NuevoPasswordComponent y se utiliza para mostrar en el template mensaje de éxito al actualizar contraseña */
    if (estadoNavegacion && estadoNavegacion.isFromNuevoPassword) {
      this.isFromNuevoPassword = true;
    }
  }

  ngOnInit() {
    this.ServiceProvider.setTituloPestana("Sistema de Gestión de la Comunicación Bidireccional");
    this.setLoginControls();
    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/';
  }

  setLoginControls() {
    this.formLogin = this.fb.group({
      correo: this.fb.control("", [Validators.required, Validators.email]),
      password: this.fb.control("", [Validators.required])
    });
  }

  get formFields() {
    return this.formLogin.controls;
  }



  async login() {
    if (this.formLogin.valid) {
      this.ServiceProvider.preloaderOn();
      this.isFromNuevoPassword = false;
      const datosUsuario = {
        correo: this.formFields.correo.value,
        password: this.formFields.password.value
      };

      try {
        const datosLogin = await this.authenticationService.login(datosUsuario);

        if (datosLogin instanceof Object) {
          this.router.navigate(["/"]);
          // this.router.navigateByUrl(this.returnUrl);
        } else {
          this.isEmailRegistered = false;
          this.msjCorreoPasswordIncorrectos = datosLogin;
        }
      } catch (error) {
        this.ServiceProvider.openPopup("error", error);
      } finally {
        this.ServiceProvider.preloaderOff();
      }

    } else {
      this.ServiceProvider.validateAllFormFields(this.formLogin);
    }
  }
}
