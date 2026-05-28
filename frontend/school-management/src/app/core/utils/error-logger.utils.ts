import { HttpErrorResponse } from "@angular/common/http";
import { BackendErrorCode } from "../models/types/backend-error-code.type";

export function logError(
  err: HttpErrorResponse,
  errorCode: BackendErrorCode,
  level: 'error' | 'warn' | 'info'
) {
  const logFn = console[level] || console.log;

  logFn(`[${level.toUpperCase()}] ${errorCode}:`, {
    url: err.url,
    status: err.status,
    message: err.message,
    error: err.error
  });

}
