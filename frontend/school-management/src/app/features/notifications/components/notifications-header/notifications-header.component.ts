import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-notifications-header',
  standalone: true,
  imports: [CommonModule, ButtonComponent],
  templateUrl: './notifications-header.component.html',
  styleUrl: './notifications-header.component.scss',
})
export class NotificationsHeaderComponent {
  @Input() onlyUnread: boolean = false;
  @Input() unreadCount: number = 0;
  @Input() readCount: number = 0;

  @Output() markAllAsRead = new EventEmitter<void>();
  @Output() viewChange = new EventEmitter<boolean>();
  @Output() refresh = new EventEmitter<void>();

  onRefresh() {
    this.refresh.emit();
  }

  showRead() {
    this.viewChange.emit(false);
  }

  showUnread() {
    this.viewChange.emit(true);
  }

  onMarkAllAsRead() {
    this.markAllAsRead.emit();
  }
}
