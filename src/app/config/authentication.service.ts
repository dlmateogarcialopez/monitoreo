import { Injectable } from "@angular/core";
import { HttpClient } from "@angular/common/http";
import { BehaviorSubject, Observable, of } from "rxjs";
import { map, catchError, tap, finalize } from "rxjs/operators";
import { URL, WEBSERVICE } from "./webservices";
import { ServiceProvider } from "./services";
import { JwtHelperService } from '@auth0/angular-jwt';
import { Router } from '@angular/router';


@Injectable({ providedIn: "root" })
export class AuthenticationService {
  private usuarioActualSubject: BehaviorSubject<any>;
  public usuarioActual: Observable<any>;
  private readonly CURRENT_USER: string = "currentUser";
  private readonly JWT_TOKEN: string = "jwtToken";

  constructor(
    private http: HttpClient,
    private ServiceProvider: ServiceProvider,
    public jwtHelper: JwtHelperService,
    private router: Router,
  ) {
    this.usuarioActualSubject = new BehaviorSubject<any>(JSON.parse(localStorage.getItem(this.CURRENT_USER)));
    this.usuarioActual = this.usuarioActualSubject.asObservable();
  }

  public get usuarioActualValue() {
    return this.usuarioActualSubject.value;
  }

  login(datosLogin: object) {
    return this.http.post<any>(URL + WEBSERVICE.LOGIN, datosLogin, { withCredentials: true })
      .pipe(
        map(user => {
          /* Ingreso exitoso si hay un token en la respuesta */
          if (user && user.datosUsuario && user.jwtToken) {
            /* Guardar detalles del usuario y token */
            localStorage.setItem(this.CURRENT_USER, JSON.stringify(user.datosUsuario));
            this.storeJwtToken(user.jwtToken);
            this.usuarioActualSubject.next(user.datosUsuario);
          }
          return user;
        }),
        catchError(this.ServiceProvider.handleError)
      ).toPromise();
  }

  logout(isTokenExpirado?: boolean) {
    if (isTokenExpirado) {
      this.ServiceProvider.openPopup("advertencia", "La sesión ha finalizado. Por favor, ingresa nuevamente.");
    }

    const idUsuario = JSON.parse(this.getCurrentUser())._id.$oid;
    return this.http.post<any>(URL + WEBSERVICE.LOGOUT, { idUsuario })
      .pipe(
        map(user => {
          /* Quitar el usuario del localStorage */
          localStorage.clear();
          this.usuarioActualSubject.next(null);
        }),
        catchError(this.ServiceProvider.handleError),
        finalize(() => {
          this.router.navigate(["/login"]);
          this.ServiceProvider.preloaderOff();
        })
      ).toPromise();
  }

  public getDatosUsuario() {
    this.ServiceProvider.preloaderOn();
    const idUsuario: string = this.jwtHelper.decodeToken(this.getJwtToken())?.data._id.$oid;
    return this.http.post(URL + WEBSERVICE.GET_DATOS_USUARIO, { idUsuario })
      .pipe(
        catchError(this.ServiceProvider.handleError),
        finalize(() => this.ServiceProvider.preloaderOff())
      );
  }

  getCurrentUser() {
    return localStorage.getItem(this.CURRENT_USER);
  }

  getJwtToken(): string {
    return localStorage.getItem(this.JWT_TOKEN);
  }

  private storeJwtToken(jwtToken: string) {
    localStorage.setItem(this.JWT_TOKEN, jwtToken);
  }

  setNuevoJwtToken() {
    const idUsuario: string = this.jwtHelper.decodeToken(this.getJwtToken())?.data._id.$oid;
    return this.http.post<any>(URL + WEBSERVICE.SET_JWT_TOKEN, {
      withCredentials: true,
      idUsuario
    }).pipe(tap((jwtToken: string) => {
      // console.log("setNuevoJwtToken", jwtToken);
      this.storeJwtToken(jwtToken);
    }));
  }
}