import { NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { CommonModule, registerLocaleData } from '@angular/common';
import localeCo from '@angular/common/locales/es-CO';
import { NgxDaterangepickerMd } from 'ngx-daterangepicker-material';
import { ChartsModule } from 'ng2-charts';
import { SatDatepickerModule, SatNativeDateModule } from 'saturn-datepicker';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule, DateAdapter, MAT_DATE_LOCALE, MAT_DATE_FORMATS } from '@angular/material/core';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import {
  DateAdapter as RangeDateAdapter,
  MAT_DATE_FORMATS as RANGE_MAT_DATE_FORMATS,
  MAT_DATE_LOCALE as RANGE_MAT_DATE_LOCALE,
} from 'saturn-datepicker';

//components
import { LucyComponent } from './lucy/lucy.component';
import { DinpComponent } from './dinp/dinp.component';
import { GeneralComponent } from './general/general.component';
import { ReportesComponent } from './reportes/reportes.component';

//Monitoreo routing
import { MonitoreoRoutingModule } from './monitoreo-routing.module';
import { FaltaEnergiaComponent } from './falta-energia/falta-energia.component';
import { CopiaFacturaComponent } from './copia-factura/copia-factura.component';
import { SeguimientoComponent } from './seguimiento/seguimiento.component';
import { ContactComponent } from './contact/contact.component';
import { MomentDateAdapter } from '@angular/material-moment-adapter';
import { NgbModule, NgbPopoverModule } from '@ng-bootstrap/ng-bootstrap';
import { LayoutModule } from '@angular/cdk/layout';
import { AlertaSinResultadosComponent } from './alertSinResultados/alertaSinResultados.component';
import { MatButtonModule } from '@angular/material/button';
import { MatSortModule } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { MatPaginatorIntl, MatPaginatorModule } from '@angular/material/paginator';
import { MatPaginatorIntlEsp } from '../../../matPaginatorIntl';
import { MatIconModule } from '@angular/material/icon';
registerLocaleData(localeCo, 'es-CO');


// export const MOMENTJS_DATE_FORMAT = {
//   parse: {
//     dateInput: 'DD.MM.YYYY',
//   },
//   display: {
//     dateInput: 'dddd, DD.MM.YYYY',
//     monthYearLabel: 'MMM YYYY',
//     dateA11yLabel: 'DD.MM.YYYY',
//     monthYearA11yLabel: 'MMMM YYYY',
//   },
// };

export const MOMENTJS_RANGE_DATE_FORMAT = {
  parse: {
    dateInput: 'll',
  },
  display: {
    dateInput: 'll',
    monthYearLabel: 'MMM YYYY',
    dateA11yLabel: 'MMM DD, YYYY',
    monthYearA11yLabel: 'MMMM YYYY',
  },
};

@NgModule({
  declarations: [
    LucyComponent,
    DinpComponent,
    GeneralComponent,
    ReportesComponent,
    FaltaEnergiaComponent,
    CopiaFacturaComponent,
    SeguimientoComponent,
    ContactComponent,
    AlertaSinResultadosComponent,
  ],
  imports: [
    FormsModule,
    CommonModule,
    MonitoreoRoutingModule,
    ChartsModule,
    SatDatepickerModule,
    SatNativeDateModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    ReactiveFormsModule,
    MatSelectModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatIconModule,
    NgxDaterangepickerMd.forRoot(),
    NgbPopoverModule,
    LayoutModule,
    NgbModule
  ],
  providers: [
    { provide: RangeDateAdapter, useClass: MomentDateAdapter, deps: [RANGE_MAT_DATE_LOCALE] },
    { provide: RANGE_MAT_DATE_FORMATS, useValue: MOMENTJS_RANGE_DATE_FORMAT },
    { provide: DateAdapter, useClass: MomentDateAdapter, deps: [MAT_DATE_LOCALE] },
    { provide: RANGE_MAT_DATE_LOCALE, useValue: "en-GB" },
    { provide: MatPaginatorIntl, useClass: MatPaginatorIntlEsp },
    // { provide: MAT_DATE_FORMATS, useValue: MOMENTJS_DATE_FORMAT },
  ]
})
export class MonitoreoModule { }
