import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-user-avatar',
  standalone: true,
  templateUrl: './user-avatar.component.html',
  styleUrl: './user-avatar.component.scss'
})
export class UserAvatarComponent {
  @Input({ required: true }) name!: string;

  get initial(): string {
    return this.name?.charAt(0).toUpperCase() ?? '';
  }

}
