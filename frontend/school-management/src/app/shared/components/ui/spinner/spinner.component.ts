import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

export type SpinnerSize = 'sm' | 'md' | 'lg' | 'xl';
export type SpinnerColor = 'default' | 'primary' | 'success' | 'warning' | 'error';

@Component({
  selector: 'app-spinner',
  standalone:true,
  imports: [CommonModule],
  templateUrl: './spinner.component.html',
  styleUrl: './spinner.component.scss'
})
export class SpinnerComponent {
  @Input() loading: boolean = false;
  @Input() size: SpinnerSize = 'md';
  @Input() color: SpinnerColor = 'primary';
  @Input() fullScreen: boolean = false;

}
