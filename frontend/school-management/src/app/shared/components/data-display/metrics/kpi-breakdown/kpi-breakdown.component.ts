import { Component, Input } from '@angular/core';
import { KpiBreakdownItem } from './kpi-breakdown-item.model';

@Component({
  selector: 'app-kpi-breakdown',
  standalone: true,
  templateUrl: './kpi-breakdown.component.html',
  styleUrl: './kpi-breakdown.component.scss'
})
export class KpiBreakdownComponent {
  @Input() items: KpiBreakdownItem[] = [];
}
