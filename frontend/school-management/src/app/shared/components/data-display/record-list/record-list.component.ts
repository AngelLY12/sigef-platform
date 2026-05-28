import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output, TemplateRef } from '@angular/core';

@Component({
  selector: 'app-record-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './record-list.component.html',
  styleUrl: './record-list.component.scss'
})
export class RecordListComponent<T> {
  @Input() items:  T[] = [];
  @Input() itemTemplate!: TemplateRef<any>;
  @Input() selectionTemplate?: TemplateRef<any>;
  @Input() selectedItems: T[] = [];
  @Input() listActionsTemplate?: TemplateRef<any>
  @Input() imageTemplate?: TemplateRef<any>;
  @Input() trackByKey!: keyof T;
  @Output() itemClick = new EventEmitter<any>();


  isSelected(item: T): boolean {
    return this.selectedItems?.some(
      i => i[this.trackByKey] === item[this.trackByKey]
    );
  }

  onItemClicked(item: T) {
    this.itemClick.emit(item);
  }

}
