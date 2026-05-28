import { HttpClient } from "@angular/common/http";
import { inject, Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { ConceptsParams } from "../../../features/financial/models/concepts/concepts-params.model";
import { Paginated } from "../../utils/paginated-helper.utils";
import { ConceptsListResponse } from "../../../features/financial/models/concepts/concepts-list.response.model";
import { FINANCIAL_STAFF_URLS } from "../../constants/api.constants";
import { ApiSuccessResponse } from "../../models/api-success-response.model";
import { PaginatedResponse } from "../../models/domain/paginated-response.model";
import { ConceptsCreateRequest } from "../../../features/financial/models/concepts/concepts-create-request.model";
import { ConceptsCreateResponse } from "../../../features/financial/models/concepts/concepts-create-response.model";
import { cleanObject, cleanObjectWithOptions } from "../../helpers";
import { SearchStudentsByNControlResponse } from "../../../features/financial/models/concepts/search-students-response.model";
import { ConceptsChangeStatusResponse } from "../../../features/financial/models/concepts/concepts-change-status-response.model";
import { ConceptDetailResponse } from "../../../features/financial/models/concepts/concept-detail-response.model";
import { ConceptRelationsResponse } from "../../../features/financial/models/concepts/concept-relations-response.model";
import { ConceptUpdateRelationsRequest, ConceptUpdateRequest } from "../../../features/financial/models/concepts/concept-update-request.model";
import { ConceptUpdateRelationsResponse, ConceptUpdateResponse } from "../../../features/financial/models/concepts/concept-update-response.model";

@Injectable({ providedIn: 'root'})
export class PaymentConceptApiService {
  private http = inject(HttpClient);

  getPaymentConcepts(params: ConceptsParams): Observable<Paginated<ConceptsListResponse>> {
    const { page, perPage, status, forceRefresh } = params;
    const url = `${FINANCIAL_STAFF_URLS.concepts}?page=${page}&perPage=${perPage}${status ? `&status=${status}` : ''}${forceRefresh ? `&forceRefresh=${forceRefresh}` : ''}`;
    return this.http.get<ApiSuccessResponse<{concepts: PaginatedResponse<ConceptsListResponse>}>>(
      url
    ).pipe(
      map(res => new Paginated(res.data.concepts))
    );
  }

  createPaymentConcept(concept: ConceptsCreateRequest): Observable<ConceptsCreateResponse> {
    const cleanedConcept = cleanObjectWithOptions(concept, { removeEmptyStrings: true, removeUndefined: true, removeNull: true, removeEmptyArrays: true });
    return this.http.post<ApiSuccessResponse<{concept: ConceptsCreateResponse}>>(
      FINANCIAL_STAFF_URLS.concepts,
      cleanedConcept
    ).pipe(
      map(res => res.data.concept)
    );
  }

  finalizeConcept(concept: number): Observable<ConceptsChangeStatusResponse> {
    return this.http.post<ApiSuccessResponse<{ concept: ConceptsChangeStatusResponse }>>(`${FINANCIAL_STAFF_URLS.concepts}/${concept}/finalize`, null)
    .pipe(
      map(res => res.data.concept)
    );
  }

  activateConcept(concept: number): Observable<ConceptsChangeStatusResponse> {
    return this.http.post<ApiSuccessResponse<{ concept: ConceptsChangeStatusResponse }>>(`${FINANCIAL_STAFF_URLS.concepts}/${concept}/activate`, null)
    .pipe(
      map(res => res.data.concept)
    );
  }

  disableConcept(concept: number): Observable<ConceptsChangeStatusResponse> {
    return this.http.post<ApiSuccessResponse<{ concept: ConceptsChangeStatusResponse }>>(`${FINANCIAL_STAFF_URLS.concepts}/${concept}/disable`, null)
    .pipe(
      map(res => res.data.concept)
    );
  }

  eliminateConcept(id: number): Observable<string> {
    return this.http.delete<ApiSuccessResponse<string>>(`${FINANCIAL_STAFF_URLS.concepts}/${id}/eliminate`)
    .pipe(
      map(res => res.message)
    );
  }

  elimintaLogicalConcept(concept: number): Observable<string> {
    return this.http.post<ApiSuccessResponse<{ concept: ConceptsChangeStatusResponse }>>(`${FINANCIAL_STAFF_URLS.concepts}/${concept}/eliminateLogical`, null)
    .pipe(
      map(res => res.data.concept.message)
    );
  }

  searchStudentsByNControl(nControl: string): Observable<SearchStudentsByNControlResponse[]> {
    const url = `${FINANCIAL_STAFF_URLS.concepts}/search/controls/?search=${nControl}`;
    return this.http.get<ApiSuccessResponse<{search: SearchStudentsByNControlResponse[]}>>(
      url
    ).pipe(
      map(res => res.data.search)
    );
  }

  conceptDetail(id: number): Observable<ConceptDetailResponse> {
    return this.http.get<ApiSuccessResponse<{ concept: ConceptDetailResponse }>>(
        `${FINANCIAL_STAFF_URLS.concepts}/${id}`
    ).pipe(
      map(res => res.data.concept)
    );
  }

  conceptRelations(id: number): Observable<ConceptRelationsResponse> {
    return this.http.get<ApiSuccessResponse<{ relations: ConceptRelationsResponse }>>(
      `${FINANCIAL_STAFF_URLS.concepts}/relations/${id}`
    ).pipe(
      map(res => res.data.relations)
    );
  }

  updateConcept(request: ConceptUpdateRequest, id: number): Observable<ConceptUpdateResponse> {
    const payload = cleanObjectWithOptions(request, {removeNull: true, removeEmptyStrings: true, removeUndefined: true});
    return this.http.patch<ApiSuccessResponse<{ concept: ConceptUpdateResponse }>>(
      `${FINANCIAL_STAFF_URLS.concepts}/${id}`,
      payload
    ).pipe(
      map(res => res.data.concept)
    );
  }

  updateConceptRelations(request: ConceptUpdateRelationsRequest, id: number): Observable<ConceptUpdateRelationsResponse> {
    const payload = cleanObjectWithOptions(request, { removeEmptyArrays: true, removeNull: true, removeUndefined: true });
    return this.http.patch<ApiSuccessResponse<{ concept: ConceptUpdateRelationsResponse }>>(
      `${FINANCIAL_STAFF_URLS.concepts}/update-relations/${id}`,
      payload
    ).pipe(
      map(res => res.data.concept)
    );
  }





}
