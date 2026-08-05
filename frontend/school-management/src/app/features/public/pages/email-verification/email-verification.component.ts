import { Component, inject, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { NavigationService } from '../../../../core/services/navigation.service';
import { AuthService } from '../../../../core/api/auth/auth.api.service';

@Component({
  selector: 'app-email-verification',
  standalone: true,
  imports: [PublicLayoutComponent],
  templateUrl: './email-verification.component.html',
  styleUrl: './email-verification.component.scss',
})
export class EmailVerificationComponent implements OnInit {
  status = '';
  private router = inject(ActivatedRoute);
  private route = inject(Router);
  private authService = inject(AuthService);
  private nagivationConfig = inject(NavigationService);
  private user = this.authService.currentUser();

  ngOnInit(): void {
    this.status = this.router.snapshot.queryParamMap.get('status') ?? 'error';
  }

  get message(): string {
    switch (this.status) {
      case 'not-found':
        return 'No se encontro el usuario asociado a este correo electrónico. Por favor, verifica el enlace o solicita uno nuevo.';
      case 'invalid':
        return 'El enlace de verificación es inválido. Por favor, verifica el enlace o solicita uno nuevo.';
      case 'already-verified':
        return 'El correo electrónico ya ha sido verificado..';
      case 'success':
        return 'Tu correo electrónico ha sido verificado exitosamente.';
      default:
        return 'Ha ocurrido un error durante la verificación del correo electrónico. Por favor, intenta nuevamente.';
    }
  }
  goBackOrHome() {
    if (window.history.length > 1) {
      window.history.back();
    } else {
      this.user
        ? this.nagivationConfig.redirectByRole(this.user.roles)
        : this.route.navigate([NAVIGATION.auth.login]);
    }
  }
}
