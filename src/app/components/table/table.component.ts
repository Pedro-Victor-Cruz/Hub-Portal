import {Component, EventEmitter, Input, OnInit, Output, ViewChild, OnChanges, SimpleChanges} from '@angular/core';
import {AgGridAngular} from 'ag-grid-angular';
import {
  ColDef,
  GridApi,
  GridReadyEvent,
  GridOptions,
  RowSelectedEvent,
  ICellRendererParams
} from 'ag-grid-community';
import {AllCommunityModule, ModuleRegistry, themeQuartz} from 'ag-grid-community';
import {CommonModule} from '@angular/common';
import {ConfirmationService} from '../confirmation-modal/confirmation-modal.service';
import {ActivatedRoute, Router} from '@angular/router';

// Register AG Grid modules
ModuleRegistry.registerModules([AllCommunityModule]);

export interface TableConfig {
  columns: ColumnConfig[];
  selectable?: boolean;
  showAddButton?: boolean;
  showExportButton?: boolean;
  showDeleteButton?: boolean;
  showEditButton?: boolean;
  enableFiltering?: boolean;
  enableSorting?: boolean;
  rowHeight?: number;
  animateRows?: boolean;
  pagination?: boolean;
  paginationPageSize?: number;
  paginationPageSizeSelector?: number[];
}

export interface ColumnConfig {
  field: string;
  headerName: string;
  width?: number;
  minWidth?: number;
  maxWidth?: number;
  filter?: boolean | string;
  sortable?: boolean;
  resizable?: boolean;
  editable?: boolean;
  cellRenderer?: any;
  valueGetter?: (params: any) => any;
  hide?: boolean;
  pinned?: 'left' | 'right' | null;
  type?: string;
}

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

@Component({
  selector: 'ub-table',
  standalone: true,
  imports: [AgGridAngular, CommonModule],
  templateUrl: './table.component.html',
  styleUrls: ['./table.component.scss']
})
export class TableComponent implements OnInit, OnChanges {
  @ViewChild(AgGridAngular) agGrid!: AgGridAngular;

  @Input({required: true}) config!: TableConfig;
  @Input({required: true}) data: any[] = [];
  @Input() pk: string = 'id';
  @Input() paginate?: Pagination;
  @Input() loading: boolean = false;

  @Output() deleteEvent = new EventEmitter<any>();
  @Output() createEvent = new EventEmitter<void>();
  @Output() editEvent = new EventEmitter<any>();
  @Output() selectEvent = new EventEmitter<any[]>();
  @Output() reloadEvent = new EventEmitter<void>();
  @Output() paginationChange = new EventEmitter<{ page: number; perPage: number }>();
  @Output() filterChange = new EventEmitter<any>();
  @Output() sortChange = new EventEmitter<any>();

  public gridApi!: GridApi;
  public columnDefs: ColDef[] = [];
  public rowData: any[] = [];

  public defaultColDef: ColDef = {
    sortable: true,
    filter: true,
    resizable: true,
    floatingFilter: true,
    minWidth: 100,
    flex: 1
  };

  public gridOptions: GridOptions = {};
  public rowSelection: 'single' | 'multiple' = 'multiple';
  public selectedRows: any[] = [];
  public theme = themeQuartz;

  constructor(
    private confirmationService: ConfirmationService,
    private router: Router,
    private route: ActivatedRoute
  ) {
  }

  ngOnInit(): void {
    this.rowData = this.data ? [...this.data] : [];
    this.setupColumnDefs();
    this.setupGridOptions();
  }

  ngOnChanges(changes: SimpleChanges): void {
    console.log('ngOnChanges:', changes);

    if (changes['config'] && this.config) {
      console.log('Config changed:', this.config);
      this.setupColumnDefs();
      if (this.gridApi) {
        this.gridApi.setGridOption('columnDefs', this.columnDefs);
      }
    }

    if (changes['data']) {
      console.log('Data changed:', this.data);
      this.rowData = this.data ? [...this.data] : [];
      console.log('New rowData:', this.rowData);
      console.log('gridApi exists?', !!this.gridApi);

      if (this.gridApi) {
        console.log('Calling refreshData');
        this.refreshData(this.rowData);
      } else {
        console.log('gridApi not ready yet, data will be set on onGridReady');
      }
    }

    if (changes['loading'] && this.gridApi) {
      this.setLoading(this.loading);
    }

  }

