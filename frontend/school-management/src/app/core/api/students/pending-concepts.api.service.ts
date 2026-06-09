import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { PendingConceptsParams } from "../../../features/client/models/pending-concepts/pending-concepts-params.model";
import { map, Observable } from "rxjs";
import { PendingConceptsResponse } from "../../../features/client/models/pending-concepts/pending-concepts-response.model";
import { STUDENTS_URL } from "../../constants/api.constants";
import { ApiSuccessResponse } from "../../models/api-success-response.model";

@Injectable({ providedIn: 'root' })
export class PendingConcepts {
  private http = inject(HttpClient);

  getPendingConcepts(params?: PendingConceptsParams): Observable<PendingConceptsResponse[]> {
    const { forceRefresh, id } = params ?? {};
    const url = `${STUDENTS_URL.pending}/${id ? `studentId=${id}` : ''}?${forceRefresh ? `&forceRefresh=${forceRefresh}` : ''}`;
    return this.http.get<ApiSuccessResponse<{ pending_payments: PendingConceptsResponse[]}>>(
      url
    ).pipe(
      map(res => res.data.pending_payments)
    );
  }

  payConcept(concept_id: number): Observable<string> {
    return this.http.post<ApiSuccessResponse<{ url_checkout: string}>>(
      `${STUDENTS_URL.pending}`,{concept_id}
    ).pipe(
      map(res => res.data.url_checkout)
    );
  }

  getOverdueConcepts(params?: PendingConceptsParams): Observable<PendingConceptsResponse[]> {
    const { forceRefresh, id } = params ?? {};
    const url = `${STUDENTS_URL.pending}/overdue/${id ? `studentId=${id}` : ''}?${forceRefresh ? `&forceRefresh=${forceRefresh}` : ''}`;
    return this.http.get<ApiSuccessResponse<{ pending_payments: PendingConceptsResponse[]}>>(
      url
    ).pipe(
      map(res => res.data.pending_payments)
    );
  }


}
