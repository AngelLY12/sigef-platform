import { Component, Input } from '@angular/core';
import { KpiCardComponent } from '../../../../../shared/components/data-display/cards/kpi-card/kpi-card.component';
import { KpiCardConfig } from '../../../../../shared/components/data-display/cards/kpi-card/kpi-card-config.model';

@Component({
  selector: 'app-analysis-card',
  standalone: true,
  imports: [KpiCardComponent],
  templateUrl: './analysis-card.component.html',
  styleUrl: './analysis-card.component.scss'
})
export class AnalysisCardComponent {
  @Input() title: string = '';
  @Input() kpiCards: KpiCardConfig[] = [];
}
