import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-metadata-row',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './metadata-row.component.html',
  styleUrl: './metadata-row.component.scss'
})
export class MetadataRowComponent {
  @Input({ required: true }) label!: string;
  @Input({ required: true }) value!: any;

}
