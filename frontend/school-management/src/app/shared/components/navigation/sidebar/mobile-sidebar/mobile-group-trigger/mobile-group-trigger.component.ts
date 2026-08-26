import { CommonModule } from '@angular/common';
import { Component, ElementRef, HostListener, inject, Input } from '@angular/core';
import { MobileBottomItemComponent } from '../mobile-bottom-item/mobile-bottom-item.component';
import { Router } from '@angular/router';
import { SideBarItem, SideBarMenuGroup } from '../../sidebar-item.model';
import { DropdownComponent } from '../../../../overlays/dropdown/dropdown.component';

@Component({
  selector: 'app-mobile-group-trigger',
  imports: [CommonModule, MobileBottomItemComponent],
  templateUrl: './mobile-group-trigger.component.html',
  styleUrl: './mobile-group-trigger.component.scss',
})
export class MobileGroupTriggerComponent {
  private router = inject(Router);
  private elementRef = inject(ElementRef);

  @Input({ required: true })
  item!: SideBarMenuGroup;

  @Input()
  variant: 'bottom' | 'menu' = 'bottom';

  @Input()
  menu?: DropdownComponent;

  expanded = false;

  toggle(): void {
    this.expanded = !this.expanded;
  }

  isActive(route: string, exact = false): boolean {
    if (exact) {
      return this.router.url === route;
    }

    return this.router.url.startsWith(route);
  }

  isAnyChildActive(): boolean {
    return this.item.children.some((child) => this.isItemActive(child));
  }

  private isItemActive(item: SideBarItem): boolean {
    if (item.type === 'item') {
      return this.isActive(item.route, item.exact);
    }

    return item.children.some((child) => this.isItemActive(child));
  }

  onChildClick(): void {
    this.menu?.closeDropdown();
    this.expanded = false;
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    if (!this.expanded) {
      return;
    }

    const target = event.target as Node;

    if (!this.elementRef.nativeElement.contains(target)) {
      this.expanded = false;
    }
  }
}
