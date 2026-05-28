import { CommonModule } from '@angular/common';
import {
  Component,
  ContentChild,
  EventEmitter,
  forwardRef,
  inject,
  Injector,
  Input,
  Optional,
  Output,
  Self,
  TemplateRef,
} from '@angular/core';
import {
  AbstractControl,
  ControlValueAccessor,
  FormsModule,
  NG_VALUE_ACCESSOR,
  NgControl,
  ReactiveFormsModule,
} from '@angular/forms';
import { InputType } from '../../../../core/models/types/input.type';
import { FormFieldComponent } from '../../layout/form-field/form-field.component';
import { BaseControlValueAccessor } from '../base/base-control-value-accessor';

@Component({
  selector: 'app-input',
  standalone: true,
  imports: [CommonModule, FormFieldComponent],
  templateUrl: './input.component.html',
  styleUrl: './input.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => InputComponent),
      multi: true,
    },
  ],
})
export class InputComponent extends BaseControlValueAccessor<string> {
  @Input() label = '';
  @Input() placeholder = '';
  @Input() type: InputType = 'text';
  @Input() name = '';
  @Input() id = '';

  @Input() iconLeft?: string;
  @Input() iconRight?: string;
  @Input() prefix?: string;
  @Input() suffix?: string;

  @Input() required = false;
  @Input() readonly = false;
  @Input() autocomplete?: string;
  @Input() maxLength?: number;
  @Input() minLength?: number;
  @Input() min?: number;
  @Input() max?: number;
  @Input() step?: number;
  @Input() pattern?: string;

  @Input() size: 'sm' | 'md' | 'lg' = 'md';
  @Input() fullWidth = true;
  @Input() hint?: string;
  @Input() customErrors: { [key: string]: string } = {};
  @Input() validateOnBlur = true;
  @Input() clearable = false;

  @Output() valueChange = new EventEmitter<string>();
  @Output() iconRightClick = new EventEmitter<void>();
  @Output() iconLeftClick = new EventEmitter<void>();
  @Output() focusEvent = new EventEmitter<void>();
  @Output() blurEvent = new EventEmitter<void>();
  @Output() enterPress = new EventEmitter<void>();
  @Output() clear = new EventEmitter<void>();

  @ContentChild('errorTemplate') errorTemplate?: TemplateRef<any>;

  private ngControl: NgControl | null = null;
  private injector = inject(Injector);

  focused = false;

  ngOnInit(): void {
    this.ngControl = this.injector.get(NgControl, null, { self: true });
    if (this.ngControl) {
      this.ngControl.valueAccessor = this;
    }
  }

  handleInput(event: Event): void {
    const raw = (event.target as HTMLInputElement).value;
    const processedValue = this.processInput(raw);
    this.updateValue(processedValue);
    this.valueChange.emit(processedValue);
  }

  private processInput(value: string): string {
    switch (this.type) {
      case 'number':
        return value.replace(/[^0-9.-]/g, '');
      case 'email':
        return value.replace(/\s/g, '');
      case 'tel':
        return value.replace(/[^0-9+()-]/g, '');
      default:
        return value;
    }
  }

  onKeyDown(event: KeyboardEvent) {
    if (event.key === 'Enter') {
      this.enterPress.emit();
    }
  }

  onFocus(): void {
    this.focused = true;
  }

  onBlur(): void {
    this.focused = false;
    this.onTouched();
  }

  clearValue(): void {
    this.updateValue('');
  }

  get control(): AbstractControl | null {
    return this.ngControl?.control ?? null;
  }

  get shouldValidate(): boolean {
    if (!this.validateOnBlur) return true;
    return this.focused
      ? false
      : this.control?.touched || this.control?.dirty || false;
  }

  get hasError(): boolean {
    return !!(this.control && this.control.invalid && this.shouldValidate);
  }

  getErrors(): { [key: string]: any } | null {
    if (!this.control?.errors || !this.shouldValidate) return null;
    return this.control.errors;
  }

  getErrorMessage(): string {
    if (!this.control?.errors || !this.shouldValidate) return '';

    const errors = this.control.errors;
    const firstError = Object.keys(errors)[0];

    if (this.customErrors[firstError]) {
      return this.customErrors[firstError];
    }

    const errorMessages: { [key: string]: string } = {
      required: 'Este campo es obligatorio',
      email: 'Ingresa un email válido',
      minlength: `Mínimo ${errors['minlength']?.requiredLength} caracteres`,
      maxlength: `Máximo ${errors['maxlength']?.requiredLength} caracteres`,
      min: `El valor mínimo es ${this.min}`,
      max: `El valor máximo es ${this.max}`,
      pattern: 'El formato no es válido',
      emailExists: 'Este email ya está registrado',
      passwordMismatch: 'Las contraseñas no coinciden',
      invalidFormat: 'Formato inválido',
    };

    return errorMessages[firstError] || 'Campo inválido';
  }

  get isRequired(): boolean {
    if (!this.control?.validator) return this.required;
    const validator = this.control.validator({} as AbstractControl);
    return !!(validator && validator['required']) || this.required;
  }
}
