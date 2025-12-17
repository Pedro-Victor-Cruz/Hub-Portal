// filters.component.ts
import {Component, Input, OnInit, OnChanges, SimpleChanges} from '@angular/core';
import {FormBuilder, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {CommonModule} from '@angular/common';
import {DragDropModule, CdkDragDrop, moveItemInArray} from '@angular/cdk/drag-drop';

import {ButtonComponent} from '../form/button/button.component';
import {InputComponent} from '../form/input/input.component';
import {DropdownComponent} from '../form/dropdown/dropdown.component';
import {TextareaComponent} from '../form/textarea/textarea.component';
import {ToastService} from '../toast/toast.service';
import {Utils} from '../../services/utils.service';
import {FormErrorHandlerService} from '../form/form-error-handler.service';
import {ObjectEditorComponent} from '../form/object-editor/object-editor.component';
import {EntityType, FilterService} from '../../services/filters.service';

interface FilterType {
  value: string;
  label: string;
  description: string;
  requiresOptions: boolean;
  defaultValue: any;
}

export interface DynamicQueryFilter {
  id?: number;
  name: string;
  description?: string;
  var_name: string;
  type: string;
  default_value: any;
  required: boolean;
  order: number;
  validation_rules: any;
  visible: boolean;
  active: boolean;
  options?: any[];
}

@Component({
  selector: 'app-filters',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    DragDropModule,
    ButtonComponent,
    InputComponent,
    DropdownComponent,
    TextareaComponent,
    ObjectEditorComponent
  ],
  templateUrl: './filters.component.html',
  styleUrl: './filters.component.scss',
  standalone: true
})
export class FiltersComponent implements OnInit, OnChanges {

  @Input() queryKey: string | null = null;
  @Input() dashboardKey: string | null = null;

  filters: DynamicQueryFilter[] = [];
  filterTypes: FilterType[] = [];
  loading: boolean = false;
  showFilterForm: boolean = false;
  editingFilter: DynamicQueryFilter | null = null;

  filterForm: FormGroup;
  errors: { [key: string]: string } = {};
  variableSuggestions: string[] = [];

  private entityType: EntityType | null = null;
  private entityKey: string | null = null;

