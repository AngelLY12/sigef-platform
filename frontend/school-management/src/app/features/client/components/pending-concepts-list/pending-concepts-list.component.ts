import { EmptyStateComponent } from './../../../../shared/components/feedback/empty-state/empty-state.component';
import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { PendingConceptsResponse } from '../../models/pending-concepts/pending-concepts-response.model';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';
import { InfoCardComponent } from '../../../../shared/components/data-display/cards/info-card/info-card.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-pending-concepts-list',
  standalone: true,
  imports: [CommonModule, InfoCardComponent, ButtonComponent, EmptyStateComponent, CurrencyMXNPipe],
  templateUrl: './pending-concepts-list.component.html',
  styleUrl: './pending-concepts-list.component.scss',
})
export class PendingConceptsListComponent {
  @Input({ required: true }) concepts!: PendingConceptsResponse[];
  @Input() loadingConceptId: number | null = null;
  @Input({ required: true }) message!: string;
  @Output() pay = new EventEmitter<number>();
}
