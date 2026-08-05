import { ModalService } from './../../../../core/services/modal.service';
import { ValidatePaymentParams } from './../../models/debts/validate-payment-params.model';
import { CommonModule } from '@angular/common';
import {
  Component,
  inject,
  Input,
  OnChanges,
  SimpleChanges,
} from '@angular/core';
import { DebtsApiService } from '../../../../core/api/payments/financial-staff/debts.api.service';
import { StripePaymentsParams } from '../../models/debts/stripe-payments-params.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { StripePaymentsResponse } from '../../models/debts/stripe-payments-response.model';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { SpinnerComponent } from '../../../../shared/components/ui/spinner/spinner.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { AnchorComponent } from '../../../../shared/components/ui/anchor/anchor.component';

@Component({
  selector: 'app-stripe-payments',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    SpinnerComponent,
    ButtonComponent,
    AnchorComponent,
  ],
  templateUrl: './stripe-payments.component.html',
  styleUrl: './stripe-payments.component.scss',
})
export class StripePaymentsComponent implements OnChanges {
  @Input() nControl!: string;
  @Input() fullName!: string;
  @Input() year!: number;

  private debtsService = inject(DebtsApiService);
  private modalService = inject(ModalService);
  private stripeParams!: StripePaymentsParams;
  private listController!: ListController<StripePaymentsParams>;

  stripeState: LoadingState = 'idle';
  stripeList: StripePaymentsResponse[] = [];
  yearControl = new FormControl<number | null>(null);
  currentIndex = 0;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['nControl'] && this.nControl) {
      this.initialize();
    }
  }

  private initialize() {
    this.stripeParams = {
      search: this.nControl,
      year: this.year,
      forceRefresh: false,
    };

    this.yearControl.setValue(this.year);

    this.listController = new ListController<StripePaymentsParams>(
      () => this.stripeParams,
      (params) => (this.stripeParams = params),
      () => this.loadStripeData(),
    );

    this.loadStripeData();
  }

  loadStripeData() {
    this.stripeState = 'loading';
    this.debtsService.getStripePayments(this.stripeParams).subscribe({
      next: (res) => {
        this.stripeState = 'success';
        this.stripeList = res;
      },
      error: () => {
        this.stripeState = 'error';
      },
    });
  }

  onValidatePayment(payment_intent_id: string) {
    const params: ValidatePaymentParams = {
      search: this.nControl,
      payment_intent_id: payment_intent_id,
    };
    this.debtsService.validatePayment(params).subscribe({
      next: (res) => {
        this.modalService.closeCustom({ refreshed: true });
        this.modalService.show({
          message: res.message,
          display: 'modal',
          type: 'success',
        });
      },
    });
  }

  onYearChange() {
    const value = this.yearControl.value ?? null;

    const updatedParams = QueryParamsHelper.changeYear(
      this.stripeParams,
      value,
    );

    this.listController.update(updatedParams);
  }

  get currentPayment(): StripePaymentsResponse | null {
    return this.stripeList?.[this.currentIndex] ?? null;
  }

  next() {
    if (this.currentIndex < this.stripeList.length - 1) {
      this.currentIndex++;
    }
  }

  prev() {
    if (this.currentIndex > 0) {
      this.currentIndex--;
    }
  }
}
