import { Notification } from "../models/domain/notification.model";
import { PaginatedResponse } from "../models/domain/paginated-response.model";

export function mapLaravelPagination<T>(res: any): PaginatedResponse<T> {
  return {
    items: res.data,
    currentPage: res.current_page,
    lastPage: res.last_page,
    perPage: res.per_page,
    total: res.total,
    hasMorePages: res.current_page < res.last_page,
    nextPage: res.current_page < res.last_page ? res.current_page + 1 : null,
    previousPage: res.current_page > 1 ? res.current_page - 1 : null
  };
}


