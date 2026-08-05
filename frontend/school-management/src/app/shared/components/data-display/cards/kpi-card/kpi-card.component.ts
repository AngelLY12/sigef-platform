import { Component, Input } from '@angular/core';
import { KpiCardConfig } from './kpi-card-config.model';


@Component({
  selector: 'app-kpi-card',
  standalone: true,
  templateUrl: './kpi-card.component.html',
  styleUrl: './kpi-card.component.scss'
})
export class KpiCardComponent {
  @Input({ required: true }) item!: KpiCardConfig;

}