  private setupColumnDefs(): void {
    if (!this.config) return;

    const newColumnDefs: ColDef[] = [];

    // Coluna de seleção (usando checkboxSelection nativo do AG Grid)
    if (this.config.selectable) {
      newColumnDefs.push({
        headerName: '',
        checkboxSelection: true,
        headerCheckboxSelection: true,
        width: 50,
        minWidth: 50,
        maxWidth: 50,
        pinned: 'left',
        lockPosition: true,
        filter: false,
        sortable: false,
        resizable: false,
        suppressHeaderMenuButton: true,
        flex: 0
      });
    }

    // Colunas de dados
    this.config.columns.forEach((col) => {
      const colDef: ColDef = {
        field: col.field,
        headerName: col.headerName,
        width: col.width,
        minWidth: col.minWidth,
        maxWidth: col.maxWidth,
        sortable: col.sortable ?? this.config.enableSorting ?? true,
        filter: col.filter ?? true,
        resizable: col.resizable ?? true,
        editable: col.editable ?? false,
        hide: col.hide ?? false,
        pinned: col.pinned ?? null,
        cellRenderer: col.cellRenderer,
        valueGetter: col.valueGetter,
        floatingFilter: this.config.enableFiltering ?? true,
        flex: col.width ? 0 : 1
      };

      newColumnDefs.push(colDef);
    });

    // Coluna de ações
    if (this.config.showEditButton || this.config.showDeleteButton) {
      newColumnDefs.push({
        headerName: 'Ações',
        field: 'actions',
        cellRenderer: (params: ICellRendererParams) => this.actionsCellRenderer(params),
        width: 120,
        minWidth: 120,
        maxWidth: 150,
        pinned: 'right',
        sortable: false,
        filter: false,
        resizable: false,
        cellClass: 'actions-cell',
        suppressHeaderMenuButton: true,
        flex: 0
      });
    }

    this.columnDefs = newColumnDefs;
  }

  private setupGridOptions(): void {
    console.log('setupGridOptions - rowData:', this.rowData);

    this.gridOptions = {
      animateRows: this.config?.animateRows ?? true,
      rowHeight: this.config?.rowHeight ?? 48,
      headerHeight: 48,
      pagination: this.config?.pagination ?? true,
      paginationPageSize: this.paginate?.per_page || this.config?.paginationPageSize || 15,
      paginationPageSizeSelector: this.config?.paginationPageSizeSelector || [10, 15, 25, 50, 100],
      rowSelection: this.config?.selectable ? 'multiple' : undefined,
      suppressCellFocus: true,
      enableCellTextSelection: true,
      onRowSelected: (event: RowSelectedEvent) => this.onRowSelected(event),
      onFilterChanged: () => this.onFilterChanged(),
      onSortChanged: () => this.onSortChanged(),
      onPaginationChanged: () => this.onPaginationChanged()
    };
  }

  onGridReady(params: GridReadyEvent): void {
    console.log('onGridReady called');
    this.gridApi = params.api;

    console.log('Setting rowData:', this.rowData);
    console.log('Column Defs:', this.columnDefs);

    // Sempre definir os dados, mesmo que vazio
    this.gridApi.setGridOption('rowData', this.rowData);
    console.log('Row count after set:', this.gridApi.getDisplayedRowCount());

    this.updateOverlay();
  }

  private updateOverlay(): void {
    if (!this.gridApi) return;

    if (this.loading) {
      this.gridApi.showLoadingOverlay();
    } else if (!this.rowData || this.rowData.length === 0) {
      this.gridApi.showNoRowsOverlay();
    } else {
      this.gridApi.hideOverlay();
    }
  }

