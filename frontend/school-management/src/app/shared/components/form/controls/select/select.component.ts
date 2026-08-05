import { CommonModule } from '@angular/common';
import { Component, ElementRef, forwardRef, HostListener, inject, Input } from '@angular/core';
import { FormsModule, NG_VALUE_ACCESSOR } from '@angular/forms';
import { DropdownComponent } from '../../../overlays/dropdown/dropdown.component';
import { MenuItemComponent } from '../../../navigation/menu-item/menu-item.component';
import { FormFieldComponent } from '../../form-field/form-field.component';
import { BaseControlValueAccessor } from '../../base/base-control-value-accessor';
import { SelectOption } from './select-option.config.model';


@Component({
  selector: 'app-select',
  standalone: true,
  imports: [CommonModule, FormsModule, DropdownComponent, MenuItemComponent ,FormFieldComponent],
  templateUrl: './select.component.html',
  styleUrl: './select.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => SelectComponent),
      multi: true
    }
  ]
})
export class SelectComponent extends BaseControlValueAccessor<any> {

  @Input() label = '';
  @Input() placeholder = 'Selecciona una opción';
  @Input() iconRight?: string;
  @Input() options: SelectOption[] = [];
  @Input() error: string = '';
  @Input() required = false;
  @Input() hint?: string;

  selectOption(option: SelectOption, dropdown: DropdownComponent): void {
    this.updateValue(option.value);
    dropdown.closeDropdown();
  }

  get selectedLabel(): string {
    const selectedOption = this.options.find(opt => opt.value === this.value);
    return selectedOption ? selectedOption.label : this.placeholder;
  }

}
