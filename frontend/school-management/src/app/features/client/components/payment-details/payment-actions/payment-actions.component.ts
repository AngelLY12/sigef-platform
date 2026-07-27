import { Component, EventEmitter, Input, Output } from '@angular/core';
import { PaymentDetailsResponse } from '../../../models/payment-history/payment-details-response.model';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { AnchorComponent } from '../../../../../shared/components/ui/anchor/anchor.component';
import { InfoCardItemComponent } from '../../../../../shared/components/data-display/info-card-item/info-card-item.component';

@Component({
  selector: 'app-payment-actions',
  imports: [ButtonComponent, AnchorComponent, InfoCardItemComponent],
  templateUrl: './payment-actions.component.html',
  styleUrl: './payment-actions.component.scss',
})
export class PaymentActionsComponent {
  @Input({ required: true })
  payment!: PaymentDetailsResponse;

  @Output()
  receipt = new EventEmitter<number>();
}
