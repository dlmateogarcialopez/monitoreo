import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NgModule } from '@angular/core';
import { BsDropdownModule } from 'ngx-bootstrap/dropdown';

import { ListadoEnviosComponent } from "./listadoEnvios.component";
import { EnviarDesdeArchivoComponent } from './enviarDesdeArchivo/enviarDesdeArchivo.component';
import { EnviarDesdeBdComponent } from './enviarDesdeBd/enviarDesdeBd.component';
import { DodRoutingModule } from "./dod-routing.module";
import { DragDropFileUploadDirective } from '../../drag-drop-file-upload.directive';
import { MatStepperModule } from '@angular/material/stepper';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatButtonModule } from '@angular/material/button';
import { MatRadioModule } from '@angular/material/radio';
import { MatSelectModule } from '@angular/material/select';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatPaginatorModule, MatPaginatorIntl } from '@angular/material/paginator';
import { MatPaginatorIntlEsp } from '../../../matPaginatorIntl';
import { NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { MatSortModule } from '@angular/material/sort';
import { DetalleEnvioDodComponent } from './detalleEnvio/detalleEnvioDod.component';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { ModalModule, CollapseModule } from 'ngx-bootstrap';
import { MentionModule } from 'angular-mentions';
import { ContenteditableValueAccessorDirective } from './contenteditable.directive';
import { BypassHtmlPipe } from '../../config/pipes/bypassHtml.pipe';
import { TruncateTextPipe } from '../../config/pipes/truncateText.pipe';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatDialogModule } from '@angular/material/dialog';
import { DialogBoxComponent } from './dialogBox/dialogBox.component';
import { CardsInfoMensajesComponent } from './cardsInfoMensajes/cardsInfoMensajes.component';
import { TablaMensajesComponent } from './tablaMensajes/tablaMensajes.component';
import { AlertasMensajesComponent } from './alertasMensajes/alertasMensajes.component';
import { ModalMensajesEnviadosComponent } from './modalMensajesEnviados/modalMensajesEnviados.component';


@NgModule({
  imports: [
    CommonModule,
    DodRoutingModule,
    ReactiveFormsModule,
    FormsModule,
    BsDropdownModule.forRoot(),
    MatInputModule,
    MatFormFieldModule,
    MatButtonModule,
    MatRadioModule,
    MatStepperModule,
    MatSelectModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatCheckboxModule,
    MatDialogModule,
    ModalModule.forRoot(),
    NgbModule,
    MentionModule,
    MatAutocompleteModule,
    MatExpansionModule,
    CollapseModule
  ],
  providers: [
    { provide: MatPaginatorIntl, useClass: MatPaginatorIntlEsp },
    BypassHtmlPipe
  ],
  declarations: [
    ListadoEnviosComponent,
    EnviarDesdeArchivoComponent,
    EnviarDesdeBdComponent,
    DetalleEnvioDodComponent,
    DragDropFileUploadDirective,
    ContenteditableValueAccessorDirective,
    BypassHtmlPipe,
    TruncateTextPipe,
    DialogBoxComponent,
    CardsInfoMensajesComponent,
    AlertasMensajesComponent,
    ModalMensajesEnviadosComponent,
    TablaMensajesComponent
  ],
  entryComponents: [
    DialogBoxComponent
  ]
})
export class DodModule { }
