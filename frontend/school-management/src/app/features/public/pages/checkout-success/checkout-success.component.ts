import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';

@Component({
  selector: 'app-checkout-success',
  standalone: true,
  imports: [CommonModule, PublicLayoutComponent],
  templateUrl: './checkout-success.component.html',
  styleUrl: './checkout-success.component.scss'
})
export class CheckoutSuccessComponent {

}
