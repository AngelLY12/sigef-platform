import { CommonModule } from '@angular/common';
import { Component, EventEmitter, inject, Input, Output } from '@angular/core';
import { SidebarMenuItemComponent } from '../sidebar-menu-item/sidebar-menu-item.component';
import { SideBarItem, SideBarMenuGroup } from '../../sidebar-item.model';
import { Router } from '@angular/router';

@Component({
  selector: 'app-sidebar-menu-group',
  standalone: true,
  imports: [CommonModule, SidebarMenuItemComponent],
  templateUrl: './sidebar-menu-group.component.html',
  styleUrl: './sidebar-menu-group.component.scss',
})
export class SidebarMenuGroupComponent {
  private router = inject(Router);
  @Input({ required: true })
  item!: SideBarMenuGroup;

  @Input()
  collapsed = false;

  expanded = false;

  @Output() expandSidebar = new EventEmitter<void>();

  toggle(): void {
    if (this.collapsed) {
      this.expandSidebar.emit();
      return;
    }

    this.expanded = !this.expanded;
  }

  isAnyChildActive(): boolean {
    return this.item.children.some((child) => this.isItemActive(child));
  }

  private isItemActive(item: SideBarItem): boolean {
    if (item.type === 'item') {
      return item.exact
        ? this.router.url === item.route
        : this.router.url.startsWith(item.route);
    }

    return item.children.some((child) => this.isItemActive(child));
  }
}
