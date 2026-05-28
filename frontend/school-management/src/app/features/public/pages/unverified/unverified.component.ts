import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';

@Component({
  selector: 'app-unverified',
  standalone: true,
  imports: [CommonModule, PublicLayoutComponent],
  templateUrl: './unverified.component.html',
  styleUrl: './unverified.component.scss'
})
export class UnverifiedComponent {

}
