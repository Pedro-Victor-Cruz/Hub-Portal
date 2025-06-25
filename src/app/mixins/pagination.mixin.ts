import { Directive, Input } from '@angular/core';
import {FolderItem} from '../components/folder-view/folder-view.component';

@Directive()
export abstract class PaginationMixin {
  loading = false;
  data: FolderItem[] = [];
  pagination: any = null;
  abstract configTable: any;

  abstract loadData(page: number, perPage: number): Promise<void>;

  async load(page: number = 1, perPage: number = 15) {
    this.loading = true;
    try {
      await this.loadData(page, perPage);
    } finally {
      this.loading = false;
    }
  }

  onPageChange(event: {page: number, perPage: number}) {
    this.load(event.page, event.perPage);
  }
}
