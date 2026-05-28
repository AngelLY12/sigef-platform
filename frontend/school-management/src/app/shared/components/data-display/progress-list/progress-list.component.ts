import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

export interface ProgressItem {
  label: string;
  value: number;
  percentage: number;
  type: 'success' | 'warning' | 'danger';
}

@Component({
  selector: 'app-progress-list',
  imports: [CommonModule],
  templateUrl: './progress-list.component.html',
  styleUrl: './progress-list.component.scss'
})
export class ProgressListComponent {
  @Input() items: ProgressItem[] = [];

}
