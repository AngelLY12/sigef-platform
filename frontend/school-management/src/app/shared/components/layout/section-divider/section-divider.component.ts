import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-section-divider',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './section-divider.component.html',
  styleUrl: './section-divider.component.scss'
})
export class SectionDividerComponent {
  @Input() title?: string;

}
