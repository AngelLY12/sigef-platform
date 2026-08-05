import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { CardsListResponse } from "../../../../features/client/models/cards/cards-list-response.model";
import { ApiSuccessResponse } from "../../../models/api/api-success-response.model";
import { STUDENTS_URL } from "../../../constants/api.constants";

@Injectable({ providedIn: 'root' })
export class CardsApiService {
  private http = inject(HttpClient);

  getCards(forceRefresh: boolean): Observable<CardsListResponse[]> {
    return this.http.get<ApiSuccessResponse<{ cards: CardsListResponse[] }>>(
      `${STUDENTS_URL.cards}?forceRefresh=${forceRefresh}`
    ).pipe(
      map(res => res.data.cards)
    );
  }

  createCard(): Observable<string>{
    return this.http.post<ApiSuccessResponse<{ url_checkout: string }>>(
      `${STUDENTS_URL.cards}`,null
    ).pipe(
      map(res => res.data.url_checkout)
    );
  }

  deleteCard(paymentMethodId: number): Observable<string> {
    return this.http.delete<ApiSuccessResponse<null>>(
      `${STUDENTS_URL.cards}/${paymentMethodId}`
    ).pipe(
      map(res => res.message)
    );
  }

}
