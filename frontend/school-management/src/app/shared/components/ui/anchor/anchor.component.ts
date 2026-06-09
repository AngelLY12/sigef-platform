import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-anchor',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './anchor.component.html',
  styleUrl: './anchor.component.scss',
})
export class AnchorComponent {
  @Input() link: string = '';
  @Input() anchorMessage: string = '';
  @Input() external = false;
  @Input() target: '_self' | '_blank' = '_self';
  @Input() rel = 'noopener noreferrer';
}
