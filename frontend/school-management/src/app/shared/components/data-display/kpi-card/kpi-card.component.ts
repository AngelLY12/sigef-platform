import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

export type KpiIconType = 'total' | 'active' | 'students' | 'alerts' | 'inactive' | 'growth' | 'roles' | 'academic';

@Component({
  selector: 'app-kpi-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './kpi-card.component.html',
  styleUrl: './kpi-card.component.scss'
})
export class KpiCardComponent {
  @Input() icon!: string;
  @Input() iconType!: KpiIconType;
  @Input() label!: string;
  @Input() value!: any;
  @Input() trend?: { icon: string; text: string };
  @Input() percentage?: number;
  @Input() subtext?: string;
  @Input() size: 'normal' | 'small' = 'normal';

}
