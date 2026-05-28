import { ControlValueAccessor } from "@angular/forms";

export abstract class BaseControlValueAccessor<T> implements ControlValueAccessor {
  value!: T;
  disabled = false;

  protected onChange: (value: T) => void = () => {};
  protected onTouched: () => void = () => {};

  writeValue(value: T): void {
    this.value = (value === undefined ? null : value) as any;
  }

  registerOnChange(fn: (value: T) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }

  protected updateValue(value: T): void {
    this.value = value;
    this.onChange(value);
    this.onTouched();
  }
}
