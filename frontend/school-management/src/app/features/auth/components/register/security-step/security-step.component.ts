import { Component, Input } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { PasswordInputComponent } from '../../../../../shared/components/form/controls/password-input/password-input.component';

@Component({
  selector: 'app-security-step',
  standalone: true,
  imports: [ReactiveFormsModule, PasswordInputComponent],
  templateUrl: './security-step.component.html',
  styleUrl: './security-step.component.scss'
})
export class SecurityStepComponent {
  @Input({ required: true }) form!: FormGroup;
  @Input({ required: true }) passwordControl!: FormControl<string>;
  @Input({ required: true }) confirmPasswordControl!: FormControl<string>;

}
