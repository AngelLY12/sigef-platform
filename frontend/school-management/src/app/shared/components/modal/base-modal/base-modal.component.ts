import { animate, style, transition, trigger } from '@angular/animations';
import { CommonModule } from '@angular/common';
import { Component, EventEmitter, inject, Input, Output } from '@angular/core';
import { LoadingOverlayComponent } from '../../feedback/loading-overlay/loading-overlay.component';

@Component({
  selector: 'app-base-modal',
  standalone: true,
  imports: [CommonModule, LoadingOverlayComponent],
  templateUrl: './base-modal.component.html',
  styleUrl: './base-modal.component.scss',
  animations: [
    trigger('fade', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('150ms ease-out', style({ opacity: 1 }))
      ]),
      transition(':leave', [
        animate('120ms ease-in', style({ opacity: 0 }))
      ])
    ]),
    trigger('scaleFade', [
      transition(':enter', [
        style({
          opacity: 0,
          transform: 'scale(0.95) translateY(10px)'
        }),
        animate(
          '220ms cubic-bezier(0.2, 0.8, 0.2, 1)',
          style({
            opacity: 1,
            transform: 'scale(1) translateY(0)'
          })
        )
      ]),
      transition(':leave', [
        animate(
          '180ms ease-in',
          style({
            opacity: 0,
            transform: 'scale(0.95) translateY(10px)'
          })
        )
      ])
    ])
  ]
})
export class BaseModalComponent {
  @Input() closeOnBackdrop = true;
  @Input() disableClose = false;
  @Input() loading = false;
  @Input() loadingMessage = '';
  @Output() backdrop = new EventEmitter<void>();

  onBackdrop(event: MouseEvent) {
    if (!this.closeOnBackdrop || this.disableClose) return;

    if (event.target === event.currentTarget) {
      this.backdrop.emit();
    }
  }

}
