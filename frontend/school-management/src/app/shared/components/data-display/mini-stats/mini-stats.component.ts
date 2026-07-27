import { Component, Input } from '@angular/core';
import { MiniStatItem } from '../../../../core/models/domain/cards/mini-stat-item.model';

@Component({
  selector: 'app-mini-stats',
  standalone: true,
  templateUrl: './mini-stats.component.html',
  styleUrl: './mini-stats.component.scss'
})
export class MiniStatsComponent {
  @Input() items: MiniStatItem[] = [];
}
