export interface ApiSuccessResponse<T> {
  success: true;
  data: T;
  message: string;
}
