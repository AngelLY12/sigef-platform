import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../ui/button/button.component';
import { CommonModule } from '@angular/common';
import { SelectorItem } from './selector-item.model';

@Component({
  selector: 'app-base-selector',
  standalone: true,
  imports: [ButtonComponent, CommonModule],
  templateUrl: './base-selector.component.html',
  styleUrl: './base-selector.component.scss',
})
export class BaseSelectorComponent {
  @Input() show = false;

  @Input({ required: true })
  icon!: string;

  @Input({ required: true })
  title!: string;

  @Input({ required: true })
  description!: string;

  @Input()
  items: SelectorItem[] = [];

  @Output()
  selected = new EventEmitter<SelectorItem>();

  @Output()
  close = new EventEmitter<void>();

  onClose(): void {
    this.close.emit();
  }

  onSelect(item: SelectorItem): void {
    this.selected.emit(item);
  }
}
