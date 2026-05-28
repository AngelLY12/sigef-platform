import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

export interface AlertItem {
  icon: string;
  title: string;
  count: number | null;
  type?: 'warning' | 'danger';
}


@Component({
  selector: 'app-alerts-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './alerts-list.component.html',
  styleUrl: './alerts-list.component.scss'
})
export class AlertsListComponent {
  @Input() alerts: AlertItem[] = [];

}
