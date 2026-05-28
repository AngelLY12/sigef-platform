export interface PaginatedResponse<T> {
  items: T[];
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
  hasMorePages: boolean;
  nextPage: number | null;
  previousPage: number | null;
}
