import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { SelectorRowComponent } from '../selector-row/selector-row.component';
import { ButtonComponent } from '../../../ui/button/button.component';
import { GroupedOption } from '../../../../../core/models/domain/action-field.modal';
import { SelectorActionState } from '../../../../../core/models/types/permissions-state.type';
import { SpinnerComponent } from '../../../ui/spinner/spinner.component';
import { LoadingState } from '../../../../../core/models/types/loading-state.type';

@Component({
  selector: 'app-group-state-selector-list',
  standalone: true,
  imports: [CommonModule, SelectorRowComponent, ButtonComponent, SpinnerComponent],
  templateUrl: './group-state-selector-list.component.html',
  styleUrl: './group-state-selector-list.component.scss',
})
export class GroupStateSelectorListComponent {
  @Input() selectorList: GroupedOption[] = [];
  @Input() loading: LoadingState = 'idle';
  @Input() state: Record<string, SelectorActionState> = {};
  @Input() isBulkOperation: boolean = false;
  @Input() assigned: Set<string> = new Set();
  @Output() stateChange = new EventEmitter<{
    value: string;
    state: SelectorActionState;
  }>();

  collapsedGroups = new Set<string>();

  toggleGroup(group: string) {
    if (this.collapsedGroups.has(group)) {
      this.collapsedGroups.delete(group);
    } else {
      this.collapsedGroups.add(group);
    }
  }

  toggleGroupAction(group: GroupedOption, action: SelectorActionState) {
    const total = group.items.length;
    const count = this.getGroupStateCount(group, action);

    const shouldReset = count === total;

    group.items.forEach((item) => {
      const isAssigned = this.assigned.has(item.value);

      if (!this.isBulkOperation) {
        if (action === 'add' && isAssigned) return;
        if (action === 'remove' && !isAssigned) return;
      }

      this.stateChange.emit({
        value: item.value,
        state: shouldReset ? 'none' : action,
      });
    });
  }

  getActiveCount(group: GroupedOption): number {
    return group.items.filter((i) => {
      const state = this.state[i.value];
      return state === 'add' || state === 'remove';
    }).length;
  }

  isCollapsed(group: string): boolean {
    return this.collapsedGroups.has(group);
  }

  getGroupItems(group: GroupedOption) {
    return group.items.map((i) => i.value);
  }

  getGroupStateCount(group: GroupedOption, state: SelectorActionState) {
    return group.items.filter((i) => this.state[i.value] === state).length;
  }
}
