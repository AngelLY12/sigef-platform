import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { PublicLayoutComponent } from '../../../../layouts/public-layout/public-layout.component';

@Component({
  selector: 'app-checkout-cancel',
  imports: [CommonModule, PublicLayoutComponent],
  templateUrl: './checkout-cancel.component.html',
  styleUrl: './checkout-cancel.component.scss'
})
export class CheckoutCancelComponent {

}
