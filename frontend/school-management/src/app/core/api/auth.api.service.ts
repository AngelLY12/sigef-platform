import { RegisterUser } from '../../features/auth/models/register.model';
import { Injectable, signal, computed } from "@angular/core";
import { HttpClient } from "@angular/common/http";
import { AuthUser } from "../models/auth/auth-user.model";
import { map, Observable, tap, throwError } from "rxjs";
import { ApiSuccessResponse } from "../models/api-success-response.model";
import { LoginData } from "../models/auth/login-data.model";
import { UserTokens } from "../models/auth/user-tokens.model";
import { API_URL } from "../constants/api.constants";

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSignal = signal<AuthUser | null>(null);
  currentUser = computed(() => this.currentUserSignal());
  isAuthenticated = computed(() => !!this.currentUserSignal());

  constructor(private http: HttpClient) {
    this.loadUserFromStorage();
  }

  login(email: string, password: string) {
    return this.http.post<ApiSuccessResponse<LoginData>>(`${API_URL}/login`, {email, password})
    .pipe(
      map(res => res.data.user_tokens),
      tap(tokens => this.handleAuthSuccess(tokens))
    );
  }

  register(user: RegisterUser) {
    return this.http.post<ApiSuccessResponse<null>>(
      `${API_URL}/register`, user
    );
  }

  refresh(): Observable<UserTokens> {
    const refresh_token = localStorage.getItem('refresh_token');
    if (!refresh_token) {
      return throwError(() => new Error('No se pudo alargar la sesión'));
    }

    return this.http.post<ApiSuccessResponse<LoginData>>(
    `${API_URL}/refresh-token`,
    { refresh_token })
    .pipe(
    map(res => res.data.user_tokens),
    tap(tokens => this.handleAuthSuccess(tokens)));
  }

  logout(): Observable<void> {
    const refreshToken = localStorage.getItem('refresh_token');
    return this.http.post<void>(
      `${API_URL}/logout`,
      {},
      {
        headers: {
          'x-refresh-token': refreshToken ?? ''
        }
      }
    ).pipe(
      tap(() => this.clearSession())
    );

  }

  forgotPassword(email: string){
    return this.http.post<ApiSuccessResponse<null>>(
          `${API_URL}/forgot-password`, email
    );
  }

  resetPassword(token: string, email: string, password: string, password_confirmation: string) {
    return this.http.post<ApiSuccessResponse<null>>(
      `${API_URL}/reset-password`, {token, email, password, password_confirmation}
    );
  }

  verifyEmail()
  {
    return this.http.post<ApiSuccessResponse<null>>(`${API_URL}/email/verification-notification`, null);
  }

  private handleAuthSuccess(response: UserTokens): void {
    localStorage.setItem('access_token', response.access_token);
    localStorage.setItem('refresh_token', response.refresh_token);
    localStorage.setItem('user', JSON.stringify(response.user_data));

    this.currentUserSignal.set(response.user_data);
  }

  clearSession(): void {
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');
    this.currentUserSignal.set(null);
  }

  checkSession(): boolean {
    const accessToken = localStorage.getItem('access_token');
    const refreshToken = localStorage.getItem('refresh_token');
    const user = localStorage.getItem('user');

    return !!(accessToken || refreshToken || user);
  }

  private loadUserFromStorage(): void {
    const user = localStorage.getItem('user');
    if (user) {
      this.currentUserSignal.set(JSON.parse(user));
    }
  }

}
