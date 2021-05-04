import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { LucyComponent } from './lucy/lucy.component';
import { DinpComponent } from './dinp/dinp.component';
import { GeneralComponent } from './general/general.component';
import { ReportesComponent } from './reportes/reportes.component';
import { FaltaEnergiaComponent } from './falta-energia/falta-energia.component';
import { CopiaFacturaComponent } from './copia-factura/copia-factura.component';
import { SeguimientoComponent } from './seguimiento/seguimiento.component';
import { ContactComponent } from './contact/contact.component';


const routes: Routes = [{
  path: '',
  data: {
    title: 'Monitoreo'
  },
  children: [
    {
      path: "",
      redirectTo: "general",
      pathMatch: "full"
    },
    {
      path: 'lucy',
      component: LucyComponent,
      data: {
        title: 'Lucy'
      }
    },
    {
      path: 'dinp',
      component: DinpComponent,
      data: {
        title: 'Difusión Interrupciones No Programadas'
      }
    },
    {
      path: 'general',
      component: GeneralComponent,
      data: {
        title: 'General'
      }
    },
    {
      path: 'reportes',
      component: ReportesComponent,
      data: {
        title: 'Reportes'
      }
    },
    {
      path: 'falta-energia',
      component: FaltaEnergiaComponent,
      data: {
        title: 'Falta de energía'
      }
    },
    {
      path: 'copia-factura',
      component: CopiaFacturaComponent,
      data: {
        title: 'Copia de factura'
      }
    },
    {
      path: 'seguimiento',
      component: SeguimientoComponent,
      data: {
        title: 'Seguimiento'
      }
    },
    {
      path: 'contact-center',
      component: ContactComponent,
      data: {
        title: 'Contact center'
      }
    }
  ]
}];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class MonitoreoRoutingModule { }
