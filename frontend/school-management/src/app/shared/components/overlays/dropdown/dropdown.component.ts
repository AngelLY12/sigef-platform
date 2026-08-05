import { CommonModule } from '@angular/common';
import { Component, ElementRef, HostListener, inject, Input } from '@angular/core';
import { Position } from '../../../../core/models/types/position.type';
import { animate, style, transition, trigger } from '@angular/animations';

@Component({
  selector: 'app-dropdown',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dropdown.component.html',
  styleUrl: './dropdown.component.scss',
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
export class DropdownComponent {
  @Input() disabled = false;
  @Input() position: Position = 'bottom';

  isOpen = false;

  private elementRef = inject(ElementRef);

  toggleDropdown(): void {

    if (this.disabled) {
      return;
    }

    this.isOpen = !this.isOpen;
  }

  closeDropdown(): void {
    this.isOpen = false;
  }

  @HostListener('document:click', ['$event'])
  onClickOutside(event: MouseEvent): void {

    const clickedInside =
      this.elementRef.nativeElement.contains(event.target);

    if (!clickedInside) {
      this.closeDropdown();
    }
  }
}
