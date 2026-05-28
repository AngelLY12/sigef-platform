import { CommonModule } from '@angular/common';
import { Component, forwardRef, Input } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { InputComponent } from '../input/input.component';

@Component({
  selector: 'app-password-input',
  standalone: true,
  imports: [CommonModule, InputComponent, ReactiveFormsModule],
  templateUrl: './password-input.component.html',
  styleUrl: './password-input.component.scss',
})
export class PasswordInputComponent {
  @Input() label = 'Password';
  @Input() placeholder = '••••••••';
  @Input() showStrength = true;
  @Input() control!: FormControl;
  @Input() autocomplete: 'off' | 'new-password' | 'current-password' = 'new-password';
  @Input() showHint: boolean = false;
  @Input() hint = 'La contraseña debe ser segura';



  showPassword = false;
  currentValue = '';
  showInfo = false;

  togglePassword() {
    this.showPassword = !this.showPassword;
  }

  handleValueChange(value: string) {
    this.currentValue = value;
    if (this.control && this.control?.value !== value) {
      this.control.markAsTouched();
      this.control.updateValueAndValidity();
    }
  }

  handleFocus() {
    this.showInfo = true;
  }

  handleBlur() {
    this.showInfo = false;
  }

  get hasUppercase() { return /[A-Z]/.test(this.currentValue); }
  get hasLowercase() { return /[a-z]/.test(this.currentValue); }
  get hasNumber() { return /\d/.test(this.currentValue); }
  get hasSymbol() { return /[@$!%*?&#=¿¡]/.test(this.currentValue); }
  get hasMinLength() { return this.currentValue.length >= 8; }

  get strength(): number {
    if (!this.currentValue) return 0;

    let score = 0;
    if (this.hasUppercase) score++;
    if (this.hasLowercase) score++;
    if (this.hasNumber) score++;
    if (this.hasSymbol) score++;
    if (this.hasMinLength) score++;
    return (score / 5) * 100;
  }
  getStrengthClass(): string {
    if (this.strength <= 40) return 'weak';
    if (this.strength <= 70) return 'medium';
    return 'strong';
  }

  getStrengthText(): string {
    if (this.strength <= 20) return 'Muy débil';
    if (this.strength <= 40) return 'Débil';
    if (this.strength <= 60) return 'Media';
    if (this.strength <= 80) return 'Fuerte';
    return 'Muy fuerte';
  }


}
