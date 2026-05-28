export interface ApiErrorResponse {
  success: false;
  message: string;
  errors: Record<string, string[]> | string[];
  error_code: string;

}
