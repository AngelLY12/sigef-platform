import { PaginatedResponse } from "../models/domain/paginated-response.model";

export class Paginated<T> {
  constructor(public data: PaginatedResponse<T>) {}

  get hasNext(): boolean {
    return this.data.hasMorePages;
  }

  get hasPrevious(): boolean {
    return !!this.data.previousPage;
  }

  get nextPageNumber(): number | null {
    return this.data.nextPage ?? null;
  }

  get previousPageNumber(): number | null {
    return this.data.previousPage ?? null;
  }

  get totalPages(): number {
    return this.data.lastPage;
  }

  get totalItems(): number {
    return this.data.total;
  }

  get currentPage(): number {
    return this.data.currentPage;
  }

  get perPage(): number {
    return this.data.perPage;
  }

  get currentItemsCount(): number {
    return this.data.items.length;
  }

  get itemsInRange(): { from: number; to: number } {
    if( this.currentItemsCount === 0 ) {
      return { from: 0, to: 0 };
    }
    const from = (this.currentPage - 1) * this.perPage + 1;
    const to = from + this.currentItemsCount - 1;
    return { from, to };
  }

  get isEmpty(): boolean {
    return !this.data.items || this.data.items.length === 0;
  }

  map<U>(fn: (item: T) => U): Paginated<U> {
    return new Paginated<U>({
      ...this.data,
      items: this.data.items.map(fn),
    });
  }
}
