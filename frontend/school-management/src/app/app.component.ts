import { Component, inject } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { NotificationModalComponent } from './shared/components/feedback/notification-modal/notification-modal.component';
import { AlertComponent } from './shared/components/feedback/alert/alert.component';
import { CommonModule } from '@angular/common';
import { ActionsModalComponent } from './shared/components/modal/actions-modal/actions-modal.component';
import { CustomModalComponent } from './shared/components/modal/custom-modal/custom-modal.component';
import { Title } from '@angular/platform-browser';
import { filter } from 'rxjs';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, CommonModule ,NotificationModalComponent, AlertComponent, ActionsModalComponent, CustomModalComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent {
  private router = inject(Router);
  private activatedRoute = inject(ActivatedRoute);
  private titleService = inject(Title);

  ngOnInit() {
    this.router.events
      .pipe(filter(event => event instanceof NavigationEnd))
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
