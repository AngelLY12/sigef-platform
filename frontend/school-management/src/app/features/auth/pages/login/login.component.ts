import { ModalService } from './../../../../core/services/modal.service';
import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { InputComponent } from '../../../../shared/components/form/input/input.component';
import { AuthLayoutComponent } from '../../../../layouts/auth-layout/auth-layout.component';
import { AuthNavigationHelper } from '../../../../core/helpers/navigation/auth-navigation.helper';
import { NavigationService } from '../../../../core/services/navigation.service';
import { AuthService } from '../../../../core/api/auth.api.service';
import { PasswordInputComponent } from '../../../../shared/components/form/password-input/password-input.component';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    ButtonComponent,
    InputComponent,
    AuthLayoutComponent,
    PasswordInputComponent,
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private modalService = inject(ModalService);
  private navigationService = inject(NavigationService);

  protected navHelper = inject(AuthNavigationHelper);

  passwordControl: FormControl<string>;

  constructor() {
    this.passwordControl = this.form.get('password') as FormControl<string>;
  }

  loading = false;
  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  submit() {
    if (this.form.invalid) return;

    this.loading = true;

    const { email, password } = this.form.value;

    this.authService.login(email!, password!).subscribe({
      next: (res) => {
        this.modalService.show({
          message: 'Inicio de sesión exitoso',
          type: 'success',
          display: 'alert',
        });
        const roles = res.user_data.roles || [];
        if (roles.length > 1) {
          this.navigationService.goToSelector();
          return;
        }
        this.navigationService.redirectByRole(roles);
      },
      error: (err) => {
        this.loading = false;
      },
    });
  }
}
