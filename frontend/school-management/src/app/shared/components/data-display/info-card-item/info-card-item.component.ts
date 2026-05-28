import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-info-card-item',
  imports: [CommonModule],
  templateUrl: './info-card-item.component.html',
  styleUrl: './info-card-item.component.scss'
})
export class InfoCardItemComponent {
  @Input() label: string = '';
  @Input() value: any;
  @Input() icon: string = '';

}
