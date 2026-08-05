import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { ChartConfiguration, ChartData, ChartTypeRegistry } from 'chart.js';
import { BaseChartDirective } from 'ng2-charts';

@Component({
  selector: 'app-chart-card',
  imports: [CommonModule, BaseChartDirective],
  templateUrl: './chart-card.component.html',
  styleUrl: './chart-card.component.scss'
})
export class ChartCardComponent {
  @Input() title!: string;
  @Input() data!: ChartData;
  @Input() options!: ChartConfiguration['options'];
  @Input() type!: keyof ChartTypeRegistry;
  @Input() size: 'normal' | 'small' | 'line-chart' = 'normal';
  @Input() fullWidth = false;

}
