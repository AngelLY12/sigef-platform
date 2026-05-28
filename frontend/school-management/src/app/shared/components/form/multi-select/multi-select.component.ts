import { CommonModule } from '@angular/common';
import { Component, forwardRef, Input } from '@angular/core';
import { DropdownComponent } from '../../layout/dropdown/dropdown.component';
import { NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseControlValueAccessor } from '../base/base-control-value-accessor';
import { FormFieldComponent } from '../../layout/form-field/form-field.component';
import { MenuItemComponent } from '../../navigation/menu-item/menu-item.component';

@Component({
  selector: 'app-multi-select',
  standalone: true,
  imports: [CommonModule, DropdownComponent, FormFieldComponent, MenuItemComponent],
  templateUrl: './multi-select.component.html',
  styleUrl: './multi-select.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => MultiSelectComponent),
      multi: true
    }
  ]
})
export class MultiSelectComponent extends BaseControlValueAccessor<any[]> {
  @Input() label = '';
  @Input() placeholder = 'Selecciona una opción';
  @Input() iconRight?: string;
  @Input() options: { label: string; value: any }[] = [];
  @Input() error: string = '';
  @Input() required = false;
  @Input() hint?: string;
  override value: any[] = [];

  toggleOption(optionValue: any): void {

    const exists = this.value.includes(optionValue);

    if (exists) {

      this.updateValue(
        this.value.filter(v => v !== optionValue)
      );

      return;
    }

    this.updateValue([
      ...this.value,
      optionValue
    ]);
  }

  isSelected(optionValue: any): boolean {
    return this.value.includes(optionValue);
  }

  get selectedLabel(): string {

    if (!this.value.length) {
      return this.placeholder;
    }

    const labels = this.options
      .filter(option => this.value.includes(option.value))
      .map(option => option.label);

    if (labels.length <= 2) {
      return labels.join(', ');
    }

    return `${labels.length} seleccionados`;
  }

}
