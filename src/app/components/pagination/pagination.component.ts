import {Component, EventEmitter, Input, Output} from '@angular/core';
import {FormsModule} from '@angular/forms';

export interface Pagination {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number;
  to: number;
}

@Component({
  selector: 'ub-pagination',
  imports: [
    FormsModule
  ],
  templateUrl: './pagination.component.html',
  standalone: true,
  styleUrl: './pagination.component.scss'
})
export class PaginationComponent {

  @Input() currentPage: number = 1;
  @Input() lastPage: number = 1;
  @Input() perPage: number = 50;
  @Input() total: number = 0;
  @Input() from: number = 0;
  @Input() to: number = 0;
  @Input() maxVisiblePages: number = 5;

  @Output() pageChange = new EventEmitter<number>();
  @Output() perPageChange = new EventEmitter<number>();

  perPageOptions: number[] = [25, 50, 100, 150];

  get showEllipsisStart(): boolean {
    return this.currentPage > Math.floor(this.maxVisiblePages / 2) + 1;
  }

  get showEllipsisEnd(): boolean {
    return this.currentPage < this.lastPage - Math.floor(this.maxVisiblePages / 2);
  }

  getPages(): number[] {
    const pages = [];
    let start = Math.max(1, this.currentPage - Math.floor(this.maxVisiblePages / 2));
    let end = Math.min(this.lastPage, start + this.maxVisiblePages - 1);

    // Ajusta o início se estivermos no final
    start = Math.max(1, end - this.maxVisiblePages + 1);

    for (let i = start; i <= end; i++) {
      pages.push(i);
    }

    return pages;
  }

  changePage(page: number): void {
    if (page >= 1 && page <= this.lastPage && page !== this.currentPage) {
      this.pageChange.emit(page);
    }
  }

  onPerPageChange(): void {
    this.perPageChange.emit(this.perPage);
  }
}
