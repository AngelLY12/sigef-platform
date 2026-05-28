import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../ui/button/button.component';

@Component({
  selector: 'app-filter-bar',
  standalone: true,
  imports: [CommonModule, ButtonComponent],
  templateUrl: './filter-bar.component.html',
  styleUrl: './filter-bar.component.scss'
})
export class FilterBarComponent {
  @Input() filtersTitle: string = 'Filtros';
  @Input() showActions = true;
  collapsed = false;

  @Output() reset = new EventEmitter<void>();

  onReset() {
    this.reset.emit();
  }

  toggle() {
    this.collapsed = !this.collapsed;
  }

}
