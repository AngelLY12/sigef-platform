import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { SelectOption } from '../../../../../core/models/domain/action-field.modal';
import { SelectorActionState } from '../../../../../core/models/types/permissions-state.type';
import { SelectorRowComponent } from '../selector-row/selector-row.component';
import { SpinnerComponent } from '../../../ui/spinner/spinner.component';
import { LoadingState } from '../../../../../core/models/types/loading-state.type';

@Component({
  selector: 'app-state-selector-list',
  standalone: true,
  imports: [CommonModule, SelectorRowComponent, SpinnerComponent],
  templateUrl: './state-selector-list.component.html',
  styleUrl: './state-selector-list.component.scss'
})
export class StateSelectorListComponent {

  @Input() selectorList: SelectOption[] = [];
  @Input() loading: LoadingState = 'idle';
  @Input() state: Record<string, SelectorActionState> = {};
  @Input() isBulkOperation: boolean = false;
  @Input() assigned: Set<string> = new Set();
  @Output() stateChange = new EventEmitter<{
    value: string;
    state: SelectorActionState;
  }>();
}
