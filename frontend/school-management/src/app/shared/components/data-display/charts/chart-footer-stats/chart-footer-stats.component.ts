import { Component, Input } from '@angular/core';
import { ChartStatItemConfig } from './chart-stat-item-config.model';

@Component({
  selector: 'app-chart-footer-stats',
  standalone: true,
  templateUrl: './chart-footer-stats.component.html',
  styleUrl: './chart-footer-stats.component.scss'
})
export class ChartFooterStatsComponent {
  @Input() items: ChartStatItemConfig[] = [];

}
