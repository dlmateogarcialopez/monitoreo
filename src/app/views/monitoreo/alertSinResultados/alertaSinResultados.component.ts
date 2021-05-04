import { Component, OnInit } from '@angular/core';
import { MESSAGES } from '../../../config/messages';

@Component({
  selector: 'alerta-sin-resultados',
  templateUrl: './alertaSinResultados.component.html',
})
export class AlertaSinResultadosComponent implements OnInit {
  MESSAGES: any = MESSAGES;

  constructor() { }

  ngOnInit() { }

}
