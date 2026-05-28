import { HttpInterceptorFn } from "@angular/common/http";
import { ErrorHandlerService } from "../services/error-handler.service";
import { catchError, throwError } from "rxjs";
import { inject } from "@angular/core";


export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const errorHandler = inject(ErrorHandlerService);

  return next(req).pipe(
    catchError((err) => {
      errorHandler.handleError(err);
      return throwError(() => err);
    })
  );
};
