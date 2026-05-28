import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';
import { Router } from '@angular/router';
import { AuthService } from '../../../../core/api/auth.api.service';

@Component({
  selector: 'app-maintenance',
  imports: [CommonModule, PublicLayoutComponent],
  templateUrl: './maintenance.component.html',
  styleUrl: './maintenance.component.scss'
})
export class MaintenanceComponent {
 private authService = inject(AuthService);

  hasActiveSession = false;
  primaryButton: any = null;

  ngOnInit() {
    this.hasActiveSession = this.authService.checkSession();

    if (this.hasActiveSession) {
      this.primaryButton = {
        text: 'Cerrar sesión',
        icon: 'logout',
        variant: 'primary',
        action: 'logout',
      };
    }
  }
}
