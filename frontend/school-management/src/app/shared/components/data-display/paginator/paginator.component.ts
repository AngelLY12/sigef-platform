import { Component, EventEmitter, Input, OnChanges, Output } from '@angular/core';
import { Paginated } from '../../../../core/utils/paginated-helper.utils';
import { ButtonComponent } from '../../ui/button/button.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-paginator',
  standalone: true,
  imports: [ButtonComponent ,CommonModule, FormsModule],
  templateUrl: './paginator.component.html',
  styleUrl: './paginator.component.scss'
})
export class PaginatorComponent implements OnChanges {
  @Input({ required: true }) paginator!: Paginated<unknown>;
  @Input() pageSizes: number[] = [5, 10, 15, 25, 50];

  @Output() pageChange = new EventEmitter<number>();
  @Output() perPageChange = new EventEmitter<number>();

  goToPage(page: number | '...') {
    if (page === '...' ||  page === this.paginator.currentPage) return;
    this.pageChange.emit(page);
  }

  onPerPageChange(value: string) {
    const perPage = Number(value);
    this.perPageChange.emit(perPage);
  }

  selectedPerPage!: number;

  ngOnChanges() {
    if (this.paginator) {
      this.selectedPerPage = this.paginator.perPage;
    }
  }

  get pageSizeOptions() {
    return this.pageSizes.map(size => ({ label: size.toString(), value: size }));
  }

  get visiblePages(): (number | '...')[] {
    const total = this.paginator.totalPages;
    const current = this.paginator.currentPage;

    const delta = 5;
    const range: (number | '...')[] = [];

    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);

    if (start > 1) {
      range.push(1);
      if (start > 2) range.push('...');
    }

    for (let i = start; i <= end; i++) {
      range.push(i);
    }

    if (end < total) {
      if (end < total - 1) range.push('...');
      range.push(total);
    }

    return range;
  }

}
