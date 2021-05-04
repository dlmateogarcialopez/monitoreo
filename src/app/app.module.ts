import { BrowserModule } from "@angular/platform-browser";
import { NgModule } from "@angular/core";
import {
  LocationStrategy,
  HashLocationStrategy,
  CommonModule
} from "@angular/common";
import { BrowserAnimationsModule } from "@angular/platform-browser/animations";
import { FormsModule, ReactiveFormsModule } from "@angular/forms";
import { PerfectScrollbarModule } from "ngx-perfect-scrollbar";
import { PERFECT_SCROLLBAR_CONFIG } from "ngx-perfect-scrollbar";
import { PerfectScrollbarConfigInterface } from "ngx-perfect-scrollbar";


const DEFAULT_PERFECT_SCROLLBAR_CONFIG: PerfectScrollbarConfigInterface = {
  suppressScrollX: true
};

import { AppComponent } from "./app.component";

// Import containers
import { DefaultLayoutComponent } from "./containers";

import { P404Component } from "./views/error/404.component";
import { LoginComponent } from "./views/login/login.component";
import { RecuperarCuentaComponent } from "./views/recuperarCuenta/recuperarCuenta.component";
import { NuevoPasswordComponent } from './views/nuevoPassword/nuevoPassword.component';
import { ToastsContainer } from "./toast.component";

const APP_CONTAINERS = [DefaultLayoutComponent];

import {
  AppAsideModule,
  AppBreadcrumbModule,
  AppHeaderModule,
  AppFooterModule,
  AppSidebarModule
} from "@coreui/angular";

// Import routing module
import { AppRoutingModule } from "./app.routing";

// Import 3rd party components
import { BsDropdownModule } from "ngx-bootstrap/dropdown";
import { TabsModule } from "ngx-bootstrap/tabs";
import { ChartsModule } from "ng2-charts";
import { ServiceProvider } from './config/services';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { ModalComponent } from './views/modal/modal.component';
import { TokenInterceptor } from './config/token.interceptor';
import { ModalModule } from 'ngx-bootstrap';
import { JwtHelperService, JWT_OPTIONS } from '@auth0/angular-jwt';
import { registerLocaleData } from '@angular/common';
import localeCo from '@angular/common/locales/es-CO';
import { BolsaMensajesComponent } from './views/bolsaMensajes/bolsaMensajes.component';
import { CuotaMensajesUsuarioComponent } from './views/cuotaMensajesUsuario/cuotaMensajesUsuario.component';
import { AdminModule } from './views/admin/admin.module';
import { LucyComponent } from './views/monitoreo/lucy/lucy.component';
import { DinpComponent } from './views/monitoreo/dinp/dinp.component';
import { GeneralComponent } from './views/monitoreo/general/general.component';
import { ReportesComponent } from './views/monitoreo/reportes/reportes.component';
import { MonitoreoModule } from './views/monitoreo/monitoreo.module';
import { MatMomentDateModule, MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS, MAT_MOMENT_DATE_FORMATS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_LOCALE, MAT_DATE_FORMATS } from '@angular/material/core';
import { MatButtonModule } from '@angular/material/button';
registerLocaleData(localeCo, 'es-CO');

@NgModule({
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    AppRoutingModule,
    AppAsideModule,
    AppBreadcrumbModule.forRoot(),
    AppFooterModule,
    AppHeaderModule,
    AppSidebarModule,
    PerfectScrollbarModule,
    BsDropdownModule.forRoot(),
    TabsModule.forRoot(),
    ChartsModule,
    HttpClientModule,
    FontAwesomeModule,
    NgbModule,
    ReactiveFormsModule,
    FormsModule,
    CommonModule,
    ModalModule.forRoot(),
    MatMomentDateModule,
    MatButtonModule,
    // AdminModule
    MonitoreoModule,
    // MatInputModule,
    // MatFormFieldModule,
    // MatStepperModule,
  ],
  declarations: [
    AppComponent,
    ...APP_CONTAINERS,
    P404Component,
    LoginComponent,
    ModalComponent,
    RecuperarCuentaComponent,
    NuevoPasswordComponent,
    ToastsContainer,
    BolsaMensajesComponent,
    CuotaMensajesUsuarioComponent
  ],
  // exports:[BolsaMensajesComponent],
  providers: [
    ServiceProvider,


    {
      provide: LocationStrategy,
      useClass: HashLocationStrategy
    },
    {
      provide: HTTP_INTERCEPTORS,
      useClass: TokenInterceptor,
      multi: true
    },
    { provide: JWT_OPTIONS, useValue: JWT_OPTIONS },
    JwtHelperService
  ],
  bootstrap: [AppComponent],
  entryComponents: [
    ModalComponent
  ]
})
export class AppModule { }
