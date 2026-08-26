import { Component, Input } from '@angular/core';
import { SideBarMenuItem } from '../../sidebar-item.model';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-sidebar-menu-item',
  imports: [CommonModule, RouterModule],
  templateUrl: './sidebar-menu-item.component.html',
  styleUrl: './sidebar-menu-item.component.scss'
})
export class SidebarMenuItemComponent {
@Input({ required: true })
  item!: SideBarMenuItem;

  @Input()
  collapsed = false;
}
