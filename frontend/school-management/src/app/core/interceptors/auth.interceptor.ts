import { HttpErrorResponse, HttpInterceptorFn } from "@angular/common/http";
import { inject } from "@angular/core";
import { BehaviorSubject, catchError, filter, switchMap, take, throwError } from "rxjs";
import { AuthService } from "../api/auth.api.service";

let isRefreshing = false;
let refreshSubject = new BehaviorSubject<string | null>(null);

export const authInterceptor: HttpInterceptorFn = (req, next) => {

  const authService = inject(AuthService);
  const accessToken = localStorage.getItem('access_token');

  const authReq = accessToken
  ? req.clone({
      setHeaders: {
          Authorization: `Bearer ${accessToken}`
      }
  }): req;

  return next(authReq).pipe(
    catchError((error: HttpErrorResponse) => {
      if(error.status !== 401) {
          return throwError(() => error);
      }

      if (req.url.includes('logout')) {
        authService.clearSession();
        return throwError(() => error);
      }

      if (req.url.includes('refresh-token')) {
        authService.clearSession();
        return throwError(() => error);
      }


      const errorCode = error.error?.error_code;
        if (errorCode !== 'ACCESS_TOKEN_EXPIRED') {
          authService.logout().subscribe();
          return throwError(() => error);
      }

      if(error.error?.error_code === 'UNAUTHENTICATED')
      {
        authService.clearSession();
        return throwError(() => error);
      }

      if (isRefreshing) {

        return refreshSubject.pipe(
          filter(token => token !== null),
          take(1),
          switchMap(token => {
            const retryReq = req.clone({
              setHeaders: {
                Authorization: `Bearer ${token}`
              }
            });
            return next(retryReq);
          })
        );
      }

      isRefreshing = true;
      refreshSubject.next(null);

      return authService.refresh().pipe(

        switchMap(tokens => {

          isRefreshing = false;
          refreshSubject.next(tokens.access_token);

          const retryReq = req.clone({
            setHeaders: {
              Authorization: `Bearer ${tokens.access_token}`
            }
          });

          return next(retryReq);
        }),

        catchError(refreshError => {

          isRefreshing = false;
          authService.clearSession();
          return throwError(() => refreshError);
        })
      );
    })
  );
};
