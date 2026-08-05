import { Component, EventEmitter, inject, Input, Output } from '@angular/core';
import { ParentsApiService } from '../../../../core/api/users/parents.api.service';
import { ChildrenData } from '../../../../features/client/models/parents/children.model';
import { SelectorItem } from '../base-selector/selector-item.model';
import { BaseSelectorComponent } from '../base-selector/base-selector.component';

@Component({
  selector: 'app-children-selector',
  standalone: true,
  imports: [BaseSelectorComponent],
  templateUrl: './children-selector.component.html',
  styleUrl: './children-selector.component.scss'
})
export class ChildrenSelectorComponent {
  private parentsService = inject(ParentsApiService);
  @Input() show = false;

  @Output() close = new EventEmitter<void>();
  @Output() childSelected = new EventEmitter<ChildrenData>();

  children: ChildrenData[] = [];

  ngOnInit(): void {
    this.loadChildren();
  }

  private loadChildren() {
    this.parentsService.getChildren().subscribe({
      next: (response) => {
        this.children = response.childrenData;
      },
    });
  }

  get items(): SelectorItem<ChildrenData>[] {
    return this.children.map((child) => ({
      id: child.id,
      icon: 'school',
      title: child.fullName,
      description: child.relationship,
      colorClass: 'student',
      data: child,
    }));
  }

  onSelected(item: SelectorItem) {
    this.childSelected.emit(item.data as ChildrenData);
  }

  onClose() {
    this.close.emit();
  }

}
