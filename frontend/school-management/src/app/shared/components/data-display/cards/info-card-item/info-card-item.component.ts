import { Component, Input } from '@angular/core';
import { InfoCardItemConfig } from './info-card-item-config.model';

@Component({
  selector: 'app-info-card-item',
  templateUrl: './info-card-item.component.html',
  styleUrl: './info-card-item.component.scss'
})
export class InfoCardItemComponent {
  @Input({ required: true })
  item!: InfoCardItemConfig;
  @Input() customContent = false;

}
