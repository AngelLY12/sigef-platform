import { CommonModule, Location } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PaymentHistoryApiService } from '../../../../core/api/students/payment-history.api.service';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { PaymentDetailsResponse } from '../../models/payment-history/payment-details-response.model';
import { PageLayoutComponent } from '../../../../shared/components/navigation/page-layout/page-layout.component';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { ActivatedRoute } from '@angular/router';
import { InfoCardItemComponent } from '../../../../shared/components/data-display/info-card-item/info-card-item.component';
import {
  CardPaymentMethod,
  OxxoPaymentMethod,
  PaymentMethodDetails,
  SpeiPaymentMethod,
} from '../../../../core/models/domain/payment-method-details-type.model';
import { AnchorComponent } from '../../../../shared/components/ui/anchor/anchor.component';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';
import { ModalService } from '../../../../core/services/modal.service';
import { PaymentReceiptResponse } from '../../models/payment-history/payment-receipt-response.model';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { PaymentSummaryComponent } from '../../components/payment-details/payment-summary/payment-summary.component';
import { PaymentMethodComponent } from '../../components/payment-details/payment-method/payment-method.component';
import { PaymentActionsComponent } from '../../components/payment-details/payment-actions/payment-actions.component';
import { FolderTabsComponent } from '../../../../shared/components/navigation/folder-tabs/folder-tabs.component';
import { FolderTab } from '../../../../core/models/domain/folder-tabs-config.model';
import { PAYMENT_DETAILS_TABS } from '../../config/client.config';

@Component({
  selector: 'app-payment-details',
  standalone: true,
  imports: [
    CommonModule,
    PageLayoutComponent,
    FolderTabsComponent,
    PaymentSummaryComponent,
    PaymentMethodComponent,
    PaymentActionsComponent
  ],
  templateUrl: './payment-details.component.html',
  styleUrl: './payment-details.component.scss',
})
export class PaymentDetailsComponent implements OnInit {
  private paymentHistoryService = inject(PaymentHistoryApiService);
  private modalService = inject(ModalService);
  private route = inject(ActivatedRoute);
  private location = inject(Location);

  loading: LoadingState = 'idle';
  payment: PaymentDetailsResponse | null = null;
  paymentId!: number;
  activeTab = 'summary';
  readonly paymentTabs: FolderTab[] = PAYMENT_DETAILS_TABS;

  ngOnInit(): void {
    this.paymentId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadPayment();
  }

  loadPayment() {
    this.loading = 'loading';
    this.paymentHistoryService.getPaymentDetails(this.paymentId).subscribe({
      next: (res) => {
        this.payment = res;
        this.loading = 'success';
      },
      error: () => {
        this.loading = 'error';
      },
    });
  }

  loadReceipt(paymentId: number) {
    const duration = 300 / 60;
    this.modalService.openConfirm({
      title: 'Recibo oficial del sistema',
      message: `Seras redireccionado al recibo oficial del sistema, este enlace tiene una duración de ${duration} minutos;
          pasado ese tiempo el enlace deja de ser valido`,
      confirmLabel: 'Ir al comprobante',
      cancelLabel: 'Cerrar',
      confirmVariant: 'primary',
      onConfirm: () => this.paymentHistoryService.getReceipt(paymentId),
      onSuccess: (res: PaymentReceiptResponse) => {
        window.open(res.url, '_blank');
      },
      onFailure: () => this.modalService.closeCustom(),
    });
  }

  goBack() {
    this.location.back();
  }
}
