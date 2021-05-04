import { Component, OnInit, OnDestroy, Input, EventEmitter, Output } from "@angular/core";
import { ServiceProvider } from "../../config/services";
import { WEBSERVICE } from '../../config/webservices';
import { Subscription, interval } from 'rxjs';
import { JwtHelperService } from '@auth0/angular-jwt';
import { AuthenticationService } from '../../config/authentication.service';
import { Router, NavigationEnd } from '@angular/router';
import { Subject } from "rxjs";


@Component({
  selector: "cuota-mensajes-usuario",
  templateUrl: "cuotaMensajesUsuario.component.html"
})
export class CuotaMensajesUsuarioComponent implements OnInit, OnDestroy {
  bolsaDineroMensajes: number = 0;
  isLoading: boolean = false;
  subscripcionCuota: Subscription;
  totalCuotaMensajesUsuario: number = 0;
  cantidaMensajesUsuarioEnviados: number = 0;
  porcentajeMensajesEnviados: number = 0;
  @Input() inputMensajesEnviados: Subject<void> = new Subject<void>();
  @Output() onCuotaMensajesInit: EventEmitter<number> = new EventEmitter<number>();


  constructor(
    private ServiceProvider: ServiceProvider,
    private jwtHelper: JwtHelperService,
    private authenticationService: AuthenticationService,
    private router: Router) { }

  ngOnInit() {

    this.getCuotaMensajes();
    this.inputMensajesEnviados.subscribe(() => this.getCuotaMensajes());
  }

  ngOnDestroy() {
    /* Se quita la subscripción al interval cuando se destruye el componente para evitar que la función siga siendo llamada */
    // if (this.subscripcionCuota) {
    //   this.subscripcionCuota.unsubscribe();
    // }
  }


  async getCuotaMensajes() {
    const idUsuario: string = this.jwtHelper.decodeToken(this.authenticationService.getJwtToken())?.data._id.$oid;

    try {
      const cuotaMensajesUsuario: any = await this.ServiceProvider.post(WEBSERVICE.GET_CUOTA_MENSAJES_USUARIO, { idUsuario });

      this.totalCuotaMensajesUsuario = cuotaMensajesUsuario.totalInicialMensajes;
      this.cantidaMensajesUsuarioEnviados = this.totalCuotaMensajesUsuario - cuotaMensajesUsuario.cantidadMensajesUsuario;
      this.porcentajeMensajesEnviados = Math.floor(this.cantidaMensajesUsuarioEnviados / this.totalCuotaMensajesUsuario * 100);

      this.onCuotaMensajesInit.emit(this.porcentajeMensajesEnviados);
    } catch (error) {
      console.error(error);
    }
  }

}