  private actionsCellRenderer(params: ICellRendererParams): HTMLElement {
    const container = document.createElement('div');
    container.className = 'actions-container';

    if (this.config.showEditButton) {
      const editBtn = document.createElement('button');
      editBtn.className = 'action-btn edit-btn';
      editBtn.innerHTML = '<i class="bx bxs-edit"></i>';
      editBtn.title = 'Editar';
      editBtn.onclick = (e) => {
        e.stopPropagation();
        this.edit(params.data);
      };
      container.appendChild(editBtn);
    }

    if (this.config.showDeleteButton) {
      const deleteBtn = document.createElement('button');
      deleteBtn.className = 'action-btn delete-btn';
      deleteBtn.innerHTML = '<i class="bx bxs-trash"></i>';
      deleteBtn.title = 'Excluir';
      deleteBtn.onclick = (e) => {
        e.stopPropagation();
        this.delete(params.data);
      };
      container.appendChild(deleteBtn);
    }

    return container;
  }

  private onRowSelected(event: RowSelectedEvent): void {
    if (this.gridApi) {
      this.selectedRows = this.gridApi.getSelectedRows();
      this.selectEvent.emit(this.selectedRows);
    }
  }

  private onFilterChanged(): void {
    if (this.gridApi) {
      const filterModel = this.gridApi.getFilterModel();
      this.filterChange.emit(filterModel);
    }
  }

  private onSortChanged(): void {
    if (this.gridApi) {
      const sortModel = this.gridApi.getColumnState()
        .filter(col => col.sort !== null)
        .map(col => ({field: col.colId, sort: col.sort}));
      this.sortChange.emit(sortModel);
    }
  }

  private onPaginationChanged(): void {
    if (!this.gridApi) return;

    const currentPage = this.gridApi.paginationGetCurrentPage() + 1;
    const pageSize = this.gridApi.paginationGetPageSize();

    if (this.paginate) {
      this.paginationChange.emit({page: currentPage, perPage: pageSize});
    }
  }

  // Public methods
  public create(): void {
    if (this.createEvent.observed) {
      this.createEvent.emit();
      return;
    }
    this.router.navigate(['manage'], {relativeTo: this.route});
  }

  public edit(item: any): void {
    if (this.editEvent.observed) {
      this.editEvent.emit(item);
      return;
    }
    this.router.navigate(['manage', item[this.pk]], {relativeTo: this.route});
  }

  public async delete(item: any): Promise<void> {
    const result = await this.confirmationService
      .confirm('Confirma a exclusão deste item?', 'Sim, excluir', 'Cancelar');

    if (result) {
      this.deleteEvent.emit(item);
    }
  }

  public reload(): void {
    this.reloadEvent.emit();
  }

  public exportToCSV(): void {
    if (this.gridApi) {
      this.gridApi.exportDataAsCsv({
        fileName: `export_${new Date().getTime()}.csv`,
        columnSeparator: ';'
      });
    }
  }

  public exportToExcel(): void {
    if (this.gridApi) {
      this.gridApi.exportDataAsExcel({
        fileName: `export_${new Date().getTime()}.xlsx`
      });
    }
  }

  public print(): void {
    window.print();
  }

  public clearFilters(): void {
    if (this.gridApi) {
      this.gridApi.setFilterModel(null);
    }
  }

  public clearSelection(): void {
    if (this.gridApi) {
      this.gridApi.deselectAll();
    }
  }

  public refreshData(newData: any[]): void {
    console.log('refreshData called with:', newData);
    this.rowData = [...newData];
    if (this.gridApi) {
      this.gridApi.setGridOption('rowData', this.rowData);
      console.log('Data refreshed, row count:', this.gridApi.getDisplayedRowCount());
      this.updateOverlay();
    } else {
      console.warn('gridApi not available in refreshData');
    }
  }

  public getSelectedRows(): any[] {
    return this.gridApi ? this.gridApi.getSelectedRows() : [];
  }

  public setLoading(loading: boolean): void {
    this.loading = loading;
    if (this.gridApi) {
      this.updateOverlay();
    }
  }
}
