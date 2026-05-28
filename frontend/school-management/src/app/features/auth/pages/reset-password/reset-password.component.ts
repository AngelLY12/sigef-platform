import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { AuthLayoutComponent } from '../../../../layouts/auth-layout/auth-layout.component';
import { AbstractControl, FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ModalService } from '../../../../core/services/modal.service';
import { InputComponent } from '../../../../shared/components/form/input/input.component';
import { AuthService } from '../../../../core/api/auth.api.service';
import { PasswordInputComponent } from '../../../../shared/components/form/password-input/password-input.component';

@Component({
  selector: 'app-reset-password',
  imports: [CommonModule, ButtonComponent, InputComponent ,PasswordInputComponent, AuthLayoutComponent, ReactiveFormsModule],
  templateUrl: './reset-password.component.html',
  styleUrl: './reset-password.component.scss'
})
export class ResetPasswordComponent implements OnInit {

  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private modalService = inject(ModalService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  loading = false;
  token: string | null = null;
  email: string | null = null;

  passwordControl: FormControl<string>;
  confirmPasswordControl: FormControl<string>;

  form = this.fb.group({
    email: this.fb.control({ value: '', disabled: true }, Validators.required),
    password: this.fb.control('', [
      Validators.required,
      Validators.minLength(8),
      Validators.pattern(/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&#=¿¡])/)
    ]),
    confirmPassword: this.fb.control('', Validators.required)
  }, {
    validators: this.passwordsMatchValidator
  });

  constructor() {
    this.passwordControl = this.form.get('password') as FormControl<string>;
    this.confirmPasswordControl = this.form.get('confirmPassword') as FormControl<string>;
  }

  ngOnInit() {
    this.token = this.route.snapshot.paramMap.get('token');

    this.email = this.route.snapshot.queryParamMap.get('email');

    if (this.email) {
      this.form.patchValue({ email: this.email });
    }

    if (!this.token || !this.email) {
      this.modalService.show({
        message: 'Enlace de recuperación inválido',
        type: 'warn',
        display: 'alert'
      });
      this.router.navigate(['/auth/login']);
    }
  }

  private passwordsMatchValidator(form: AbstractControl) {
    const password = form.get('password')?.value;
    const confirm = form.get('confirmPassword')?.value;

    return password === confirm ? null : { passwordsMismatch: true };
  }

  submit() {
    if(this.form.invalid) return;

    this.loading = true;

    const { password, confirmPassword } = this.form.getRawValue();

    this.authService.resetPassword(
      this.token!,
      this.email!,
      password!,
      confirmPassword!
    )
    .subscribe({
      next: (res) => {
        this.loading = false;
        this.modalService.show({
          message: res.message ?? 'Contraseña actualizada correctamente',
          type: 'success',
          display: 'alert'
        });
        this.router.navigate(['/auth/login']);
      },
      error: (err) => {
        this.loading = false;
        this.modalService.show({
          message: err.error?.message || 'Error al restablecer la contraseña',
          type: 'error',
          display: 'alert'
        });
      }
    });
  }

}
