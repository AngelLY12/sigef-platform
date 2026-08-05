import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

export interface DemographicItem {
  label: string;
  value: number | string;
  type: 'success' | 'warning' | 'danger' | 'primary';
  suffix?: string;
}


@Component({
  selector: 'app-demographics-grid',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './demographics-grid.component.html',
  styleUrl: './demographics-grid.component.scss'
})
export class DemographicsGridComponent {
  @Input() items: DemographicItem[] = [];

}
