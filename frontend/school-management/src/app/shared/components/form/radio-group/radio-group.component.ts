import { CommonModule } from '@angular/common';
import { Component, forwardRef, Input } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { BaseControlValueAccessor } from '../base/base-control-value-accessor';

@Component({
  selector: 'app-radio-group',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './radio-group.component.html',
  styleUrl: './radio-group.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => RadioGroupComponent),
      multi: true
    }
  ]

})
export class RadioGroupComponent extends BaseControlValueAccessor<any> {
  @Input() label = '';
  @Input() options: { label: string; value: any; description?: string }[] = [];

  select(value: any) {
    this.updateValue(value);
  }

}
