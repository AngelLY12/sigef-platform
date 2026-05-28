import { CommonModule } from '@angular/common';
import { Component, forwardRef, Input } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseControlValueAccessor } from '../base/base-control-value-accessor';

@Component({
  selector: 'app-checkbox',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './checkbox.component.html',
  styleUrl: './checkbox.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => CheckboxComponent),
      multi: true
    }
  ]
})
export class CheckboxComponent extends BaseControlValueAccessor<boolean> {
  @Input() label?: string;

  toggle(event: MouseEvent) {
    event.stopPropagation();

    if (this.disabled) return;

    this.updateValue(!this.value)
  }

}
