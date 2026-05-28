import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { TableColumn } from '../../../../core/models/domain/table-column.model';

@Component({
  selector: 'app-table',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './table.component.html',
  styleUrl: './table.component.scss'
})
export class TableComponent<T = any> {
  @Input() columns: TableColumn<T>[] = [];
  @Input() data: T[] = [];
  @Input() noDataMessage: string = 'Sin datos para mostrar';
  @Input() actionsTemplate: any;

  getValue(row: T, key: keyof T | string): any {
    return row[key as keyof T];
  }

  resolveBadgeType(col: TableColumn<T>, row: T): string | undefined {
    if (!col.badgeType) return undefined;

    if (typeof col.badgeType === 'function') {
      return col.badgeType(this.getValue(row, col.key), row);
    }

    return col.badgeType;
  }
}
