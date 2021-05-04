import { Injectable } from "@angular/core";
import { HttpRequest, HttpHandler, HttpEvent, HttpInterceptor, HttpErrorResponse, HttpResponse, HttpClient } from "@angular/common/http";
import { AuthenticationService } from "./authentication.service";
import { Observable, throwError, BehaviorSubject, of } from "rxjs";
import { catchError, filter, take, switchMap } from "rxjs/operators";
import { ServiceProvider } from "./services";
import { WEBSERVICE, URL } from "./webservices";

@Injectable()
export class TokenInterceptor implements HttpInterceptor {
  private isRefreshing = false;
  private refreshTokenSubject: BehaviorSubject<any> = new BehaviorSubject<any>(null);
  isTokenExpirado: boolean = true;

  constructor(
    private http: HttpClient,
    public authenticationService: AuthenticationService,
    private ServiceProvider: ServiceProvider,
  ) { }

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    if (this.authenticationService.getJwtToken()) {
      request = this.addToken(request, this.authenticationService.getJwtToken());
    }

    return next.handle(request)
      .pipe(catchError((error) => {
        // let isBackendError: boolean = false;
        // const datosError = {
        //   tipo: "",
        //   mensajeError: ""
        // };

        // if (error.error instanceof ErrorEvent) {
        //   /* Se muestran errores del lado del cliente o errores de red. */
        //   console.error("Ocurrió un error:", error.error.message);
        //   datosError.tipo = "Error del cliente.";
        //   datosError.mensajeError = error.error.message;
        // } else {
        //   isBackendError = true;
        //   /* Desde el backend se retorna código y cuerpo del error */
        //   console.error("Código retornado por el backend:", error.status);
        //   console.error("Cuerpo del error:", error.error);

        //   datosError.tipo = "Error del servidor.";
        //   datosError.mensajeError = `Estado del error: ${error.status}. ${error.error.error.message}`;
        // }
        // // this.http.post(URL + WEBSERVICE.INSERT_ERROR_LOG, datosError).subscribe();
        // console.log(error.status);


        if (error instanceof HttpErrorResponse && error.status === 401) {
          return this.handle401Error(request, next);
        }
        return throwError(error);
      }));
  }

  private addToken(request: HttpRequest<any>, token: string) {
    return request.clone({
      withCredentials: true,
      setHeaders: {
        "Authorization": `Bearer ${token}`
      },
    });
  }

  private handle401Error(request: HttpRequest<any>, next: HttpHandler) {
    // console.log(this.refreshTokenSubject);
    if (!this.isRefreshing) {
      this.isRefreshing = true;
      this.refreshTokenSubject.next(null);

      return this.authenticationService.setNuevoJwtToken()
        .pipe(
          switchMap((jwtToken: string) => {
            // console.log("swtch", jwtToken);
            this.isRefreshing = false;
            this.refreshTokenSubject.next(jwtToken);

            // if (!jwtToken) {
            //   this.authenticationService.logout(this.isTokenExpirado);
            // }
            return next.handle(this.addToken(request, jwtToken));
          }),
          catchError((error: HttpErrorResponse) => {
            // console.log(error);
            this.authenticationService.logout(this.isTokenExpirado);
            return of(new HttpResponse({ statusText: error.statusText }));
          })
        );
    } else {
      // console.log("llega a isRefreshing");
      return this.refreshTokenSubject.pipe(
        filter(jwtToken => jwtToken != null),
        take(1),
        switchMap(jwtToken => {
          return next.handle(this.addToken(request, jwtToken));
        }),
        catchError((error: HttpErrorResponse) => {
          console.error(error.statusText);
          return of(new HttpResponse({ statusText: error.statusText }));
        })
      );
    }
  }
}