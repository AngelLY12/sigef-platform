import { CommonModule } from '@angular/common';
import { Component, EventEmitter, inject, Input, Output } from '@angular/core';
import { Router, RouterModule } from '@angular/router';
import { SideBarMenuItem } from '../../sidebar-item.model';
import { DropdownComponent } from '../../../../overlays/dropdown/dropdown.component';

@Component({
  selector: 'app-mobile-bottom-item',
  imports: [CommonModule, RouterModule],
  templateUrl: './mobile-bottom-item.component.html',
  styleUrl: './mobile-bottom-item.component.scss',
})
export class MobileBottomItemComponent {
  private router = inject(Router);

  @Input({ required: true })
  item!: SideBarMenuItem;

  /**
   * bottom = item de la barra inferior
   * menu = item dentro del menú More
   */
  @Input()
  variant: 'bottom' | 'menu' = 'bottom';

  @Input()
  menu?: DropdownComponent;

  @Output()
  itemClick = new EventEmitter<SideBarMenuItem>();

  isActive(): boolean {
    if (this.item.exact) {
      return this.router.url === this.item.route;
    }

    return this.router.url.startsWith(this.item.route);
  }

  onClick(): void {
    this.itemClick.emit(this.item);

    if (this.variant === 'menu') {
      this.router.navigate([this.item.route]);
      this.menu?.closeDropdown();
    }
  }
}
