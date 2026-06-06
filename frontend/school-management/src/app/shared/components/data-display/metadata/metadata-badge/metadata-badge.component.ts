import { Component, Input } from '@angular/core';
import { statusType } from '../../../../../core/models/types/status-type.type';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-metadata-badge',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './metadata-badge.component.html',
  styleUrl: './metadata-badge.component.scss',
})
export class MetadataBadgeComponent {
  @Input() type: statusType = 'info';
  @Input() text!: string;
}
