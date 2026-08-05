import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-relation-avatar',
  imports: [],
  templateUrl: './relation-avatar.component.html',
  styleUrl: './relation-avatar.component.scss'
})
export class RelationAvatarComponent {

  @Input({ required: true })
  fullName!: string;

  get initials(): string {
    return this.fullName
      .split(' ')
      .slice(0, 2)
      .map(n => n[0])
      .join('');
  }

}
