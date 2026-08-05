import { Observable } from 'rxjs';

export interface ConfirmModalConfig {
  title: string;
  message: string;

  confirmLabel?: string;
  cancelLabel?: string;

  confirmVariant?: 'primary' | 'danger';

  onConfirm: () => Observable<any> | void;

  onSuccess?: (result: any) => void;
  onFailure?: (error: any) => void;
}
