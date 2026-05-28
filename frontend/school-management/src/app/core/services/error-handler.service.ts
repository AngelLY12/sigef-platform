import { Injectable, inject } from "@angular/core";
import { HttpErrorResponse } from "@angular/common/http";
import { Router } from "@angular/router";
import { ModalService } from "./modal.service";
import { ApiErrorResponse } from "../models/api-error-response.model";
import { BackendErrorCode } from "../models/types/backend-error-code.type";
import { ERROR_MESSAGES, ErrorConfig } from "../config/error-message.config";
import { logError } from "../utils/error-logger.utils";
import { ModalType } from "../models/types/modal-error.type";
import { NAVIGATION } from "../navigation/navigation.config";

@Injectable({ providedIn: 'root' })
export class ErrorHandlerService {
  private modalService = inject(ModalService);
  private router = inject(Router);

  handleError(err: HttpErrorResponse): void {
    const apiError = err.error as ApiErrorResponse;
    const errorCode = apiError?.error_code as BackendErrorCode;

    if (errorCode && ERROR_MESSAGES[errorCode]) {
      this.handleKnownError(err, errorCode, apiError);
      return;
    }

    this.handleGenericError(err);
  }

  private handleKnownError(
    err: HttpErrorResponse,
    errorCode: BackendErrorCode,
    apiError: ApiErrorResponse
  ): void {
    const config = ERROR_MESSAGES[errorCode];

    if (config.shouldShow) {
      this.showErrorModal(config, errorCode, apiError);
    }

    this.executeAction(config);

    logError(err, errorCode, config.logLevel);
  }

  private handleGenericError(err: HttpErrorResponse): void {

    if (err.status === 0) {
      this.modalService.show({
        message: 'No se pudo conectar con el servidor. Seras redireccionado a la página de mantenimiento.',
        type: 'warn',
        display: 'modal'
      });
      setInterval(() => {
        this.router.navigate([NAVIGATION.common.maintenance]);
      }, 3000);
      return;
    }

    const httpMessages: Record<number, string> = {
      400: 'Solicitud incorrecta',
      401: 'No autorizado',
      405: 'Petición desconocida',
      403: 'Acceso denegado',
      422: 'No procesable',
      404: 'Recurso no encontrada',
      500: 'Error interno del servidor'
    };

    if (err.status >= 500) {
      this.modalService.show({
        message: 'Error en el servidor. Intenta más tarde',
        type: 'error',
        display: 'modal'
      });
      console.error('Error de servidor:', err);
    }
    else if (err.status === 401) {
      this.router.navigate([NAVIGATION.auth.login]);
    } else if (httpMessages[err.status]) {
      const apiError = typeof err.error === 'object'
        ? err.error as ApiErrorResponse
        : null;

      this.modalService.show({
        message: httpMessages[err.status],
        errors: apiError?.errors
          ? this.flattenErrors(apiError.errors)
          : undefined,
        type: 'warn',
        display: 'modal'
      });

    }
  }

  private showErrorModal(
    config: ErrorConfig,
    errorCode: BackendErrorCode,
    apiError: ApiErrorResponse
  ): void {
    let errors: string[] | undefined;
    let mainMessage: string;
    let backendMessage: string | undefined;

    mainMessage = config.message;

    if (apiError?.message) {
      backendMessage = apiError.message;
    }

    if (apiError?.errors) {
      errors = this.flattenErrors(apiError.errors);
    }

    const allMessages: string[] = [];
    if (backendMessage && backendMessage !== mainMessage) {
      allMessages.push(`${backendMessage}`);
    }
    allMessages.push(`${mainMessage}`);

    this.modalService.show({
      message: allMessages.join('\n\n'),
      errors,
      type: this.mapLogLevelToModalType(config.logLevel),
      display: 'modal'
    });
  }

  private mapLogLevelToModalType(level: ModalType): ModalType {
    switch(level) {
      case 'error': return 'error';
      case 'warn': return 'warn';
      case 'info': return 'info';
      default: return 'info';
    }
  }

  private executeAction(config: ErrorConfig): void {
    if (config.action === 'redirect' && config.redirectTo) {
      this.router.navigate([config.redirectTo]);
    }
  }

  private flattenErrors(errors: Record<string, string[]> | string[]): string[] {
    if (Array.isArray(errors)) {
      return errors;
    }

    return Object.entries(errors).flatMap(([field, fieldErrors]) =>
      fieldErrors.map(error => `${field}: ${error}`)
    );
  }
}
