export class QueryParamsHelper {
  static changePage<T extends { page: number }>(params: T, page: number): T {
    return {
      ...params,
      page,
    };
  }

  static changeStatus<T extends { status?: string | null; page: number }>(
    params: T,
    status: string | null,
  ): T {
    return {
      ...params,
      status: status ?? undefined,
      page: 1,
    };
  }

  static changePageSize<T extends { page: number; perPage: number }>(
    params: T,
    perPage: number,
  ): T {
    return {
      ...params,
      perPage,
      page: 1,
    };
  }

  static refreshData<T extends { forceRefresh?: boolean }>(params: T): T {
    return {
      ...params,
      forceRefresh: true,
    };
  }

  static changeSearch<T extends { search?: string | null; page?: number }>(
    params: T,
    search: string | null,
    resetPage = true,
  ): T {
    return {
      ...params,
      search: search?.trim() || undefined,
      ...(resetPage && 'page' in params ? { page: 1 } : {}),
    };
  }

  static changeYear<T extends { year?: number | null; page?: number }>(
    params: T,
    year: number | null,
    resetPage = true,
  ): T {
    return {
      ...params,
      year: year || undefined,
      ...(resetPage && 'page' in params ? { page: 1 } : {}),
    };
  }
}
