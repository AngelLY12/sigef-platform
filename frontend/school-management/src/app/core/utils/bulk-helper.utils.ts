import { Observable } from "rxjs";

export class BulkHelper{
   static execute<TResponse>({
    ids,
    action,
    onSuccess,
    onError,
    setState
  }: {
    ids: number[];
    action: (ids: number[]) => Observable<TResponse>;
    onSuccess: (response: TResponse) => void;
    onError?: () => void;
    setState?: (state: 'loading' | 'success' | 'error') => void;
  }) {
    setState?.('loading');

    action(ids).subscribe({
      next: (response) => {
        onSuccess(response);
        setState?.('success');
      },
      error: () => {
        onError?.();
        setState?.('error');
      }
    });
  }

}
