import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CardsListResponse } from '../../models/cards/cards-list-response.model';
import { CommonModule } from '@angular/common';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { ConfirmModalComponent } from '../../../../shared/components/modal/confirm-modal/confirm-modal.component';

@Component({
  selector: 'app-cards-list',
  imports: [CommonModule, ButtonComponent],
  templateUrl: './cards-list.component.html',
  styleUrl: './cards-list.component.scss'
})
export class CardsListComponent {
  @Input({ required: true }) cards!: CardsListResponse[];
  @Output() deleteCard = new EventEmitter<number>();

}
