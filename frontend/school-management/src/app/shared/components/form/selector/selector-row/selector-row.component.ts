import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CheckboxComponent } from '../../checkbox/checkbox.component';
import { FormsModule } from '@angular/forms';
import { SelectorActionState } from '../../../../../core/models/types/permissions-state.type';

@Component({
  selector: 'app-selector-row',
  standalone: true,
  imports: [CommonModule, CheckboxComponent, FormsModule],
  templateUrl: './selector-row.component.html',
  styleUrl: './selector-row.component.scss'
})
export class SelectorRowComponent {
  @Input() label!: string;
  @Input() value!: any;
  @Input() state: Record<string, SelectorActionState> = {};
  @Input() assigned: Set<string> = new Set();
  @Input() isBulkOperation = false;

  @Output() stateChange = new EventEmitter<{
    value: string;
    state: SelectorActionState;
  }>();

  isAdd(): boolean {
    return this.state[this.value] === 'add';
  }

  isRemove(): boolean {
    return this.state[this.value] === 'remove';
  }

  isAssigned(): boolean {
    return this.assigned.has(this.value);
  }

  setState(state: SelectorActionState) {
    this.stateChange.emit({ value: this.value, state });
  }

  onAddChange(checked: boolean) {
    this.setState(checked ? 'add' : 'none');
  }

  onRemoveChange(checked: boolean) {
    this.setState(checked ? 'remove' : 'none');
  }

}
