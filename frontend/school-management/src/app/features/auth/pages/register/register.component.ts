import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormBuilder, FormControl, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { RegisterUser } from '../../models/register.model';
import { AuthLayoutComponent } from '../../../../layouts/auth-layout/auth-layout.component';
import { ModalService } from '../../../../core/services/modal.service';
import { cleanObject } from '../../../../core/helpers';
import { AuthNavigationHelper } from '../../../../core/helpers/navigation/auth-navigation.helper';
import { AuthService } from '../../../../core/api/auth/auth.api.service';
import { createRegisterForm } from '../../utils/register-form.utils';
import {
  ADDRESS_CUSTOM_ERRORS,
  REGISTER_STEPS,
} from '../../config/register.config';
import { PersonalStepComponent } from '../../components/register/personal-step/personal-step.component';
import { ContactStepComponent } from '../../components/register/contact-step/contact-step.component';
import { SecurityStepComponent } from '../../components/register/security-step/security-step.component';
import { AddressComponent } from '../../../../shared/components/form/sections/address/address.component';
import { StepperComponent } from '../../../../shared/components/form/layouts/stepper/stepper.component';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    AddressComponent,
    AuthLayoutComponent,
    StepperComponent,
    PersonalStepComponent,
    ContactStepComponent,
    SecurityStepComponent,
  ],
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent {
  protected navHelper = inject(AuthNavigationHelper);

  passwordControl: FormControl<string>;
  confirmPasswordControl: FormControl<string>;

  constructor() {
    this.passwordControl = this.form.get('password') as FormControl<string>;
    this.confirmPasswordControl = this.form.get(
      'confirmPassword',
    ) as FormControl<string>;
  }

  private fb = inject(FormBuilder).nonNullable;
  private authService = inject(AuthService);
  private modalService = inject(ModalService);
  private router = inject(Router);
  currentStep = 0;
  loading = false;
  readonly addressCustomErrors = ADDRESS_CUSTOM_ERRORS;
  readonly steps = REGISTER_STEPS;

  form = createRegisterForm(this.fb);

  isCurrentStepValid(): boolean {
    const controls = this.form.controls;

    switch (this.currentStep) {
      case 0:
        return [
          controls.name,
          controls.last_name,
          controls.birthdate,
          controls.gender,
          controls.curp,
          controls.blood_type,
        ].every((control) => control.valid);

      case 1:
        return [controls.email, controls.phone_number].every(
          (control) => control.valid,
        );

      case 2:
        return controls.address.valid;

      case 3:
        return (
          controls.password.valid &&
          controls.confirmPassword.valid &&
          !this.form.hasError('passwordsMismatch')
        );

      default:
        return false;
    }
  }

  submit() {
    if (this.form.invalid) return;

    this.loading = true;

    const { confirmPassword, address, ...rest } = this.form.getRawValue();

    const cleanedRest = cleanObject(rest);

    const user: RegisterUser = {
      ...cleanedRest,
      address: address,
      status: 'activo',
    } as RegisterUser;

    this.authService.register(user).subscribe({
      next: (res) => {
        this.loading = false;
        this.modalService.show({
          message: res.message ?? 'Usuario creado correctamente',
          type: 'success',
          display: 'alert',
        });
        this.router.navigate(['/auth/login']);
      },
      error: (err) => {
        this.loading = false;
      },
    });
  }
}
