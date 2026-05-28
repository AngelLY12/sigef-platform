import { CommonModule } from '@angular/common';
import {
  Component,
  inject,
  Input,
  OnChanges,
  OnInit,
  SimpleChanges,
} from '@angular/core';
import { DebtsApiService } from '../../../../core/api/financial-staff/debts.api.service';
import { StripePaymentsParams } from '../../models/debts/stripe-payments-params.model';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { StripePaymentsResponse } from '../../models/debts/stripe-payments-response.model';
import { QueryParamsHelper } from '../../../../core/utils/query-params-helper.utils';
import { ListController } from '../../../../core/utils/list-controller.utils';
import { FormControl, ReactiveFormsModule } from '@angular/forms';

@Component({
  selector: 'app-stripe-payments',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './stripe-payments.component.html',
  styleUrl: './stripe-payments.component.scss',
})
export class StripePaymentsComponent implements OnChanges {
  @Input() nControl!: string;
  @Input() fullName!: string;
  @Input() year!: number;

  private debtsService = inject(DebtsApiService);
  private stripeParams!: StripePaymentsParams;
  private listController!: ListController<StripePaymentsParams>;

  stripeState: LoadingState = 'idle';
  stripeList: StripePaymentsResponse[] = [];
  yearControl = new FormControl<number | null>(null);

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

  onYearChange() {
    const value = this.yearControl.value ?? null;

    const updatedParams = QueryParamsHelper.changeYear(
      this.stripeParams,
      value,
    );

    this.listController.update(updatedParams);
  }
}
