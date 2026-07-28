import { Component, Input } from '@angular/core';
import { CurrencyMXNPipe } from '../../../../../shared/pipes/currency-mxn.pipe';
import { ConceptsListResponse } from '../../../models/concepts/concepts-list.response.model';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-concept-card',
  imports: [CurrencyMXNPipe, CommonModule],
  templateUrl: './concept-card.component.html',
  styleUrl: './concept-card.component.scss'
})
export class ConceptCardComponent {
  @Input({ required: true }) concept!: ConceptsListResponse;
}
