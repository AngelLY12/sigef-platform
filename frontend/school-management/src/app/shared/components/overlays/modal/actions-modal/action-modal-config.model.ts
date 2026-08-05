import { Observable } from "rxjs";
import { ActionField } from "./action-field.modal";

export interface ActionModalConfig {
  title: string;
  description?: string;
  entityName?: string;
  fields: ActionField[];
  submitLabel?: string;
  onSubmit: (data: any) => Observable<unknown>;
  onSuccess?: (response: any) => void;
  onFailure?: (response: any) => void;

}
