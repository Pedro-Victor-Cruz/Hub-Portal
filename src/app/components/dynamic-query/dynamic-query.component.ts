import {Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges} from '@angular/core';
import {FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {DynamicQueryService} from '../../services/dynamic-query.service';
import {ToastService} from '../toast/toast.service';
import {ServicesService} from '../../services/services.service';
import {Utils} from '../../services/utils.service';
import {ButtonComponent} from '../form/button/button.component';
import {DropdownComponent} from '../form/dropdown/dropdown.component';
import {DynamicQueryTestComponent} from './dynamic-query-test/dynamic-query-test.component';
import {DynamicQueryFilter, FiltersComponent} from '../filters/filters.component';
import {InputComponent} from '../form/input/input.component';
import {ServiceParametersComponent} from './service-parameters/service-parameters.component';
import {TabComponent} from '../tabs/tab/tab.component';
import {TabsComponent} from '../tabs/tabs.component';
import {TextareaComponent} from '../form/textarea/textarea.component';
import {FormErrorHandlerService} from '../form/form-error-handler.service';
import {CommonModule} from '@angular/common';
import {subscribeOn} from 'rxjs';
import {
  DynamicQueryFieldsMetadataBuilderComponent, FieldMetadata, FieldMetadataConfig
} from './dynamic-query-fields-metadata-builder/dynamic-query-fields-metadata-builder.component';

export interface DynamicQuery {
  id: number;
  key: string;
  name: string;
  description: string | null;
  service_slug: string;
  service_params: { [key: string]: any };
  query_config: any;
  fields_metadata: FieldMetadataConfig;
  active: boolean;
  required_params: string[];
  active_filters: DynamicQueryFilter[];
}

@Component({
  selector: 'app-dynamic-query',
  imports: [
    CommonModule,
    ButtonComponent,
    DropdownComponent,
    DynamicQueryTestComponent,
    FiltersComponent,
    InputComponent,
    ReactiveFormsModule,
    ServiceParametersComponent,
    TabComponent,
    TabsComponent,
    TextareaComponent,
    FormsModule,
    DynamicQueryFieldsMetadataBuilderComponent
  ],
  templateUrl: './dynamic-query.component.html',
  styleUrl: './dynamic-query.component.scss',
  standalone: true
})
export class DynamicQueryComponent implements OnInit, OnChanges {

  @Input() dynamicQueryKey: string | null = null;
  @Input({
    alias: 'listQueries'
  }) listQueriesActive: boolean = false;

  @Input() selectedQueryId: number | null = null;
  @Output() selectedQueryIdChange = new EventEmitter<number | null>();

  dynamicQuery: DynamicQuery | null = null;
  form: FormGroup;
  errors: { [key: string]: string } = {};
  loading: boolean = false;
  services: any[] = [];
  listQueries: DynamicQuery[] = [];

  // Propriedades da sidebar
  loadingQuery: boolean = false;
  searchQuery: string = '';

  constructor(
    private fb: FormBuilder,
    private dynamicQueryService: DynamicQueryService,
    private toast: ToastService,
    private servicesService: ServicesService,
    private utils: Utils
  ) {
    this.form = this.fb.group({
      key: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
      name: ['', [Validators.required]],
      description: [''],
      service_slug: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = this.FormErrorHandlerService.getErrorMessages(this.form);
    });
  }

  ngOnInit() {
    this.load();
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['dynamicQueryKey'] && !changes['dynamicQueryKey'].firstChange) {
      this.load();
    }
  }

  protected async load() {
    this.loading = true;
    await this.loadQueries();
    await this.loadDynamicQuery();
    await this.loadServices();
    this.loading = false;
  }

  protected async loadQueries() {
    if (this.listQueriesActive) {
      try {
        const response = await this.dynamicQueryService.getDynamicQueries();
        this.listQueries = response.data;
      } catch (error) {
        this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar consultas dinâmicas'));
      }
    }
  }

  protected async loadDynamicQuery() {
    if (this.dynamicQueryKey) {
      try {
        const responseQuery = await this.dynamicQueryService.getDynamicQuery(this.dynamicQueryKey);
        this.dynamicQuery = responseQuery.data ? responseQuery.data.query : null;

        if (!this.dynamicQuery) {
          this.toast.error('Consulta dinâmica não encontrada.');
          return;
        }

        this.form.patchValue({
          key: this.dynamicQuery.key,
          name: this.dynamicQuery.name,
          description: this.dynamicQuery.description,
          service_slug: this.dynamicQuery.service_slug
        });

        this.selectedQueryId = this.dynamicQuery.id;
        this.selectedQueryIdChange.emit(this.selectedQueryId);
      } catch (err: any) {
        this.toast.error(Utils.getErrorMessage(err, 'Erro ao carregar consulta dinâmica'));
      }
    }
  }

  protected async loadServices() {
    try {
      const response = await this.servicesService.getServices('query');
      this.services = response.data;
    } catch (error) {
      this.toast.error(Utils.getErrorMessage(error, 'Erro ao carregar serviços'));
    }
  }

  onSubmit() {
    if (this.form.invalid) {
      this.toast.error('Por favor, corrija os erros no formulário antes de enviar.');
      return;
    }

    this.loading = true;
    const data = this.form.value;

    if (this.dynamicQueryKey) {
      this.updateDynamicQuery(data);
    } else {
      this.createDynamicQuery(data);
    }
  }

  async createDynamicQuery(data: any) {
    try {
      const response = await this.dynamicQueryService.createDynamicQuery(data);
      this.toast.success('Consulta dinâmica criada com sucesso!');

      this.dynamicQueryKey = response.data.key;
      this.dynamicQuery = response.data;
      this.selectedQueryId = response.data.id;
      this.selectedQueryIdChange.emit(this.selectedQueryId);

      await this.loadQueries();
    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, this.form);
    } finally {
      this.loading = false;
    }
  }

  async updateDynamicQuery(data: any) {
    try {
      const response = await this.dynamicQueryService.updateDynamicQuery(this.dynamicQueryKey!, data);
      this.toast.success('Consulta dinâmica atualizada com sucesso!');

      this.dynamicQuery = {...this.dynamicQuery, ...response.data};

      await this.loadQueries();
    } catch (error) {
      this.errors = this.utils.handleErrorsForm(error, this.form);
    } finally {
      this.loading = false;
    }
  }

  generateKey(event: FocusEvent) {
    const input = event.target as HTMLInputElement;
    const value = input.value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-');
    this.form.patchValue({key: value});
  }

  async selectQuery(query: DynamicQuery) {
    if (this.selectedQueryId === query.id || this.loadingQuery) return;

    this.loadingQuery = true;
    this.dynamicQueryKey = query.key;
    this.selectedQueryId = query.id;
    this.selectedQueryIdChange.emit(this.selectedQueryId);

    await this.loadDynamicQuery();
    this.loadingQuery = false;
  }

  createNew() {
    if (this.loadingQuery) return;

    this.dynamicQueryKey = null;
    this.dynamicQuery = null;
    this.selectedQueryId = null;
    this.selectedQueryIdChange.emit(null);
    this.form.reset();
  }

  get filteredQueries(): DynamicQuery[] {
    if (!this.searchQuery.trim()) {
      return this.listQueries;
    }

    const search = this.searchQuery.toLowerCase().trim();
    return this.listQueries.filter(query =>
      query.name.toLowerCase().includes(search) ||
      query.key.toLowerCase().includes(search)
    );
  }


  onFieldsMetadataChange(metadata: FieldMetadataConfig) {
    if (this.dynamicQuery) {
      this.dynamicQuery.fields_metadata = metadata;
      this.saveFieldsMetadata(metadata);
    }
  }

  async saveFieldsMetadata(metadata: FieldMetadataConfig) {
    try {
      await this.dynamicQueryService.updateDynamicQuery(this.dynamicQueryKey!, {
        fields_metadata: metadata
      });
      this.toast.success('Configuração de campos salva com sucesso!');
    } catch (error) {
      this.toast.error('Erro ao salvar configuração de campos');
    }
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
  protected readonly subscribeOn = subscribeOn;
}
