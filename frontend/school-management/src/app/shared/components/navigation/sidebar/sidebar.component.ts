import {
  Component,
  EventEmitter,
  Input,
  Output,
} from '@angular/core';
import { Role } from '../../../../core/models/enums/role.enum';
import { SideBarItem } from './sidebar-item.model';
import { DesktopSidebarComponent } from './desktop-sidebar/desktop-sidebar.component';
import { MobileSidebarComponent } from './mobile-sidebar/mobile-sidebar.component';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [
    DesktopSidebarComponent,
    MobileSidebarComponent
  ],
  templateUrl: './sidebar.component.html',
  styleUrl: './sidebar.component.scss',
})
export class SidebarComponent {
  @Input() sideBarItems: SideBarItem[] = [];
  @Input() collapsed: boolean = false;
  @Input() logoText: string = 'SIGEF';
  @Input() logoIcon: string = 'payments';

  @Input() role!: Role;
  @Input() userName: string = '';
  @Input() userAvatar: string = '';
  @Input() showUserInfo: boolean = true;
  @Input() showRoleSelector: boolean = false;
  @Input() showChildrenSelector = false;

  @Output() collapsedChange = new EventEmitter<boolean>();
  @Output() changeRole = new EventEmitter<void>();
  @Output() selectChild = new EventEmitter<void>();

  onCollapsedChange(collapsed: boolean): void {
    this.collapsedChange.emit(collapsed);
  }
}
