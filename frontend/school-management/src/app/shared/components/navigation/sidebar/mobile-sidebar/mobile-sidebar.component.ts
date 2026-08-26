import { Component, EventEmitter, inject, Input, Output } from '@angular/core';
import { AvatarComponent } from '../../../ui/avatar/avatar.component';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { DropdownComponent } from '../../../overlays/dropdown/dropdown.component';
import { MenuItemComponent } from '../../menu-item/menu-item.component';
import {
  SideBarItem,
  SideBarMenuGroup,
  SideBarMenuItem,
} from '../sidebar-item.model';
import { Role } from '../../../../../core/models/enums/role.enum';
import { NAVIGATION } from '../../../../../core/navigation/navigation.config';
import { MobileBottomItemComponent } from './mobile-bottom-item/mobile-bottom-item.component';
import { MobileGroupTriggerComponent } from './mobile-group-trigger/mobile-group-trigger.component';

@Component({
  selector: 'app-mobile-sidebar',
  imports: [
    CommonModule,
    RouterModule,
    AvatarComponent,
    MobileBottomItemComponent,
    MobileGroupTriggerComponent,
  ],
  templateUrl: './mobile-sidebar.component.html',
  styleUrl: './mobile-sidebar.component.scss',
})
export class MobileSidebarComponent {
  private router = inject(Router);

  private _sideBarItems: SideBarItem[] = [];
  moreMenu: SideBarMenuGroup = {
    key: 'mobile-more',
    label: 'Más',
    icon: 'more_horiz',
    type: 'group',
    children: [],
  };

  @Input()
  set sideBarItems(value: SideBarItem[]) {
    this._sideBarItems = value;

    this.moreMenu = {
      key: 'mobile-more',
      label: 'Más',
      icon: 'more_horiz',
      type: 'group',
      children: value.slice(this.mobileVisibleItems),
    };
  }

  get sideBarItems(): SideBarItem[] {
    return this._sideBarItems;
  }

  @Input() logoText: string = 'SIGEF';
  @Input() logoIcon: string = 'payments';

  @Input() role!: Role;
  @Input() userName: string = '';
  @Input() userAvatar: string = '';

  @Input() showRoleSelector: boolean = false;
  @Input() showChildrenSelector = false;

  @Output() changeRole = new EventEmitter<void>();
  @Output() selectChild = new EventEmitter<void>();

  mobileExpandedMenus: Set<string> = new Set();

  mobileVisibleItems = 4;

  onProfileClick(): void {
    this.router.navigate([NAVIGATION.profile.view]);
  }

  onChangeRole(): void {
    if (this.showRoleSelector) {
      this.changeRole.emit();
    }
  }

  onSelectChild(): void {
    if (this.showChildrenSelector) {
      this.selectChild.emit();
    }
  }
}
