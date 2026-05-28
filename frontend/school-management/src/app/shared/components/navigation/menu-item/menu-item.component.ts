import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Optional, Output } from '@angular/core';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { SpinnerComponent } from '../../ui/spinner/spinner.component';

@Component({
  selector: 'app-menu-item',
  standalone:true,
  imports: [CommonModule, SpinnerComponent],
  templateUrl: './menu-item.component.html',
  styleUrl: './menu-item.component.scss'
})
export class MenuItemComponent {
  @Input() icon?: string;
  @Input() variant: 'default' | 'danger' = 'default';
  @Input() state: LoadingState = 'idle'
  @Input() disabled = false;
  @Input() selected = false;
  @Input() allowSelected = false;
  @Output() itemClick = new EventEmitter<void>();

  handleClick(event: Event) {
     event.stopPropagation();
    if (!this.disabled) {
      this.itemClick.emit();
    }
  }


}
