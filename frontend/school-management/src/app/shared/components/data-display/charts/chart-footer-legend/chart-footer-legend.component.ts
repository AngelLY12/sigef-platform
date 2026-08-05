import { Component, Input } from '@angular/core';
import { ChartLegendItemConfig } from './chart-legend-item-config.model';

@Component({
  selector: 'app-chart-footer-legend',
  standalone: true,
  templateUrl: './chart-footer-legend.component.html',
  styleUrl: './chart-footer-legend.component.scss'
})
export class ChartFooterLegendComponent {
  @Input() items: ChartLegendItemConfig[] = [];

}