  constructor(
    private fb: FormBuilder,
    private filterService: FilterService,
    private toast: ToastService,
    private utils: Utils
  ) {
    this.filterForm = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(100)]],
      description: ['', [Validators.maxLength(500)]],
      var_name: ['', [Validators.required, Validators.maxLength(50), Validators.pattern(/^[A-Z][A-Z0-9_]*$/)]],
      type: ['text', [Validators.required]],
      default_value: [null],
      required: [false],
      visible: [true],
      active: [true],
      options: [null]
    });

    this.filterForm.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.filterForm);
    });
  }

  ngOnInit() {
    this.validateInputs();
    if (this.entityType && this.entityKey) {
      this.loadData();
    }
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['queryKey'] || changes['dashboardKey']) {
      this.validateInputs();
      if (this.entityType && this.entityKey) {
        this.loadData();
      }
    }
  }

  private validateInputs(): void {
    const hasQuery = !!this.queryKey;
    const hasDashboard = !!this.dashboardKey;

    if (hasQuery && hasDashboard) {
      console.error('Apenas um dos inputs (queryKey ou dashboardKey) deve ser fornecido');
      throw new Error('Apenas um dos inputs (queryKey ou dashboardKey) deve ser fornecido');
    }

    if (!hasQuery && !hasDashboard) {
      console.error('É obrigatório fornecer um dos inputs: queryKey ou dashboardKey');
      throw new Error('É obrigatório fornecer um dos inputs: queryKey ou dashboardKey');
    }

    if (hasQuery) {
      this.entityType = EntityType.QUERY;
      this.entityKey = this.queryKey!;
    } else {
      this.entityType = EntityType.DASHBOARD;
      this.entityKey = this.dashboardKey!;
    }
  }

  private getEntityInfo(): { type: EntityType; key: string } {
    if (!this.entityType || !this.entityKey) {
      throw new Error('Entidade não definida');
    }
    return { type: this.entityType, key: this.entityKey };
  }

  async loadData() {
    await Promise.all([
      this.loadFilters(),
      this.loadFilterTypes(),
      this.loadVariableSuggestions()
    ]);
  }

  async loadFilters() {
    try {
      this.loading = true;
      const entity = this.getEntityInfo();
      const response = await this.filterService.getFilters(
        entity.type,
        entity.key
      );
      this.filters = response.data || [];
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar filtros'));
    } finally {
      this.loading = false;
    }
  }

  async loadFilterTypes() {
    try {
      const entity = this.getEntityInfo();
      const response = await this.filterService.getFilterTypes(entity.type);
      this.filterTypes = response.data || [];
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar tipos de filtro'));
    }
  }

  async loadVariableSuggestions() {
    try {
      const entity = this.getEntityInfo();
      const response = await this.filterService.getVariableSuggestions(
        entity.type,
        entity.key
      );
      this.variableSuggestions = response.data?.suggestions || [];
    } catch (error) {
      console.warn('Erro ao carregar sugestões de variáveis:', error);
    }
  }

  showAddFilterForm() {
    this.editingFilter = null;
    this.resetForm();
    this.showFilterForm = true;
  }

  editFilter(filter: DynamicQueryFilter) {
    this.editingFilter = filter;
    this.filterForm.patchValue({
      name: filter.name,
      description: filter.description || '',
      var_name: filter.var_name,
      type: filter.type,
      default_value: filter.default_value,
      required: filter.required,
      visible: filter.visible,
      active: filter.active,
      options: filter.options
    });
    this.showFilterForm = true;
  }

  cancelEdit() {
    this.showFilterForm = false;
    this.editingFilter = null;
    this.resetForm();
  }

  async saveFilter() {
    if (this.filterForm.invalid) {
      this.toast.error('Por favor, corrija os erros no formulário antes de salvar.');
      return;
    }

    try {
      this.loading = true;
      const formData = this.filterForm.value;
      const entity = this.getEntityInfo();

      if (this.editingFilter) {
        await this.filterService.updateFilter(
          entity.type,
          entity.key,
          this.editingFilter.var_name,
          formData
        );
        this.toast.success('Filtro atualizado com sucesso!');
      } else {
        await this.filterService.createFilter(
          entity.type,
          entity.key,
          formData
        );
        this.toast.success('Filtro criado com sucesso!');
      }

      await this.loadFilters();
      await this.loadVariableSuggestions();
      this.cancelEdit();
    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, this.filterForm);
    } finally {
      this.loading = false;
    }
  }

  async deleteFilter(filter: DynamicQueryFilter) {
    if (!confirm(`Tem certeza que deseja excluir o filtro "${filter.name}"?`)) {
      return;
    }

    try {
      this.loading = true;
      const entity = this.getEntityInfo();
      await this.filterService.deleteFilter(
        entity.type,
        entity.key,
        filter.var_name
      );
      this.toast.success('Filtro excluído com sucesso!');
      await this.loadFilters();
      await this.loadVariableSuggestions();
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao excluir filtro'));
    } finally {
      this.loading = false;
    }
  }

  async onFiltersReorder(event: CdkDragDrop<DynamicQueryFilter[]>) {
    moveItemInArray(this.filters, event.previousIndex, event.currentIndex);

    try {
      const entity = this.getEntityInfo();
      const orderedVarNames = this.filters.map(f => f.var_name);
      await this.filterService.reorderFilters(
        entity.type,
        entity.key,
        orderedVarNames
      );
      this.toast.success('Ordem dos filtros atualizada!');
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao reordenar filtros'));
      // Reverte a mudança em caso de erro
      await this.loadFilters();
    }
  }

  onTypeChange() {
    const selectedType = this.filterTypes.find(t => t.value === this.filterForm.get('type')?.value);
    if (selectedType) {
      this.filterForm.patchValue({
        default_value: selectedType.defaultValue,
        options: selectedType.requiresOptions ? [] : null
      });
    }
  }

  generateVarName(event: FocusEvent) {
    const input = event.target as HTMLInputElement;
    const value = input.value.trim()
      .toUpperCase()
      .replace(/[^A-Z0-9\s]/g, '')
      .replace(/\s+/g, '_');
    this.filterForm.patchValue({var_name: value});
  }

  useSuggestion(suggestion: string) {
    this.showAddFilterForm();
    this.filterForm.patchValue({
      var_name: suggestion,
      name: this.formatSuggestionAsName(suggestion)
    });
  }

  private formatSuggestionAsName(varName: string): string {
    return varName
      .toLowerCase()
      .replace(/_/g, ' ')
      .replace(/\b\w/g, char => char.toUpperCase());
  }

  private resetForm() {
    this.filterForm.reset({
      name: '',
      description: '',
      var_name: '',
      type: 'text',
      default_value: null,
      required: false,
      visible: true,
      active: true,
      options: null
    });
    this.errors = {};
  }

  get selectedFilterType(): FilterType | undefined {
    return this.filterTypes.find(t => t.value === this.filterForm.get('type')?.value);
  }

  get showOptionsField(): boolean {
    return this.selectedFilterType?.requiresOptions || false;
  }

  get hasFilters(): boolean {
    return this.filters && this.filters.length > 0;
  }

  get hasSuggestions(): boolean {
    return this.variableSuggestions && this.variableSuggestions.length > 0;
  }

  get entityLabel(): string {
    return this.entityType === EntityType.QUERY ? 'Consulta' : 'Dashboard';
  }

  get entityIdentifier(): string {
    return this.entityKey || '';
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
