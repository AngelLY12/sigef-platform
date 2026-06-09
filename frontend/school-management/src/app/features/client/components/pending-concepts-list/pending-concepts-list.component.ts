import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { PendingConceptsResponse } from '../../models/pending-concepts/pending-concepts-response.model';
import { InfoCardComponent } from '../../../../shared/components/data-display/info-card/info-card.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { CurrencyMXNPipe } from '../../../../shared/pipes/currency-mxn.pipe';

@Component({
  selector: 'app-pending-concepts-list',
  standalone: true,
  imports: [CommonModule, InfoCardComponent, ButtonComponent, CurrencyMXNPipe],
  templateUrl: './pending-concepts-list.component.html',
  styleUrl: './pending-concepts-list.component.scss',
})
export class PendingConceptsListComponent {
  @Input({ required: true }) concepts!: PendingConceptsResponse[];
  @Input() loadingConceptId: number | null = null;
  @Output() pay = new EventEmitter<number>();
}
