import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { PageLayoutComponent } from '../../../../shared/components/layout/page-layout/page-layout.component';
import { LoadingState } from '../../../../core/models/types/loading-state.type';
import { CardsListResponse } from '../../models/cards/cards-list-response.model';
import { CardsApiService } from '../../../../core/api/payments/students/cards.api.service';
import { CardsListComponent } from '../../components/cards-list/cards-list.component';
import { ModalService } from '../../../../core/services/modal.service';

@Component({
  selector: 'app-cards',
  imports: [CommonModule, PageLayoutComponent, CardsListComponent],
  templateUrl: './cards.component.html',
  styleUrl: './cards.component.scss',
})
export class CardsComponent implements OnInit {
  private cardsService = inject(CardsApiService);
  private modalService = inject(ModalService);
  loading: LoadingState = 'idle';
  cards: CardsListResponse[] | null = null;
  forceRefresh: boolean = false;

  ngOnInit(): void {
    this.loadCards();
  }

  loadCards() {
    this.loading = 'loading';
    this.cardsService.getCards(this.forceRefresh).subscribe({
      next: (res) => {
        this.cards = res;
        this.loading = 'success';
        this.forceRefresh = false;
      },
      error: () => {
        this.loading = 'error';
        this.forceRefresh = false;
      },
    });
  }

  onRefreshData() {
    this.forceRefresh = true;
    this.loadCards();
  }

  onDeleteCard(paymentMethodId: number) {
    this.modalService.openConfirm({
      title: 'Eliminar tarjeta',
      message:
        '¿Estás seguro de que deseas eliminar esta tarjeta? Esta acción no se puede deshacer.',

      confirmLabel: 'Eliminar',
      cancelLabel: 'Cancelar',
      confirmVariant: 'danger',
      onConfirm: () => this.cardsService.deleteCard(paymentMethodId),

      onSuccess: () => {
        this.loadCards();
      },
    });
  }
}
