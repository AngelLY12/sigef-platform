import { CommonModule } from '@angular/common';
import { Component, inject, Input } from '@angular/core';
import { AnchorComponent } from '../../shared/components/ui/anchor/anchor.component';
import { animate, style, transition, trigger } from '@angular/animations';
import { AuthNavigationHelper } from '../../core/helpers/navigation/auth-navigation.helper';
import { ThemeButtonComponent } from '../../shared/components/ui/theme-button/theme-button.component';

@Component({
  selector: 'app-auth-layout',
  standalone: true,
  imports: [CommonModule, AnchorComponent, ThemeButtonComponent],
  templateUrl: './auth-layout.component.html',
  styleUrl: './auth-layout.component.scss',
  animations: [
    trigger('fadeSlide', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(50px)' }),
        animate('0.5s ease', style({ opacity: 1, transform: 'translateY(0)' }))
      ])
    ])
  ]

})
export class AuthLayoutComponent {
  @Input() title = '';
  @Input() subtitle = '';
  @Input() footerMessage = '';
  @Input() footerLink = '';
  @Input() footerAnchorMessage = '';
  @Input() hideForgotPassword: boolean = false;
  protected navHelper = inject(AuthNavigationHelper);

}
