import { Component, DestroyRef, inject } from '@angular/core';
import {
  ActivatedRoute,
  NavigationEnd,
  Router,
  RouterOutlet,
} from '@angular/router';
import { NotificationModalComponent } from './shared/components/overlays/modal/notification-modal/notification-modal.component';
import { AlertComponent } from './shared/components/feedback/alert/alert.component';
import { CommonModule } from '@angular/common';
import { ActionsModalComponent } from './shared/components/overlays/modal/actions-modal/actions-modal.component';
import { CustomModalComponent } from './shared/components/overlays/modal/custom-modal/custom-modal.component';
import { Title } from '@angular/platform-browser';
import { filter } from 'rxjs';
import { ConfirmModalComponent } from './shared/components/overlays/modal/confirm-modal/confirm-modal.component';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

@Component({
  selector: 'app-root',
  imports: [
    RouterOutlet,
    CommonModule,
    NotificationModalComponent,
    AlertComponent,
    ActionsModalComponent,
    CustomModalComponent,
    ConfirmModalComponent,
  ],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss',
})
export class AppComponent {
  private router = inject(Router);
  private activatedRoute = inject(ActivatedRoute);
  private titleService = inject(Title);
  private destroyRef = inject(DestroyRef);

  ngOnInit() {
    this.router.events
      .pipe(
        filter((event) => event instanceof NavigationEnd),
        takeUntilDestroyed(this.destroyRef)
      )
      .subscribe(() => {
        const route = this.getDeepestRoute(this.activatedRoute);
        const title = route.snapshot.data['title'];

        if (title) {
          this.titleService.setTitle(`${title} - SIGEF`);
        }
      });
  }

  private getDeepestRoute(route: ActivatedRoute): ActivatedRoute {
    while (route.firstChild) {
      route = route.firstChild;
    }
    return route;
  }
}
