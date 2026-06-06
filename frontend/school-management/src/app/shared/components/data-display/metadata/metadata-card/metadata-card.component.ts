import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-metadata-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './metadata-card.component.html',
  styleUrl: './metadata-card.component.scss'
})
export class MetadataCardComponent {
  @Input({ required: true }) title!: string;
}
