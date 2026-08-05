import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ParentData } from '../../../models/parents/parents.model';
import { ChildrenData } from '../../../models/parents/children.model';
import { MetadataBadgeComponent } from '../../../../../shared/components/data-display/metadata/metadata-badge/metadata-badge.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-relation-item',
  standalone: true,
  imports: [MetadataBadgeComponent, ButtonComponent],
  templateUrl: './relation-item.component.html',
  styleUrl: './relation-item.component.scss',
})
export class RelationItemComponent {
  @Input({ required: true }) item!: ParentData | ChildrenData;
  @Input() showRemoveBtn: boolean = false;
  @Output() remove = new EventEmitter<ParentData>();

  onRemoveClick() {
    this.remove.emit(this.item);
  }
}
