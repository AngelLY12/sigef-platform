import { Component } from '@angular/core';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';
import { CommonModule } from '@angular/common';
@Component({
  selector: 'app-unauthorized',
  standalone: true,
  imports: [CommonModule, PublicLayoutComponent],
  templateUrl: './unauthorized.component.html',
  styleUrl: './unauthorized.component.scss'
})
export class UnauthorizedComponent {

}
