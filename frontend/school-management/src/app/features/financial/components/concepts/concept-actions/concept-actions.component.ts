import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DropdownComponent } from '../../../../../shared/components/layout/dropdown/dropdown.component';
import { MenuItemComponent } from '../../../../../shared/components/navigation/menu-item/menu-item.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { ConceptsListResponse } from '../../../models/concepts/concepts-list.response.model';

@Component({
  selector: 'app-concept-actions',
  imports: [DropdownComponent, MenuItemComponent, ButtonComponent],
  templateUrl: './concept-actions.component.html',
  styleUrl: './concept-actions.component.scss',
})
export class ConceptActionsComponent {
  @Input({ required: true })
  concept!: ConceptsListResponse;

  @Output() delete = new EventEmitter<ConceptsListResponse>();
  @Output() activate = new EventEmitter<ConceptsListResponse>();
  @Output() view = new EventEmitter<ConceptsListResponse>();
  @Output() finalize = new EventEmitter<ConceptsListResponse>();
  @Output() desactivate = new EventEmitter<ConceptsListResponse>();
}
