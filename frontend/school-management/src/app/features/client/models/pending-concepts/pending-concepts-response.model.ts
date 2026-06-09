import { ExpirationInfo } from "../../../../core/models/domain/expiration-info.model";

export interface PendingConceptsResponse {
  id: number;
  concept_name: string;
  description: string;
  amount: string;
  start_date: string;
  end_date: string;
  expiration_human: string;
  expiration_info: ExpirationInfo;
}
