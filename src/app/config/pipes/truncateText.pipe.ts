import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'truncateText'
})
export class TruncateTextPipe implements PipeTransform {
  /** En template
    `
    <p [innerHTML]="description | truncateText:30:showAll"></p>
    <button (click)="triggerReadMore()" *ngIf="!showAll">Read More<button>`

    En componente:
    `public showAll: any = false;

    triggerReadMore() {
        this.showAll = true;
    }`
 */

  transform(text: string, length: number = 20, showAll: boolean = false, ellipsis: string = "...") {

    if (showAll) {
      return text;
    }

    if (text && text.split(" ").length > length) {
      return text.split(" ").splice(0, length).join(" ") + ellipsis;
    }
    return text;
  }

}