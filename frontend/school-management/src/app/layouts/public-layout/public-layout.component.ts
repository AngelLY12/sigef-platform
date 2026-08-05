import { CommonModule } from '@angular/common';
import { Component, EventEmitter, inject, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { Router } from '@angular/router';
import { ThemeButtonComponent } from '../../shared/components/ui/theme-button/theme-button.component';
import { ButtonConfig } from '../../core/models/types/button/btn-config.type';
import { LogoutService } from '../../core/services/logout.service';
import { NAVIGATION } from '../../core/navigation/navigation.config';
import { NavigationService } from '../../core/services/navigation.service';
import { AuthService } from '../../core/api/auth/auth.api.service';

@Component({
  selector: 'app-public-layout',
  standalone: true,
  imports: [CommonModule, ButtonComponent, ThemeButtonComponent],
  templateUrl: './public-layout.component.html',
  styleUrl: './public-layout.component.scss',
})
export class PublicLayoutComponent {
  @Input() icon: string = 'error_outline';
  @Input() title: string = '';
  @Input() message: string = '';

  @Input() showErrorCode: boolean = false;
  @Input() errorCodeLeft: string = '4';
  @Input() errorCodeRight: string = '4';

  @Input() primaryButton?: ButtonConfig;
  @Input() secondaryButton?: ButtonConfig;

  @Input() showIllustration: boolean = true;
  @Input() illustrationType:
    | 'default'
    | 'unverified'
    | 'maintenance'
    | 'unauthorized'
    | 'success'
    | 'warning' = 'default';

  @Output() primaryAction = new EventEmitter<void>();
  @Output() secondaryAction = new EventEmitter<void>();

  private logoutService = inject(LogoutService);
  private navigationService = inject(NavigationService);
  private authService = inject(AuthService);
  private router = inject(Router);

  handleAction(button: ButtonConfig | undefined, event: Event) {
    if (!button) return;

    const currentButton = button;
    currentButton.loading = true;

    const completeAction = () => {
      currentButton.loading = false;
    };

    switch (button.action) {
      case 'navigate':
        if (button.route) {
          this.router.navigate([button.route]);
        }
        completeAction();
        break;

      case 'home':
        const user = this.authService.currentUser();

        if (!user) {
          this.router.navigate([NAVIGATION.auth.login]);
          return;
        }

        if (user.roles.length > 1) {
          this.navigationService.goToSelector();
          return;
        }

        this.navigationService.navigateToRoleDashboard(user.roles[0]);
        break;

      case 'function':
        if (button.handler) {
          button.handler();
        } else {
          this.primaryAction.emit();
        }
        completeAction();
        break;

      case 'logout':
        this.logoutService.logout().subscribe({
          complete: () => completeAction(),
        });
        break;

      case 'back':
        window.history.back();
        completeAction();
        break;
    }
  }
}
