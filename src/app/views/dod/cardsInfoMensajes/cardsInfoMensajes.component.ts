import { Component, OnInit, Input } from '@angular/core';

@Component({
  selector: 'cards-info-mensajes',
  templateUrl: './cardsInfoMensajes.component.html',
})
export class CardsInfoMensajesComponent implements OnInit {
  @Input() totalDefinitivoMensajes: number;
  @Input() valorMensajesAEnviar: number;
  @Input() totalDefinitivoCelulares: number;

  constructor() { }

  ngOnInit() {

  }

}
