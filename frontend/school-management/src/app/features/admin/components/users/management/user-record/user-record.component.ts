import { Component, Input } from '@angular/core';
import { Status } from '../../../../../../core/models/enums/status.enum';
import { CommonModule } from '@angular/common';
import { UserListItem } from '../../../../models/response/user-list-item.model';

@Component({
  selector: 'app-user-record',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './user-record.component.html',
  styleUrl: './user-record.component.scss',
})
export class UserRecordComponent {
  @Input({ required: true }) user!: UserListItem;

  get statusColor(): string {
    switch (this.user.status) {
      case Status.ACTIVO:
        return 'success';
      case Status.ELIMINADO:
        return 'error';
      case Status.BAJA:
      case Status.BAJA_TEMPORAL:
        return 'warning';
      default:
        return 'default';
    }
  }
}
