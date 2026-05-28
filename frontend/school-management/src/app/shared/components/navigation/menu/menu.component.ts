import { animate, style, transition, trigger } from '@angular/animations';
import { CommonModule } from '@angular/common';
import { Component, ElementRef, HostListener, Input, ViewChild } from '@angular/core';

@Component({
  selector: 'app-menu',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './menu.component.html',
  styleUrl: './menu.component.scss',
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
export class MenuComponent {
   @Input() position: 'left' | 'right' = 'right';

  @ViewChild('trigger', { static: false }) triggerRef!: ElementRef;

  isOpen = false;
  menuStyles: any = {};

  constructor(private el: ElementRef) {}

  toggle(event: Event) {
    event.stopPropagation();
    this.isOpen = !this.isOpen;

    if (this.isOpen && this.triggerRef?.nativeElement) {
      const rect = this.triggerRef?.nativeElement.getBoundingClientRect();

      this.menuStyles = {
        top: `${rect.bottom + 4}px`,
        left: this.position === 'right'
          ? `${rect.right}px`
          : `${rect.left}px`,
        transform: this.position === 'right'
          ? 'translateX(-100%)'
          : 'none'
      };
    }
  }

  close() {
    this.isOpen = false;
  }

  @HostListener('document:click', ['$event'])
  onClickOutside(event: Event) {
    if (window.innerWidth <= 768) return;
    const clickedInside = this.el.nativeElement.contains(event.target);
    if (!clickedInside) {
      this.close();
    }
  }

}
