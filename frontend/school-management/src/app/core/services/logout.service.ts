import { inject, Injectable } from "@angular/core";
import { Router } from "@angular/router";
import { ModalService } from "./modal.service";
import { NAVIGATION } from "../navigation/navigation.config";
import { Observable } from "rxjs";
import { AuthService } from "../api/auth.api.service";

@Injectable({ providedIn: 'root' })
export class LogoutService {
  private authService = inject(AuthService);
  private router = inject(Router);
  private modalService = inject(ModalService);

  logout(): Observable<void> {
    return new Observable(subscriber => {
      this.authService.logout().subscribe({
        next: () => {
          this.modalService.show({
            message: 'Has cerrado sesión.',
            type: 'success',
            display: 'alert'
          });
          this.router.navigate([NAVIGATION.auth.login]);
          subscriber.next();
          subscriber.complete();
        },
        error: () => {
          this.authService.clearSession();
          this.router.navigate([NAVIGATION.auth.login]);
          subscriber.next();
          subscriber.complete();
        }
      });
    });
  }

  logoutAndClear(): void {
    this.authService.clearSession();
    this.router.navigate([NAVIGATION.auth.login]);
  }
}
