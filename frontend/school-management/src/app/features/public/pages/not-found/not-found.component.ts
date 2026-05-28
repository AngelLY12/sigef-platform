import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';
import { Router } from '@angular/router';
import { NavigationService } from '../../../../core/services/navigation.service';
import { NAVIGATION } from '../../../../core/navigation/navigation.config';
import { AuthService } from '../../../../core/api/auth.api.service';

@Component({
  selector: 'app-not-found',
  imports: [CommonModule, PublicLayoutComponent],
  templateUrl: './not-found.component.html',
  styleUrl: './not-found.component.scss'
})
export class NotFoundComponent {
  private router = inject(Router);
  private authService = inject(AuthService);
  private nagivationConfig = inject(NavigationService);
  private user = this.authService.currentUser();

messages = [
    '¿Te perdiste? No te preocupes, a todos nos pasa...',
    'Esta página decidió tomar unas vacaciones',
    '404: La página que buscas está en otro castillo',
    'Parece que el enlace se rompió... como mi corazón',
    'Ni Yuri podía encontrar esta página',
    'Aquí no hay nada, mejor regresa',
    'La página se fue de fiesta y no volvió',
    '404: No encontrado, como mi motivación los lunes',
    'Este enlace llevaba al infierno y alguien lo cerró',
    'Lo que buscas está en otro lugar... probablemente'
  ];

  randomMessage = this.messages[Math.floor(Math.random() * this.messages.length)];

  goBackOrHome() {
    if (window.history.length > 1) {
      window.history.back();
    } else {
      this.user ? this.nagivationConfig.redirectByRole(this.user.roles) : this.router.navigate([NAVIGATION.auth.login]);
    }
  }

}
