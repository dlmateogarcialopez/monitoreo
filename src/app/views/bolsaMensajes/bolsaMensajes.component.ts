import { Component, OnInit, Input, ViewChild, OnDestroy } from "@angular/core";
import { ServiceProvider } from "../../config/services";
import { WEBSERVICE } from '../../config/webservices';
import { Router, NavigationStart, NavigationEnd, NavigationError } from '@angular/router';
import { Subscription, interval } from 'rxjs';
import { takeUntil, takeWhile } from 'rxjs/operators';
import { JwtHelperService } from '@auth0/angular-jwt';
import { AuthenticationService } from '../../config/authentication.service';
import { Users, BolsaMensajes } from '../../config/interfaces';

@Component({
  selector: "bolsa-mensajes",
  templateUrl: "bolsaMensajes.component.html"
})
export class BolsaMensajesComponent implements OnInit, OnDestroy {
  bolsaDineroMensajes: number = 0;
  isLoading: boolean = false;
  subscripcionBolsa: Subscription;
  datosUsuarioJWT: Users = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken())?.data;
  /** Nombre de la bolsa de mensajes asignada al usuario */
  bolsaUsuario: string = this.datosUsuarioJWT.permisos.selectBolsa

  constructor(
    private authenticationService: AuthenticationService,
    private ServiceProvider: ServiceProvider,
    public jwtHelper: JwtHelperService,
  ) { }

  ngOnInit() {
    this.getBolsaMensajes(this.bolsaUsuario);

    /* Obtener el valor actual de la bolsa de mensajes cada 10 minutos */
    this.subscripcionBolsa = interval(1000 * 60 * 10).subscribe(() => this.getBolsaMensajes(this.bolsaUsuario));
  }

  ngOnDestroy() {
    /* Se quita la subscripción al interval cuando se destruye el componente para evitar que la función siga siendo llamada */
    if (this.subscripcionBolsa) {
      this.subscripcionBolsa.unsubscribe();
    }
  }

  /** Retorna los datos correspondientes a la bolsa de mensajes de cada usuario */
  async getBolsaMensajes(nombreBolsa: string) {
    this.isLoading = true;
    try {
      const bolsaMensajes: BolsaMensajes = await this.ServiceProvider.post(WEBSERVICE.GET_BOLSA_MENSAJES_USUARIO, { nombreBolsa });
      // console.log(bolsaMensajes);
      this.bolsaDineroMensajes = bolsaMensajes.valor_actual;
    } catch (error) {
      console.error(error);
    } finally {
      this.isLoading = false;
    }
  }

}
