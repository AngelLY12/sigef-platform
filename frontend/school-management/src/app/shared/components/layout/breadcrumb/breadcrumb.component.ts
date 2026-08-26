import { Component, inject, OnInit } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { Breadcrumb } from './breadcrumb.model';
import { filter } from 'rxjs';
import { AnchorComponent } from '../../ui/anchor/anchor.component';

@Component({
  selector: 'app-breadcrumb',
  imports: [AnchorComponent],
  templateUrl: './breadcrumb.component.html',
  styleUrl: './breadcrumb.component.scss',
})
export class BreadcrumbComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  breadcrumbs: Breadcrumb[] = [];

  ngOnInit(): void {
    this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe(() => {
        this.buildBreadcrumbs();
      });
    this.buildBreadcrumbs();
  }

  private buildBreadcrumbs(): void {
    const route = this.getDeepestRoute(this.route);
    const breadcrumb = route.snapshot.data['breadcrumb'];
    const breadcrumbParent = route.snapshot.data['breadcrumbParent'];
    const breadcrumbParam = route.snapshot.data['breadcrumbParam'];

    const breadcrumbs: Breadcrumb[] = [];

    if (breadcrumbParent) {
      if (Array.isArray(breadcrumbParent)) {
        breadcrumbs.push(...breadcrumbParent);
      } else {
        breadcrumbs.push(breadcrumbParent);
      }
    }

    if (breadcrumb) {
      breadcrumbs.push({
        label: breadcrumb,
      });
    }

    if (breadcrumbParam) {
      const value = route.snapshot.paramMap.get(breadcrumbParam.param);

      if (value) {
        breadcrumbs.push({
          label: `${breadcrumbParam.label} #${value}`,
        });
      }
    }

    this.breadcrumbs = breadcrumbs;
  }

  private getDeepestRoute(route: ActivatedRoute): ActivatedRoute {
    while (route.firstChild) {
      route = route.firstChild;
    }

    return route;
  }
}
