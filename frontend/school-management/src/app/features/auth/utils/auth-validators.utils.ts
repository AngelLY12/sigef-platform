import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

export function passwordsMatchValidator(
  passwordField = 'password',
  confirmField = 'confirmPassword',
): ValidatorFn {
  return (form: AbstractControl): ValidationErrors | null => {
    const password = form.get(passwordField)?.value;
    const confirmPassword = form.get(confirmField)?.value;

    return password === confirmPassword ? null : { passwordsMismatch: true };
  };
}

export function ageRangeValidator(minAge: number, maxAge: number): ValidatorFn {
  return (control: AbstractControl): ValidationErrors | null => {
    if (!control.value) return null;

    const date = new Date(control.value);

    if (Number.isNaN(date.getTime())) {
      return { invalidDate: true };
    }

    const today = new Date();

    const youngestAllowed = new Date(
      today.getFullYear() - minAge,
      today.getMonth(),
      today.getDate(),
    );

    const oldestAllowed = new Date(
      today.getFullYear() - maxAge,
      today.getMonth(),
      today.getDate(),
    );

    if (date > youngestAllowed) {
      return { underage: true };
    }

    if (date < oldestAllowed) {
      return { tooOld: true };
    }

    return null;
  };
}
