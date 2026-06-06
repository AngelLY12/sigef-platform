import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-metadata-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './metadata-list.component.html',
  styleUrl: './metadata-list.component.scss'
})
export class MetadataListComponent {
  @Input({ required: true }) title!: string;
  @Input({ required: true }) changes!: string[];
}
