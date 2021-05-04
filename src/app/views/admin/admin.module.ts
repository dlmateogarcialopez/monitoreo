import { NgModule } from '@angular/core';

import { ListadoUsuariosComponent } from './usuarios/listadoUsuarios.component'
import { AdminRoutingModule } from './admin-routing.module';
import { ReactiveFormsModule, FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatTableModule } from '@angular/material/table';
import { MatPaginatorModule, MatPaginatorIntl } from '@angular/material/paginator';
import { MatPaginatorIntlEsp } from '../../../matPaginatorIntl';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { BolsaAdminComponent } from './bolsa/bolsaAdmin.component';
import { AdminNavBarComponent } from './adminNavBar/adminNavBar.component';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { MatInputModule } from '@angular/material/input';
import { AddUsuarioComponent } from './usuarios/addUsuario.component';
import { MatStepperModule } from '@angular/material/stepper';
import { MatSortModule } from '@angular/material/sort';
import { BolsaMensajesComponent } from '../bolsaMensajes/bolsaMensajes.component';
import { AppModule } from '../../app.module';
import { MatIconModule } from '@angular/material/icon';



@NgModule({
  imports: [
    CommonModule,
    AdminRoutingModule,
    ReactiveFormsModule,
    FormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatStepperModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatButtonModule,
    MatCheckboxModule,
    NgbModule,
    MatIconModule,
    // AppModule
  ],
  providers: [
    { provide: MatPaginatorIntl, useClass: MatPaginatorIntlEsp },
  ],
  // exports:[BolsaMensajesComponent],
  declarations: [
    ListadoUsuariosComponent,
    AddUsuarioComponent,
    BolsaAdminComponent,
    AdminNavBarComponent,
    // BolsaMensajesComponent
  ]
})
export class AdminModule { }
